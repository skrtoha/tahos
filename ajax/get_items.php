<?php  
require_once ("../class/database_class.php");
require_once('../core/functions.php');
session_start();
$res['post'] = $_POST;
$sub_id = $_POST['sub_id'];

$db = new DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$chunk = $_POST['chunk'];
$settings = $db->select('settings', '*', '`id`=1'); $settings = $settings[0];
if (!$_POST['filters_on']){
	if ($chunk < $settings['cat_countChunk'] && $chunk > 0){
		$items = $_SESSION['items_chunks'][$chunk];
		if (!count($items)) exit();
		$items_values = cat_get_items_values($items);
		$user = cat_get_user();
		$ratings = json_decode($settings['ratings'], true);
		foreach ($items as $key => $item){
			$items[$key]['price'] = get_user_price($item['price'], $user).$user['designation'];
			$items[$key]['filters_values'] = $items_values[$item['id']];
			$items[$key]['rating'] = get_rating($item['rating'], $ratings);
		} 
		unset($items_values);
		$chunk++;
		$res['chunk'] = $chunk;
		$res['items'] = $items;
		if ($chunk == $settings['cat_countChunk']) $res['reset'] = 1;
	}
	else{
		if ($chunk == 0) $_SESSION['start'] = 0;
		else $_SESSION['start'] += $settings['cat_perPage'] * $settings['cat_countChunk'];
		$sort = [
			'type' => $_POST['sort'],
			'desc' => $_POST['desc'] ? 'DESC' : ''
		];
		$res['items'] = category_items_without_filters($sub_id, $sort);
		$res['chunk'] = 1;
	}
}
else{
	if ($chunk < $settings['cat_countChunk'] && $chunk > 0){
		$items = $_SESSION['items_chunks'][$chunk];
		$items_values = cat_get_items_values($items);
		$user = cat_get_user();
		$ratings = json_decode($settings['ratings'], true);
		foreach ($items as $key => $item){
			$items[$key]['price'] = get_user_price($item['price'], $user).$user['designation'];
			$items[$key]['filters_values'] = $items_values[$item['id']];
			$items[$key]['rating'] = get_rating($item['rating'], $ratings);
		} 
		unset($items_values);
		$chunk++;
		$res['chunk'] = $chunk;
		$res['items'] = $items;
		if ($chunk == $settings['cat_countChunk']) $res['reset'] = 1;
	}
	else{
		if ($chunk == 0) $_SESSION['start'] = 0;
		else $_SESSION['start'] += $settings['cat_perPage'] * $settings['cat_countChunk'];
		$sort = [
			'type' => $_POST['sort'],
			'desc' => $_POST['desc'] ? 'DESC' : ''
		];
		$res['items'] = category_items_with_filters($sub_id, $sort);
		$res['chunk'] = 1;
	}
}
echo json_encode($res);
function end_script(){
	echo false;
	exit();
}
?>