<?php 
require_once ("../../core/DataBase.php");

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

switch($_POST['act']){
	case 'item_search':
		// print_r($_POST);
		$items = array();
		$search = preg_replace('/[\W_]+/', '', $_POST['search']);
		if ($_POST['exact_match']) $where = "i.article='{$search}' OR ib.barcode='{$search}'";
		else $where = "i.article REGEXP '{$search}' OR ib.barcode REGEXP '{$search}'";
		$res = $db->query("
			SELECT
				i.id,
				i.title_full,
				i.article,
				ib.barcode,
				i.brend_id,
				b.title AS brend,
				si.store_id,
				FLOOR(si.price * c.rate) AS price,
				si.in_stock,
				si.packaging,
				p.title AS provider,
				ps.title AS provider_store
			FROM
				#items i
			LEFT JOIN
				#brends b ON b.id=i.brend_id
			LEFT JOIN
				#item_barcodes ib ON ib.item_id = i.id
			LEFT JOIN
				#store_items si ON si.item_id=i.id
			LEFT JOIN 
				#provider_stores ps ON ps.id=si.store_id
			LEFT JOIN
				#providers p ON p.id=ps.provider_id
			LEFT JOIN
				#currencies c ON c.id=ps.currency_id
			WHERE
				$where
		", '');
		if ($res->num_rows == 0) exit();
		/*if ($res->num_rows > 50){
			echo $res->num_rows;
			exit();
		}*/
		while($row = $res->fetch_assoc()){
			$i = & $items[$row['id']];
			$i['title_full'] = $row['title_full'];
			$i['article'] = $row['article'];
			$i['barcode'] = $row['barcode'];
			$i['brend_id'] = $row['brend_id'];
			$i['brend'] = $row['brend'];
			if ($row['store_id']){
				$i['stores'][$row['store_id']]['title'] = $row['provider_store'];
				$i['stores'][$row['store_id']]['price'] = $row['price'];
				$i['stores'][$row['store_id']]['in_stock'] = $row['in_stock'];
				$i['stores'][$row['store_id']]['packaging'] = $row['packaging'];
			}
		}
		echo json_encode($items);
		break;
	case 'to_order':
		$order = json_decode($_POST['order'], true);
		$res_order_insert = $db->insert(
			'orders',
			['user_id' => $_POST['user_id']]
		);
		// print_r($order);
		// print_r($_POST);
		// exit();
		if ($res_order_insert === true){
			$order_id = $db->last_id();
			foreach($order as $value){
				$db->insert(
					'orders_values',
					[
						'user_id' => $_POST['user_id'],
						'order_id' => $order_id,
						'store_id' => $value['store_id'] ? $value['store_id'] : 0,
						'item_id' => $value['item_id'],
						'price' => $value['price'],
						'quan' => $value['quan'],
						'comment' => $value['comment']
					]
					// ,['print_query' => 1]
				);
			}
			echo true;
		}
		else echo $res_order_insert;
		break;
	case 'wrongAnalogy':
		$res = $db->query("
			SELECT
				i.id,
				i.article,
				i.title_full,
				b.title AS brend
			FROM
				#items i
			LEFT JOIN
				#brends b ON b.id=i.brend_id
			WHERE
				i.id IN ({$_POST['item_id']}, {$_POST['item_diff']})
		", '');
		while ($row = $res->fetch_assoc()){
			if ($row['id'] == $_POST['item_id']) $item_id = $row;
			if ($row['id'] == $_POST['item_diff']) $item_diff = $row; 
		}
		$user = $db->select_one('users', 'id,name_1,name_2,name_3', "`id`={$_POST['user_id']}");
		// print_r($item_id);
		// print_r($item_diff);
		$text = "
			у <a target='_blank' href='/admin/?view=items&act=item&id={$item_id['id']}'>{$item_id['brend']} - {$item_id['article']} - {$item_id['title_full']}</a> 
			неправильный аналог
			<a target='_blank' href='/admin/?view=items&act=item&id={$item_diff['id']}'>{$item_diff['brend']} - {$item_diff['article']} - {$item_diff['title_full']}</a> 
		";
		$db->insert('log_diff', [
			'type' => 'wrongAnalogy',
			'from' => '<a target="_blank" href="/admin/?view=users&act=change&id='.$_POST['user_id'].'">'.$user['name_1'].' '.$user['name_2'].' '.$user['name_3'].'</a>',
			'text' => $text,
			'param1' => $_POST['item_id'],
			'param2' => $_POST['item_diff']
		], ['print_query' => false]);
		break;
	case 'applyUserMarkup':
		$db->update(
			'users', 
			['markupSettings' => !empty($_POST['params']) ? json_encode($_POST['params']) : ''], 
			"`id` = {$_POST['user_id']}"
		);
		break;
	case 'subscribeTahosPrice':
		$res_store_items = core\StoreItem::getStoreItemsByStoreID([core\Provider\Tahos::$store_id]);
		$file = core\Provider\Tahos::processExcelFileForSubscribePrices($res_store_items, 'price_tahos');

		$res = core\Mailer::send([
			'emails' => [$_POST['email']],
			'subject' => 'Прайс с tahos.ru',
			'body' => 'Прайс с tahos.ru'
		], [$file]);
		if ($res !== true) die($res);
		echo true;
		break;
	case 'subscribeMainStoresPrice':
		if ($_POST['isFormNew'] == 'true'){
			$mainStores = json_decode(core\Setting::get('settings', 'storesForSubscribe'));
			$res_store_items = core\StoreItem::getStoreItemsByStoreID($mainStores);
			$file = core\Provider\Tahos::processExcelFileForSubscribePrices($res_store_items, 'main_stores');
		}
		else $file = $_SERVER['DOCUMENT_ROOT'] . "/tmp/main_stores.xlsx";

		$res = core\Mailer::send([
			'emails' => [$_POST['email']],
			'subject' => 'Прайс с tahos.ru',
			'body' => 'Прайс с tahos.ru'
		], [$file]);
		if ($res !== true) die($res);
		echo (true);
		break;
	case 'return_money':
		$res_user = core\User::get(['user_id' => $_POST['user_id']]);
		$user = $res_user->fetch_assoc();
		core\Fund::insert(1, [
			'sum' => $_POST['amount'],
			'remainder' => $user['bill'] + $_POST['amount'],
			'user_id' => $_POST['user_id'],
			'comment' => 'Возврат средств'
		]);
		core\User::update(
			$_POST['user_id'],
			['bill' => "`bill` + ".$_POST['amount']]
		);
		break;
}

?>
