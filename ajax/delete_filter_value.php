<?php 
require_once ("../class/database_class.php");

$db = new DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

if ($db->delete('filters_values', 'id='.$_POST['filter_value_id'])){
	$db->delete('items_values', "`value_id`=".$_POST['filter_value_id']);
}
echo "ok";
