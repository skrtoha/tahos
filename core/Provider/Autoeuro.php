<?php
namespace core\Provider;
use core\Provider;
class Autoeuro extends Provider{
	
	private static $email = 'info@tahos.ru';
	private static $password = 'VGpS5DxFrslpgx046vVFb8dh';
	private static $apiKey = 'w84dWVnWf0fahzrMQhALbEVflzGrazSQgmMoSZWHmd5oHarZvJR0ULlLzjjh';
	private static $url = 'https://api.autoeuro.ru/api/v-1.0/shop/';

	public static $provider_id = 18;

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
			// if ($o->proposal != 'АвтоЕвро') continue;
			var_dump(parent::getInstanceDataBase()->insert('autoeuro_order_keys', [
				'item_id' => $item_id, 
				'order_key' => substr($o->order_key, 0, 255)
			]));
		}
		// if (!isset($->))
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
