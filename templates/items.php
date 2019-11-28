<?php
$act = $_GET['act'];
switch ($act) {
	case 'delete':
		if ($db->delete('items', "`id`=".$_GET['id'])){
			message('Успешно удалено!');
			header("Location: ?view=items");
		}
		break;
	case 'search': search(); break;
	default:
		view();
}
function view(){
	global $status, $db, $page_title;
	require_once('templates/pagination.php');
	$all = $db->getCount('items');
	$perPage = 30;
	$linkLimit = 10;
	$page = $_GET['page'] ? $_GET['page'] : 1;
	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ? $chank[$page] : 0;
	$items = $db->select('items', 'title_full,id,article,barcode,brend_id', '', 'id', false, "$start,$perPage");
	$page_title = "Номенклатура";
	$status = "<a href='/admin'>Главная</a> > $page_title"?>
	<div id="total" style="margin-top: 10px;">Всего: <?=$all?></div>
	<div class="actions" style="">
		<form style="margin-top: -3px;float: left;margin-bottom: 10px;" action="?view=items&act=search" method="post">
			<input style="width: 264px;" type="text" name="search" value="" placeholder="Поиск по артикулу, vid и названию" required>
			<input type="submit" value="Искать">
		</form>
		<a style="position: relative;left: 14px;top: 5px;" href="?view=item&act=add">Добавить</a>
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
					<td><?=$db->getFieldOnID('brends', $item['brend_id'], 'title')?></td>
					<td><?=$item['article']?></td>
					<td><?=$item['title_full']?></td>
					<td><?=$item['barcode']?></td>
					<td>
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
			<tr>
				<td colspan="5">Номенклатура пуста</td>
			</tr>
		<?}?>
	</table>
	<?pagination($chank, $page, ceil($all / $perPage), $href = "?view=items&page=");
}
function search(){
	global $status, $db;
	$search = str_replace(array('-',' ', '.', ',', '/', '(', ')'), '', $_POST['search']);
	if (!$search) header ('Location: ?view=items');
	$where = "`article`='$search' OR `barcode` LIKE '%$search%' OR `title_full` LIKE '%$search%'";
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
				<td><?=$db->getFieldOnID('brends', $item['brend_id'], 'title')?></td>
				<td><?=$item['article']?></td>
				<td><?=$item['title_full']?></td>
				<td><?=$item['barcode']?></td>
				<td>
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