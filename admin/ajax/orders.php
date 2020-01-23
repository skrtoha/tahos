<?php
require_once('../../class/database_class.php');
require_once("{$_SERVER['DOCUMENT_ROOT']}/admin/functions/orders.function.php");


$db = new DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$where = "
	`order_id`={$_POST['order_id']} AND
	`store_id`={$_POST['store_id']} AND
	`item_id`={$_POST['item_id']}
";
$user = $db->select_one('users', 'id,bonus_program,bonus_count', "`id`={$_POST['user_id']}");
$settings = $db->select_one('settings', '*', '`id`=1');
// print_r($settings);
// print_r($_POST);
// exit();
$item = $db->select_unique("
	SELECT
		i.article,
		i.title,
		b.title as brend
	FROM
		#items i
	LEFT JOIN
		#brends b ON b.id=i.brend_id
	WHERE
		i.id={$_POST['item_id']}
", '');
$item = $item[0];
$title = '<b style="font-weight: 700">'.$item['brend'].'</b> 
			<a href="'.$_SERVER['HTTP_HOST'].'/search/article/'.$item['article'].'" class="articul">'.
				$item['article'].'</a> '.$item['title_full'];
switch($_POST['status_id']){
	case 1://выдано
		$res_1 = $db->query("
			UPDATE 
				#orders_values 
			SET 
				`issued` = `issued` + {$_POST['arrived']},
				`status_id` = 1
			WHERE $where
		", '');
		$res_2 = $db->insert(
			'funds',
			[
				'type_operation' => 2,
				'sum' => $_POST['price'] * $_POST['arrived'],
				'remainder' => $_POST['bill'] - $_POST['price'] * $_POST['arrived'],
				'user_id' => $_POST['user_id'],
				'comment' => addslashes('Списание средств на оплату "'.$title.'"')
			],
			['print_query' => false]
		);
		if ($user['bonus_program']){
			$bonus_size = $db->getField('settings', 'bonus_size', 'id', 1);
			$bonus_count = floor($_POST['price'] * $_POST['arrived'] * $bonus_size / 100);
			$res_4 = $db->insert(
				'funds',
				[
					'type_operation' => 3,
					'sum' => $bonus_count,
					'remainder' => $user['bonus_count'] + $bonus_count,
					'user_id' => $_POST['user_id'],
					'comment' => addslashes('Начисление бонусов за заказ "'.$title.'"'),
				],
				['print_query' => false]
			);
			// exit();
		}
		$res_3 = $db->query("
			UPDATE
				#users
			SET
				`reserved_funds`=`reserved_funds` - {$_POST['price']} * {$_POST['arrived']},
				`bill`=`bill` - {$_POST['price']} * {$_POST['arrived']}
			WHERE
				`id`={$_POST['user_id']}
		", '');
		break;
	case 2://возврат
		// print_r($_POST); exit();
		if ($_POST['new_returned'] + $_POST['returned'] < $_POST['issued']) $status_id = 1;
		else $status_id = 2;
		$db->query("
			UPDATE 
				#orders_values 
			SET 
				`returned` = {$_POST['returned']} + {$_POST['new_returned']},
				`status_id` = $status_id
			WHERE $where
		", '');
		$db->query("
			UPDATE
				#store_items 
			SET
				in_stock = in_stock + {$_POST['ordered']}
			WHERE
				store_id = {$_POST['store_id']} AND
				item_id = {$_POST['item_id']}
		", '');
		$db->insert(
			'funds',
			[
				'type_operation' => 1,
				'sum' => $_POST['price'] * $_POST['new_returned'],
				'remainder' => $_POST['bill'] + $_POST['price'] * $_POST['new_returned'],
				'user_id' => $_POST['user_id'],
				'comment' => addslashes('Возврат средств за "'.$title.'"')
			],
			['print_query' => false]
		);
		if ($user['bonus_program']){
			$bonus_return = round($_POST['price'] * $_POST['new_returned'] * $settings['bonus_size'] / 100);
			$set = ", `bonus_count`=`bonus_count` - $bonus_return";
			$db->insert(
				'funds',
				[
					'type_operation' => 4,
					'sum' => $bonus_return,
					'remainder' => $user['bonus_count'] - $bonus_return,
					'user_id' => $_POST['user_id'],
					'comment' => addslashes('Списание бонусов за возврат "'.$title.'"'),
					'transfered' => 1
				],
				['print_query' => false]
			);
			// $db->query("
			// 	UPDATE
			// 		#users
			// 	SET
			// 		`bonus_count` = `bonus_count`- $bonus_return
			// 	WHERE
			// 		`user_id` = {$_POST['user_id']}
			// ");
		} 
		$res_3 = $db->query("
			UPDATE
				#users
			SET
				`bill`=`bill` + {$_POST['price']} * {$_POST['new_returned']}
				$set
			WHERE
				`id`={$_POST['user_id']}
		", '');
		$res = core\Mailer::send([
			'email' => $db->getFieldOnID('users', $_POST['user_id'], 'email'),
			'subject' => 'Возврат средств',
			'body' => "У вас возврат средств ".$_POST['price'] * $_POST['new_returned']." руб. за $title"
		]);
		if ($res !== true) die($res);
		break;
	case 3://пришло
		$db->query("
			UPDATE 
				#orders_values 
			SET 
				`arrived` = {$_POST['arrived']},
				`status_id`= 3
			WHERE $where
		", '');
		if (isOrderReady($_POST['order_id'])){
			$res = core\Mailer::send([
				'email' => $db->getFieldOnID('users', $_POST['user_id'], 'email'),
				'subject' => 'Ваш заказ готов к отгрузке',
				'body' => file_get_contents("http://{$_SERVER['HTTP_HOST']}/admin/?view=orders&act=print&id={$_POST['order_id']}")
			]);
		}
		break;
	case 6://нет в наличии
		$db->query("
			UPDATE 
				#orders_values 
			SET 
				`status_id`= 6
			WHERE $where
		", '');
		$db->delete('store_items', "store_id = {$_POST['store_id']} AND item_id = {$_POST['item_id']}");
		$res = core\Mailer::send([
			'email' => $db->getFieldOnID('users', $_POST['user_id'], 'email'),
			'subject' => 'Товара нет в наличии',
			'body' => "Сообщаем вам, что в заказазе №{$_POST['order_id']} товара $title нет в наличии"
		]);
		break;
	case 8://отменен
		$db->query("
			UPDATE
				#users
			SET
				`reserved_funds`=`reserved_funds` - {$_POST['price']} * {$_POST['ordered']}
			WHERE
				`id`={$_POST['user_id']}
		", '');
		$db->query("
			UPDATE 
				#orders_values 
			SET 
				`status_id`= 8
			WHERE $where
		", '');
		$db->query("
			UPDATE
				#store_items 
			SET
				in_stock = in_stock + {$_POST['ordered']}
			WHERE
				store_id = {$_POST['store_id']} AND
				item_id = {$_POST['item_id']}
		", '');
		break;
	case 11://заказано
		$db->query("
			UPDATE 
				#orders_values 
			SET 
				`status_id`=11,
				`ordered` = {$_POST['ordered']}
			WHERE $where
		", '');
		$db->query("
			UPDATE
				#users
			SET
				`reserved_funds`=`reserved_funds` + {$_POST['price']} * {$_POST['ordered']}
			WHERE
				`id`={$_POST['user_id']}
		", '');
		$db->query("
			UPDATE
				#store_items 
			SET
				in_stock = in_stock - {$_POST['ordered']}
			WHERE
				store_id = {$_POST['store_id']} AND
				item_id = {$_POST['item_id']}
		", '');
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
				'comment' => addslashes('Списание средств на оплату "'.$title.'"')
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
	default:
		$db->query("
			UPDATE 
				#orders_values 
			SET 
				`status_id`= {$_POST['status_id']}
			WHERE $where
		", '');
}

?>