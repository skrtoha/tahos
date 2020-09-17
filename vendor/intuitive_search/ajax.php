<?php
require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/DataBase.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/templates/functions.php");
error_reporting(E_ERROR | E_PARSE);

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$output = '';
switch($_GET['tableName']){
	case 'items':
		$query = core\Item::getQueryItemInfo();
		$query .= "
			WHERE
				i.article LIKE '{$_GET['value']}%'
			LIMIT
				0, {$_GET['maxCountResults']}
		";
		$res_items = $db->query($query);
		if (!$res_items->num_rows) break;
		foreach($res_items as $item){
			$output .= "
				<li>
					<a href=\"/admin/?view=items&act=item&id={$item['id']}\">
						{$item['brend']} - {$item['article']} - {$item['title_full']}
					</a>
				</li>";
		}
		break;
	case 'store_items':
		$query = core\StoreItem::getQueryStoreItem();
		$query .= "
			WHERE
				i.article LIKE '{$_GET['value']}%' AND si.store_id = {$_GET['additionalConditions']['store_id']}
			LIMIT
				0, {$_GET['maxCountResults']}
		";
		$res_store_items = $db->query($query);
		if (!$res_store_items->num_rows) break;
		foreach($res_store_items as $v){
			$output .= "
				<li>
					<a store_id=\"{$v['store_id']}\" item_id=\"{$v['item_id']}\" class=\"showStoreItemInfo\" href=\"#\">
						{$v['brend']} - {$v['article']} - {$v['title_full']}
					</a>
				</li>";
		}
		break;
	case 'storeItemsForAdding':
		$query = core\Item::getQueryItemInfo();
		$query .= "
			LEFT JOIN
				#store_items si ON si.item_id = i.id AND si.store_id = {$_GET['additionalConditions']['store_id']}
			WHERE
				i.article LIKE '{$_GET['value']}%' AND si.store_id IS NULL
			LIMIT
				0, {$_GET['maxCountResults']}
		";
		$res_items = $db->query($query);
		if (!$res_items->num_rows) break;
		foreach($res_items as $item){
			$output .= "
				<li>
					<a class=\"addStoreItem\" item_id=\"{$item['id']}\">
						{$item['brend']} - {$item['article']} - {$item['title_full']}
					</a>
				</li>";
		}
		break;
}
echo $output;
