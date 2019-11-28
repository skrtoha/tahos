<?php 
require_once ("../class/database_class.php");

$db = new DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

if ($db->insert('categories_items', array('item_id' => $_POST['item_id'], 'category_id' => $_POST['category_id']))) echo "ok";
?>
