<?php
require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/DataBase.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/functions.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

switch($_POST['act']){
	case 'isGaraged':
        $isGaraged = '';
		$entitiesPartsCatalogs = getEntitiesPartsCatalogs();
		$row = $db->select_one('garage', '*', "`user_id` = {$_POST['user_id']} AND `modification_id` = '$entitiesPartsCatalogs'");
		if ($row) $isGaraged = 'is_garaged';
        $result = \core\User::get(['user_id' => $_POST['user_id']]);
        $userInfo = $result->fetch_assoc();
        echo json_encode([
            'isGaraged' => $isGaraged,
            'userFullName' => $userInfo['full_name']
        ]);
		break;
	case 'addToGarage':
        $array = [
            'user_id' => $_POST['user_id'],
            'modification_id' => getEntitiesPartsCatalogs(),
            'title' => trim($_POST['title'])
        ];
        if (isset($_POST['year']) && $_POST['year']) $array['year'] = $_POST['year'];
        if (isset($_POST['owner']) && $_POST['owner']) $array['owner'] = $_POST['owner'];
        if (isset($_POST['year']) && $_POST['year']) $array['year'] = $_POST['year'];
        
		$db->insert('garage', $array);
		break;
	case 'removeFromGarage':
		$entitiesPartsCatalogs = getEntitiesPartsCatalogs();
		$db->delete('garage', "`user_id` = {$_POST['user_id']} AND `modification_id` = '$entitiesPartsCatalogs'");
		break;
}
function getEntitiesPartsCatalogs(){
    if (isset($_POST['modification_id'])) return $_POST['modification_id'];
	return "{$_POST['catalogId']},{$_POST['modelId']},{$_POST['carId']},{$_POST['q']}";
}
