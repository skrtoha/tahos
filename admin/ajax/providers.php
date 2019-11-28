<?require_once ("../../class/database_class.php");
require_once ("../../templates/functions.php");

$db = new DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

switch($_POST['act']){
	case 'get_store':
		$store = $db->select_one('provider_stores', '*', "`id`={$_POST['store_id']}");
		echo json_encode($store);
		break;
	case 'store_change';
		$array = [
			'title' => $_POST['title'],
			'cipher' => strtoupper($_POST['cipher']),
			'city' => $_POST['city'],
			'currency_id' => $_POST['currency_id'],
			'percent' => $_POST['percent'],
			'provider_id' => $_POST['provider_id'],
			'delivery' => $_POST['delivery'],
			'delivery_max' => $_POST['delivery_max'],
			'under_order' => $_POST['under_order'],
			'prevail' => $_POST['prevail'] ? 1 : 0,
			'noReturn' => $_POST['noReturn'] ? 1 : 0
		];
		if ($_POST['store_id']) $res = $db->update(
			'provider_stores',
			$array,
			"`id`={$_POST['store_id']}"
		);
		else{
			$res = $db->insert('provider_stores', $array);
			if ($res === true) $res = $db->last_id();
		} 
		echo $res;
		break;
	case 'store_delete':
		$db->delete('provider_stores', "`id`={$_POST['id']}");
		break;
	case 'get_currencies':
		$currencies = $db->select('currencies', 'id,title');
		echo json_encode($currencies);
		break;
}