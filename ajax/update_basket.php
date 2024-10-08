<?php  
require_once ("../core/Database.php");
session_start();
require_once ("../core/functions.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

switch($_GET['act']){
	case 'update_price':
		$db->update(
			'basket', 
			['price' => $_GET['price']],
			"
				`user_id`={$_SESSION['user']} AND 
				`item_id`={$_GET['item_id']} AND 
				`store_id`={$_GET['store_id']}
			"
		);
        $db->update(
            'store_items',
            ['price' => $_GET['price']],
            "`store_id` = {$_GET['store_id']} and `item_id`={$_GET['item_id']}"
        );
		break;
}
header('Location: '.$_SERVER['HTTP_REFERER']);
?>