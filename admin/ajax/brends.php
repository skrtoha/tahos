<?php
require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/DataBase.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/templates/functions.php");
error_reporting(E_ERROR | E_PARSE);

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();
if (isset($_GET['brend_id'])){
	$imagesList = array();
	foreach($_FILES as $file){
		$image = array();
		$image['article_cat'] = preg_replace('/\..*$/', '', $file['name']);
		$article = article_clear($image['article_cat']);
		$image['fileSource'] = $file['name'];

		//uncomment if need to insert new item
		// $db->insert(
		// 	'items',
		// 	[
		// 		'title_full' => 'Деталь',
		// 		'title' => 'Деталь',
		// 		'brend_id' => $_GET['brend_id'],
		// 		'article' => $article,
		// 		'article_cat' => $image['article_cat'],
		// 	],
		// 	['print_query' => false]
		// );
		// $item_id = $db->last_id();
		// $db->insert('articles', ['item_id' => $item_id, 'item_diff' => $item_id]);

		$array = $db->select_one('items', ['id'], "`brend_id`={$_GET['brend_id']} AND `article`='$article'");
		if (empty($array)){
			$image['error'] = "Артикул {$article} не найден";
			$imagesList[] = $image;
			continue;
		}
		$res = set_image($file, $array['id']);
		$image['error'] = $res['error'];
		$image['fileSaved'] = $res['name'];
		$image['item_id'] = $array['id'];
		$imagesList[] = $image;
		if ($image['error']) continue;
		// $db->update('items', ['foto' => $res['name']], "`id`={$array['id']}");
		core\Item::update(['foto' => $res['name']], ['id' => $array['id']]);
	};
	echo json_encode($imagesList);
}

?>