<?php 
require_once ("../core/DataBase.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$name = $_POST['name'];
if (unlink(core\Config::$imgPath . "/temp/$name")) echo true;
else echo false;
?>
