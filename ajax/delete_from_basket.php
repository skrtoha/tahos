<?php  
require_once ("../core/DataBase.php");

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

session_start();
$db->delete('basket', "
	`provider_id`={$_POST['provider_id']} AND 
	`item_id`={$_POST['item_id']} AND 
	`user_id`={$_SESSION['user']}
");
?>
		