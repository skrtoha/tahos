<?php 
require_once ("../core/DataBase.php");

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

if ($db->update('filters', array('title' => $_POST['title']), 'id='.$_POST['filter_id'])) echo "ok";
