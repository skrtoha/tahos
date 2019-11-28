<?php 
require_once ("../class/database_class.php");

$db = new DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$text = $db->select_one("help_texts", '*', "`id`={$_POST['id']}");
echo json_encode($text);
?>