<?
require_once('../core/Database.php');
require_once('../core/functions.php');

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$favorite_id = $_POST['favorite_id'];
if ($db->delete('favorites', "`id`=$favorite_id")) echo true;
else echo false;
?>
