<?php
ini_set('error_reporting', E_ERROR);
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
		$osiArray = explode(',', $request['osi']);
		foreach($osiArray as $osiString){
			$osi = Synchronization::getArrayOSIFromString($osiString);
			$ov_result = core\OrderValue::get($osi);
			core\OrderValue::changeStatus(11, $ov_result->fetch_assoc());
		}
		$nonSynchronizedOrders = core\Synchronization::getNoneSynchronizedOrders();
		core\Synchronization::sendRequest('orders/write_orders', $nonSynchronizedOrders);
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
			$osi = Synchronization::getArrayOSIFromString($request['osi'][$i]);
			$ov_result = core\OrderValue::get($osi);
			$ov = $ov_result->fetch_assoc();
			$ov['quan'] = $request['quan'][$i];
			core\OrderValue::changeStatus(2, $ov);
		}
		break;
}

?>