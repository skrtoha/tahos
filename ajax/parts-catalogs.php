<?php
require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/DataBase.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/functions.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

switch($_POST['act']){
	case 'isGaraged':
		$entitiesPartsCatalogs = getEntitiesPartsCatalogs();
		$row = $db->select_one('garage', '*', "`user_id` = {$_POST['user_id']} AND `modification_id` = '$entitiesPartsCatalogs'");
		if ($row) echo 'is_garaged';
		break;
	case 'addToGarage':
		$db->insert('garage', [
			'user_id' => $_POST['user_id'],
			'modification_id' => getEntitiesPartsCatalogs(),
			'title' => trim($_POST['title'])
		]/*, ['print' => true]*/);
		break;
	case 'removeFromGarage':
		$entitiesPartsCatalogs = getEntitiesPartsCatalogs();
		$db->delete('garage', "`user_id` = {$_POST['user_id']} AND `modification_id` = '$entitiesPartsCatalogs'");
		break;
}
function getEntitiesPartsCatalogs(){
	return "{$_POST['catalogId']},{$_POST['modelId']},{$_POST['carId']},{$_POST['q']}";
}
