<?php
namespace core;

class Synchronization{
	private static $url = 'http://134.249.158.237/trade/hs';
	public static function getNoneSynchronizedOrders(){
		return self::getOrders(['synchronized' => 0], '');
	}
	public static function getOrders($params, $flag = ''){
		$output = [];
		$res_order_values = OrderValue::get($params, $flag);
		foreach($res_order_values as $ov){
			$o = & $output[$ov['order_id']];
			$o['user_id'] = $ov['user_id'];
			$o['userName'] = $ov['userName'];
			$o['created'] = $ov['created'];
			$output[$ov['order_id']]['values'][] = [
				'status_id' => $ov['status_id'],
				'provider_id' => $ov['provider_id'],
				'provider' => $ov['provider'],
				'cipher' => $ov['cipher'],
				'order_id' => $ov['order_id'],
				'user_id' => $ov['user_id'],
				'store_id' => $ov['store_id'],
				'providerStore' => $ov['providerStore'],
				'brend' => $ov['brend'],
				'brend_id' => $ov['brend_id'],
				'item_id' => $ov['item_id'],
				'article' => $ov['article'],
				'title_full' => $ov['title_full'],
				'packaging' => $ov['packaging'],
				'price' => $ov['price'],
				'quan' => $ov['quan'],
				'ordered' => $ov['ordered'],
				'arrived' => $ov['arrived'],
				'issued' => $ov['issued'],
				'returned' => $ov['returned'],
				'updated' => $ov['updated'] ? $ov['updated'] : $ov['created'],
				'typeOrganization' => $ov['typeOrganization'],
				'withoutMarkup' => $ov['withoutMarkup'],
			];
		}
		return $output;
	}
	public static function sendRequest($method, $array){
		return Provider::getCurlUrlData(
			self::$url . "/$method",
			json_encode($array, JSON_UNESCAPED_UNICODE)
		);
	}
	public static function setOrdersSynchronized($orders){
		return $GLOBALS['db']->query("
			UPDATE #orders_values SET synchronized = 1 WHERE order_id IN ($orders) AND synchronized = 0
		", '');
	}
	public static function getArrayOSIFromString($osi){
		$array = explode('-', $osi);
		return [
			'order_id' => $array[0],
			'store_id' => $array[1],
			'item_id' => $array[2]
		]; 
	}
}
