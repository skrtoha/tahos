<?php  
require_once ("../core/Database.php");
session_start();

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$where = "`user_id`={$_SESSION['user']}";
if (!$_POST['filds']) $where .= " AND `item_id`= {$_POST['item_id']} AND `store_id`={$_POST['store_id']}";
$db->update('basket', ['comment' => $_POST['comment']], $where);
echo true;
?>
