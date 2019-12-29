<?php
$act = $_GET['act'];
switch ($act) {
	case 'delete':
		$res_1 = $db->delete('analogies', "`item_id`=".$_GET['item_id']." AND `item_diff`=".$_GET['delete_item']);
		$res_2 = $db->delete('analogies', "`item_id`=".$_GET['delete_item']." AND `item_diff`=".$_GET['item_id']);
		if ($res_1 and $res_2){
			message('Успешно удалено');
			header("Location: ?view=substitutes&act=item&item_id=".$_GET['item_id']);
		}
		break;
	case 'search': search(); break;
	case 'item_search': item_search(); break;
	case 'item': item(); break;
	case 'add_item':
		$res_1 = $db->insert('analogies', array('item_id' => $_GET['item_id'], 'item_diff' => $_GET['id']));
		$res_2 = $db->insert('analogies', array('item_id' => $_GET['id'], 'item_diff' => $_GET['item_id']));
		if ($res_1 and $res_2){
			message('Аналог успешно добавлен!');
			header('Location: ?view=analogies&act=item&item_id='.$_GET['item_id']);
		}
		break;
	case 'auth':
		$_SESSION['auth'] = 1;	
		break;
	default:
		view();
}
/**
 * @return [type]
 */
function view(){
	global $status, $db;
	require_once('templates/pagination.php');
	$where = "1 GROUP BY `item_id`";
	$all = count($db->select('analogies','id', $where));
	$perPage = 30;
	$linkLimit = 10;
	$page = $_GET['page'] ? $_GET['page'] : 1;
	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ? $chank[$page] : 0;
	$categories = $db->select('categories', '*', '', '', '', '', true);
	$analogies = $db->select('analogies', '*', $where, '', '', "$start,$perPage");
	$page_title = "Аналоги";
	$status = "<a href='/admin'>Главная</a> > $page_title"?>
	<div id="total" style="margin-top: 10px;">Всего: <?=$all?></div>
	<div class="actions">
		<form style="margin-top: -3px;float: left;margin-bottom: 10px;" action="?view=analogies&act=search" method="post">
			<input style="width: 264px;" type="text" name="search" value="" placeholder="Поиск по артикулу, vid и названию">
			<input type="submit" value="Искать">
		</form>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Артикул</td>
			<td>Название</td>
			<td>Бренд</td>
			<td>Штрих-код</td>
			<td>Категории</td>
			<td></td>
		</tr>
		<?if (count($analogies)){
			foreach($analogies as $analogy){
				$item = $db->select('items', 'id,article,title_full,barcode,brend_id', "`id`=".$analogy['item_id']);
				$item = $item[0]?>
				<tr>
					<td label="Артикул"><?=$item['article']?></td>
					<td label="Название"><?=$item['title_full']?></td>
					<td label="Бренд"><?=$db->getFieldOnID('brends', $item['brend_id'], 'title')?></td>
					<td label="Штрих-код"><?=$item['barcode']?></td>
					<td label="Категории">
						<?$categories_items = $db->select('categories_items', 'category_id', "`item_id`=".$item['id']);
						if (count($categories_items)){
							foreach ($categories_items as $category_item) {?>
								<a href="/admin/?view=category&id=<?=$category_item['category_id']?>"><?=$categories[$category_item['category_id']]['title']?></a>
							<?}
						}?>
					</td>
					<td label="">
						<?$count = $db->getCount('analogies', "`item_id`=".$analogy['item_id']);?>
						<a href="?view=analogies&act=item&item_id=<?=$analogy['item_id']?>">Аналоги(<?=$count?>)</a>
					</td>
				</tr>
			<?}	
		}
		else{?>
			<tr><td colspan="6">Аналогов не найдено</td></tr>
		<?}?>
	</table>
	<?pagination($chank, $page, ceil($all / $perPage), $href = "?view=analogies&page=");
}
function search(){
	global $status, $db;
	$search = $_POST['search'];
	if (!$search) header ('Location: ?view=analogies');
	$where = "`article`='$search' OR `barcode` LIKE '%$search%' OR `title_full` LIKE '%$search%'";
	$items = $db->select('items', 'id', $where);
	if (count($items)){
		$in = "";
		foreach ($items as $item) $in .= $item['id'].",";
		$in = substr($in, 0, -1);
		$where = "`item_id` IN ($in) GROUP BY `item_id`";
		$analogies = $db->select('analogies', '*', $where);
		$all = count($analogies);
	}
	$categories = $db->select('categories', '*', '', '', '', '', true);
	$page_title = "Поиск по Аналоговам";
	$status = "<a href='/admin'>Главная</a> > <a href='?view=analogies'>Аналоги</a> > $page_title"?>
	<div id="total">Всего: <?=$all?></div>
	<div class="actions" style="width: 100%">
		<form style="margin-top: -3px;float: left;margin-bottom: 10px;" action="?view=analogies&act=search" method="post">
			<input style="width: 264px;" type="text" name="search" value="<?=$_POST['search']?>" placeholder="Поиск">
			<input type="submit" value="Искать">
		</form>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Артикул</td>
			<td>Название</td>
			<td>Штрих-код</td>
			<td>Категории</td>
			<td></td>
		</tr>
		<?if (count($analogies)){
			foreach($analogies as $analogy){
				$item = $db->select('items', 'id,article,title_full,barcode', "`id`=".$analogy['item_id']);
				$item = $item[0]?>
				<tr>
					<td><?=$item['article']?></td>
					<td><?=$item['title_full']?></td>
					<td><?=$item['barcode']?></td>
					<td>
						<?$categories_items = $db->select('categories_items', 'category_id', "`item_id`=".$item['id']);
						if (count($categories_items)){
							foreach ($categories_items as $category_item) {?>
								<a href="/admin/?view=category&id=<?=$category_item['category_id']?>"><?=$categories[$category_item['category_id']]['title']?></a>
							<?}
						}?>
					</td>
					<td>
						<?$count = $db->getCount('analogies', "`item_id`=".$analogy['item_id']);?>
						<a href="?view=analogies&act=item&item_id=<?=$analogy['item_id']?>">Аналоги(<?=$count?>)</a>
					</td>
				</tr>
			<?}	
		}
		else{?>
			<tr><td colspan="5">Аналогов не найдено</td></tr>
		<?}?>
	</table>
<?}
function item(){
	global $status, $db;
	$item_id = $_GET['item_id'];
	$item = $db->select('items', 'id,article,title_full,barcode,brend_id', "`id`=$item_id");
	$item = $item[0];
	$page_title = "Аналоги для товара";
	$categories = $db->select('categories', 'id,title', "", '', '', '', true);
	$status = "<a href='/admin'>Главная</a> > <a href='?view=analogies'>Аналоги</a> > $page_title";
	$analogies = $db->select('analogies', 'item_diff', "`item_id`=$item_id");?>
	<a href="?view=item&id=<?=$item_id?>&act=change" style="margin-bottom: 10px;display: block">Карточка товара</a>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Название</td>
			<td>Штрих-код</td>
			<td>Категории</td>
		</tr>
		<tr>
			<td><?=$db->getFieldOnID('brends', $item['brend_id'], 'title')?></td>
			<td><a href="/admin/?view=item&id=<?=$item['id']?>"><?=$item['article']?></a></td>
			<td><?=$item['title_full']?></td>
			<td><?=$item['barcode']?></td>
			<td>
				<?$categories_items = $db->select('categories_items', 'category_id', "`item_id`=".$item['id']);
				if (count($categories_items)){
					foreach ($categories_items as $category_item) {?>
						<a href="/admin/?view=category&id=<?=$category_item['category_id']?>"><?=$categories[$category_item['category_id']]['title']?></a>
					<?}
				}?>
			</td>
		</tr>
	</table>
	<b style="display: block; margin: 10px 0">Аналоги:</b>
	<div id="total" style="margin-top: 20px">Всего: <?=count($analogies)?></div>
	<div class="actions">
		<form style="margin-top: -3px;float: left;margin-bottom: 10px;" action="?view=analogies&act=item_search&item_id=<?=$item_id?>" method="post">
			<input style="width: 264px;" type="text" name="search" value="<?=$_POST['search']?>" placeholder="Поиск для добавления">
			<input type="submit" value="Искать">
		</form>
	</div>
	<table style="" class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Название</td>
			<td>Штрих-код</td>
			<td>Категории</td>
			<td></td>
		</tr>
		<?if (count($analogies)){
			foreach($analogies as $analogy){
				$item = $db->select('items', 'id,article,title_full,barcode,brend_id', "`id`=".$analogy['item_diff']);
				$item = $item[0]?>
				<tr>
					<td><?=$db->getFieldOnID('brends', $item['brend_id'], 'title')?></td>
					<td><a href="?view=item&id=<?=$item['id']?>"><?=$item['article']?></a></td>
					<td><?=$item['title_full']?></td>
					<td><?=$item['barcode']?></td>
					<td>
						<?$categories_items = $db->select('categories_items', 'category_id', "`item_id`=".$item['id']);
						if (count($categories_items)){
							foreach ($categories_items as $category_item) {?>
								<a href="/admin/?view=category&id=<?=$category_item['category_id']?>"><?=$categories[$category_item['category_id']]['title']?></a>
							<?}
						}?>
					</td>
					<td><a class="delete_item" href="?view=analogies&act=delete&item_id=<?=$item_id?>&delete_item=<?=$item['id']?>">Удалить</a></td>
				</tr>
			<?}	
		}
		else{?>
			<tr><td colspan="6">Аналогов не найдено</td></tr>
		<?}?>
	</table>
<?}
function item_search(){
	global $status, $db, $page_title;
	$item_id = $_GET['item_id'];
	$item = $db->select('items', 'id,article,title_full,barcode,brend_id', "`id`=$item_id");
	$item = $item[0];
	$page_title = "Добавление аналогов для товара";
	$search = article_clear($_POST['search']);
	if (!$search) header ('Location: ?view=analogies');
	$is_subtitutes = $db->select('analogies', "item_diff", "`item_id`=$item_id");
	if (count($is_subtitutes)){
		$in = "";
		foreach ($is_subtitutes as $value) $in .= $value['item_diff'].',';
		$in = substr($in, 0, -1);
	} 
	if ($in) $where = "(`article`='$search' OR `id`=$search) AND `id` NOT IN ($in)";
	else $where = "`article`='$search'";
	$items = $db->select('items', 'title_full,id,article,barcode,brend_id', $where);
	$categories = $db->select('categories', '*', '', '', '', '', true);
	$status = "<a href='/admin'>Главная</a> > <a href='?view=analogies'>Аналоги</a> > $page_title";?>
	<b style="margin-bottom: 10px;display: block">Товар</b>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Название</td>
			<td>Штрих-код</td>
			<td>Категории</td>
			<td></td>
		</tr>
		<tr>
			<td><?=$db->getFieldOnID('brends', $item['brend_id'], 'title')?></td>
			<td><a href="?view=item&id=<?=$item['id']?>"><?=$item['article']?></a></td>
			<td><?=$item['title_full']?></td>
			<td><?=$item['barcode']?></td>
			<td>
				<?$categories_items = $db->select('categories_items', 'category_id', "`item_id`=".$item['id']);
				if (count($categories_items)){
					foreach ($categories_items as $category_item) {?>
						<a href="/admin/?view=category&id=<?=$category_item['category_id']?>"><?=$categories[$category_item['category_id']]['title']?></a>
					<?}
				}?>
			</td>
			<td></td>
		</tr>
	</table>
	<b style="display: block; margin: 10px 0">Найденные товары:</b>
	<div id="total">Всего: <?=count($items)?></div>
	<div class="actions" style="width: 100%">
		<form style="margin-top: -3px;float: left;margin-bottom: 10px;" action="?view=analogies&act=item_search&item_id=<?=$item_id?>" method="post">
			<input style="width: 264px;" type="text" name="search" value="<?=$_POST['search']?>" placeholder="Поиск">
			<input type="submit" value="Искать">
		</form>
	</div>
	<table style="" class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Название</td>
			<td>Штрих-код</td>
			<td>Категории</td>
			<td></td>
		</tr>
		<?if (count($items)){
			foreach($items as $item){?>
				<tr>
					<td><?=$db->getFieldOnID('brends', $item['brend_id'], 'title')?></td>
					<td><a href="?view=item&id=<?=$item['id']?>"><?=$item['article']?></a></td>
					<td><?=$item['title_full']?></td>
					<td><?=$item['barcode']?></td>
					<td>
						<?$categories_items = $db->select('categories_items', 'category_id', "`item_id`=".$item['id']);
						if (count($categories_items)){
							foreach ($categories_items as $category_item) {?>
								<a href="/admin/?view=category&id=<?=$category_item['category_id']?>"><?=$categories[$category_item['category_id']]['title']?></a>
							<?}
						}?>
					</td>
					<td><a href="?view=analogies&act=add_item&item_id=<?=$item_id?>&id=<?=$item['id']?>">Добавить</a></td>
				</tr>
			<?}	
		}
		else{?>
			<tr><td colspan="5">Товары не найдены</td></tr>
		<?}?>
	</table>
<?}
?>