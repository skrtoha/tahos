<?
require_once('../core/DataBase.php');
require_once('../core/functions.php');

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$user_id = $_POST['user_id'];
if ($db->delete('favorites', "`user_id`=$user_id")) echo true;
else echo false;
?>
