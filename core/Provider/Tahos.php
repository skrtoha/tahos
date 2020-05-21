<?
namespace core\Provider;
class Tahos{
	public static $store_id = 23;
	public static $provider_id = 14;
	public static $isDoNotShowStoresCheeperTahos = true;

	public function parseResItem(\mysqli_result $res_items): array
	{
		$priceTahos = NULL;	
		$items = [];
		foreach($res_items as $v){
			$items[] = $v;
			if ($v['store_id'] == self::$store_id) $priceTahos = $v['price'];
		}
		if (!self::$isDoNotShowStoresCheeperTahos || !$priceTahos) return $items;
		$output = [];
		foreach($items as $v){
			if ($v['price'] < $priceTahos && $v['store_id'] != self::$store_id) continue;
			$output[] = $v;
		}
		return $output;
	}
}
