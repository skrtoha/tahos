<?require_once ("../core/DataBase.php");
session_start();

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

switch($_POST['act']){
	case 'get_brends':
		$brends = $db->select_unique("
			SELECT
				DISTINCT f.brend_id,
				b.title,
				b.href
			FROM
				#vehicle_filters f
			LEFT JOIN
				#brends b ON b.id=f.brend_id
			LEFT JOIN
				#vehicles v ON v.id=f.vehicle_id
			WHERE
				v.href='{$_POST['vehicle']}'
			ORDER BY
				b.title
		", '');
		echo json_encode($brends);
		break;
	case 'get_models_and_years':
		// print_r($_POST);
		$models = $db->select_unique("
			SELECT
				m.id,
				m.title,
				m.href
			FROM
				#models m
			LEFT JOIN
				#vehicles v ON v.id=m.vehicle_id
			LEFT JOIN
				#brends b ON b.id=m.brend_id
			WHERE
				v.href='{$_POST['vehicle']}' AND
				b.href='{$_POST['brend']}'
			ORDER BY
				m.title
		", '');
		$years = $db->select_unique("
			SELECT
				fv.id,
				fv.title
			FROM
				#vehicle_filter_values fv
			LEFT JOIN
				#vehicle_filters f ON fv.filter_id=f.id 
			LEFT JOIN
				#vehicles v ON v.id=f.vehicle_id
			LEFT JOIN
				#brends b ON b.id=f.brend_id
			WHERE
				f.title='Год' AND v.href='{$_POST['vehicle']}' AND b.href='{$_POST['brend']}'
		", '');
		if (empty($models) || empty($years)) break;
		$array = [
			'models' => $models,
			'years' => $years
		];
		echo json_encode($array);
		break;
	case 'get_models':
		// print_r($_POST);
		$array = $db->select_unique("
			SELECT
				mf.model_id,
				md.title,
				md.href
			FROM
				#vehicle_model_fvs fvs
			LEFT JOIN
				#modifications mf ON fvs.modification_id=mf.id
			LEFT JOIN
				#models md ON md.id=mf.model_id
			LEFT JOIN
				#vehicle_filter_values fv ON fv.id=fvs.fv_id
			LEFT JOIN
				#vehicle_filters f ON f.id=fv.filter_id
			LEFT JOIN
				#vehicles v ON v.id=md.vehicle_id
			LEFT JOIN
				#brends b ON b.id=md.brend_id
			WHERE
				fv.title='{$_POST['year']}' AND
				f.title='Год' AND
				v.href='{$_POST['vehicle']}' AND
				b.href='{$_POST['brend']}'
			GROUP BY
				mf.model_id
			ORDER BY
				md.title
		", '');
		echo json_encode($array);
		break;
	case 'modification_delete':
		$res = $db->update(
			'garage',
			['is_active' => 0],
			"`modification_id`='{$_POST['modification_id']}' AND `user_id`={$_SESSION['user']}"
		);
		if ($res !== true) break;
		$modifications = get_modifications();
		echo json_encode($modifications[0]);
		break;
	case 'modification_restore':
		$res = $db->update(
			'garage',
			['is_active' => 1],
			"`modification_id`='{$_POST['modification_id']}' AND `user_id`={$_SESSION['user']}"
		);
		if ($res !== true) break;
		$modifications = get_modifications();
		echo json_encode($modifications[0]);
		break;
	case 'modification_delete_fully':
		$res = $db->delete('garage', "`user_id`={$_SESSION['user']} AND `modification_id`='{$_POST['modification_id']}'");
		if ($res !== true) break;
		echo true;
		break;
	case 'change_garage':
		// print_r($_POST);
		$array = [];
		if (isset($_POST['modification_title'])) $array['title'] = $_POST['modification_title'];
		if (isset($_POST['comment'])) $array['comment'] = $_POST['comment'];
		$res = $db->update(
			"garage",
			$array,
			"`user_id`={$_SESSION['user']} AND `modification_id`='{$_POST['modification_id']}'"
		);
		if ($res === true) echo true;
		else echo $res;
		break;
	case 'from_modification_from_garage':
		$res = $db->delete('garage', "`user_id`={$_POST['user_id']} AND `modification_id`={$_POST['modification_id']}");
		if ($res === true) echo 1;
		break;
	case 'from_modification_to_garage':
		$db->delete('garage', "`user_id`={$_POST['user_id']} AND `modification_id`={$_POST['modification_id']}");
		$db->insert('garage', ['user_id' => $_POST['user_id'], 'modification_id' => $_POST['modification_id']]/*, ['print' => true]*/);
		break;
	case 'request_delete_item':
		$db->insert('user_request_delete_item', ['user_id' => $_POST['user_id'], 'item_id' => $_POST['item_id']]);
		break;
}
function get_modifications(){
	global $db;
	return $db->select_unique("
			SELECT
				mf.id AS modification_id,
				g.title AS title_garage,
				mf.model_id,
				md.title AS title_modification,
				md.vin,
				fv.title AS year
			FROM
				#modifications mf
			LEFT JOIN
				#garage g ON g.modification_id=mf.id AND g.user_id={$_SESSION['user']}
			LEFT JOIN
				#models md ON md.id=mf.model_id
			LEFT JOIN
				#vehicle_model_fvs fvs ON fvs.modification_id=mf.id
			LEFT JOIN
				#vehicle_filter_values fv ON fv.id=fvs.fv_id
			LEFT JOIN
				#vehicle_filters f ON f.id=fv.filter_id 
			WHERE
				mf.id='{$_POST['modification_id']}' AND f.title='Год'
		", '');
}
?> 
