<?
ini_set('error_reporting', E_PARSE | E_ERROR);

use core\Basket;
use core\Database;
use core\OrderValue;
use core\StoreItem;

require_once('../../core/Database.php');

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

        if ($post['pay_type'] == 'Наличный' || $post['pay_type'] == 'Онлайн') $tableColumn = 'reserved_cash';
        if ($post['pay_type'] == 'Безналичный') $tableColumn = 'reserved_cashless';

		$db->query("
			UPDATE
				#users
			SET
				`$tableColumn` = `$tableColumn` - {$_POST['current']} * {$_POST['price']}
			WHERE
				`id`={$post['user_id']}
		", '');
		break;
	case 'return_to_basket':
		foreach($post['data'] as $row){
            $order_id = $row['order_id'];
            Basket::addToBasket($row, false);
			$db->delete('orders_values', core\Provider::getWhere($row));
		}
        if (!$db->getCount('orders_values', "`order_id` = $order_id")){
            $db->delete('orders', "`id` = $order_id");
            echo 0;
            break;
        }
        echo 1;
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
		$output = OrderValue::get([
			'order_id' => $array[0],
			'store_id' => $array[1],
			'item_id' => $array[2]
		])->fetch_assoc();
        $output['stores'] = [];
        $query = StoreItem::getQueryStoreItem();
        $query .= " where si.item_id = {$array[2]} AND si.in_stock >= {$output['quan']} ORDER BY si.price";
        $result = Database::getInstance()->query($query);
        foreach($result as $row){
            $output['stores'][] = [
                'cipher' => $row['cipher'],
                'store_id' => $row['store_id'],
                'price' => $row['price'],
                'withoutMarkup' => $row['priceWithoutMarkup']
            ];
        }
		echo json_encode($output);
		break;
	case 'editOrderValue':
        $data = $_POST;
        $array = explode('-', $data['osi']);
		OrderValue::update(
			[
				'price' => $data['price'],
				'quan' => $data['quan'],
				'comment' => $data['comment'],
                'store_id' => $data['store_id'],
                'withoutMarkup' => $data['withoutMarkup']
			],
			[
				'order_id' => $array[0],
				'store_id' => $array[1],
				'item_id' => $array[2]
			]
		);
		echo json_encode($data);
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