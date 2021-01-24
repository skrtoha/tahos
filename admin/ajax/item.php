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
	case 'savePhoto':
		// debug($_POST);
		// debug($_FILES);
		$name = time();
		$pathBig = "/big_$name.jpg";
		$pathSmall = "/small_$name.jpg";

		copy($_FILES['croppedImage']['tmp_name'], core\Config::$tmpFolderPath . $pathSmall);
		copy($_POST['initial'], core\Config::$tmpFolderPath . $pathBig);

		echo json_encode([
			'small' => core\Config::$tmpFolderUrl . $pathSmall,
			'big' => core\Config::$tmpFolderUrl . $pathBig
		]);
		break;
	case 'applyCategory':
		$db->insert('categories_items', ['item_id' => $_POST['item_id'], 'category_id' => $_POST['category_id']]);
		break;
	case 'getStoreItem':
		$query = core\StoreItem::getQueryStoreItem();
		$query .= "
			WHERE
				si.store_id = {$_POST['store_id']} AND si.item_id = {$_POST['item_id']}
		";
		$res_store_items = $db->query($query, '');
		echo json_encode($res_store_items->fetch_assoc());
		break;
	case 'getItemInfo':
		// debug($_POST);
		echo json_encode(core\Item::getByID($_POST['item_id']));
		break;
	case 'addItem':
		$res = $db->query("
			SELECT * FROM #analogies WHERE item_id={$_POST['item_diff']}
		", '');

		$db->insert($_POST['type'], ['item_id' => $_POST['item_id'], 'item_diff' => $_POST['item_diff']]/*, ['print' => true]*/);
		if (in_array($_POST['type'], ['articles', 'analogies', 'substitutes'])){
			$db->insert($_POST['type'], ['item_id' => $_POST['item_diff'], 'item_diff' => $_POST['item_id']]/*, ['print' => true]*/);
		}
		if ($_POST['addAllAnalogies']){
			if ($res->num_rows){
				while($row = $res->fetch_assoc()){
					$db->insert('analogies', ['item_id' => $_POST['item_id'], 'item_diff' => $row['item_diff']]/*, ['print' => true]*/);
					$db->insert('analogies', ['item_id' => $row['item_diff'], 'item_diff' => $_POST['item_id']]/*, ['print' => true]*/);
				} 
			}
		}
		$res_items = core\Item::getResItemDiff($_POST['type'], $_POST['item_id'], '');
		$output = [];
		if ($res_items->num_rows){
			foreach($res_items as $item) $output[] = $item;
		}
		echo json_encode($output);
		break;
	case 'deleteItemDiff':
		//debug($_POST); exit();
		$db->delete($_POST['type'], "`item_id` = {$_POST['item_id']} AND `item_diff` = {$_POST['item_diff']}");
		$db->delete($_POST['type'], "`item_id` = {$_POST['item_diff']} AND `item_diff` = {$_POST['item_id']}");
		if ($_POST['type'] == 'analogies'){
			$res = $db->query("
				SELECT item_diff FROM #analogies WHERE `item_id`={$_POST['item_id']}
			", '');
			if ($res->num_rows){
				while($row = $res->fetch_assoc()){
					$db->delete('analogies', "
						(`item_id`={$_POST['item_diff']} AND `item_diff`={$row['item_diff']}) OR
						(`item_id`={$row['item_diff']} AND `item_diff`={$_POST['item_diff']})
					");
				}
			}
		}
		$res_items = core\Item::getResItemDiff($_POST['type'], $_POST['item_id'], '');
		$output = [];
		if ($res_items->num_rows){
			foreach($res_items as $item) $output[] = $item;
		}
		echo json_encode($output);
		break;
	case 'clearItemDiff':
		$db->delete($_POST['type'], "`item_id` = {$_POST['item_id']} OR `item_diff` = {$_POST['item_id']}");
		break;
}
?>
