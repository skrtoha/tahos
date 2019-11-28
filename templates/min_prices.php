<?
if ($_POST['submit']) update_price_min();
function update_price_min(){
	global $db;
	$in = [];
	foreach($_POST as $key => $value){
		if ($key != 'submit') $in[] = $value;
	}
	if (empty($in)) return message('Выберите категории!', false);
	$sub_cats = $db->select('categories', 'id', '`parent_id` IN ('.implode(',', $in).')');
	if (empty($sub_cats)) return message('Не затронуто ни одной строки!');
	$in = [];
	foreach ($sub_cats as $value) $in[] = $value['id'];
	$categories_items = $db->select('categories_items', 'item_id', '`category_id` IN ('.implode(',', $in).')');
	if (empty($categories_items)) return message('Не затронуто ни одной строки!');
	foreach($categories_items as $value) $items[] = $value['item_id'];
	$providers_items = $db->select('providers_items', '*', '`item_id` IN ('.implode(',', $items).')');
	if (empty($providers_items)) return message('Не затронуто ни одной строки!');
	$in_array = [];
	$items = [];
	$providers = $db->select('providers', 'id,currency_id,percent,delivery,under_order', '', '', '', '', true);
	$currencies = $db->select('currencies', 'id,rate', '', '', '', '', true);
	foreach ($providers_items as $value){
		$item = $value['item_id'];
		if (!in_array($item, $in_array)){
			$in_array[] = $item;
			$items[$item][0]['price'] = $value['price'];
			$items[$item][0]['in_stock'] = $value['in_stock'];
			$items[$item][0]['provider_id'] = $value['provider_id'];
			$items[$item][0]['pi_id'] = $value['id'];
		}
		else{
			$count = count($items[$item]);
			$items[$item][$count]['price'] = $value['price'];
			$items[$item][$count]['in_stock'] = $value['in_stock'];
			$items[$item][$count]['provider_id'] = $value['provider_id'];
			$items[$item][$count]['pi_id'] = $value['id'];
		}
	}
	debug($providers_items);
	exit();
	foreach ($items as $key => $item){
		foreach ($item as $k => $value){
			$p = $providers[$value['provider_id']];
			$currency_id = $p['currency_id'];
			$rubls = $currencies[$currency_id]['rate'] * $value['price'];
			$rubls = $rubls + $rubls/100*$p['percent'];
			$items[$key][$k]['rubls'] = ceil($rubls);
			$items[$key][$k]['delivery'] = $value['in_stock'] ? $p['delivery'] : $p['under_order'];
		}
	}
	$items_min = [];
	foreach ($items as $key => $item){
		$price = [];
		$delivery = [];
		foreach ($item as $k => $value){
			$price[] = $value['rubls'];
			$delivery[] = $value['delivery'];
		}
		$items_min[$key]['price'] = min($price);
		$items_min[$key]['delivery'] = min($delivery);
	}
	foreach ($items as $key => $item){
		$bool = false;
		foreach ($item as $value){
			if ($value['rubls'] == $items_min[$key]['price']){
				$items_min[$key]['provider_id'] = $value['provider_id'];
				$items_min[$key]['pi_id'] = $value['pi_id'];
			} 
		}
	}
	// debug($items);
	unset($items);
	foreach ($items_min as $key => $value){
		$array = [
			'price' => $value['price'], 
			'delivery' => $value['delivery'], 
			'provider_id' => $value['provider_id'],
			'pi_id' => $value['pi_id']
		];
		$db->update('items', $array, "`id`=$key");
	}
	message('Успешно обновлено!');
	// debug($currencies);

}
$act = $_GET['act'];
switch ($act) {
	default:
		view();
		break;
}
function view(){
	global $status, $db, $page_title;
	$page_title = 'Обновление цен';
	$status = "<a href='/admin'>Главная</a> > $page_title";?>
	<div class="t_form">
		<div class="bg">
			<div class="field">
				<div class="title">Обновление цен</div>
				<div class="value">
					<p id="p1">Выберите категории для обновления</p>
					<form method="post">
						<input type="hidden" name="submit" value="1">
						<?$categories = $db->select('categories', 'id,title', "`parent_id`=0");
						if (count($categories)){
							foreach ($categories as $value){?>
								<input type="checkbox" name="<?=$value['id']?>" value="<?=$value['id']?>" id="cat_<?=$value['id']?>">
								<label for="cat_<?=$value['id']?>"><?=$value['title']?></label>
							<?}?>
							<input id="p2" type="submit" value="Обновить">
						<?}?>
					</form>
				</div>
			</div>
		</div>
	</div>
<?}?>