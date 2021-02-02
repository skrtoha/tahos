<?php
require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/DataBase.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/templates/functions.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/functions/orders.function.php");
error_reporting(E_ERROR | E_PARSE);

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();
$request = !empty($_POST) ? $_POST : $_GET;

switch($request['act']){
	case 'getOrders':
		$output = [];
		$res_order_values = get_order_values(['is_synchronized' => 0], '');
		foreach($res_order_values as $ov){
			$o = & $output[$ov['order_id']];
			$o['user_id'] = $ov['user_id'];
			$o['userName'] = $ov['userName'];
			$o['created'] = $ov['created'];
			$output[$ov['order_id']]['values'][] = [
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
				'withoutMarkup' => $ov['withoutMarkup'],
			];
		}
		// debug($output);
		echo json_encode($output);
		break;
}

?>