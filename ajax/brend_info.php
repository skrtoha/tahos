<?php  
require_once ("../class/database_class.php");

$db = new DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$brend = $db->select('brends', '*', "`id`={$_POST['id']}");
echo json_encode($brend[0]);
?>
