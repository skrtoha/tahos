<?php  
require_once ("../core/database_class.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

session_start();
if ($db->delete('basket', '`user_id`='.$_SESSION['user'])) echo 'ok';?>
	