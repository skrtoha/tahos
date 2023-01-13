<?php  
use core\Provider\Autoeuro;
session_start();
require_once ("../core/DataBase.php");
require_once ("../core/functions.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$user_id = $_POST['user_id'] ?? $_SESSION['user'];

$res = $db->insert(
	'basket',
	[
		'user_id' => $user_id,
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
		