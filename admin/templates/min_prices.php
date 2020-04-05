<?
if ($_POST['submit']) update_price_min();
function update_price_min(){
	global $db;
	$db->delete('prices', "item_id > 0");
	$res = $db->query("
		SELECT
			ci.item_id,
			MIN(si.price) AS price,
			MIN(ps.delivery) AS delivery
		FROM
			#categories_items ci
		LEFT JOIN
			#store_items si ON si.item_id = ci.item_id
		LEFT JOIN
			#provider_stores ps ON ps.id = si.store_id
		WHERE
			si.price IS NOT null
		GROUP BY
			ci.item_id
	", '');
	if (!$res->num_rows) return true;
	foreach($res as $item) $db->insert('prices', $item);
	message('Обновление прошло успешно!');
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
					<a href="/admin/templates/update_prices.php?cron=0">Обновить прайсы</a>
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