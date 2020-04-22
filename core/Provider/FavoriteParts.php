<?php
namespace core\Provider;

use core\Provider;
use core\OrderValue;
use core\Log;

class FavoriteParts extends Provider{
	public static $key = 'F4289750-BAFA-434C-8C2D-09AC06D6E6C2';
	public static $developerKey = '156C7176-B22F-4617-94B0-94C1B530FA75';
	
	public static $provider_id = 19;
	public static $error;

	private static function getCodesByCipher($cipher): array
	{
		switch($cipher){
			case 'FAVO':
				return ['МЦС'];
				break;
			case 'FAMO':
				return ['МС1', 'МС2', 'Дилер OE', 'МСК'];
				break;
		}
		return false;
	}
	public static function getPrice(array $params){
		$url = 'http://api.favorit-parts.ru/hs/hsprice/?key='.self::$key.'&number='.$params['article'].'&brand='.$params['brend'].'&analogues=';
		$response = self::getUrlData($url);
		$array = json_decode($response, true);
		if (isset($array['error'])) return false;
		
		$storeInfo = parent::getStoreInfo($params['store_id']);

		$codes = self::getCodesByCipher($storeInfo['cipher']);

		foreach($array['goods'] as $good){
			if (empty($good['warehouses'])) continue;
			foreach($good['warehouses'] as $warehouse){
				if (in_array($warehouse['code'], $codes)) return [
					'price' => $warehouse['price'],
					'available' => $warehouse['stock']
				];
			}
		}
	}

	/**
	 * gets items by article
	 * @param  [string] $search search article
	 * @return [array] array like [brend => title]
	 */
	public static function getSearch($search){
		if (!parent::getIsEnabledApiSearch(self::$provider_id)) return false;
		$coincidences = array();
		$response = self::getUrlData(
			'http://api.favorit-parts.ru/hs/hsprice/?key='.self::$key.'&number='.$search
		);
		$items = json_decode($response, true);
		if (empty($items)) return false;
		foreach($items['goods'] as $value){
			if (empty($value['warehouses'])) continue;
			$coincidences[$value['brand']] = $value['name'];
		} 
		return $coincidences;
	}

	private static function getStoreByWarehouseGroup($basket, $warehouseGroup){
		$code = $basket['warehouseGroup'][$warehouseGroup]['code'];
		return self::getCipherByCode($code);
	}

	public static function getItemsToOrder(int $provider_id){
		$basket = self::getBasket();
		if (!$basket) return false;
		$output = [];
		foreach($basket['cart'] as $c) $output[] = [
			'provider' => 'FavoriteParts',
			'store' => self::getStoreByWarehouseGroup($basket, $c['warehouseGroup']),
			'brend' => $basket['goods'][$c['goods']]['Brand'],
			'article' => $basket['goods'][$c['goods']]['Number'],
			'title_full' => $basket['goods'][$c['goods']]['Name'],
			'price' => $c['price'],
			'count' => $c['count']
		];
		return $output;
	}

	/**
	 * gets item by brend and article
	 * @param  string $brend item brend
	 * @param  string $article item article
	 * @return array array of items
	 */
	public static function getItem($brend, $article){
		if (!parent::getIsEnabledApiSearch(self::$provider_id)) return false;
		$url = 'http://api.favorit-parts.ru/hs/hsprice/?key='.self::$key.'&number='.$article.'&brand='.$brend.'&analogues=';
		$response = self::getUrlData($url);
		$GLOBALS['response_header'];
		$array = json_decode($response, true);
		return $array['goods'][0];
	}

	/**
	 * adds item in basket
	 * @param array $ov order value (order_id, store_id, item_id, quan)
	 			if quan = 0 then deletes from basket
	 * @return boolean true if successfully added, false if failed
	 */
	public static function addToBasket($ov){
		if (isset($ov['quan']) && $ov['quan']){
			$item = self::getItem($ov['brend'], $ov['article']);
			$codes = self::getCodesByCipher($ov['cipher']);
			$warehouseGroup = self::getWarehouseGroupIDByCodes($item, $codes);
			if (!$warehouseGroup){
				Log::insert([
					'text' => "Ошибка получения warehouseGroup для " . self::$error,
					'additional' => "osi: {$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}"
				]);
				return false;
			}
		} 
		else{
			//эта часть кода используется если товар удаляется с корзины
			$item = [
				'goodsID' => $ov['goods']
			];
			$warehouseGroup = $ov['warehouseGroup'];
			$ov['quan'] = 0;
		} 
		$url = "http://api.favorit-parts.ru/ws/v1/cart/add/";
		$url .= "?key=".self::$key;
		$url .= '&developerKey='.self::$developerKey;
		$url .= "&goods={$item['goodsID']}";
		$url .= "&warehouseGroup=$warehouseGroup";
		$url .= "&comment={$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}";
		$url .= "&count={$ov['quan']}";
		parent::getUrlData($url);
		if ($GLOBALS['response_header'][0] != 'HTTP/1.1 200 OK'){
			Log::insert([
				'text' => 'Произошла ошибка добавления в корзину. Ответ сервера: ' . $GLOBALS['response_header'][0],
				'additional' => "osi: {$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}"
			]);
			return false;
		} 
		$status_id = isset($ov['quan']) && $ov['quan'] ? 7 : 5;
		OrderValue::changeStatus($status_id, $ov);
		return true;
	}

