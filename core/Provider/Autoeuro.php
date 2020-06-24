<?php
namespace core\Provider;
use core\Provider;
use core\Log;
use core\OrderValue;

class Autoeuro extends Provider{
	
	private static $email = 'info@tahos.ru';
	private static $password = 'VGpS5DxFrslpgx046vVFb8dh';
	private static $apiKey = 'w84dWVnWf0fahzrMQhALbEVflzGrazSQgmMoSZWHmd5oHarZvJR0ULlLzjjh';
	private static $url = 'https://api.autoeuro.ru/api/v-1.0/shop/';
	private static $stores = [];
	private static $delivery_key = 'Zid3sPWIUfEZXeXVuy8e46Zr1MwD704huFO0nxypa7DxUyB%2BlF1Vh4dMAnAa3qxhhI272D8npAC0Gl%2FUWNynsfbWxSyjqG9S9fQ%2Bk1GeP1xCjg5J470ZnoZeTHsZICym%2BEUUZ0wYVPCUbjbnHwTR3c227AGXDBbI4gwxzixWriUj2dc%2FC5nUdYyeuRFAg5bQBbnx5eqCyWlfs9lJdoUqRCzT7mJ230xoZrOGKYkb%2Bsak752366%2FSmFCKUy%2F4FtqlHAeLbNkJtNkZF%2F9ra6Rggg%3D%3D';
	private static $subdivision_key = 'ogeRtaAVTbn%2FgjvNpHEZxi0FXtlvnXv8GaUON7FfMMbY4DF3brfW6H1mxYDjkR7wNHIqYZrxHMMzutXD%2FsOKHGey%2Fz2NAQSA8MudmokeGJKbkysLvH99q7C257zSxsOYnhH2iiGgyeuK2I7X3FcMcUY8DBVv2LhSrviyKC1Sg5UImARZeRFLy2gLnycQa6ooEQs4gviFLLkqoAGEKBj5LupIhMwMI7mzHWVZSzMQFmQshZqiaI5ck%2BA5djEzmSoV53%2B8QSe89ndE9E6BGE87Nclu%2B9kKr2VTPYUDMcCRDYI%3D';

	public static $provider_id = 18;
	public static $mainStoreID = 22657;
	public static $minPriceStoreID = 23617;
	public static $minDeliveryStoreID = 23618;

	private static function getUrlString($action){
		return self::$url . "$action/json/" . self::$apiKey;
	}

