<?php
namespace core\Provider;
use core\Provider;
class Impex extends Provider{
	public static $provider_id = 1;
	public static $marks = [
		'TOYOTA / LEXUS' => 1,
		'NISSAN' => 2,
		'MITSUBISHI' => 3,
		'MAZDA' => 4,
		'HONDA' => 5,
		'ISUZU' => 6,
		'DAIHATSU' => 7,
		'SUBARU' => 8,
		'SUZUKI' => 9,
		'HINO' => 10,
		'NISSAN DIESEL' => 12,
		'MITSUBISHI FUSO' => 13,
		'NIHON FORD' => 14,
		'ISUZU TRUCK' => 16,
		'HONDA MOTO' => 21,
		'SUZUKI MOTO' => 22,
		'KAWASAKI' => 23,
		'YAMAHA' => 24,
		'ISEKI' => 100,
		'KUBOTA' => 102,
		'YANMAR' => 103,
		'TCM' => 110,
		'KYOKUTO' => 114,
		'KOMATSU' => 115,
		'TOKYU' => 119,
		'HITACHI' => 123,
		'FURUKAWA' => 127,
		'MERCURY-MERCRUISER' => 234,
		'AISIN' => 1002,
		'AKEBONO' => 1003,
		'OHNO 5825' => 1102,
		'KYB' => 1127,
		'HKT' => 1151,
		'JTEKT' => 1228,
		'JAPAN REBUILT' => 1270,
		'STANLEY' => 1302,
		'SPK' => 1303,
		'GATES' => 1331,
		'DAIWA RADIATOR' => 1359,
		'KOYO' => 1360,
		'EXEDY' => 1378,
		'DAITO PRESS' => 1381,
		'TAMA KOUGYOU' => 1385,
		'TACTI' => 1396,
		'RAP' => 1411,
		'TOKICO' => 1484,
		'NILES' => 1501,
		'PITWORK' => 1522,
		'NGK' => 1534,
		'NIPPON MICROFILTER' => 1537,
		'NIPPON WIPER BLADE' => 1538,
		'PMC FILTER' => 1626,
		'PARAUT' => 1628,
		'BANDO' => 1630,
		'MATSUI' => 1752,
		'MATSUKO' => 1753,
		'TOKAI MATERIAL' => 1779,
		'MITSUBOSHI' => 1780,
		'MIYACO' => 1781,
		'MUSASHI OIL SEAL' => 1801,
		'AIRMAN' => 9601,
		'CATERPILLAR' => 9602,
		'IHI' => 9603,
		'KATO' => 9604,
		'KOBELCO' => 9605,
		'SHINMAYWA' => 9606,
		'SUMITOMO' => 9607,
		'TADANO' => 9608,
		'TAKEUCHI' => 9610,
		'UNIC' => 9611,
		'TOHATSU' => 10000,
		'HONDA MARINE' => 10001,
		'SUZUKI MARINE' => 10002,
	]; 
	public static function getPrice(array $params){}
	public static function getItemsToOrder(int $provider_id){}
	public static function getData($params){
		$url = "https://www.impex-jp.com/api/parts/search.html?part_no={$params['article']}&key=EicWfyYXZs5xJeKtrVuQ";
		if (isset($params['brend']) && $params['brend']){
			$mark_id = self::$marks[strtoupper($params['brend'])];
			$url .= "&mark_id=$mark_id";
		} 
		return json_decode(file_get_contents($url), true);
	}
	public static function setSearch($params){
		if (!Provider::getIsEnabledApiSearch(1)) return false;
		$db = $GLOBALS['db'];
		$data = self::getData([
			'article' => article_clear($params['search']),
			'brend' => $params['brend']
		]);
		$is_empty_original_parts = empty($data['original_parts']); 
		$is_empty_replacement_parts = empty($data['replacement_parts']);
		if (!$is_empty_original_parts){
			$store_id = $db->getField('provider_stores', 'id', 'cipher', 'impx');
			foreach($data['original_parts'] as $item){
				if (!$item['price_yen']) continue;
				$title_full = $item['name_rus'] ? $item['name_rus'] : $item['name'];
				$brend_id = get_brend($item['mark']);
				$res = $db->insert(
					'items',
					[
						'title_full' => $title_full,
						'title' => $title_full,
						'brend_id' => $brend_id,
						'article' => $item['part_no_raw'],
						'article_cat' => $item['part'],
						'weight' => $item['weight'] * 1000
					],
					['deincrement_dublicate' => 1]
				);
				if ($res === true){
					$item_last_id = $db->last_id();
					$articles[] = $item_last_id;
				} 
				else{
					$array = $db->select_one(
						'items', 
						'id,title,title_full', 
						"`article`='{$item['part_no_raw']}' AND `brend_id`=$brend_id"
					);
					if ($array['title_full'] == 'Деталь') \core\Item::update(['title_full' => $title_full, 'title' => $title_full], ['id' => $array['id']]);
					$item_last_id = $array['id'];
					$articles[] = $item_last_id;
				} 
				if ($store_id) $db->insert(
					'store_items', 
					[
						'item_id' =>$item_last_id,
						'store_id' => $store_id,
						'price' => $item['price_yen'],
						'in_stock' => 0,
						'packaging' => 1
					]
				); 
				if (!$is_empty_replacement_parts){
					foreach($data['replacement_parts'] as $item){
						if (!$item['price_yen']) continue;
						$brend_id = get_brend($item['mark']);
						$res = $db->insert(
							'items',
							[
								'title_full' => $item['name_rus'] ? $item['name_rus'] : $item['name'],
								'title' => $item['name_rus'] ? $item['name_rus'] : $item['name'],
								'brend_id' => $brend_id,
								'article' => $item['part_no_raw'],
								'article_cat' => $item['part'],
								'weight' => $item['weight'] * 1000
							]
						);
						if ($res === true) $last_sub = $db->last_id();
						else{
							$array = $db->select_one(
								'items', 
								'id', 
								"`article`='{$item['part_no_raw']}' AND `brend_id`=$brend_id"
							);
							$last_sub = $array['id'];
						} 
						$db->insert('articles', ['item_id' => $last_sub, 'item_diff' => $last_sub]);
						$db->insert('analogies',['item_id' => $item_last_id, 'item_diff' => $last_sub]);
						$db->insert('analogies',['item_id' => $last_sub, 'item_diff' => $item_last_id]);
						if ($store_id && $item['price_yen']) $db->insert(
							'store_items', 
							[
								'item_id' =>$last_sub,
								'store_id' => $store_id,
								'price' => $item['price_yen'],
								'in_stock' => 0,
								'packaging' => 1
							]
						);
					}
				}
			}
			// debug($articles); exit();
			if (!empty($articles)){
				foreach($articles as $value){
					$current = $value;
					foreach($articles as $val) $db->insert(
						'articles',
						[
							'item_id' => $current,
							'item_diff' => $val
						]
					);
				}
			}
		}
	}
	public static function isInBasket($ov){}
}