	public static function removeFromBasket($ov){
		$basket = self::getBasket();
		$osi = "{$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}";
		foreach($basket['cart'] as $basket){
			if ($basket['comment'] == $osi) return self::addToBasket($basket);
		}
	}

	/**
	 * gets warehouseGroup 
	 * @param  array $ov order value array, if $ov['quan'] = 0 then warehouseGroup is required
	 * @param  item $item array with goodsID and warehouseGroup
	 * @return string id warehouse
	 */
	private static function getWarehouseGroupIDByCodes($item, $codes){
		foreach($item['warehouses'] as $value){
			if (in_array($value['code'], $codes)) return $value['id'];
		}
		self::$error = $item['warehouses'][0]['code'];
		return false;
	}

	/**
	 * checks if a item exists in the basket
	 * @param  array  $ov brend, article
	 * @return boolean     [description]
	 */
	public static function isInBasket($ov){
		$basket = self::getBasket();
		// debug($ov, 'ov');
		// debug($basket, 'basket');
		foreach($basket['cart'] as $b){
			if ($b['comment'] == "{$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}") return [
				'goodsID' => $b['goods'],
				'warehouseGroup' => $b['warehouseGroup']
			];
		}
		return false;
	}

	private static function getCipherByCode($code){
		if ($code == 'МС1' || $code == 'МС2') return 'FAVO';
		if ($code == 'МЦС') return 'FAJA';
		return false;
	}
	/**
	 * gets favorite basket
	 * @return array favorite basket
	 */
	private static function getBasket(){
		$url = "http://api.favorit-parts.ru/ws/v1/cart/";
		$res = self::getUrlData($url, null, [
			"X-Favorit-DeveloperKey: ".self::$developerKey,
			'X-Favorit-ClientKey: '.self::$key
		]);
		return json_decode($res, true);
	}
	/**
	 * sends goods in favorite basket to order
	 * @return mixed false - no goods for order, true - successfully sent, string - error
	 */
	public static function toOrder(){
		$basket = self::getBasket();
		$GoodsList = self::getGoodsForBasket($basket);
		if (empty($GoodsList)) return false;
		$user = self::getUser();
		$array = [
			'WarehouseShipping' => self::getWarehouseShipping($basket),
			'ShippingDate' => self::getShippingDate($basket['cart']),
			'TradePoint' => 'B26463A0-021B-11EA-A2FB-005056802F4C',
			'PaymentType' => $user['paymentType'],
			'DeliveryType' => $user['deliveryType'],
			'TransportType' => $user['transportType'],
			'Comment' => '',
			'GoodsList' => $GoodsList 
		];
		$curl = curl_init('http://api.favorit-parts.ru/ws/v1/order/');
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($array));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, [
		   "Content-type: application/json", 
			"X-Favorit-DeveloperKey: ".self::$developerKey,
			'X-Favorit-ClientKey: '.self::$key
		]);
		$result = curl_exec($curl);
		curl_close($curl);
		self::setStatusOrdered();
		if (gettype($result) == 'string') return $result;
	}
	/**
	 * sets status "ordered" after successfully sended order favorite parts
	 */
	public static function setStatusOrdered(){
		$orderValue = new OrderValue();
		$orders_values = $GLOBALS['db']->query("
			SELECT
				ov.*
			FROM 
				#orders_values ov
			LEFT JOIN
				#provider_stores ps ON ps.id = ov.store_id
			WHERE
				ps.provider_id = ".self::$provider_id." AND
				ov.status_id = 7
		", '');
		foreach($orders_values as $ov) $orderValue->changeStatus(11, $ov);
	}
	/**
	 * gets goods for favorite basket using getBasket
	 * @param  array $basket received by getBasket
	 * @return [type]         [description]
	 */
	private static function getGoodsForBasket($basket){
		$output = array();
		foreach($basket['cart'] as $b) $output[] = [
			'Goods' => $b['goods'],
			'WarehouseGroup' => $b['warehouseGroup'],
			'Count' => $b['count'],
			'Comment' => ''
		];
		return $output;
	}
	/**
	 * gets Warehouse Shipping for basket
	 * @param  array $basket value received by method 
	 * @return [type]         [description]
	 */
	private static function getWarehouseShipping($basket){
		foreach($basket['warehouseShipping'] as $key => $value) return $key;
	}
	/**
	 * gets dateShipment in goods $basket['cart']
	 * @param  array $cart $basket['cart']
	 * @return [type] max date shipping
	 */
	private static function getShippingDate($cart){
		// debug($cart);
		$max = [
			'unix' => strtotime($cart[0]['dateShipment']),
			'dateShipment' => $cart[0]['dateShipment']
		];
		foreach($cart as $value){
			if (strtotime($value['dateShipment']) > $max['unix']) $max = [
				'unix' => strtotime($value['dateShipment']),
				'dateShipment' => $value['dateShipment']
			];
		}
		return $max['dateShipment'];
	}
	/**
	 * gets user info in Favorite Parts
	 * @return $array array with user info
	 */
	private static function getUser(){
		$url = "http://api.favorit-parts.ru//ws/v1/references/profile/";
		$res = self::getUrlData($url, null, [
			"X-Favorit-DeveloperKey: ".self::$developerKey,
			'X-Favorit-ClientKey: '.self::$key
		]);
		return json_decode($res, true);
	}
}
