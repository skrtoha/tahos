<?php
namespace core\Provider;
use core\Provider;

class Autoeuro extends Provider{
	
	private static $email = 'info@tahos.ru';
	private static $password = 'VGpS5DxFrslpgx046vVFb8dh';
	private static $apiKey = 'w84dWVnWf0fahzrMQhALbEVflzGrazSQgmMoSZWHmd5oHarZvJR0ULlLzjjh';
	private static $url = 'https://api.autoeuro.ru/api/v-1.0/shop/';

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
	public static function getPrice($params){
		debug($params);
		if (!parent::getIsEnabledApiOrder(self::$provider_id)) return false;
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
	private static function parseObjectData($o, $mainItemID, $isObjectCrosses = false): void
	{
		echo "<hr>isObjectCrosses = $isObjectCrosses";
		if ($isObjectCrosses){
			$item_id = self::insertItem($o);
			parent::getInstanceDataBase()->insert('analogies', ['item_id' => $item_id, 'item_diff' => $mainItemID]);
			parent::getInstanceDataBase()->insert('analogies', ['item_id' => $mainItemID, 'item_diff' => $item_id]);
			$mainItemID = $item_id;
		}
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
		debug($o, parent::getInstanceDataBase()->insert('autoeuro_order_keys', [
			'cipher' => $cipher,
			'item_id' => $mainItemID, 
			'price' => floor($o->price),
			'order_term' => $o->order_term,
			'order_key' => $o->order_key,
		]
		, ['get' => true]
		).":<br><br>  " . $res);
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
		echo "$res_provider_stores";
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
		$object = json_decode($response);
		// debug($object);
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
}
