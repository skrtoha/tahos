<?php
namespace core;
class FavoriteParts{
	private static $key = 'F4289750-BAFA-434C-8C2D-09AC06D6E6C2';
	private static $developerKey = '156C7176-B22F-4617-94B0-94C1B530FA75';
	
	private static $provider_id = 19;

	/**
	 * gets items by article
	 * @param  [string] $search search article
	 * @return [array] array like [brend => title]
	 */
	public static function getSearch($search){
		$coincidences = array();
		$response = Abcp::getUrlData(
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

	/**
	 * gets item by brend and article
	 * @param  string $brend item brend
	 * @param  string $article item article
	 * @return array array of items
	 */
	public static function getItem($brend, $article){
		$response = Abcp::getUrlData(
			'http://api.favorit-parts.ru/hs/hsprice/?key='.self::$key.'&number='.$article.'&brand='.$brend.'&analogues='
		);
		$array = json_decode($response, true);
		return $array['goods'][0];
	}

	/**
	 * adds item in basket
	 * @param array $ov order value (order_id, store_id, item_id, quan)
	 * @return boolean true if successfully added, false if faled
	 */
	public static function addToBasket($ov){
		if ($ov['quan']) $item = self::getItem($ov['brend'], $ov['article']);
		else $item = [
			'goodsID' => $ov['goodsID'],
			'warehouseGroup' => $ov['warehouseGroup']
		];
		$warehouseGroup = self::getWarehouseGroup($ov, $item);
		$url = "http://api.favorit-parts.ru/ws/v1/cart/add/";
		$url .= "?key=".self::$key;
		$url .= '&developerKey='.self::$developerKey;
		$url .= "&goods={$item['goodsID']}";
		$url .= "&warehouseGroup=$warehouseGroup";
		$url .= "&count={$ov['quan']}";
		Abcp::getUrlData($url); 
		if ($GLOBALS['response_header'][0] != 'HTTP/1.1 200 OK') return false;
		$orderValue = new OrderValue();
		$status_id = $ov['quan'] > 0 ? 7 : 5;
		$orderValue->changeStatus($status_id, $ov);
		return true;
	}

	/**
	 * gets warehouseGroup 
	 * @param  array $ov order value array, if $ov['quan'] = 0 then warehouseGroup is required
	 * @param  item $item array with goodsID and warehouseGroup
	 * @return string id warehouse
	 */
	private static function getWarehouseGroup($ov, $item){
		if ($ov['quan'] == 0) return $ov['warehouseGroup'];
		foreach($item['warehouses'] as $value){
			switch($ov['cipher']){
				case 'FAVO':
					if ($value['code'] == 'МС1' || $value['code'] == 'МС2') return $value['id'];
					break;
				case 'FAJA':
					if ($value['code'] == 'МЦС') return $value['id'];
					break;
			}
		}
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
			$code = $basket['warehouseGroup'][$b['warehouseGroup']]['code'];
			if (
				Armtek::getComparableString($basket['goods'][$b['goods']]['Brand']) == Armtek::getComparableString($ov['brend']) &&
				Armtek::getComparableString($basket['goods'][$b['goods']]['Number']) == Armtek::getComparableString($ov['article']) &&
				$ov['cipher'] == self::getCipherByCode($code)
			) return [
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
		$res = Abcp::getUrlData($url, null, [
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
		$res = Abcp::getUrlData($url, null, [
			"X-Favorit-DeveloperKey: ".self::$developerKey,
			'X-Favorit-ClientKey: '.self::$key
		]);
		return json_decode($res, true);
	}
}
