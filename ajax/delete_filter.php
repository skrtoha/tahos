<?php 
require_once ("../class/database_class.php");

$db = new DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

if ($db->delete('filters', 'id='.$_POST['filter_id'])){
	$filters_values = $db->select('filters_values', 'id', "`filter_id`=".$_POST['filter_id']){
		if (count($filters_values)){
			foreach ($filters_values as $filter_value) {
				$db->delete('items_values', "`value_id`=".$filter_value['id']);
				$db->delete('filters_values', "`id`=".$filter_value['id']);
			}
		}
	}
}
echo "ok";
