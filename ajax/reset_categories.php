<?php 
require_once ("../core/Database.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$res_1 = $db->delete('categories_items', "`item_id`=".$_POST['item_id']);
$res_2 = $db->delete('items_values', "`item_id`=".$_POST['item_id']);
if ($res_1 and $res_2) echo "ok";
?>
