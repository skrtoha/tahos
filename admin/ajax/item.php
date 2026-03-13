<?php

use core\Config;
use core\Item;
use core\Marketplaces\Avito;
use core\Marketplaces\Marketplaces;
use core\Marketplaces\Ozon;
use core\Provider;
use core\Setting;
use core\StoreItem;

require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/DataBase.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/templates/functions.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$post = $_POST;
if (empty($post)) {
    $post = json_decode(file_get_contents('php://input'), true);
}

switch($post['act']){
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
				f.category_id={$post['category_id']}
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
		// print_r($post); exit();
		$db->delete(
			'items_values', 
			"
				`item_id`={$post['item_id']} AND
				`category_id`={$post['category_id']}
			"
		);
		foreach($post as $key => $value){
			if (!is_numeric($key) || !$value) continue;
			$db->insert(
				'items_values',
				[
					'item_id' => $post['item_id'],
					'category_id' => $post['category_id'],
					'value_id' => $value
				]
			);
		}
		break;
	case 'category_delete':
		$db->delete(
			'categories_items',
			"`item_id`={$post['item_id']} AND `category_id`={$post['category_id']}"
		);
		break;
	case 'savePhoto':
		// debug($post);
		// debug($_FILES);
		$name = time();
		$pathBig = "/big_$name.jpg";
		$pathSmall = "/small_$name.jpg";

		copy($_FILES['croppedImage']['tmp_name'], core\Config::$tmpFolderPath . $pathSmall);
		copy($_SERVER['DOCUMENT_ROOT'].$post['initial'], core\Config::$tmpFolderPath . $pathBig);

		echo json_encode([
			'small' => Config::$tmpFolderUrl . $pathSmall,
			'big' => Config::$tmpFolderUrl . $pathBig
		]);
		break;
	case 'applyCategory':
        if (isset($post['isAvito']) && $post['isAvito']){
            $db->query("
                DELETE ci FROM
                   #categories_items ci
                LEFT JOIN #categories c ON c.id = ci.category_id
                WHERE 
                    c.parent_id IN (".Avito::getParentCategories(true).") AND
                    ci.item_id = {$post['item_id']}
            ");
        }
		$db->insert('categories_items', ['item_id' => $post['item_id'], 'category_id' => $post['category_id']]);

		if (isset($post['marketplace_description'])){
			Marketplaces::setItemDescription($post['item_id'], $post['marketplace_description']);
		}
		break;
	case 'getStoreItem':
        $query = "
            select
                b.title as brend,
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
				si.store_id = {$post['store_id']} AND si.item_id = {$post['item_id']}
		";
		$res_store_items = $db->query($query, '');
        $store_items = $res_store_items->fetch_assoc();
        if ($post['self_store']){
            $result = $db->query("
                select
                    ps.id as store_id,
                    ps.provider_id,
                    ps.cipher,
                    ps.title,
                    msi.min_price,
                    ri.requiredRemain
                from
                    #main_store_item msi
                left join
                    #provider_stores ps on ps.id = msi.store_id
                left join 
                    #required_remains ri on ri.item_id = msi.item_id AND ri.self_store_id = {$post['store_id']}
                where
                    msi.item_id = {$post['item_id']}
            ", '');
            $store_items['main_store'] = $result->fetch_assoc();
            $store_items['providerList'] = Provider::get();
            $store_items['providerStoreList'] = $db->select(
                'provider_stores',
                'id,title,cipher',
                "`provider_id` = {$store_items['main_store']['provider_id']} AND is_main = 1"
            );
        }
		echo json_encode($store_items);
		break;
	case 'getItemInfo':
		$params = [];
		if (isset($post['marketplace_description'])) $params[] = 'marketplace_description';
		if (isset($post['additional_options'])) $params[] = 'additional_options';
		if (isset($post['category_tahos'])) $params[] = 'category_tahos';
		if (isset($post['ozon_item'])) $params[] = 'ozon_item';

		$itemInfo = core\Item::getByID($post['item_id'], $params);

		if (isset($post['ozon_product_info']) && $post['ozon_product_info']){
			$ozonProductInfo = Ozon::getProductInfo($post['item_id']);
            $ozonItem = Ozon::getItemOzon(['offer_id' => $post['item_id']]);
			$itemInfo = array_merge(
                $itemInfo,
                $ozonProductInfo,
                ['store_id' => $ozonItem['store_id']]
            );
		}

        if (isset($post['ozon_markup']) && $post['ozon_markup']){
            $itemInfo['ozon_markup_old_price'] = Setting::get('marketplaces', 'ozon_markup_old_price');
            $itemInfo['ozon_markup_common'] = Setting::get('marketplaces', 'ozon_markup_common');
        }

		if (!isset($itemInfo['offer_id'])) $itemInfo['offer_id'] = $itemInfo['id'];

		if (!isset($itemInfo['price'])){
			$query = StoreItem::getQueryStoreItem();
			$query .= " WHERE si.item_id = {$itemInfo['id']} AND si.store_id = ".Provider\Tahos::$store_id;
			$result = $db->query($query)->fetch_assoc();
			$itemInfo['price'] = $result['price'];
			$itemInfo['old_price'] = 0;
		}

        $itemInfo['weight'] = $itemInfo['weight'] * 1000;
        echo json_encode($itemInfo);
		break;
	case 'addItem':
		//debug($post); //exit();
		$res = $db->query("
			SELECT * FROM #item_analogies WHERE item_id={$post['item_id']}
		", '');

		$db->insert('item_'.$post['type'], ['item_id' => $post['item_id'], 'item_diff' => $post['item_diff']]/*, ['print' => true]*/);
		if (in_array($post['type'], ['articles', 'analogies', 'substitutes'])){
			$db->insert('item_'.$post['type'], ['item_id' => $post['item_diff'], 'item_diff' => $post['item_id']]/*, ['print' => true]*/);
		}
		if ($post['addAllAnalogies']){
			if ($res->num_rows){
				while($row = $res->fetch_assoc()){
					$db->insert('item_analogies', ['item_id' => $post['item_diff'], 'item_diff' => $row['item_diff']]/*, ['print' => true]*/);
					$db->insert('item_analogies', ['item_id' => $row['item_diff'], 'item_diff' => $post['item_diff']]/*, ['print' => true]*/);
				} 
			}
		}
		$res_items = core\Item::getResItemDiff($post['type'], $post['item_id'], '');
		$output = [];
		if ($res_items->num_rows){
			foreach($res_items as $item) $output[] = $item;
		}
		echo json_encode($output);
		break;
	case 'deleteItemDiff':
		//debug($post); exit();
		$db->delete('item_'.$post['type'], "`item_id` = {$post['item_id']} AND `item_diff` = {$post['item_diff']}");
		$db->delete('item_'.$post['type'], "`item_id` = {$post['item_diff']} AND `item_diff` = {$post['item_id']}");
		if ($post['type'] == 'analogies'){
			$res = $db->query("
				SELECT item_diff FROM #item_analogies WHERE `item_id`={$post['item_id']}
			", '');
			if ($res->num_rows){
				while($row = $res->fetch_assoc()){
					$db->delete('item_analogies', "
						(`item_id`={$post['item_diff']} AND `item_diff`={$row['item_diff']}) OR
						(`item_id`={$row['item_diff']} AND `item_diff`={$post['item_diff']})
					");
				}
			}
		}
		$res_items = core\Item::getResItemDiff($post['type'], $post['item_id'], '');
		$output = [];
		if ($res_items->num_rows){
			foreach($res_items as $item) $output[] = $item;
		}
		echo json_encode($output);
		break;
	case 'clearItemDiff':
		$db->delete('item_'.$post['type'], "`item_id` = {$post['item_id']} OR `item_diff` = {$post['item_id']}");
		break;
    case 'getSubCategory':
		$multipleCategory = isset($post['marketplace_description']);
        echo Item::getSubCategory($post['parent_id'], $post['category_id'], $multipleCategory);
        break;
    case 'deleteCategory':
        $db->query("
            delete 
                f
            from
                tahos_filters f
            left join tahos_categories c on c.id = f.category_id
            where
                c.id = {$post['id']} or c.parent_id = {$post['id']}
        ");
        $db->delete('categories', "`id` = {$post['id']} or `parent_id` = {$post['id']}");
        break;
    case 'setHidden':
        $db->update(
            'categories',
            ['hidden' => $post['hidden']],
            "`id` = {$post['id']}"
        );
        break;
    case 'getMainCategory':
        echo Item::getMainCategory();
        break;
    case 'group_change_status':
        foreach($post['elements'] as $element){
            $db->update('item_analogies', ['status' => $post['status_id']], "`item_id` = {$element['item_id']} AND `item_diff` = {$element['item_diff']}");
            $db->update('item_analogies', ['status' => $post['status_id']], "`item_id` = {$element['item_diff']} AND `item_diff` = {$element['item_id']}");
        }
        break;
}
?>
