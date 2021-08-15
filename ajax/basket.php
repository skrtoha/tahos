<?php  

require_once ("../core/DataBase.php");
session_start();

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$store_id = $_POST['store_id'];
$item_id = $_POST['item_id'];
$user_id = $_SESSION['user'];
// print_r($_POST);
switch ($_POST['act']){
	case 'delete':
		$res = $db->delete('basket', "
			`store_id`=$store_id AND
			`item_id`=$item_id AND
			`user_id`=$user_id
		");
		break;
	case 'plus':
	case 'minus':
		$act = $_POST['act'] == 'minus' ? '-' : '+';
		$res = $db->query("
			UPDATE
				#basket
			SET 
				`quan` = `quan` $act {$_POST['packaging']}
			WHERE
				`store_id`= $store_id AND
				`item_id` = $item_id AND
				`user_id`= $user_id
		");
		break;
	case 'clear':
		$res = $db->delete('basket', "`user_id`=$user_id");
		break;
	case 'computing':
		// print_r($_POST);
		$res = $db->query("
			UPDATE
				#basket
			SET 
				`quan` = {$_POST['value']}
			WHERE
				`store_id`=$store_id AND
				`item_id` = $item_id AND
				`user_id`=$user_id
		", '');
		break;
	case 'isToOrder':
	case 'noToOrder':
        foreach($_POST['items'] as $value) $db->update(
            'basket',
            ['isToOrder' => $_POST['act'] == 'isToOrder' ? 1 : 0],
            "
            `store_id` = {$value['store_id']} AND
            `item_id` = {$value['item_id']} AND
            `user_id` = $user_id
        ");
        break;
}
if ($res) echo true;
else echo false;
?>
