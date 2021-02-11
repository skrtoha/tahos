<?php
use core\Synchronization;

require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/DataBase.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/templates/functions.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/functions/orders.function.php");

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

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
}

?>