	public static function getBrends(){
		$response = parent::getUrlData(self::getUrlString('brends'));
		return json_decode($response);
	}
	private static function getStockItems($brend, $article, $with_crosses = 0){
		return parent::getUrlData(self::getUrlString('stock_items'), [
			'brand' => $brend, 
			'code' => $article, 
			'with_crosses' => $with_crosses
		]);
	}
	/**
	 * [getOrderKey description]
	 * @param array $params store_id, item_id
	 * @return string order_key
	 */
	public static function getOrderKey($params){
		if ($params['store_id'] == self::$mainStoreID){
			$response = self::getStockItems(strtoupper($params['brend']), $params['article'], 0);
			if (!$response || $response == 'Пустой ключ покупателя'){
				$providerBrend = parent::getProviderBrend(self::$provider_id, $params['brend']);
				$response = self::getStockItems(strtoupper($providerBrend), $params['article']);
			}
			$json = json_decode($response);
			if (!isset($json->DATA->CODES)) return false;
			foreach($json->DATA->CODES as $code){
				if ($code->proposal == 'АвтоЕвро'){
					return $code->order_key;
				} 
			}
			return false;
		}
		$resAutoeuroOrderKeys = parent::getInstanceDataBase()->query("
			SELECT
				aok.order_key
			FROM
				#autoeuro_order_keys aok
			WHERE
				aok.store_id = {$params['store_id']} AND aok.item_id = {$params['item_id']}
		", '');
		$output = $resAutoeuroOrderKeys->fetch_assoc();
		return $output['order_key'];
	}
	public static function getPrice($params){
		return [
			'price' => $params['price'],
			'available' => $params['in_stock']
		];
	}
	public static function getItemsToOrder($provider_id): array
	{
		if (!parent::getIsEnabledApiOrder(self::$provider_id)) return false;
		$basket_items = self::getBasket();
		$output = [];
		foreach($basket_items->DATA as $bi){
			if (!$bi->comment) continue;
			$osi = explode('-', $bi->comment);
			$storeItem = OrderValue::get([
				'order_id' => $osi[0],
				'store_id' => $osi[1],
				'item_id' => $osi[2]
			]);
			if (!$storeItem->num_rows) return [];
			foreach($storeItem as $si){
				$output[] = [
					'provider' => 'Autoeuro',
					'store' => $si['cipher'],
					'brend' => $si['brend'],
					'article' => $si['article'],
					'title_full' => $si['title_full'],
					'price' => $si['price'],
					'count' => $si['quan']
				];
			}
		}
		return $output;
	}
	private static function insertItem($o){
		/**
		 * @var array [brend_idArticle => item_id]
		 */
		static $items;
		$brend_id = Armtek::getBrendId($o->maker);
		if (!$brend_id) return false;
		if (isset($items["$brend_id{$o->code}"])) return $items["$brend_id{$o->code}"];
		$article = article_clear($o->code);
		$resInsertItem = parent::getInstanceDataBase()->insert('items', [
			'brend_id' => $brend_id,
			'article' => $article,
			'article_cat' => $o->code,
			'title' => $o->name,
			'title_full' => $o->name,
			'source' => 'Autoeuro'
		]);
		if ($resInsertItem === true){
			$item_id = parent::getInstanceDataBase()->last_id();
			parent::getInstanceDataBase()->insert('articles', ['item_id' => $item_id, 'item_diff' => $item_id]);
			$items["$brend_id{$o->code}"] = $item_id;
			return $item_id;
		}
		elseif (parent::isDuplicate($resInsertItem)){
			$resItem = parent::getInstanceDataBase()->select_one('items', '*', "`brend_id` = $brend_id AND `article` = '$article'");
			if (empty($resItem)) return false;
			$item_id = $resItem['id'];
			$items["$brend_id{$o->code}"] = $item_id;
			return $item_id;
		}
		return false;
	}
	private static function removeItemsAndProviderStores($item_id): void
	{
		$resAutoeuroOrderKeys = parent::getInstanceDataBase()->query("
			SELECT
				aok.cipher,
				aok.item_id,
				b.store_id
			FROM
				#autoeuro_order_keys aok
			LEFT JOIN
				#provider_stores ps ON ps.cipher = aok.cipher
			LEFT JOIN
				#basket b ON b.store_id = ps.id AND b.item_id = aok.item_id
			WHERE
				aok.item_id = $item_id AND b.store_id IS NULL
		", '');
		if (!$resAutoeuroOrderKeys->num_rows) return;
		foreach($resAutoeuroOrderKeys as $value){
			parent::getInstanceDataBase()->delete('provider_stores', "`cipher` = '{$value['cipher']}'");
			parent::getInstanceDataBase()->delete('autoeuro_order_keys', "`cipher` = '{$value['cipher']}'");
		}
	}
	private static function parseObjectData($object): array
	{
		$output = [];
		foreach($object as $o){
			if ($o->proposal == 'АвтоЕвро') continue;
			$key = "{$o->maker}{$o->code}";
			if (!isset($output[$key])){
				$output[$key]['price'] = $o;
				if (count($object) > 1) $output[$key]['order_term'] = $o;
			} 
			if ($o->price < $output[$key]['price']->price) $output[$key]['price'] = $o;
			if ($o->order_term < $output[$key]['order_term']->order_term) $output[$key]['order_term'] = $o;
		}
		//это цикл нужен для того, чтобы удалить order_term если цена и доставка равна price
		foreach($output as $key => $o){
			if (
				$o['price']->price == $o['order_term']->price && 
				$o['price']->order_term == $o['order_term']->order_term
			){
				unset($output[$key]['order_term']);
			} 
		}
		return $output;
	}
	public static function setArticle($brend, $article, $mainItemID){
		if (!parent::getIsEnabledApiSearch(self::$provider_id)) return false;
		$response = self::getStockItems(strtoupper($brend), $article, 1);
		if (!$response || $response == 'Пустой ключ покупателя'){
			$providerBrend = parent::getProviderBrend(self::$provider_id, $brend);
			$response = self::getStockItems(strtoupper($providerBrend), $article);
		}
		if (!$response) return false;
		$object = json_decode($response);
		$codes = [];
		$crosses = [];
		if (isset($object->DATA->CODES)){
			$codes = self::parseObjectData($object->DATA->CODES);
			foreach($codes as $code) self::parseCode($code);
		}
		if (isset($object->DATA->CROSSES)){
			$crosses = self::parseObjectData($object->DATA->CROSSES);
			foreach($crosses as $cross) self::parseCode($cross, $mainItemID);
		}
	}
	private static function parseCode($code, $mainItemID = null){
		$item_id = self::insertItem($code['price']);
		if (!$item_id) return;

		if ($mainItemID){
			parent::getInstanceDataBase()->insert('analogies', ['item_id' => $item_id, 'item_diff' => $mainItemID]);
			parent::getInstanceDataBase()->insert('analogies', ['item_id' => $mainItemID, 'item_diff' => $item_id]);
		}

		//price
		$resInsertStoreItem = parent::getInstanceDataBase()->insert(
			'store_items', 
			[
				'store_id' => self::$minPriceStoreID,
				'item_id' => $item_id,
				'price' => $code['price']->price,
				'in_stock' => $code['price']->amount,
				'packaging' => $code['price']->packing
			],
			['duplicate' => [
				'in_stock' => $code['price']->amount,
				'price' => $code['price']->price
			]/*, 'print' => true*/]
		);

		$resInsertAutoeuroOrderKeys = parent::getInstanceDataBase()->insert(
			'autoeuro_order_keys',
			[
				'store_id' => self::$minPriceStoreID,
				'item_id' => $item_id,
				'order_term' => $code['price']->order_term ? $code['price']->order_term : 1,
				'order_key' => $code['price']->order_key
			],
			['duplicate' => [
				'order_key' => $code['price']->order_key,
				'order_term' => $code['price']->order_term ? $code['price']->order_term : 1,
			]/*, 'print' => true*/]
		);

		//order_term
		if (!isset($code['order_term'])) return;
		$resInsertStoreItem = parent::getInstanceDataBase()->insert(
			'store_items', 
			[
				'store_id' => self::$minDeliveryStoreID,
				'item_id' => $item_id,
				'price' => $code['order_term']->price,
				'in_stock' => $code['order_term']->amount,
				'packaging' => $code['order_term']->packing
			],
			['duplicate' => [
				'in_stock' => $code['order_term']->amount,
				'price' => $code['order_term']->price
			]/*, 'print' => true*/]
		);
		$resInsertAutoeuroOrderKeys = parent::getInstanceDataBase()->insert(
			'autoeuro_order_keys',
			[
				'store_id' => self::$minDeliveryStoreID,
				'item_id' => $item_id,
				'order_term' => $code['order_term']->order_term ? $code['order_term']->order_term : 1,
				'order_key' => $code['order_term']->order_key
			],
			['duplicate' => [
				'order_key' => $code['order_term']->order_key,
				'order_term' => $code['order_term']->order_term ? $code['order_term']->order_term : 1
			]/*, 'print' => true*/]
		);
	}
	public static function getSearch($code){
		if (!parent::getIsEnabledApiSearch(self::$provider_id)) return false;
		$response = parent::getUrlData(self::getUrlString('stock_items'), ['code' => $code]);
		if (!$response) return false;
		$itemsList = json_decode($response);
		$output = [];
		if (isset($itemsList->DATA->VARIANTS)){
			foreach($itemsList->DATA->VARIANTS as $item) $output[$item->brand] = $item->name;
		}
		if (isset($itemsList->DATA->CODES)){
			foreach($itemsList->DATA->CODES as $item) $output[$item->maker] = $item->name;
		}
		return $output;
	}
	public static function isAutoeuro($store_id){
		$store = parent::getStoreInfo($store_id);
		if ($store['provider_id'] == self::$provider_id) return true;
		else return false;
	}
	/**
	 * [isInBasket description]
	 * @param  array  $params store_id, item_id
	 * @return mixed basket_item_key if is in basket,  false if not
	 */
	public static function isInBasket($params){
		$basket_items = self::getBasket();
		if (!isset($basket_items->DATA)) return false;
		foreach($basket_items->DATA as $data){
			if ($data->comment == self::getStringBasketComment($params)) return $data->basket_item_key;
		}
		return false;
	}
	private static function getStringBasketComment($params): string
	{
		return "{$params['order_id']}-{$params['store_id']}-{$params['item_id']}";
	}
	public static function removeFromBasket($ov){
		$basket_item_key = self::isInBasket($ov);
		self::removeBasket($basket_item_key);
		return true;
	}
	public static function removeBasket($basket_item_key)
	{
		return json_decode(parent::getUrlData(self::getUrlString('basket_del'), [
			'basket_item_key' => $basket_item_key
		]));
	}
	/**
	 * [putBusket description]
	 * @param  array $params store_id, item_id, quan
	 * @return [type]         [description]
	 */
	public static function putBusket($params){
		$order_key = self::getOrderKey($params);
		if (!$order_key){
			Log::insert([
				'text' => 'АвтоЕвро ошибка получения order_key',
				'additional' => 'osi: ' . self::getStringBasketComment($params)
			]);
			return false;
		}
		if ($basket_item_key = self::isInBasket($params)){
			self::removeBasket($basket_item_key);
		}
		$response = parent::getUrlData(
			self::getUrlString('basket_put'),
			[
				'order_key' => $order_key,
				'quantity' => $params['quan'],
				'item_note' => self::getStringBasketComment($params)
			]
		);
		if ($GLOBALS['response_header'][0] != 'HTTP/1.1 200 OK'){
			Log::insert([
				'text' => 'Произошла ошибка добавления в корзину',
				'additional' => "osi: ".self::getStringBasketComment($params)
			]);
			return;
		}
		OrderValue::changeStatus(7, $params);
		if ($response) return true;
		else return false;
	}
	public static function getBasket(){
		$response = parent::getUrlData(self::getUrlString('basket_items'));
		return json_decode($response);
	}
	public static function sendOrder(){
		// debug(json_decode(parent::getUrlData(self::getUrlString('subdivisions'))));
		// exit();
		$basket_items = self::getBasket();
		if (!isset($basket_items->DATA)) return false;
		// debug($basket_items);
		$basket_item_keys = [];
		foreach($basket_items->DATA as $b){
			if (!$b->comment) continue;
			$basket_item_keys[] = $b->basket_item_key;
		}
		$response = parent::getCurlUrlData(
			self::getUrlString('order_basket'),
			[
				'delivery_key' => self::$delivery_key,
				'subdivision_key' => self::$subdivision_key,
				'basket_item_keys' => json_encode($basket_item_keys)
			]
		);
		$json = json_decode($response);
		debug($GLOBALS['response_header']);
		debug($json);
		if (!$response){
			foreach($basket_items->DATA as $b){
				if (!$b->comment) continue;
				Log::insert([
					'text' => 'Ошибка отправления в заказ',
					'additional' => "osi: {$b->comment}"
				]);
			}
			return false;
		}
		foreach($basket_items->DATA as $b){
			if (!$b->comment) continue;
			$array = explode('-', $b->comment);
			$resOrderValue = OrderValue::get([
				'order_id' => $array[0],
				'store_id' => $array[1],
				'item_id' => $array[2]
			]);
			$orderValue = $resOrderValue->fetch_assoc();
			OrderValue::changeStatus(11, [
				'order_id' => $array[0], 
				'store_id' => $array[1],
				'item_id' => $array[2],
				'price' => $orderValue['price'],
				'quan' => $orderValue['quan'],
				'user_id' => $orderValue['user_id']
			]);
		}
	}
}
