<?php
require_once ("{$_SERVER['DOCUMENT_ROOT']}/class/database_class.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/templates/functions.php");

$db = new DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();
if (isset($_GET['brend_id'])){
	foreach($_FILES as $file){
		$article = preg_replace('/\..*$/', '', $file['name']);
		// print_r($file); continue;

		//if need to insert new items
		// $db->insert(
		// 	'items',
		// 	[
		// 		'title_full' => 'Деталь',
		// 		'title' => 'Деталь',
		// 		'brend_id' => $_GET['brend_id'],
		// 		'article' => $article,
		// 		'article_cat' => article_clear($article),
		// 	],
		// 	['print_query' => false]
		// );
		// $item_id = $db->last_id();
		// $db->insert('articles', ['item_id' => $item_id, 'item_diff' => $item_id]);
		// 
		$image = set_image($file, $array['id']);
		$array = $db->select_one('items', ['id'], "`brend_id`={$_GET['brend_id']} AND `article`='$article'");
		if ($image['error']) continue;
		$db->update('items', ['foto' => $image['name']], "`id`={$array['id']}");
	};
}

?>