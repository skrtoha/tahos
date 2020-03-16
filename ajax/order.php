<?php  
require_once ("../core/DataBase.php");
session_start();

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

// print_r($_POST); exit();
switch ($_POST['act']){
	case 'plus':
	case 'minus':
		$act = $_POST['act'] == 'minus' ? '-' : '+';
		$res = $db->query("
			UPDATE
				#orders_values
			SET 
				`quan` = `quan` $act {$_POST['packaging']},
				`status_id`=5
			WHERE
				`order_id`={$_POST['order_id']} AND
				`store_id`={$_POST['store_id']} AND
				`item_id` = {$_POST['item_id']}
		");
		break;
	case 'comment':
		print_r($_POST);
		$res = $db->query("
			UPDATE
				#orders_values
			SET 
				`comment` = '{$_POST['text']}',
				`status_id`=5
			WHERE
				`order_id`={$_POST['order_id']} AND
				`store_id`={$_POST['store_id']} AND
				`item_id` = {$_POST['item_id']}
		", '');
		break;
	case 'delete':
		$db->delete('orders_values', "`order_id`={$_POST['order_id']} AND `item_id`={$_POST['item_id']} AND `store_id`={$_POST['store_id']}");
		if (!$db->getCount('orders_values', "`order_id`={$_POST['order_id']}")) $db->delete('orders', "`id`={$_POST['order_id']}");
		// print_r($_POST);
		break;
}
if ($res) echo true;
else echo false;
?>
