<?php

use core\Config;
use core\Provider;

require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/DataBase.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/templates/functions.php");

$db = new core\Database();
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
		copy($_SERVER['DOCUMENT_ROOT'].$_POST['initial'], core\Config::$tmpFolderPath . $pathBig);

		echo json_encode([
			'small' => Config::$tmpFolderUrl . $pathSmall,
			'big' => Config::$tmpFolderUrl . $pathBig
		]);
		break;
	case 'applyCategory':
		$db->insert('categories_items', ['item_id' => $_POST['item_id'], 'category_id' => $_POST['category_id']]);
		break;
	case 'getStoreItem':
        $query = "
            select
                i.article,
                si.item_id,
                si.store_id,
                i.title_full,
                si.price as priceWithoutMarkup,
                si.in_stock,
                si.packaging
            from
                #store_items si
            left join
                #items i ON i.id = si.item_id
            left join
                #brends b ON b.id = i.brend_id
        ";
		$query .= "
			WHERE
				si.store_id = {$_POST['store_id']} AND si.item_id = {$_POST['item_id']}
		";
		$res_store_items = $db->query($query, '');
        $store_items = $res_store_items->fetch_assoc();
        if ($_POST['store_id'] == Config::MAIN_STORE_ID){
            $result = $db->query("
                select
                    ps.id as store_id,
                    ps.provider_id,
                    ps.cipher,
                    ps.title
                from
                    #main_store_item msi
                left join
                    #provider_stores ps on ps.id = msi.store_id
                where
                    msi.item_id = {$_POST['item_id']}
            ");
            $store_items['main_store'] = $result->fetch_assoc();
            $store_items['providerList'] = Provider::get();
            $store_items['providerStoreList'] = $db->select(
                'provider_stores',
                'id,title,cipher',
                "`provider_id` = {$store_items['main_store']['provider_id']}"
            );
        }
		echo json_encode($store_items);
		break;
	case 'getItemInfo':
        $itemInfo = core\Item::getByID($_POST['item_id']);
        echo json_encode($itemInfo);
		break;
	case 'addItem':
		//debug($_POST); //exit();
		$res = $db->query("
			SELECT * FROM #item_analogies WHERE item_id={$_POST['item_id']}
		", '');

		$db->insert('item_'.$_POST['type'], ['item_id' => $_POST['item_id'], 'item_diff' => $_POST['item_diff']]/*, ['print' => true]*/);
		if (in_array($_POST['type'], ['articles', 'analogies', 'substitutes'])){
			$db->insert('item_'.$_POST['type'], ['item_id' => $_POST['item_diff'], 'item_diff' => $_POST['item_id']]/*, ['print' => true]*/);
		}
		if ($_POST['addAllAnalogies']){
			if ($res->num_rows){
				while($row = $res->fetch_assoc()){
					$db->insert('item_analogies', ['item_id' => $_POST['item_diff'], 'item_diff' => $row['item_diff']]/*, ['print' => true]*/);
					$db->insert('item_analogies', ['item_id' => $row['item_diff'], 'item_diff' => $_POST['item_diff']]/*, ['print' => true]*/);
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
		$db->delete('item_'.$_POST['type'], "`item_id` = {$_POST['item_id']} AND `item_diff` = {$_POST['item_diff']}");
		$db->delete('item_'.$_POST['type'], "`item_id` = {$_POST['item_diff']} AND `item_diff` = {$_POST['item_id']}");
		if ($_POST['type'] == 'analogies'){
			$res = $db->query("
				SELECT item_diff FROM #item_analogies WHERE `item_id`={$_POST['item_id']}
			", '');
			if ($res->num_rows){
				while($row = $res->fetch_assoc()){
					$db->delete('item_analogies', "
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
		$db->delete('item_'.$_POST['type'], "`item_id` = {$_POST['item_id']} OR `item_diff` = {$_POST['item_id']}");
		break;
}
?>
