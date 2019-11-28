<?php  
require_once ("../class/database_class.php");
session_start();
require_once ("../core/functions.php");

$db = new DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

// print_r($_POST);
$orders_values = get_orders($_POST, '');
if (!$orders_values) exit();
foreach($orders_values as $key => $value) $orders_values[$key]['href'] = getHrefArticle($value['article']);
echo json_encode($orders_values);
?>