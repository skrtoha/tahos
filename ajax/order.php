<?php  
require_once ("../core/DataBase.php");
session_start();

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

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
	case 'to_return':
		$emailPrices = core\Provider::getEmailPrices();
		foreach($_POST['items'] as $value){
			if (in_array($value['store_id'], $emailPrices)) $status_id = 2;
			else $status_id = 1;
			$db->query("
				INSERT INTO #returns (`order_id`,`store_id`,`item_id`,`reason_id`,`quan`,`status_id`) 
				VALUES ({$value['order_id']}, {$value['store_id']}, {$value['item_id']}, {$value['reason_id']}, {$value['quan']}, $status_id) 
				ON DUPLICATE KEY UPDATE `status_id` = $status_id,`created` = CURRENT_TIMESTAMP, `updated` = NULL
			", '');
		}
		break;
	case 'get_returns':
		$res_returns = core\Returns::get(['user_id' => $_SESSION['user']]);
		if (!$res_returns->num_rows) break;
		$output = [];
		foreach($res_returns as $value) $output[] = $value;
		echo json_encode($output);
		break;
	case 'get_reasons':
		$res_reasons = core\Returns::getReasons();
		$output = [];
		foreach($res_reasons as $value) $output[] = $value;
		echo json_encode($output);
		break;
	case 'removeFromOrder':
		$db->delete('orders_values', core\Provider::getWhere($_POST));
		break;
	case 'undoReturn':
		$db->update('returns', ['status_id' => 5], "`id` = {$_POST['return_id']}");
		break;
}
if ($res) echo true;
else echo false;
?>
