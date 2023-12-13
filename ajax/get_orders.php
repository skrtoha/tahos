<?php  
session_start();
require_once ("../core/Database.php");
require_once ("../core/functions.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

// print_r($_POST);
$orders_values = get_orders($_POST, '');
if (!$orders_values) exit();
foreach($orders_values as $key => $value) $orders_values[$key]['href'] = core\Item::getHrefArticle($value['article']);
echo json_encode($orders_values);
?>