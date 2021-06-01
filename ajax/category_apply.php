<?php
require_once ("../core/DataBase.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$category_id = $_POST['category_id'];
$where = "`item_id`=".$_POST['item_id'];
// print_r($_POST);
// exit();
if (!$category_id){
	if ($db->delete('categories_items', $where)){
		$db->delete('items_values', "`item_id`=".$_POST['item_id']);
		echo "ok";
	} 
} 
else{
	if ($db->getCount('categories_items', $where)){
		if ($db->update('categories_items', array('category_id' => $category_id), $where)) echo "ok";
	}
	else{
		if ($db->insert('categories_items', array('category_id' => $category_id, 'item_id' => $_POST['item_id']))) echo "ok";
	}
}
?>