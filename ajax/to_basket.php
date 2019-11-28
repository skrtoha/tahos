<?php  
session_start();
require_once ("../class/database_class.php");
require_once ("../core/functions.php");

$db = new DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

// print_r($_POST);
// exit();
$store_id = $_POST['store_id'];
$item_id = $_POST['item_id'];
$price = $_POST['price'];
$packaging = $_POST['packaging'];
$user_id = $_SESSION['user'];
$db->insert(
	'basket',
	[
		'user_id' => $user_id, 
		'store_id' => $store_id, 
		'item_id' => $item_id,
		'quan' => $packaging, 
		'price' => $price,
	],
	['duplicate' => [
		'quan' => 'quan + 1'
	]]
);
echo (json_encode(get_basket()));
?>
		