<?php  
use core\Provider\Autoeuro;
session_start();
require_once ("../core/DataBase.php");
require_once ("../core/functions.php");

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

if (Autoeuro::isAutoeuro($_POST['store_id'])) Autoeuro::putBusket($_POST);

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
		