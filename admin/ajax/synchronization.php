<?php
ini_set('error_reporting', E_ERROR | E_PARSE);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

use core\Synchronization;

require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/DataBase.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/templates/functions.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/functions/orders.function.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/functions/order_issues.function.php");


$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();
$request = !empty($_POST) ? $_POST : $_GET;

switch($request['act']){
	case 'getOrders':
		$output = Synchronization::getNoneSynchronizedOrders();
		echo json_encode($output, JSON_UNESCAPED_UNICODE);
		break;
	case 'setSynchronizedOrders':
		Synchronization::setOrdersSynchronized($request['orders']);
		break;
	//в 1С создает заказ поставщику и отправляет в "Заказано"
	case 'createOrderAndSendOrdered':
		$countOrdered = 0;
		$osiArray = explode(',', $request['osi']);
		foreach($osiArray as $osiString){
			$osi = Synchronization::getArrayOSIFromString($osiString);
			$ov_result = core\OrderValue::get($osi, '');
			core\OrderValue::setStatusInWork($ov_result->fetch_assoc(), true);
			$countOrdered = core\OrderValue::$countOrdered;
		}
		$nonSynchronizedOrders = core\Synchronization::getNoneSynchronizedOrders();
		core\Synchronization::sendRequest('orders/write_orders', $nonSynchronizedOrders);
		echo $countOrdered;
		break;
	case 'setStatusArrived':
		$orders = Synchronization::getOrders(Synchronization::getArrayOSIFromString($request['osi']));
		$order = array_shift($orders);
		foreach($order['values'] as $ov){
			$ov['quan'] = $request['quan'];
			core\OrderValue::changeStatus(3, $ov);
		}
		break;
	case 'setStatusIssued':
		$issues = new \Issues($request['user_id'], $db);
		$income = [];

		for ($i = 0; $i < count($request['osi']); $i++) { 
			$osi = Synchronization::getArrayOSIFromString($request['osi'][$i]);
			$income["{$osi['order_id']}:{$osi['item_id']}:{$osi['store_id']}"] = $request['quan'][$i];
		}
		var_dump($issues->setIncome($income, true));
		break;
	case 'cancelItemsFromOrder':
		$osiArray = explode(',', $request['osi']);
		foreach($osiArray as $osiString){
			$osi = Synchronization::getArrayOSIFromString($osiString);
			$ov_result = core\OrderValue::get($osi);
			$ov = $ov_result->fetch_assoc();
			core\OrderValue::changeStatus(6, $ov);
		}
		break;
	case 'returnOrderValues':
		for ($i = 0; $i < count($request['osi']); $i++) { 
			$items = [];
			$osi = Synchronization::getArrayOSIFromString($request['osi'][$i]);
			$ov_result = core\OrderValue::get($osi);
			$ov = $ov_result->fetch_assoc();
			$items[] = [
				'order_id' => $ov['order_id'],
				'store_id' => $ov['store_id'],
				'item_id' => $ov['item_id'],
				'title' => "<b class=\"brend_info\" brend_id=\"{$ov['brend_id']}\">{$ov['brend']}</b> 
					<a href=\"/search/article/{$ov['article']}\" class=\"articul\">{$ov['article']}</a> 
					{$ov['title_full']}",									
				'summ' => $ov['quan'] * $ov['price'],
				'return_price' => $request['return_price'][$i],
				'days_from_purchase' => 14,
				'packaging' => $ov['packaging'],
				'reason_id' => 1,
				'quan' => $request['quan'][$i]
			];
			core\Returns::createReturnRequest($items);
			$paramsReturn = [
				'return_price' => $request['return_price'][$i],
				'quan' => $request['quan'][$i],
				'status_id' => 3
			];
			$res_return = core\Returns::get($osi);
			$return = $res_return->fetch_assoc();
			core\Returns::processReturn($paramsReturn, $return);
		}
		break;
}

?>
