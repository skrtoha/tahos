<?php
session_start();
use core\Basket;


require_once ("../core/Database.php");
require_once ("../core/functions.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$user_id = $_POST['user_id'] ?? $_SESSION['user'];

$res = Basket::addToBasket([
    'user_id' => $user_id,
    'store_id' => $_POST['store_id'],
    'item_id' => $_POST['item_id'],
    'quan' => $_POST['quan'],
    'price' => $_POST['price'],
]);

echo (json_encode(get_basket()));
?>
		