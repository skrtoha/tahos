<?php
require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/DataBase.php");

use core\Database;

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

switch($_POST['act']){
	case 'remove':
        Database::getInstance()->delete('managers', "id = '{$_POST['id']}'");
		break;
}

?>