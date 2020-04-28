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
	public static function getPrice(){}
	public static function getItemsToOrder($provider_id){}
	public static function setArticle($brend, $article, $item_id){
		$response = self::getStockItems($brend, $article);
		if (!$response || $response == 'Пустой ключ покупателя'){
			$providerBrend = parent::getProviderBrend(self::$provider_id, $brend);
			$response = self::getStockItems($providerBrend, $article);
		}
		$object = json_decode($response);
		debug($object);
		foreach($object->DATA->CODES as $o){
			if ($o->proposal == 'АвтоЕвро'){
				parent::getInstanceDataBase()->insert('store_items', [
					'store_id' => self::$mainStoreID,
					'item_id' => $item_id,
					'price' => $o->price,
					'in_stock' => $o->amount,
					'packaging' => $o->packing
				], ['duplicate' =>[
					'price' => $o->price,
					'in_stock' => $o->amount
				]]);
				continue;
			};
			$cipher = strtoupper(parent::getRandomString(4));
			$res = parent::getInstanceDataBase()->insert('autoeuro_order_keys', [
				'cipher' => $cipher,
				'item_id' => $item_id, 
				'order_key' => $o->order_key,
			]);
			if (parent::isDuplicate($res)){
				$res_aok = parent::getInstanceDataBase()->query("
					SELECT
						aok.*
					FROM
						#autoeuro_order_keys aok
					WHERE
						aok.order_key = '{$o->order_key}'
				", '');
				$array = $res_aok->fetch_assoc();
				$cipher = $array['cipher'];
			}
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
				'item_id' => $item_id,
				'price' => $o->price,
				'in_stock' => $o->amount,
				'packaging' => $o->packing
			]);
		}
	}
	public static function getSearch($code){
		$response = parent::getUrlData(self::getUrlString('stock_items'), ['code' => $code]);
		if (!$response) return false;
		$itemsList = json_decode($response);
		$output = [];
		foreach($itemsList->DATA->VARIANTS as $item) $output[$item->brand] = $item->name;
		return $output;
	}
}
