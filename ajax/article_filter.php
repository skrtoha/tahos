<?php
/** @var $res_user mysqli_result */

session_start();
require_once ("../core/Database.php");
require_once ("../core/functions.php");
error_reporting(E_ERROR);

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

if ($_POST['filters_on']) $filters = [
	'price_from' => $_POST['price_from'],
	'price_to' => $_POST['price_to'],
	'time_from' => $_POST['time_from'],
	'time_to' => $_POST['time_to'],
	'in_stock' => $_POST['in_stock']
];
else $filters = [];

$res_user = core\User::get(['user_id' => $_SESSION['user'] ?: false]);
if ($res_user->num_rows) $user = $res_user->fetch_assoc();
else $user = $res_user;

$array = core\Search::articleStoreItems(
    $_POST['item_id'],
    $_SESSION['user'] ?? null,
    $filters,
    $_POST['search_type']
);

$store_items = & $array['store_items'];
foreach ($store_items as $k => $v){
	$si = & $store_items[$k];
	$si['href_article'] = core\Item::getHrefArticle($si['article']);
	if (!empty($si['list'])){
		$si['min_price']['user_price'] = core\User::getHtmlUserPrice($si['min_price']['price'], $user['designation']);
		foreach($si['list'] as $key => $value){
			$si['list'][$key]['user_price'] = core\User::getHtmlUserPrice($value['price'], $user['designation']);
		} 
	} 
	if (!empty($si['prevails'])) foreach($si['prevails'] as $key => $value){
		$si['prevails'][$key]['user_price'] = core\User::getHtmlUserPrice($value['price'], $user['designation']);
	} 
	if (count($si['list']) > 1){
		$si['min_delivery']['user_price'] = core\User::getHtmlUserPrice($si['min_delivery']['price'], $user['designation']);
	}
}
$tempArray = array();
foreach($store_items as $key => $value){
	$tempArray[] = [
		'item_id' => $key,
		'store_item' => $value
	];
}
$array['store_items'] = $tempArray;
echo json_encode($array);
?>
