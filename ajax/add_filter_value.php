<?php 
require_once ("../class/database_class.php");

$db = new DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$res = $db->insert('filters_values', array('title' => $_POST['title'], 'filter_id' => $_POST['filter_id']));
if ($res !== true) echo $db->error();
