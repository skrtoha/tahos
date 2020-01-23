<?php
namespace core;
class FavoriteParts{
	private static $key = 'F4289750-BAFA-434C-8C2D-09AC06D6E6C2';
	
	private static $provider_id = 19;
	private static $stores = [
		'22664' => [
			'code' => 'МС2',
			'id' => 'E8C31C27-031C-11E7-80CF-0050568E1762',
			'cipher' => 'FAVO'
		],
		'22663' => [
			'code' => 'МЦС',
			'id' => '23F7657D-FA9D-11E7-812E-0050568E1762',
			'cipher' => 'FAVO'
		]
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

	public static function getItem($brend, $article){
		$response = Abcp::getUrlData(
			'http://api.favorit-parts.ru/hs/hsprice/?key='.self::$key.'&number='.$article.'&brand='.$brend.'&analogues='
		);
		$array = json_decode($response, true);
		return $array['goods'][0];
	}

	public static function addToBasket($ov){
		debug($ov, 'ov');
		$item = self::getItem($ov['brend'], $ov['article']);
		debug($item, 'item');
		$url = "https://api.favorit-parts.ru/ws/v1/cart/add/";
		$url .= "?key=".self::$key;
		$url .= "&goods={$item['goodsID']}";
		$url .= "&warehouseGroup=".self::$stores[$ov['store_id']]['id'];
		$url .= "&count={$ov['quan']}";
		debug($url, 'url');
		// $response = Abcp::getUrlData($url);
		$response = file_get_contents($url);
		debug($response);
		debug(json_decode($response, true));

		// count=1&comment="
	}
}
