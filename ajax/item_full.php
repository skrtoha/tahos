<?
session_start();
require_once('../core/DataBase.php');
require_once('../core/functions.php');

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$id = $_POST['id'];
if ($_SESSION['user']){
	$user = core\User::get();
	$where_basket = "LEFT JOIN #basket ba  ON ba.item_id=i.id AND ba.store_id=ps.id AND ba.user_id={$_SESSION['user']}";
	$where_basket .= " LEFT JOIN #items_ratings ir ON ir.item_id=i.id AND ir.user_id={$_SESSION['user']}";
	$clause = ",ir.rate AS rating, ba.quan as in_basket";
	$userDiscount = " - si.price * c.rate * {$user['discount']} / 100";
}
$q_item = "
	SELECT 
		i.id, 
		i.title, 
		IF (
				i.article_cat != '', 
				i.article_cat, 
				IF (
					i.article !='',
					i.article,
					i.barcode
				)
		) AS article,
		i.full_desc,
		i.applicability,
		i.characteristics,
		b.title AS brend,
		i.brend_id,
		i.full_desc,
		i.photo,
		IF (i.title_full, i.title_full, i.title) AS title_full,
		IF (si.in_stock = 0, ps.under_order, ps.delivery) AS delivery,
		CEIL(si.price * c.rate + si.price * c.rate * ps.percent / 100 $userDiscount) as price,
		si.in_stock,
		si.packaging,
		si.store_id,
		IF(f.item_id is not null, 1, 0) AS in_favorite
		$clause
	FROM `#items` i
	LEFT JOIN #store_items si ON i.id=si.item_id
	LEFT JOIN #provider_stores ps ON ps.id=si.store_id
	LEFT JOIN #currencies c ON c.id=ps.currency_id
	LEFT JOIN #brends b ON b.id=i.brend_id
	LEFT JOIN #favorites f ON f.item_id=i.id
	$where_basket
	WHERE i.id={$_POST['id']}
";
// $item['query'] = $db->query($q_item, 'get');
$res_item = $db->query($q_item, '');
$settings = $db->select('settings', '*', '`id`=1'); $settings = $settings[0];
$ratings = json_decode($settings['ratings'], true);
if ($res_item->num_rows){
	while($v = $res_item->fetch_assoc()){
		$prices[] = $v['price'];
		$deliveries[] = $v['delivery'];
		$item['item']['id'] = $v['id'];
		$item['item']['title'] = $v['title'];
		$item['item']['article'] = $v['article'];
		$item['item']['rating'] = $v['rating'];
		$item['item']['in_favorite'] = $v['in_favorite'];
		$item['item']['full_desc'] = html_entity_decode($v['full_desc']);
		$item['item']['applicability'] = html_entity_decode($v['applicability']);
		$item['item']['characteristics'] = html_entity_decode($v['characteristics']);
		$item['item']['brend'] = $v['brend'];
		$item['item']['brend_id'] = $v['brend_id'];
		$item['item']['full_desc'] = $v['full_desc'];
		$item['item']['photo'] = $v['photo'];
		if (!$v['store_id']) continue;
		$item['store_items'][$v['store_id']]['price'] = $v['price'];
		$item['store_items'][$v['store_id']]['in_stock'] = $v['in_stock'];
		$item['store_items'][$v['store_id']]['store_id'] = $v['store_id'];
		$item['store_items'][$v['store_id']]['packaging'] = $v['packaging'];
		$item['store_items'][$v['store_id']]['delivery'] = $v['delivery'];
		$item['store_items'][$v['store_id']]['in_basket'] = $v['in_basket'];
	}
}

$item['photos'] = array();
$dirPhotos = core\Config::$imgPath . "/items/small/{$_POST['id']}/";
if (file_exists($dirPhotos)){
	$photoNames = scandir($dirPhotos);
	if (!empty($photoNames)){
		foreach($photoNames as $name){
			if (!preg_match('/.+\.jpg/', $name)) continue;
			$item['photos'][] = $name;
		}
	} 
}

$user = core\User::get();
$item['user_id'] = $_SESSION['user'];
$item['designation'] = $user['designation'];
if (isset($item['providers_items']) && !count($item['providers_items'])) {
	$item['min']['delivery'] = array();
	$item['min']['price'] = array();
	echo json_encode($item);
	exit();
}
$min_price = min($prices);
$min_delivery = min($deliveries);
if (isset($item['store_items']) && count($item['store_items'])){
	foreach($item['store_items'] as $key => $value){
		if ($value['delivery'] == $min_delivery){
			$item['min']['delivery'] = $value;
			$item['min']['delivery']['user_price'] = get_user_price($item['min']['delivery']['price'], $user);
		} 
		if ($value['price'] == $min_price){
			$item['min']['price'] = $value;
			$item['min']['price']['user_price'] = get_user_price($item['min']['price']['price'], $user);
		} 
	}
}
unset($item['store_items']);
echo json_encode($item);?>