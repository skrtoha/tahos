<?php 
require_once ("../../core/DataBase.php");

$db = new core\DataBase();
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
