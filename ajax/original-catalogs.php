<?php
require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/DataBase.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/functions.php");

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

// print_r($_POST); exit();
switch($_POST['act']){
	case 'apply_filter':
		$a = explode(',', $_POST['filter_values']);
		$cnt = count($a);
		$filters = explode(';', $_POST['filters']);
		$where = '';
		if ($_POST['search']) $where .= "m.title LIKE '%{$_POST['search']}%' AND ";
		if ($_POST['filter_values']) $where .= "fv.fv_id IN ({$_POST['filter_values']}) AND ";
		if ($where) $where = substr($where, 0, -4);
		else break;
		$res_modifications = $db->query("
			SELECT
				fv.modification_id,
				m.title,
				fv.fv_id,
				COUNT(*) AS cnt
			FROM
				#vehicle_model_fvs fv
			LEFT JOIN #modifications m ON m.id=fv.modification_id 
			WHERE
				$where AND m.model_id={$_POST['model_id']}
			GROUP BY fv.modification_id
			HAVING cnt>=$cnt
		", '');
		if (!$res_modifications->num_rows) break;
		while ($row = $res_modifications->fetch_assoc()){
				// debug($row);
				$m = & $modifications[$row['modification_id']];
				$m['title'] = $row['title'];
				$res_fvs = $db->query("
					SELECT
						fvs.fv_id,
						f.title AS filter,
						fv.title AS filter_value
					FROM 
						#vehicle_model_fvs fvs
					LEFT JOIN #vehicle_filter_values fv ON fv.id=fvs.fv_id
					LEFT JOIN #vehicle_filters f ON f.id=fv.filter_id
					WHERE
						fvs.modification_id={$row['modification_id']}
				", '');
				if ($res_fvs->num_rows) while($row = $res_fvs->fetch_assoc()) $fvs[$row['filter']] = $row['filter_value'];
				// print_r($fvs);
				foreach($filters as $v) $m['filter_values'][$v] = $fvs[$v];
			}
		// print_r($modifications);
		echo json_encode($modifications);
		break;
	case 'parent_nodes':
		// print_r($_POST);
		$res_nodes = $db->query("
			SELECT
				n.id,
				n.title
			FROM
				#nodes n
			WHERE
				n.id IN ({$_POST['childs']}) AND 
				n.subgroups_exist!=1
		", '');
		while($row = $res_nodes->fetch_assoc()){
			$path = "{$_SERVER['DOCUMENT_ROOT']}/images/nodes/small/{$_POST['brend']}/{$row['id']}.jpg";
			$nodes[] = [
				'id' => $row['id'],
				'title' => $row['title'],
				'is_img' => file_exists($path) ? 1 : 0
			];
		}
		echo json_encode($nodes);
		break;
	case 'vehicle_add':
		// print_r($_POST); exit();
		$href = translite($_POST['title']);
		$href = preg_replace('/[\W\s]+/', '-', $href);
		$db->insert(
			'vehicles',
			[
				'title' => $_POST['title'], 
				'category_id' => $_POST['category_id'], 
				'pos' => $_POST['pos'],
				'href' => $href,
				'is_mosaic' => $_POST['is_mosaic']
			]
		);
		echo json_encode([
			'id' => $db->last_id(),
			'title' => $_POST['title'],
			'category' => $db->getFieldOnID('vehicle_categories', $_POST['category_id'], 'title'),
			'pos' => $_POST['pos'],
			'is_mosaic' => $_POST['is_mosaic'] ? 'да' : 'нет'
		]);
		break;
	case 'vehicle_remove':
		$db->delete('vehicles', "`id`={$_POST['id']}");
		break;
	case 'vehicle_change': 
		// print_r($_POST);
		$db->update(
			'vehicles',
			[
				'title' => $_POST['title'], 
				'category_id' => $_POST['category_id'], 
				'pos' => $_POST['pos'], 
				'is_mosaic' => $_POST['is_mosaic'] 
			],
			"`id`={$_POST['id']}"
		);
		break;
	case 'vehicle_image_delete':
		unlink("{$_SERVER['DOCUMENT_ROOT']}{$_POST['path']}");
		break;
	case 'get_vehicle_categories':
		$vehicle_categories = $db->select('vehicle_categories', '*', '', 'title', true);
		echo json_encode($vehicle_categories);
		break;
	case 'vehicle_category_change':
		// print_r($_POST);
		$db->update('vehicle_categories', ['title' => $_POST['new_value']], "`id`='{$_POST['id']}'");
		break;
	case 'vehicle_category_delete':
		$db->delete('vehicle_categories', "`id`='{$_POST['id']}'");
		break;
	case 'vehicle_category_add':
		$res = $db->insert('vehicle_categories', ['title' => $_POST['new_value']]);
		if ($res === true) echo $db->last_id();
		else echo $db->error();
		break;
	case 'get_brends':
		$brends = $db->select_unique("
			SELECT 
				b.id,
				b.title
			FROM
				#brends b
			LEFT JOIN #vehicle_filters vf ON vf.brend_id=b.id AND vf.vehicle_id={$_POST['vehicle_id']}
			WHERE
				b.parent_id=0 AND
				vf.brend_id IS NULL
			ORDER BY
				b.title
		", '');
		echo json_encode($brends);
		break;
	case 'brend_add':
		$db->insert(
			'vehicle_filters',
			[
				'brend_id' => $_POST['brend_id'],
				'vehicle_id' => $_POST['vehicle_id'],
				'title' => 'Год'
			]
		);
		echo json_encode([
			'id' => $_POST['brend_id'],
			'title' => $db->getFieldOnID('brends', $_POST['brend_id'], 'title'),
		]);
		break;
	case 'brend_remove':
		$db->delete('vehicle_filters', "`brend_id`={$_POST['brend_id']} AND `vehicle_id`={$_POST['vehicle_id']}");
		break;
	case 'model_add':
		// print_r($_POST); exit();
		$db->insert(
			'models',
			[
				'title' => $_POST['title'],
				'vin' => strtoupper($_POST['vin']),
				'href' => $_POST['href'] ? $_POST['href'] : str_replace(' ', '-', translite($_POST['title'])),
				'brend_id' => $_POST['brend_id'],
				'vehicle_id' => $_POST['vehicle_id']
			]
		);
		echo json_encode([
			'id' => $db->last_id(),
			'title' => strtoupper($_POST['title']),
			'vin' => $_POST['vin']
		]);
		break;
	case 'model_search':
		print_r($_POST);
		break;
	case 'brend_remove':
		$db->delete('models', "`id`={$_POST['model_id']}");
		break;
	case 'model_remove':
		$db->update('models', ['is_removed' => 1], "`id`={$_POST['id']}");
		break;
	case 'modification_add':
		foreach($_POST as $key => $value){
			if (!is_numeric($key)) continue;
			if ($db->getFieldOnID('vehicle_filters', $key, 'title') == 'Год'){
				$fv_id = $value;
				break;
			} 
		}
		$db->insert('modifications', ['title' => $_POST['title'], 'model_id' => $_POST['model_id'], 'fv_id' => $fv_id]);
		$modification_id = $db->last_id();
		foreach($_POST as $key => $value){
			if (!is_numeric($key)) continue;
			$db->insert('vehicle_model_fvs', ['modification_id' => $modification_id, 'fv_id' => $value]);
		}
		echo $modification_id;
		break;
	case 'modification_remove':
		$db->delete('modifications', "`id`={$_POST['modification_id']}");
		break;
	case 'get_models':
		// print_r($_POST);
		$where = '';
		if ($_POST['year']) $where .= "fv.title='{$_POST['year']}' AND ";
		if ($_POST['search']) $where .= "md.title LIKE '%{$_POST['search']}%' AND ";
		$where = substr($where, 0, -5);
		$res_models = $db->query("
			SELECT
				fvs.modification_id,
				mf.title AS modification_title,
				mf.model_id,
				md.title AS model_title,
				md.href AS model_href,
				fvs.fv_id
			FROM
				#vehicle_model_fvs fvs
			LEFT JOIN
				#modifications mf ON mf.id=fvs.modification_id
			LEFT JOIN
				#models md ON md.id=mf.model_id
			LEFT JOIN
				#brends b ON b.id=md.brend_id
			LEFT JOIN
				#vehicles v ON v.id=md.vehicle_id
			LEFT JOIN
				#vehicle_filters f ON f.title='Год' AND b.id=md.brend_id AND md.vehicle_id=v.id
			LEFT JOIN
				#vehicle_filter_values fv ON fv.id=fvs.fv_id
			WHERE
				$where AND
				b.href='{$_POST['brend']}' AND
				v.href='{$_POST['vehicle']}'
			GROUP BY
				md.title
			ORDER BY
				md.title
		", '');
		$models = array();
		if ($res_models->num_rows){
			while($row = $res_models->fetch_assoc()){
				$letter = mb_strtoupper(mb_substr($row['model_title'], 0 , 1, 'UTF-8'), 'UTF-8');
				$models[$letter][$row['model_id']] = [
					'model_id' => $row['model_id'],
					'title' => $row['model_title'],
					'href' => $row['model_href']
				];
			}
		}
		// print_r($models);
		echo json_encode($models);
		break;
	case 'get_models_adjourn':
		$models = $db->select(
			'models', 
			['id', 'title'], 
			"`vehicle_id`={$_POST['vehicle_id']} AND `brend_id`={$_POST['brend_id']} AND `id`!={$_POST['model_id']}"
		);
		echo json_encode($models);
		break;
	case 'search_nodes':
		// print_r($_POST);
		$nodes = $db->select_unique("
			SELECT
				n1.title as parent,
				n.id,
				n.title,
				n.parent_id
			FROM
				#nodes n
			LEFT JOIN #nodes n1 ON n.parent_id=n1.id
			WHERE
				n.title LIKE '%{$_POST['title']}%'
		", '');
		echo json_encode($nodes);
		break;
	case 'search_items':
		$article = article_clear($_POST['article']);
		$res_items = $db->query("
			SELECT
				i.id,
				i.article,
				i.title_full,
				b.title AS brend
			FROM
				#items i
			LEFT JOIN #brends b ON b.id=i.brend_id
			WHERE
				i.article = '$article'
		", '');
		if ($res_items->num_rows >= 20){
			echo $res_items->num_rows;
			break;
		}
		while($row = $res_items->fetch_assoc()) $items[] = [
			'id' => $row['id'],
			'article' => $row['article'],
			'title_full' => $row['title_full'],
			'brend' => $row['brend']
		];
		echo json_encode($items);
		break;
	case 'set_item':
		$res = $db->insert(
			'node_items',
			[
				'pos' => $_POST['pos'],
				'node_id' => $_POST['node_id'],
				'item_id' => $_POST['item_id'],
				'quan' => $_POST['quan'],
				'comment' => $_POST['comment'],
			]
		);
		echo $res;
		break;
	case 'item_change':
		// print_r($_POST);
		$res = $db->update(
			'node_items',
			[
				'pos' => $_POST['pos'],
				'quan' => $_POST['quan'],
				'comment' => $_POST['comment'],
			],
			"`node_id`={$_POST['node_id']} AND `item_id`={$_POST['item_id']}"
		);
		echo $res;
		break;
	case 'item_remove':
		// print_r($_POST);
		$db->delete('node_items', "`node_id`={$_POST['node_id']} AND `item_id`={$_POST['item_id']}");
		break;
	case 'filter_add':
		$res = $db->insert(
			'vehicle_filters', 
			[
				'title' => $_POST['title'],
				'vehicle_id' => $_POST['vehicle_id'],
				'brend_id' => $_POST['brend_id']
			]
		);
		if ($res === true) echo $db->last_id();
		else echo $db->error();
		break;
	case 'filter_change':
		$filter = $db->select_one('vehicle_filters', 'vehicle_id,brend_id', "`id`={$_POST['filter_id']}");
		// print_r($filter);
		$count = $db->getCount('vehicle_filters', "`vehicle_id`={$filter['vehicle_id']} AND `brend_id`={$filter['brend_id']}");
		if ($count == 1 && !$_POST['title']){
			echo 'not';
			break;
		}
		if ($_POST['title']) $db->update('vehicle_filters', ['title' => $_POST['title']], "`id`={$_POST['filter_id']}");
		else {
			$db->delete('vehicle_filters', "`id`={$_POST['filter_id']}");
			$db->delete('vehicle_filter_values', "`filter_id`={$_POST['filter_id']}");
		}
		break;
	case 'filter_value_add':
		$res = $db->insert('vehicle_filter_values', ['title' => $_POST['title'], 'filter_id' => $_POST['filter_id']]);
		if ($res === true) echo $db->last_id();
		else echo $db->error();
		break;
	case 'filter_value_change':
		if (!$_POST['title']) $db->delete('vehicle_filter_values', "`id`={$_POST['fv_id']}");
		else $db->update('vehicle_filter_values', ['title' => $_POST['title']], "`id`={$_POST['fv_id']}"); 
		break;
	case 'model_image_delete':
		unlink("{$_SERVER['DOCUMENT_ROOT']}/images/models/{$_POST['model_id']}.jpg");
		break;
	case 'node_change':
		// print_r($_POST); exit();
		if (!$_POST['title']) $db->delete('nodes', "`id`={$_POST['node_id']} OR `parent_id`={$_POST['node_id']}");
		else $db->update('nodes', ['title' => $_POST['title']], "`id`={$_POST['node_id']}");
	case 'model_change': 
		// print_r($_POST);
		$db->update(
			'models',
			[
				'title' => $_POST['title'],
				'vin' => strtoupper($_POST['vin']),
				'href' => $_POST['href'] ? $_POST['href'] : str_replace(' ', '-', translite($_POST['title'])),
			 ],
			"`id`={$_POST['id']}"
		);
		break;
	case 'modification_change': 
		if (!$_POST['title']){
			$res_modification = $db->query("
				SELECT
					m.id,
					m.title,
					f.id AS filter_id,
					fv.id AS filter_value_id
				FROM
					#modifications m
				LEFT JOIN #vehicle_model_fvs fvs ON fvs.modification_id=m.id
				LEFT JOIN #vehicle_filter_values fv ON fv.id=fvs.fv_id
				LEFT JOIN #vehicle_filters f ON f.id=fv.filter_id
				WHERE
					m.id={$_POST['modification_id']}
			", '');
			while($row = $res_modification->fetch_assoc()){
				$modification['id'] = $row['id'];
				$modification['title'] = $row['title'];
				if ($row['filter_id'] && $row['filter_value_id']) $modification['filter_values'][$row['filter_id']] = $row['filter_value_id'];
			}
			echo json_encode([
				'modification' => $modification,
				'filters' => modification_get_filters()
			]);
		}
		else{
			// print_r($_POST);
			// exit();
			$db->delete('vehicle_model_fvs', "`modification_id`={$_POST['modification_id']}");
			foreach($_POST as $key => $value){
				if (!is_numeric($key) || !$value) continue;
				$db->insert('vehicle_model_fvs', ['modification_id' => $_POST['modification_id'], 'fv_id' => $value]);
			}
			$db->update('modifications', ['title' => $_POST['title']], "`id`={$_POST['modification_id']}");
		}
		break;
	case 'modification_to_other_model':
		// print_r($_POST);
		$db->update('modifications', ['model_id' => $_POST['model_id'], 'is_moved' => 1], "`id`={$_POST['modification_id']}");
		break;
	case 'get_filters':
		$filters = modification_get_filters();
		echo json_encode($filters);
		break;
}
function modification_get_filters(){
	global $db;
	$res_filters = $db->query("
		SELECT
			f.id,
			f.title,
			fv.id AS filter_value_id,
			fv.title AS filter_value_title
		FROM
			#vehicle_filters f
		LEFT JOIN #vehicle_filter_values fv ON fv.filter_id=f.id
		WHERE
			f.vehicle_id={$_POST['vehicle_id']} AND 
			f.brend_id={$_POST['brend_id']}
	", '');
	if ($res_filters->num_rows){
		while($row = $res_filters->fetch_assoc()){
			$filters[$row['id']]['title'] = $row['title'];
			if (!$row['filter_value_id']) continue;
			$filters[$row['id']]['filter_values'][$row['filter_value_id']] = $row['filter_value_title'];
		}
	}
	return $filters;
}
?>