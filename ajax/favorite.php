<?
require_once('../core/DataBase.php');
require_once('../core/functions.php');

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

session_start();
$item_id = $_POST['item_id'];
$user_id = $_SESSION['user'];
$where = "`item_id`=$item_id AND `user_id`=$user_id";
switch($_POST['act']){
	case 'delete':
		$res = $db->delete('favorites', $where);
		break;
	case 'add':
		$res = $db->insert('favorites', ['item_id' => $item_id, 'user_id' => $user_id]);
		break;
	case 'remark':
		$res = $db->update('favorites', ['remark' => $_POST['remark']], $where);
		break;
	case 'clear':
		$res = $db->delete('favorites', "`user_id`=$user_id");
		break;
}
echo $res;
?>
