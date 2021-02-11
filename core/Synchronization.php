<?php
namespace core;
require_once($_SERVER['DOCUMENT_ROOT'] . '/admin/functions/orders.function.php');

class Synchronization{
	private static $url = 'http://localhost/trade/hs';
	public static function getNoneSynchronizedOrders(){
		return self::getOrders(['is_synchronized' => 0], '');
	}
	private static function getOrders($params, $flag = ''){
		$output = [];
		$res_order_values = get_order_values($params, $flag);
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
			UPDATE #orders_values SET is_synchronized = 1 WHERE order_id IN ($orders)
		", '');
	}
}
