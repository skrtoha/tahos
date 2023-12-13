<?
require_once('../core/Database.php');
require_once('../core/functions.php');
session_start();
$user_id = $_SESSION['user'];

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$temp = json_decode($_COOKIE['basket'], true);
$basket = $temp['basket'];
$user_id = $_SESSION['user'];
foreach($basket as $value){
	$provider_item_id = $value['id'];
	$item_id = $db->getFieldOnID('providers_items', $provider_item_id, 'item_id');
	$item = $db->select('items', 'artice,title_full,brend_id', "`id`=$item_id"); $item = $item[0];
	$article = $item['artice'];
	$title = $item['title_full'];
	$brend = $db->getFieldOnID('brends', $item['brend_id'], 'title');
	$quan = $value['quan'];
	$price = $value['price'];
	$date = time();
	$insert = compact('provider_item_id', 'quan', 'price', 'user_id', 'date', 'article', 'title', 'brend');
	$db->insert('orders', $insert);
}
echo true;
?>
