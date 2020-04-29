<?php
namespace core\Provider;
use core\Provider;

class Autoeuro extends Provider{
	
	private static $email = 'info@tahos.ru';
	private static $password = 'VGpS5DxFrslpgx046vVFb8dh';
	private static $apiKey = 'w84dWVnWf0fahzrMQhALbEVflzGrazSQgmMoSZWHmd5oHarZvJR0ULlLzjjh';
	private static $url = 'https://api.autoeuro.ru/api/v-1.0/shop/';
	private static $stores = [];

	public static $provider_id = 18;
	public static $mainStoreID = 22657;

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
	public static function getOrderKey($store_id){
		$resAutoeuroOrderKeys = parent::getInstanceDataBase()->query("
			SELECT
				aok.order_key
			FROM
				#autoeuro_order_keys aok
			LEFT JOIN
				#provider_stores ps ON ps.cipher = aok.cipher
			WHERE
				ps.id = $store_id
		", '');
		$output = $resAutoeuroOrderKeys->fetch_assoc();
		return $output['order_key'];
	}
	public static function getPrice($params){
		// echo "<hr>";
		// debug($params, 'params');
		if (!parent::getIsEnabledApiOrder(self::$provider_id)) return false;
		$response = self::getStockItems($params['brend'], $params['article'], 1);
		$stock_items = json_decode($response);
		debug($stock_items);
		$order_key = self::getOrderKey($params['store_id']);
		// echo "$order_key<br><br>";
		if (isset($stock_items->DATA->CODES)){
			foreach($stock_items->DATA->CODES as $code){
				if ($code->order_key == $order_key){
					// debug($code);
					return [
						'price' => $code->price,
						'available' => $code->amount
					];
				} 
			}
		}
		if (isset($stock_items->DATA->CROSSES)){
			foreach($stock_items->DATA->CROSSES as $code){
				// debug($code);
				if ($code->order_key == $order_key){
					return [
						'price' => $code->price,
						'available' => $code->amount
					];
				} 
			}
		}
		return false;
	}
	public static function getItemsToOrder($provider_id){
		if (!parent::getIsEnabledApiOrder(self::$provider_id)) return false;
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
	private static function parseObjectData($o, $mainItemID, $isObjectCrosses = false): void
	{
		if ($isObjectCrosses){
			$item_id = self::insertItem($o);
			parent::getInstanceDataBase()->insert('analogies', ['item_id' => $item_id, 'item_diff' => $mainItemID]);
			parent::getInstanceDataBase()->insert('analogies', ['item_id' => $mainItemID, 'item_diff' => $item_id]);
			$mainItemID = $item_id;
		}
		// self::removeItemsAndProviderStores($mainItemID);
		if ($o->proposal == 'АвтоЕвро'){
			parent::getInstanceDataBase()->insert('store_items', [
				'store_id' => self::$mainStoreID,
				'item_id' => $mainItemID,
				'price' => $o->price,
				'in_stock' => $o->amount,
				'packaging' => $o->packing
			], ['duplicate' =>[
				'price' => $o->price,
				'in_stock' => $o->amount
			]]);
			return;
		};
		$cipher = strtoupper(parent::getRandomString(4));
		$res = parent::getInstanceDataBase()->insert('autoeuro_order_keys', [
			'cipher' => $cipher,
			'item_id' => $mainItemID, 
			'price' => ceil($o->price),
			'order_term' => $o->order_term,
			'order_key' => $o->order_key,
		]
		// , ['print' => true]
		);
		if (parent::isDuplicate($res)) return;
		/*if (parent::isDuplicate($res)){
			$res_aok = parent::getInstanceDataBase()->query("
				SELECT
					aok.*
				FROM
					#autoeuro_order_keys aok
				WHERE
					aok.order_key = '{$o->order_key}'
			", 'result');
			$array = $res_aok->fetch_assoc();
			$cipher = $array['cipher'];
		}*/
		$res_provider_stores = parent::getInstanceDataBase()->insert('provider_stores', [
			'title' => "АвтоЕвро - $cipher",
			'cipher' => $cipher,
			'percent' => 10,
			'currency_id' => 1,
			'provider_id' => self::$provider_id,
			'delivery' => $o->order_term,
			'delivery_max' => $o->order_term,
			'under_order' => $o->order_term
		]);
		if ($res_provider_stores !== true){
			$provider_store = parent::getInstanceDataBase()->select_one('provider_stores', '*', "`cipher` = '$cipher'");
			$store_id = $provider_store['id'];
		}
		else $store_id = parent::getInstanceDataBase()->last_id();
		if (!$store_id) die("Ошибка получение store_id");
		$res = parent::getInstanceDataBase()->insert('store_items', [
			'store_id' => $store_id,
			'item_id' => $mainItemID,
			'price' => $o->price,
			'in_stock' => $o->amount,
			'packaging' => $o->packing
		]);
	}
	public static function setArticle($brend, $article, $mainItemID){
		if (!parent::getIsEnabledApiSearch(self::$provider_id)) return false;
		$response = self::getStockItems($brend, $article, 1);
		if (!$response || $response == 'Пустой ключ покупателя'){
			$providerBrend = parent::getProviderBrend(self::$provider_id, $brend);
			$response = self::getStockItems($providerBrend, $article);
		}
		if (!$response) return false;
		$object = json_decode($response);
		if (isset($object->DATA->CODES)){
			foreach($object->DATA->CODES as $o){
				self::parseObjectData($o, $mainItemID);
			}
		}
		if (isset($object->DATA->CROSSES)){
			foreach($object->DATA->CROSSES as $o){
				self::parseObjectData($o, $mainItemID, true);
			}
		}
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
	 * @param  array  $params [description]
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
		return "{$params['store_id']}-{$params['item_id']}";
	}
	public static function removeBasket($basket_item_key): void
	{
		debug(json_decode(parent::getUrlData(self::getUrlString('basket_del'))));
	}
	public static function putBusket($params)
	{
		$order_key = self::getOrderKey($params['store_id']);
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
		$json = json_decode($response);
		print_r($GLOBALS['response_header']);
		print_r($json);
	}
	public static function getBasket(){
		$response = parent::getUrlData(self::getUrlString('basket_items'));
		return json_decode($response);
	}
}
