<?
require_once('../core/DataBase.php');
require_once('../core/functions.php');

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$favorite_id = $_POST['favorite_id'];
if ($db->delete('favorites', "`id`=$favorite_id")) echo true;
else echo false;
?>
