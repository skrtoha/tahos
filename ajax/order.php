<?php

use core\OrderValue;
use core\Synchronization;

ini_set('error_reporting', E_ERROR | E_PARSE);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once ("../core/Database.php");
session_start();


$db = new core\Database();
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
		core\Returns::createReturnRequest($_POST['items']);
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
		$countOrderValues = $db->getCount('orders_values', "`order_id` = {$_POST['order_id']}");
		if (!$countOrderValues) $db->delete('orders', "`id` = {$_POST['order_id']}");
		break;
	case 'undoReturn':
		$db->update('returns', ['status_id' => 5], "`id` = {$_POST['return_id']}");
		break;
    case 'changeOrderInfo':
        $values = $_POST['values'];
        $dateTimeObject = DateTime::createFromFormat('d.m.Y', $values['date_issue']);
        $values['date_issue'] = $dateTimeObject->format('Y-m-d');
        $values['entire_order'] = $values['entire_order'] ?? 0;
        $db->update('orders', $values, "`id` = {$_POST['order_id']}");
        break;
    case 'set_1c_return':
        $data = $_POST['items'][0];
        $orderInfo = OrderValue::get(['order_id' => $data['order_id']])->fetch_assoc();
        $params = [
            'user_id' => $orderInfo['user_id'],
            'item_id' => $data['item_id'],
            'quan' => $data['quan'],
            'arrangement_uid' => $orderInfo['arrangement_uid']
        ];
        Synchronization::createReturn($params);
        break;
}
if ($res) echo true;
else echo false;
?>
