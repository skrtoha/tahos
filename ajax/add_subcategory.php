<?php 
require_once ("../core/Database.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

if ($db->insert('categories', array('title' => $_POST['title'], 'parent_id' => $_POST['category_id']))) echo "ok";
