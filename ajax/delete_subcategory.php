<?php 
require_once ("../core/Database.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

if ($db->delete('categories', 'id='.$_POST['category_id'])) echo "ok";
