<?php 
require_once ("../core/Database.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$res = $db->insert('filters', array('title' => $_POST['title'], 'category_id' => $_POST['category_id']));
if ($res !== true) echo $db->error();
