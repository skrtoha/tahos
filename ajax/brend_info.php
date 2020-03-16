<?php  
require_once ("../core/DataBase.php");

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$brend = $db->select('brends', '*', "`id`={$_POST['id']}");
echo json_encode($brend[0]);
?>
