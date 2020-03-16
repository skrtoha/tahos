<?php 
require_once ("../core/DataBase.php");

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$res = $db->update('filters_values', array('title' => $_POST['title']), 'id='.$_POST['filter_value_id']);
if ($res !== true) echo $db->error();
