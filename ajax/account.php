<?
session_start();
require_once('../core/DataBase.php');
require_once('../core/functions.php');
require_once('../admin/functions/order_issues.function.php');

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

switch($_POST['act']){
	case 'getDebtList':
        $params = [];
        $params['user_id'] = $_SESSION['user'];
        if (isset($_POST['begin'])){
            $dateTime = DateTime::createFromFormat('d.m.Y', $_POST['begin']);
            $params['begin'] = $dateTime->format('Y-m-d 00:00:00');
        }
        if (isset($_POST['end'])){
            $dateTime = DateTime::createFromFormat('d.m.Y', $_POST['end']);
            $params['end'] = $dateTime->format('Y-m-d 23:59:59');
        }
        $res = \core\User::getDebtList($params);
        $res = json_encode($res);
		break;
    case 'getOrderIssueInfo':
        $issuesClass = new Issues(null, $db);
        $res = json_encode($issuesClass->getIssueWithUser($_POST['issue_id']));
        break;

}
echo $res;
?>
