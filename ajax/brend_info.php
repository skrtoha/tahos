<?php  
require_once ("../core/Database.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$brend = $db->select('brends', '*', "`id`={$_POST['id']}");
echo json_encode($brend[0]);
?>
