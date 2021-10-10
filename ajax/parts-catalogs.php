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
        echo json_encode([
            'isGaraged' => $isGaraged,
            'userFullName' => ''
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
        if (isset($_POST['phone']) && $_POST['phone']) $array['phone'] = $_POST['phone'];
        
		$db->insert('garage', $array, [
            'duplicate' => [
                'owner' => $_POST['owner'],
                'phone' => $_POST['phone'],
                'year' => $_POST['year'],
                'title' => $_POST['title']
            ]
        ]);
		break;
	case 'removeFromGarage':
		$entitiesPartsCatalogs = getEntitiesPartsCatalogs();
		$db->delete('garage', "`user_id` = {$_POST['user_id']} AND `modification_id` = '$entitiesPartsCatalogs'");
		break;
    case 'getGarageInfo':
        $query = \core\Garage::getQuery();
        $query .= "WHERE g.modification_id = '{$_POST['modification_id']}'";
        echo json_encode($db->query($query)->fetch_assoc());
        break;
}
function getEntitiesPartsCatalogs(){
    if (isset($_POST['modification_id'])) return $_POST['modification_id'];
	return "{$_POST['catalogId']},{$_POST['modelId']},{$_POST['carId']},{$_POST['q']}";
}
