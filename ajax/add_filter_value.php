<?php 
require_once ("../core/DataBase.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$res = $db->insert('filters_values', array('title' => $_POST['title'], 'filter_id' => $_POST['filter_id']));
if ($res !== true) echo $db->error();
