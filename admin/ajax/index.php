<?php
require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/Database.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/templates/functions.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

switch($_GET['act']){
	case 'getOrderFundsHtmlData':
		echo core\Index::getHtmlOrderFunds(
			$_GET['dateFrom'],
			$_GET['dateTo'],
			$_GET['user_id']
		);
		break;
}

?>