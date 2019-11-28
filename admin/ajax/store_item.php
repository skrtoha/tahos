<?php 
require_once ("../../class/database_class.php");

$db = new DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$value = $_POST['value'];
$where = "`store_id`={$_POST['store_id']} AND `item_id`={$_POST['item_id']}";
switch ($_POST['column']) {
	case 'price':
		$res = $db->update('store_items', array('price' => $value), $where);
		break;
	case 'in_stock':
		$res = $db->update('store_items', array('in_stock' => $value), $where);
		break;
	case 'delivery':
		$res = $db->update('store_items', array('delivery' => $value), $where);
		break;
	case 'packaging':
		$res = $db->update('store_items', array('packaging' => $value), $where);
		break;
}
$price_updated = $db->query("
	UPDATE
		#provider_stores
	SET
		price_updated=CURRENT_TIMESTAMP
	WHERE
		`id`={$_POST['store_id']}
", '');
if ($res and $price_updated === true) echo "ok";
?>
