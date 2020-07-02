<?php
require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/DataBase.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/templates/functions.php");

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

switch($_POST['act']){
	case 'get_filters':
		$res_filters = $db->query("
			SELECT
				f.title AS filter_title,
				f.id AS filter_id,
				fv.id AS value_id,
				fv.title AS value_title,
				CAST(fv.title AS UNSIGNED) AS value_title_2
			FROM
				#filters f
			LEFT JOIN #filters_values fv ON fv.filter_id=f.id
			WHERE
				f.category_id={$_POST['category_id']}
			ORDER BY f.pos, value_title_2, fv.title
		", '');
		if ($res_filters->num_rows) while($r = $res_filters->fetch_assoc()){
			$t_filters[$r['filter_id']]['title'] = $r['filter_title'];
			$t_filters[$r['filter_id']]['filter_values'][$r['value_id']] = $r['value_title'];
		}
		$i = 0;
		foreach($t_filters as $key => $value){
			$filters[$i]['id'] = $key;
			$filters[$i]['title'] = $value['title'];
			foreach($value['filter_values'] as $k => $v){
				$filters[$i]['filter_values'][] = [
					'id' => $k,
					'title' => $v
				];
			}
			$i++;
		}
		// print_r($filters);
		echo json_encode($filters);
		break;
	case 'apply_filter':
		// print_r($_POST); exit();
		$db->delete(
			'items_values', 
			"
				`item_id`={$_POST['item_id']} AND 
				`category_id`={$_POST['category_id']}
			"
		);
		foreach($_POST as $key => $value){
			if (!is_numeric($key) || !$value) continue;
			$db->insert(
				'items_values',
				[
					'item_id' => $_POST['item_id'],
					'category_id' => $_POST['category_id'],
					'value_id' => $value
				]
			);
		}
		break;
	case 'category_delete':
		$db->delete(
			'categories_items',
			"`item_id`={$_POST['item_id']} AND `category_id`={$_POST['category_id']}"
		);
		break;
	case 'analogy_hide':
		print_r($_POST);
		$db->update(
			'analogies', 
			['hidden' => $_POST['checked']], 
			"
				(`item_id`={$_POST['item_id']} AND `item_diff`={$_POST['value']}) OR
				(`item_id`={$_POST['value']} AND `item_diff`={$_POST['item_id']})
			"
		);
		break;
	case 'savePhoto':
		// debug($_POST);
		// debug($_FILES);
		$name = time();
		$pathBig = "/tmp/big_$name.jpg";
		$pathSmall = "/tmp/small_$name.jpg";
		copy($_FILES['croppedImage']['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . "$pathSmall");

		copy($_SERVER['DOCUMENT_ROOT'] . $_POST['initial'], $_SERVER['DOCUMENT_ROOT'] . "$pathBig");
		echo json_encode([
			'small' => $pathSmall,
			'big' => $pathBig
		]);
		break;
	case 'applyCategory':
		$db->insert('categories_items', ['item_id' => $_POST['item_id'], 'category_id' => $_POST['category_id']]);
		break;
}
?>