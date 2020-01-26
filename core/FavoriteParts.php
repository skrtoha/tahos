<?php
namespace core;
class FavoriteParts{
	private static $key = 'F4289750-BAFA-434C-8C2D-09AC06D6E6C2';
	private static $developerKey = '156C7176-B22F-4617-94B0-94C1B530FA75';
	
	private static $provider_id = 19;
	private static $stores = [
		'22663' => [
			'code' => 'МЦС',
			'id' => '23F7657D-FA9D-11E7-812E-0050568E1762',
			'cipher' => 'FAVO'
		]
		// ,
		// '22663' => [
		// 	'code' => 'МС2',
		// 	'id' => '23F7657D-FA9D-11E7-812E-0050568E1762',
		// 	'cipher' => 'FAVO'
		// ]
	];

	public function __construct($db){
		$this->db = $db;
	}

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
		// print_r($items);
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
	 *       if quan = 0 then removes from basket (goodsID and warehouse is required)
	 * @return boolean true if successfully added, false if faled
	 */
	public static function addToBasket($ov){
		if ($ov['quan']) $item = self::getItem($ov['brend'], $ov['article']);
		else $item = [
			'goodsID' => $ov['goodsID'],
			'warehouseGroup' => $ov['warehouseGroup']
		];
		// debug($ov, 'ov');
		// debug($item, 'item'); //return;
		// debug(self::getBasket());
		$warehouseGroup = $ov['quan'] ? $item['warehouses'][0]['id'] : $ov['warehouseGroup'];
		$url = "http://api.favorit-parts.ru/ws/v1/cart/add/";
		$url .= "?key=".self::$key;
		$url .= '&developerKey='.self::$developerKey;
		$url .= "&goods={$item['goodsID']}";
		$url .= "&warehouseGroup=$warehouseGroup";
		$url .= "&count={$ov['quan']}";
		Abcp::getUrlData($url);
		if ($ov['quan']){
			if (!self::isInBasket($ov)) return false;
		}
		$orderValue = new OrderValue();
		$status_id = $ov['quan'] > 0 ? 7 : 5;
		$orderValue->changeStatus($status_id, $ov);
		return true;
	}

	/**
	 * checks if a item exists in the basket
	 * @param  array  $ov brend, article
	 * @return boolean     [description]
	 */
	public static function isInBasket($ov){
		$basket = self::getBasket();
		// debug($ov, 'ov');
		foreach($basket['cart'] as $b){
			if (
				Armtek::getComparableString($basket['goods'][$b['goods']]['Brand']) == Armtek::getComparableString($ov['brend']) &&
				Armtek::getComparableString($basket['goods'][$b['goods']]['Number']) == Armtek::getComparableString($ov['article'])
			) return [
				'goodsID' => $b['goods'],
				'warehouseGroup' => $b['warehouseGroup']
			];
		}
		return false;
	}

	private static function getBasket(){
		$url = "http://api.favorit-parts.ru/ws/v1/cart/";
		$url .= "?key=".self::$key;
		$url .= '&developerKey='.self::$developerKey;
		$response = Abcp::getUrlData($url);
		$array = json_decode($response, true);
		return $array;
	}
}
