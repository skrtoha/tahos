<?php 
require_once ("../class/database_class.php");

$db = new DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$res = $db->insert('filters', array('title' => $_POST['title'], 'category_id' => $_POST['category_id']));
if ($res !== true) echo $db->error();
