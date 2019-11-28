<?php  
require_once ("../class/database_class.php");
session_start();
require_once ("../core/functions.php");

$db = new DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

switch($_GET['act']){
	case 'update_price':
		$db->update(
			'basket', 
			['price' => $_GET['new_price']],
			"
				`user_id`={$_SESSION['user']} AND 
				`item_id`={$_GET['item_id']} AND 
				`provider_id`={$_GET['provider_id']}
			"
		);
		break;
	case 'update_quan':
		$db->update(
			'basket', 
			['quan' => $_GET['quan']],
			"
				`user_id`={$_SESSION['user']} AND 
				`item_id`={$_GET['item_id']} AND 
				`provider_id`={$_GET['provider_id']}
			"
		);
		break;
}
header('Location: '.$_SERVER['HTTP_REFERER']);
?>