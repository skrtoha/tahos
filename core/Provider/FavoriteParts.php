<?php
namespace core\Provider;

use core\Provider;
use core\OrderValue;
use core\Log;

class FavoriteParts extends Provider{
	public static $fieldsForSettings = [
		"isActive",	// is required	
		'key',
		'developerKey',
		'provider_id',
		''
	];
	public static $error;

	public static function getParams($typeOrganization = 'entity'){
		return parent::getApiParams([
			'api_title' => 'FavoriteParts',
			'typeOrganization' => $typeOrganization
		]);
	}

	private static function getCodesByCipher($cipher): array
	{
		switch($cipher){
			case 'FAVO':
				return ['МЦС'];
				break;
			case 'FAMO':
				return ['МС1', 'МС2', 'Дилер OE', 'МСК', 'ЦС OE'];
				break;
		}
		return false;
	}
	public static function getPrice(array $params){
		$url = 'http://api.favorit-parts.ru/hs/hsprice/?key='.self::getParams()->key.'&number='.$params['article'].'&brand='.$params['brend'].'&analogues=';
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
		if (!parent::getIsEnabledApiSearch(self::getParams()->provider_id)) return false;
		if (!parent::isActive(self::getParams()->provider_id)) return false;
		$coincidences = array();
		$response = self::getUrlData(
			'http://api.favorit-parts.ru/hs/hsprice/?key='.self::getParams()->key.'&number='.$search
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
		$baskets = [
			'entity' => self::getBasket('entity'),
			'private' => self::getBasket('private')
		];
		$output = [];
		foreach($baskets as $basket){
			if (!$basket) continue;
			foreach($basket['cart'] as $c){
				$osi = explode('-', $c['comment']);
				$output[$c['comment']] = [
					'provider' => 'FavoriteParts',
					'provider_id' => self::getParams()->provider_id,
					'order_id' => $osi[0],
					'store_id' => $osi[1],
					'item_id' => $osi[2],
					'store' => self::getStoreByWarehouseGroup($basket, $c['warehouseGroup']),
					'brend' => $basket['goods'][$c['goods']]['Brand'],
					'article' => $basket['goods'][$c['goods']]['Number'],
					'title_full' => $basket['goods'][$c['goods']]['Name'],
					'price' => $c['price'],
					'count' => $c['count'],
					'typeOrganization' => parent::getUserTypeByOrderID($osi[0])
				];
			} 
		}
		return $output;
	}

	/**
	 * gets item by brend and article
	 * @param  string $brend item brend
	 * @param  string $article item article
	 * @return array array of items
	 */
	public static function getItem($brend, $article){
		$url = 'http://api.favorit-parts.ru/hs/hsprice/?key='.self::getParams()->key.'&number='.$article.'&brand='.$brend.'&analogues=';
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
		debug($ov);
		if (!parent::getIsEnabledApiOrder(self::getParams()->provider_id)) return false;
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
		debug(self::getParams($ov['typeOrganization']));
		$url = "http://api.favorit-parts.ru/ws/v1/cart/add/";
		$url .= "?key=".self::getParams($ov['typeOrganization'])->key;
		$url .= '&developerKey='.self::getParams($ov['typeOrganization'])->developerKey;
		$url .= "&goods={$item['goodsID']}";
		$url .= "&warehouseGroup=$warehouseGroup";
		$url .= "&comment={$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}";
		$url .= "&count={$ov['quan']}";
		parent::getUrlData($url);
		debug($GLOBALS['response_header']);
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
		$basket = self::getBasket($ov['typeOrganization']);
		$osi = "{$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}";
		foreach($basket['cart'] as $basket){
			if ($basket['comment'] == $osi){
				$basket['typeOrganization'] = $ov['typeOrganization'];
				return self::addToBasket($basket);
			} 
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
	private static function getBasket($typeOrganization = 'entity'){
		$url = "http://api.favorit-parts.ru/ws/v1/cart/";
		$res = self::getUrlData($url, null, [
			"X-Favorit-DeveloperKey: ".self::getParams($typeOrganization)->developerKey,
			'X-Favorit-ClientKey: '.self::getParams($typeOrganization)->key
		]);
		return json_decode($res, true);
	}
	/**
	 * sends goods in favorite basket to order
	 * @return mixed false - no goods for order, true - successfully sent, string - error
	 */
	public static function toOrder(){
		$baskets = [
			'entity' => self::getBasket('entity'),
			'private' => self::getBasket('private')
		];
		foreach($baskets as $typeOrganization => $basket){
			$dateShipments = [];
			$PaymentType = $typeOrganization == 'entity' ? 2 : 1;
			foreach($basket['cart'] as $value) $dateShipments[$value['dateShipment']][] = $value;
			foreach($dateShipments as $date => $dateShipment){
				$GoodsList = self::getGoodsForBasket($dateShipment, $typeOrganization);
				if (empty($GoodsList)) continue;
				$user = self::getUser($typeOrganization);
				// debug($user);
				$array = [
					'WarehouseShipping' => self::getWarehouseShipping($basket),
					'ShippingDate' => $date,
					'TradePoint' => '070C5324-32F6-11EB-A33B-005056802F4C',
					'PaymentType' => $PaymentType,
					'DeliveryType' => $user['deliveryType'],
					'TransportType' => $user['transportType'],
					'Comment' => '',
					'GoodsList' => $GoodsList 
				];
				// debug($array); exit();
				$curl = curl_init('http://api.favorit-parts.ru/ws/v1/order/');
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($array));
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_HTTPHEADER, [
				   "Content-type: application/json", 
					"X-Favorit-DeveloperKey: ".self::getParams($typeOrganization)->developerKey,
					'X-Favorit-ClientKey: '.self::getParams($typeOrganization)->key
				]);
				$result = curl_exec($curl);
				if (!parent::isJSON($result)){
					foreach($GoodsList as $good) Log::insert([
						'text' => $result,
						'additional' => "osi: " . $good['Comment']
					]);
				}
				curl_close($curl);
				self::setStatusOrdered($GoodsList);
			}
		}
	}
	/**
	 * sets status "ordered" after successfully sended order favorite parts
	 */
	public static function setStatusOrdered($GoodsList){
		$where = '';
		foreach($GoodsList as $good){
			$array = explode('-', $good['Comment']);
			$where .= "(`order_id` = {$array[0]} AND `store_id` = {$array[1]} AND `item_id` = {$array[2]}) OR ";
		}
		$where = substr($where, 0, -4);
		$orders_values = $GLOBALS['db']->query("
			SELECT
				ov.*
			FROM 
				#orders_values ov
			LEFT JOIN
				#provider_stores ps ON ps.id = ov.store_id
			WHERE
				$where
		", '');
		foreach($orders_values as $ov) OrderValue::changeStatus(11, $ov);
	}
	/**
	 * gets goods for favorite basket using getBasket
	 * @param  array $basket received by getBasket
	 * @return [type]         [description]
	 */
	private static function getGoodsForBasket($cart, $inputTypeOrganization){
		$output = array();
		foreach($cart as $b){
			$osi = explode('-', $b['comment']);
			$typeOrganization = parent::getUserTypeByOrderID($osi[0]);
			if ($typeOrganization != $inputTypeOrganization) continue;
			$output[] = [
				'Goods' => $b['goods'],
				'WarehouseGroup' => $b['warehouseGroup'],
				'Count' => $b['count'],
				'Comment' => $b['comment'],
				'PaymentType' => $typeOrganization == 'private' ? 1 : 2 
			];
		} 
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
	private static function getUser($typeOrganization = 'entity'){
		$url = "http://api.favorit-parts.ru//ws/v1/references/profile/";
		$res = self::getUrlData($url, null, [
			"X-Favorit-DeveloperKey: ".self::getParams($typeOrganization)->developerKey,
			'X-Favorit-ClientKey: '.self::getParams($typeOrganization)->key
		]);
		return json_decode($res, true);
	}
}
