<?php  
require_once ("../core/Database.php");

$db = new core\Database();
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
		