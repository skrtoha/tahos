<?php  
session_start();
require_once ("../class/database_class.php");
require_once ("../core/functions.php");

$db = new DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$db->insert(
	'basket',
	[
		'user_id' => $_SESSION['user'], 
		'store_id' => $_POST['store_id'], 
		'item_id' => $_POST['item_id'],
		'quan' => $_POST['quan'], 
		'price' => $_POST['price'],
	],
	['duplicate' => [
		'quan' => "{$_POST['quan']}"
	]]
);
echo (json_encode(get_basket()));
?>
		