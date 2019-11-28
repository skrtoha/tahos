<?php  
require_once ("../class/database_class.php");
session_start();

$db = new DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$where = "`user_id`={$_SESSION['user']}";
print_r($_POST);
if (!$_POST['filds']) $where .= " AND `item_id`= {$_POST['item_id']} AND `store_id`={$_POST['store_id']}";
$db->update('basket', ['comment' => $_POST['comment']], $where);
echo true;
?>
