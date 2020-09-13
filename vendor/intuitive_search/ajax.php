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
		$res_items = $db->query("
			SELECT
				i.id,
				IF(i.article_cat != '', i.article_cat, i.article) AS article,
				i.title_full,
				i.brend_id,
				b.title AS brend
			FROM
				#items i
			LEFT JOIN
				#brends b ON b.id = i.brend_id
			WHERE
				i.article LIKE '{$_GET['value']}%'
			LIMIT
				0, {$_GET['maxCountResults']}
		", '');
		if (!$res_items->num_rows) break;
		foreach($res_items as $item){
			$output .= "
				<li>
					<a href=\"/admin/?view=items&act=item&id={$item['id']}\">
						{$item['brend']} - {$item['article']}
					</a>
				</li>";
		}
		break;
}
echo $output;
