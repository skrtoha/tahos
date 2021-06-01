<?php
require_once ("../core/DataBase.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$bool = false;
$value_id = $_POST['prop_value'];
$item_id = $_POST['item_id'];
print_r($_POST);
//получаем id свойства
$filter_id = $db->getFieldOnID('filters_values', $value_id, 'filter_id');
// echo $filter_id;
// exit();
//получаем id значений данного свойства
$temp = $db->select('filters_values', 'id', "`filter_id` = $filter_id");
$count = count($temp);
for ($i = 0; $i < $count; $i++) $values[] = $temp[$i]['id'];
// echo "id значений данного свойства<br>";
// print_r($values);
//проверяем какие значения свойства есть items_properties
$values_item = $db->select('items_values', '*', "`item_id` = $item_id");
// echo "значения свойств данное товара <br>";
// print_r($values_item);
$count = count($values_item);
//проверяем нету ли в items_properties значений полученных свойств
for ($i = 0; $i < $count; $i++){
	if (in_array($values_item[$i]['value_id'], $values)){
		if ($db->update('items_values', array('value_id' => $value_id), "`id` = ".$values_item[$i]['id'])){
			echo "ok";
			$bool = true;
			break;
		} 
	}
}
if (!$bool){
	if ($db->insert('items_values', array('item_id' => $item_id, 'value_id' => $value_id))) echo "ok";
} 
?>