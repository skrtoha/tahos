<?
require_once('../class/database_class.php');
require_once('../core/functions.php');

$db = new DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

session_start();
$item_id = $_POST['item_id'];
$remark = $_POST['remark'];
if ($db->update('favorites', ['remark' => $remark], "`item_id`=$item_id AND `user_id`={$_SESSION['user']}")) echo true;
else echo false;
?>
