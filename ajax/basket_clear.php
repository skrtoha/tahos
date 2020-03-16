<?php  
require_once ("../core/database_class.php");

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

session_start();
if ($db->delete('basket', '`user_id`='.$_SESSION['user'])) echo 'ok';?>
	