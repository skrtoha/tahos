<?php
$act = $_GET['act'];
switch ($act) {
	case 'delete':
		if ($db->delete('items', "`id`=".$_GET['id'])){
			$db->query("
				UPDATE #settings SET `countItems` = `countItems` - 1 WHERE `id`=1
			");
			message('Успешно удалено!');
			header("Location: ?view=items");
		}
		break;
	case 'search': search(); break;
	case 'block_item': 
		core\Timer::start();
		core\Item::blockItem(); 
		echo "Обработка заняла ".core\Timer::end()." секунд";
		break;
	default:
		view();
}
function view(){
	global $status, $db, $page_title, $settings;
	require_once('templates/pagination.php');
	$all = $settings['countItems'];
	$perPage = 30;
	$linkLimit = 10;
	$page = $_GET['page'] ? $_GET['page'] : 1;
	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ? $chank[$page] : 0;
	$items = $db->select('items', 'title_full,id,article,article_cat,barcode,brend_id', '', 'id', false, "$start,$perPage");
	$page_title = "Номенклатура";
	$status = "<a href='/admin'>Главная</a> > $page_title"?>
	<div id="total" style="margin-top: 10px;">Всего: <?=$all?></div>
	<div class="actions items" class="" style="">
		<form action="?view=items&act=search" method="post">
			<input style="width: 264px;" type="text" name="search" value="" placeholder="Поиск по артикулу, vid и названию" required>
			<input type="submit" value="Искать">
		</form>
		<a href="?view=item&act=add">Добавить</a>
		<a href="?view=items&act=block_item">Заблокировать товар</a>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Каталожный номер</td>
			<td>Название</td>
			<td>Штрих-код</td>
			<td>Категории</td>
		</tr>
		<?if (count($items)){
			$db->isProfiling = false;
			foreach($items as $item){?>
				<tr class="items_box" item_id="<?=$item['id']?>">
					<td label="Бренд"><?=$db->getFieldOnID('brends', $item['brend_id'], 'title')?></td>
					<td label="Артикул"><?=$item['article']?></td>
					<td label="Каталожный номер"><?=$item['article_cat']?></td>
					<td label="Название"><?=$item['title_full']?></td>
					<td label="Штрих-код"><?=$item['barcode']?></td>
					<td label="Категории">
						<?$categories_items = $db->select('categories_items', 'category_id', "`item_id`=".$item['id']);
						if (!empty($categories_items)){
							foreach ($categories_items as $category_item) {?>
								<a href="?view=category&act=items&id=<?=$category_item['category_id']?>"><?=$db->getFieldOnID('categories', $category_item['category_id'], 'title')?></a>
							<?}
							
						}?>
					</td>
				</tr>
			<?}
			$db->isProfiling = true;
		}
		else{?>
			<tr>
				<td colspan="5">Номенклатура пуста</td>
			</tr>
		<?}?>
	</table>
	<?pagination($chank, $page, ceil($all / $perPage), $href = "?view=items&page=");
}
function search(){
	global $status, $db;
	$search = article_clear($_POST['search']);
	if (!$search) header ('Location: ?view=items');
	$where = "
		`article`='$search' OR 
		`barcode`='$search' OR 
		`id`='$search'
	";
	$all = $db->getCount('items', $where);
	$items = $db->select('items', 'title_full,id,article,barcode,brend_id', $where);
	$page_title = "Поиск по номенклатуре";
	$categories = $db->select('categories', '*', '', '', '', '', true);
	$status = "<a href='/admin'>Главная</a> > <a href='?view=items'>Номенклатура</a> > $page_title"?>
	<div id="total">Всего: <?=$all?></div>
	<div class="actions" style="width: 100%">
		<form style="margin-top: -3px;float: left;margin-bottom: 10px;" action="?view=items&act=search" method="post">
			<input style="width: 264px;" type="text" name="search" value="<?=$_POST['search']?>" placeholder="Поиск">
			<input type="submit" value="Искать">
		</form>
		<a style="margin-left: 10px;" href="?view=item&act=add">Добавить</a>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Название</td>
			<td>Штрих-код</td>
			<td>Категории</td>
		</tr>
		<?if (count($items)){
			foreach($items as $item){?>
			<tr class="items_box" item_id="<?=$item['id']?>">
				<td label="Бренд"><?=$db->getFieldOnID('brends', $item['brend_id'], 'title')?></td>
				<td label="Артикул"><?=$item['article']?></td>
				<td label="Название"><?=$item['title_full']?></td>
				<td label="Штрих-код"><?=$item['barcode']?></td>
				<td label="Категории">
					<?$categories_items = $db->select('categories_items', 'category_id', "`item_id`=".$item['id']);
					if (count($categories_items)){
						foreach ($categories_items as $category_item) {?>
							<a href="?view=category&act=items&id=<?=$category_item['category_id']?>"><?=$db->getFieldOnID('categories', $category_item['category_id'], 'title')?></a>
						<?}
					}?>
				</td>
			</tr>
		<?}
		}
		else{?>
			<tr><td colspan="5">Товаров не найдено</td></tr>
		<?}?>
	</table>
<?}?>