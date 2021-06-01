<?php 
require_once ("../core/DataBase.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

// print_r($_POST);
switch($_POST['act']){
	case 'item_search':
		$items = array();
		if ($_POST['exact_match']) $where = "i.article='{$_POST['user_item_search']}' OR ib.barcode='{$_POST['user_item_search']}'";
		else $where = "i.article REGEXP '{$_POST['user_item_search']}' OR ib.barcode REGEXP '{$_POST['user_item_search']}'";
		$res = $db->query("
			SELECT
				i.id,
				i.title_full,
				i.article,
				ib.barcode,
				i.brend_id,
				b.title AS brend
			FROM
				#items i
			LEFT JOIN
				#brends b ON b.id=i.brend_id
			LEFT JOIN
				#item_barcodes ib ON ib.item_id = i.id
			WHERE
				$where
		", '');
		if ($res->num_rows == 0) exit();
		if ($res->num_rows > 10){
			echo $res->num_rows;
			exit();
		}
		while($row = $res->fetch_assoc()){
			$items[$row['id']]['title_full'] = $row['title_full'];
			$items[$row['id']]['article'] = $row['article'];
			$items[$row['id']]['barcode'] = $row['barcode'];
			$items[$row['id']]['brend_id'] = $row['brend_id'];
			$items[$row['id']]['brend'] = $row['brend'];
		}
		echo json_encode($items);
		break;
	case 'stores_search':
		$output = array();
		$res = $db->query("
			SELECT
				i.id AS item_id,
				i.title_full,
				i.article,
				ib.barcode,
				b.title AS brend,
				ps.id AS store_id,
				ps.cipher AS cipher,
				ps.delivery,
				FLOOR(c.rate * si.price) AS price
			FROM
				#store_items si
			LEFT JOIN
				#items i ON i.id=si.item_id
			LEFT JOIN
				#item_barcodes ib ON ib.item_id = i.id
			LEFT JOIN
				#provider_stores ps ON ps.id=si.store_id
			LEFT JOIN
				#currencies c ON c.id=ps.currency_id 
			LEFT JOIN
				#brends b ON b.id=i.brend_id
			WHERE
				si.item_id={$_POST['item_id']}
		", '');
		if (!$res->num_rows) break;
		while($row = $res->fetch_assoc()){
			$o = & $output;
			$o['id'] = $row['item_id'];
			$o['title_full'] = $row['title_full'];
			$o['article'] = $row['article'];
			$o['barcode'] = $row['barcode'];
			$o['brend'] = $row['brend'];
			$o['stores'][] = [
				'id' => $row['store_id'],
				'cipher' => $row['cipher'],
				'price' => $row['price'],
				'delivery' => $row['delivery']
			];
		}
		echo json_encode($output);
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
}

?>
