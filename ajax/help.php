<?php 
require_once ("../core/DataBase.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$text = $db->select_one("text_articles", '*', "`id`={$_POST['id']}");
echo json_encode($text);
?>