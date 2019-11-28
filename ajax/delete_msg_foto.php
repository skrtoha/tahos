<?php 
require_once ("../class/database_class.php");

$db = new DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$name = $_POST['name'];
if (unlink("../images/temp/$name")) echo true;
else echo false;
?>
