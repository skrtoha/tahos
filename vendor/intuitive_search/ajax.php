<?php
require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/DataBase.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/templates/functions.php");
error_reporting(E_ERROR | E_PARSE);

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$output = '';
// debug($_GET);
switch($_GET['tableName']){
	case 'items':
		switch($_GET['additionalConditions']['act']){
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
							<a item_id=\"{$item['id']}\" class=\"resultItem\" href=\"/admin/?view=items&act=item&id={$item['id']}\">
								{$item['brend']} - {$item['article']} - {$item['title_full']}
							</a>
						</li>";
				}
				break;
			case 'complects':
			case 'substitutes':
			case 'analogies':
			case 'articles':
				$query = core\Item::getQueryItemInfo();
				switch($_GET['additionalConditions']['act']){
					case 'substitutes':
					case 'analogies':
					case 'complects':
						$additionalWhere = " AND i.id != {$_GET['additionalConditions']['item_id']}";
						break;
					default:
						$additionalWhere = '';
				}
				$query .= "
					LEFT JOIN
						#{$_GET['additionalConditions']['act']} diff ON diff.item_diff = i.id AND diff.item_id = {$_GET['additionalConditions']['item_id']}
					WHERE
						i.article LIKE '{$_GET['value']}%' AND diff.item_id IS NULL $additionalWhere
					LIMIT
						0, {$_GET['maxCountResults']}
				";
				$res_items = $db->query($query, '');
				if (!$res_items->num_rows) break;
				foreach($res_items as $item){
					$output .= "
						<li>
							<a item_id=\"{$item['id']}\" class=\"addItem\" type=\"{$_GET['additionalConditions']['act']}\" href=\"#\">
								{$item['brend']} - {$item['article']} - {$item['title_full']}
							</a>
						</li>";
				}
				break;
		}
		break;
	case 'brends':
		$res_brends = core\Brend::get(
			[
				'title' => $_GET['value'],
				'parent_id' => 0,
				'limit' => $_GET['maxCountResults']
			], 
			[], '');
		if (!$res_brends->num_rows) break;
		foreach($res_brends as $brend){
			$output .= "
				<li>
					<a brend_id=\"{$brend['id']}\" class=\"resultBrend\">
						{$brend['title']}
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
	case 'brendItems':
		$query = core\Item::getQueryItemInfo();
		$query .= "
			WHERE
				i.article LIKE '{$_GET['value']}%' AND
				i.brend_id = {$_GET['additionalConditions']['brend_id']}
			LIMIT
				0, {$_GET['maxCountResults']}
		";
		$res_items = $db->query($query);
		if (!$res_items->num_rows) break;
		foreach($res_items as $item){
			$output .= "
				<li>
					<a item_id=\"{$item['id']}\" class=\"resultItem\" href=\"/admin/?view=brends&act=items&id={$_GET['additionalConditions']['brend_id']}&item_id={$item['id']}\">
						{$item['brend']} - {$item['article']} - {$item['title_full']}
					</a>
				</li>";
		}
		break;
		case 'provider_stores':
			$res_provider_stores = $db->query("
				SELECT
					ps.id,
					ps.cipher,
					ps.title
				FROM
					#provider_stores ps
				WHERE
					ps.cipher LIKE '%{$_GET['value']}%' OR ps.title LIKE '%{$_GET['value']}%'
				LIMIT
					0, {$_GET['maxCountResults']}
				", '');
				foreach($res_provider_stores as $ps){
					$output .= "
						<li>
							<a store_id=\"{$ps['id']}\" class=\"provider_store\" href=\"#\">
								{$ps['cipher']} - {$ps['title']}
							</a>
						</li>";
				}
			break;
}
echo $output;
