<?php
/** @var $result mysqli_result */
require_once ("../../core/DataBase.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$value = $_POST['value'];
$where = "`store_id`={$_POST['store_id']} AND `item_id`={$_POST['item_id']}";
switch ($_POST['column']) {
	case 'price':
	case 'in_stock':
	case 'delivery':
	case 'packaging':
		$res = $db->update('store_items', array($_POST['column'] => $value), $where);
		break;
	case 'requiredRemain':
		$res = $db->insert('required_remains', ['item_id' => $_POST['item_id'], 'requiredRemain' => $value], [
			'duplicate' => [
				'requiredRemain' => $value
			]/*,
			'print' => true*/
		]);
		break;
	case 'add_item_to_store':
		$db->insert(
			'store_items',
			[
				'store_id' => $_POST['store_id'],
				'item_id' => $_POST['item_id'],
				'price' => $_POST['price'],
				'in_stock' => $_POST['in_stock'],
				'packaging' => $_POST['packaging']
			],
			['duplicate' => [
				'price' => $_POST['price'],
				'in_stock' => $_POST['in_stock'],
				'packaging' => $_POST['packaging']
			]]
		);

		if (isset($_POST['requiredRemain'])){
			$db->insert(
				'required_remains', 
				[
					'item_id' => $_POST['item_id'], 
					'requiredRemain' => $_POST['requiredRemain']
				],
				['duplicate' => [
					'requiredRemain' => $_POST['requiredRemain']
				]]
			);
		}

        if ($_POST['is_main']){
            $db->delete(
                'main_store_item',
                "`item_id` = {$_POST['item_id']} AND `store_id` = {$_POST['main_store_id']}"
            );
            $db->insert('main_store_item', [
                'item_id' => $_POST['item_id'],
                'store_id' => $_POST['main_store_id']
            ], ['duplicate' => [
                'store_id' => $_POST['main_store_id']
            ]]);
        }
		break;
	case 'getStoreItemsByItemID':
        $result = \core\User::get(['id' => $_POST['user_id']]);
        $userInfo = $result->fetch_assoc();
		$query = core\StoreItem::getQueryStoreItem($userInfo['discount']);
		$query .= "
			WHERE si.item_id = {$_POST['item_id']}
		";
		$res = $db->query($query, '');
		if (!$res->num_rows){
			$query = core\Item::getQueryItemInfo();
			$query .= "
				WHERE i.id = {$_POST['item_id']}
			";
			$res = $db->query($query, '');
			echo json_encode($res->fetch_assoc());
			break;
		} 
		$item = [];
		foreach($res as $row){
			$item['item_id'] = $row['item_id'];
			$item['brend'] = $row['brend'];
			$item['article'] = $row['article'];
			$item['title_full'] = $row['title_full'];
			$item['stores'][$row['store_id']]['price'] = $row['price'];
			$item['stores'][$row['store_id']]['packaging'] = $row['packaging'];
			$item['stores'][$row['store_id']]['in_stock'] = $row['in_stock'];
			$item['stores'][$row['store_id']]['cipher'] = $row['cipher'];
			$item['stores'][$row['store_id']]['provider'] = $row['provider'];
		}
		echo json_encode($item);
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
