<?php 
require_once ("../core/Database.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

if ($db->update('filters', array('title' => $_POST['title']), 'id='.$_POST['filter_id'])) echo "ok";
