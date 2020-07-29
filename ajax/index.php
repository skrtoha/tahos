<?php
require_once ($_SERVER['DOCUMENT_ROOT'] . "/core/DataBase.php");
// require_once ($_SERVER['DOsCUMENT_ROOT'] . "/templates/functions.php");

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

// print_r($_POST);
switch($_POST['act']){
	case 'get_brends':
		// print_r($_POST);
		$res_brends = $db->select_unique("
			SELECT
				fv.brend_id,
				b.title,
				b.href
			FROM
				#vehicle_filters fv
			LEFT JOIN
				#brends b ON b.id=fv.brend_id
			WHERE
				fv.vehicle_id={$_POST['vehicle_id']}
			GROUP BY fv.brend_id
			ORDER BY b.title
		", '');
		echo json_encode($res_brends);
		break;
	case 'get_years':
		// print_r($_POST);
		$res = $db->select_unique("
			SELECT
				fv.id,
				fv.title
			FROM
				#vehicle_filter_values fv
			LEFT JOIN
				#vehicle_filters f ON f.id=fv.filter_id
			WHERE
				f.title='Год' AND f.vehicle_id={$_POST['vehicle_id']} AND f.brend_id={$_POST['brend_id']}
			ORDER BY
				fv.title DESC
		", '');
		echo json_encode($res);
		break;
	case 'get_models':
		// print_r($_POST);
		$res = $db->select_unique("
			SELECT
				fvs.modification_id,
				mf.model_id,
				md.title,
				md.href
			FROM
				#vehicle_model_fvs fvs
			LEFT JOIN
				#modifications mf ON mf.id=fvs.modification_id
			LEFT JOIN
				#models md ON md.id=mf.model_id
			WHERE
				fvs.fv_id={$_POST['year_id']} AND md.is_removed=0
			GROUP BY
				mf.model_id
		", '');
		echo json_encode($res);
		break;
}
?>