<?php 
require_once ("../core/Database.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

if ($db->delete('filters_values', 'id='.$_POST['filter_value_id'])){
	$db->delete('items_values', "`value_id`=".$_POST['filter_value_id']);
}
echo "ok";
