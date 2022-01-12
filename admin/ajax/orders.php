<?
ini_set('error_reporting', E_PARSE | E_ERROR);
use core\OrderValue;

require_once('../../core/DataBase.php');

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$where = "
	`order_id`={$_POST['order_id']} AND
	`store_id`={$_POST['store_id']} AND
	`item_id`={$_POST['item_id']}
";
$user = $db->select_one('users', 'id,bonus_program,bonus_count', "`id`={$_POST['user_id']}");

$post = $_POST;
switch($_POST['status_id']){
	case 1://выдано
		$post['issued'] = $_POST['arrived'];
		core\OrderValue::changeStatus(1, $post);
		break;
	case 2://возврат
		// print_r($_POST); exit();
		$post['quan'] = $_POST['new_returned'];
		core\OrderValue::changeStatus(2, $post);
		break;
	case 3://пришло
		$post['quan'] = $_POST['arrived'];
		core\OrderValue::changeStatus(3, $post);
		break;
	case 6://нет в наличии
		core\OrderValue::changeStatus(6, $post);
		core\Provider::updateProviderBasket([
			'order_id' => $post['order_id'],
			'store_id' => $post['store_id'],
			'item_id' => $post['item_id']
		], ['response' => 'нет в наличии']);
		break;
    case 8://отменен
    case 12:
		$post['quan'] = $_POST['ordered'];
		core\OrderValue::changeStatus($_POST['status_id'], $post);
		core\Provider::updateProviderBasket([
			'order_id' => $post['order_id'],
			'store_id' => $post['store_id'],
			'item_id' => $post['item_id']
		], ['response' => 'отменен']);
		break;
    case 11://заказано
		$post['quan'] = $_POST['ordered'];
		core\OrderValue::changeStatus(11, $post);
		break;
	case 'arrived_new':
		// print_r($_POST);
		$db->query("
			UPDATE 
				#orders_values 
			SET 
				`arrived` = `arrived` + {$_POST['current']},
				`status_id`= 3
			WHERE $where
		", '');
		break;
	case 'declined':
		// print_r($_POST);
		$db->query("
			UPDATE 
				#orders_values 
			SET 
				`declined` = 1
			WHERE $where
		", '');
		$db->query("
			UPDATE
				#users
			SET
				`reserved_funds` = `reserved_funds` - {$_POST['current']} * {$_POST['price']}
			WHERE
				`id`={$_POST['user_id']}
		", '');
		break;
	case 'issued_new':
		$db->query("
			UPDATE 
				#orders_values 
			SET 
				`issued` = `issued` + {$_POST['issued_new']}
			WHERE $where
		", '');
		$db->insert(
			'funds',
			[
				'type_operation' => 2,
				'sum' => $_POST['price'] * $_POST['issued_new'],
				'remainder' => $_POST['bill'] - $_POST['price'] * $_POST['issued_new'],
				'user_id' => $_POST['user_id'],
				'comment' => 'Списание средств на оплату "'.$title.'"'
			],
			['print_query' => false]
		);
		$db->query("
			UPDATE
				#users
			SET
				`reserved_funds`=`reserved_funds` - {$_POST['price']} * {$_POST['issued_new']},
				`bill`=`bill` - {$_POST['price']} * {$_POST['issued_new']}
			WHERE
				`id`={$_POST['user_id']}
		", '');
		break;
	case 'return_to_basket':
		// print_r($_POST);
		$values = explode(',', $_POST['str']);
		foreach($values as $value){
			$str = explode(':', $value);
			$where = "`user_id`={$str[0]} AND `order_id`={$str[1]} AND `store_id`={$str[2]} AND `item_id`={$str[3]}";
			$s = $db->select_one('orders_values', '*', $where);
			$ov = [
				'user_id' => $s['user_id'],
				'order_id' => $s['order_id'],
				'store_id' => $s['store_id'],
				'item_id' => $s['item_id'],
				'quan' => $s['quan'],
				'price' => $s['price'],
				'comment' => $s['comment']
			];
			// print_r($ov); exit();
			$db->delete('orders_values', $where);
			if (!$db->getCount('orders_values', "`order_id`={$ov['order_id']}")){
				$db->delete('orders', "`id`={$ov['order_id']}");
			}
			$db->insert('basket', [
				'user_id' => $ov['user_id'],
				'store_id' => $ov['store_id'],
				'item_id' => $ov['item_id'],
				'quan' => $ov['quan'],
				'price' => $ov['price'],
				'comment' => $ov['comment']
			]);
		}
		break;
	case 'comment':
		$db->query("
			UPDATE 
				#orders_values 
			SET 
				`comment`= '{$_POST['text']}'
			WHERE $where
		", '');
		break;
	case 'remove':
		$db->delete('orders_values', $where);
		$count = $db->getCount('orders_values', "`order_id`={$_POST['order_id']}");
		if (!$count) $db->delete('orders', "`id`={$_POST['order_id']}");
		echo $count;
		break;
	case 'change_draft':
		$db->update('orders_values', [$_POST['name'] => $_POST['value'] ? $_POST['value'] : null], $where);
		break;
	case 'to_order':
		// print_r($_POST); exit();
		$db->update('orders', ['is_draft' => 0], "`id`={$_POST['order_id']}");
		$db->update('orders_values', ['status_id' => 5], "`order_id`={$_POST['order_id']}");
		break;
	case 'getOrderValue':
		$array = explode('-', $_POST['osi']);
		$res = OrderValue::get([
			'order_id' => $array[0],
			'store_id' => $array[1],
			'item_id' => $array[2]
		]);
		echo json_encode($res->fetch_assoc());
		break;
	case 'editOrderValue':
		$array = explode('-', $_POST['osi']);
		OrderValue::update(
			[
				'price' => $_POST['price'], 
				'quan' => $_POST['quan'],
				'comment' => $_POST['comment']
			],
			[
				'order_id' => $array[0],
				'store_id' => $array[1],
				'item_id' => $array[2]
			]
		);
		echo json_encode($_POST);
		break;
    case 'getItemsToOrder':
        $commonItemsToOrders = core\Provider::getCommonItemsToOrders();
		$countItemsToOrder = core\Provider::getCountItemsToOrders($commonItemsToOrders);
		if ($countItemsToOrder){
		    echo "
                <a style=\"margin-left: 15px;\" href=\"?view=providers&act=itemsToOrder\">
                    Товары ожидающие отправку в заказ
                    <strong style=\"color: red\">($countItemsToOrder)</strong>
                </a>
		    ";
		}
        break;
	default:
		core\OrderValue::changeStatus($_POST['status_id'], $_POST);
}

/*switch($_POST['status_id']){
	case 2://возврат
	case 3://пришло
	case 6://нет в наличии
	case 8://отменен
	case 11://заказано
		$nonSynchronizedOrders = core\Synchronization::getNoneSynchronizedOrders();
		core\Synchronization::sendRequest('orders/write_orders', $nonSynchronizedOrders);	
		break;
}*/

?>
