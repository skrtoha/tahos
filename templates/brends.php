<?php
$act = $_GET['act'];
switch ($act){
	case 'delete':
		if ($db->getCount('items', '`brend_id`='.$_GET['id'])){
			message('Данный бренд уже привязан к товару!', false);
			header('Location: ?view=brends');
			exit();
		}
		if ($db->delete('brends', "`id`=".$_GET['id'])){
			$db->delete('brends', '`parent_id`='.$_GET['id']);
			message('Бренд успешно удален!');
			header("Location: ".$_SERVER['HTTP_REFERER']);
		}
		break;
	case 'items': items(); break;
	case 'items_search': items_search(); break;
	case 'change': show_form('s_change');break;
	case 'add': show_form('s_add');break;
	case 's_change':
		foreach ($_POST as $key => $value) $_POST[$key] = $value;
		if ($db->update('brends', $_POST, "`id`=".$_GET['id'])){
			message('Бренд успешно изменен!');
			header("Location: ?view=brends");
		}
		break;
	case 's_add':
		foreach ($_POST as $key => $value) $_POST[$key] = $value;
		// print_r($_GET);
		// exit();
		$brend_title = $_POST['title'];
		if ($db->getCount('brends', "`title`='$brend_title'")){
			message('Такое имя уже присутствует!', false);
			show_form('s_add');
		}
		elseif ($db->insert('brends', $_POST)){
			message('Бренд успешно добавлен!');
			if ($_GET['from_item'] == 'new_item') header('Location: ?view=item&act=add&new_brend='.$db->getMax('brends', 'id'));
			elseif ($_GET['from_item']) header('Location: ?view=item&id='.$_GET['from_item'].'&new_brend='.$db->getMax('brends', 'id'));
			else header("Location: ?view=brends");
		}
		break;
	case 'subbrends':
		subbrends();
		break;
	default:
		view();
}
function view(){
	global $status, $db, $page_title;
	require_once('templates/pagination.php');
	$where = '`parent_id`=0';
	$search = $_GET['search'] ? $_GET['search'] : $_POST['search'];
	if ($search) $where .= " AND `title` LIKE '%$search%'";
	$all = $db->getCount('brends', $where);
	$perPage = 30;
	$linkLimit = 10;
	$page = $_GET['page'] ? $_GET['page'] : 1;
	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ? $chank[$page] : 0;
	$brends = $db->select('brends', '*', $where, 'title', true, "$start,$perPage");
	$page_title = "Бренды товаров";
	$status = "<a href='/admin'>Главная</a> > $page_title"?>
	<div id="total" style="margin-top: 10px;">Всего: <?=$db->getCount('brends', $where)?></div>
	<div class="actions">
		<form style="margin-top: -3px;float: left;margin-bottom: 10px;" action="?view=brends" method="post">
			<input style="width: 264px;" required type="text" name="search" value="<?=$search?>" placeholder="Поиск по бренду">
			<input type="submit" value="Искать">
		</form>
		<a style="position: relative;left: 14px;top: 5px;" href="?view=brends&act=add">Добавить</a>
		<a style="position: relative;left: 14px;top: 5px;" href="?view=brends&act=subbrends">Подбренды</a>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Название</td>
			<td></td>
			<td></td>
		</tr>
		<?if (count($brends)){
			foreach($brends as $brend){?>
				<tr>
					<td><?=$brend['title']?></td>
					<td>
						<a href="?view=brends&act=items&id=<?=$brend['id']?>">Товары</a>
					</td>
					<td>
						<a href="?view=brends&id=<?=$brend['id']?>&act=change">Изменить</a>
						<?$count_items = $db->getCount('items', "`brend_id`={$brend['id']}");
						$count_subbrends = $db->getCount('brends', "`parent_id`={$brend['id']}");
						if (!$count_items && !$count_subbrends){?>
							<a href="?view=brends&id=<?=$brend['id']?>&act=delete" class="delete_item" brend_id="<?=$item['id']?>">Удалить</a>
						<?}?>
					</td>
				</tr>
			<?}
		}
		else{?>
			<tr>
				<td colspan="3">Брендов не найдено</td>
			</tr>
		<?}?>
	</table>
		<?pagination($chank, $page, ceil($all / $perPage), $href = "?view=brends&act=brends&search=$search&page=");
}
function subbrends(){
	global $status, $db, $page_title;
	require_once('templates/pagination.php');
	$where = '`parent_id`!=0';
	$search = $_GET['search'] ? $_GET['search'] : $_POST['search'];
	if ($search) $where .= " AND `title` LIKE '%$search%'";
	$all = $db->getCount('brends', $where);
	$perPage = 30;
	$linkLimit = 10;
	$page = $_GET['page'] ? $_GET['page'] : 1;
	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ? $chank[$page] : 0;
	$subbrends = $db->select('brends', '*', $where, 'title', true, "$start,$perPage");
	$page_title = "Подбренды";
	$status = "<a href='/admin'>Главная</a> > ";
	$status .= "<a href='?view=brends'>Бренды</a> > ";
	$status .= $page_title;
	$brends = $db->select('brends', '*', "`parent_id`=0", 'id', true, '', true);
	// debug($brends);
	?>
	<div id="total" style="margin-top: 10px;">Всего: <?=$db->getCount('brends', $where)?></div>
	<div class="actions">
		<form style="margin-top: -3px;float: left;margin-bottom: 10px;" action="?view=brends&act=subbrends" method="post">
			<input style="width: 264px;" required type="text" name="search" value="<?=$search?>" placeholder="Поиск по подбрендам">
			<input type="submit" value="Искать">
		</form>
	</div>
	<?if (count($brends)){?>
		<table class="t_table" cellspacing="1">
			<tr class="head">
				<td>Подбренд</td>
				<td>Бренд</td>
				<td></td>
			</tr>
			<?if (count($subbrends)){
				foreach($subbrends as $brend){?>
					<tr>
						<td><?=$brend['title']?></td>
						<td>
							<a href="?view=brends&id=<?=$brend['parent_id']?>&act=change">
								<?=$brends[$brend['parent_id']]['title']?>
							</a>
						</td>
						<td>
							<a href="?view=brends&id=<?=$brend['id']?>&act=change">Изменить</a>
							<a href="?view=brends&id=<?=$brend['id']?>&act=delete" class="delete_item" brend_id="<?=$item['id']?>">Удалить</a>
						</td>
					</tr>
				<?}
			}
			else{?>
				<tr>
					<td colspan="3">Брендов не найдено</td>
				</tr>
			<?}?>
		</table>
	<?}?>
	<?pagination($chank, $page, ceil($all / $perPage), $href = "?view=brends&act=subbrends&search=$search&page=");
}
function items(){
	global $status, $db, $page_title;
	$id = $_GET['id'];
	$where = "`brend_id`=$id";
	require_once('templates/pagination.php');
	$perPage = 30;
	$linkLimit = 10;
	$all = $db->getCount('items', $where);
	$page = $_GET['page'] ? $_GET['page'] : 1;
	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ? $chank[$page] : 0;
	$query = core\Item::getQueryItemInfo();
	$query .= "
		WHERE
			$where
		LIMIT
			$start,$perPage
	";
	$res_items = $db->query($query);
	$categories = $db->select('categories', '*', '', '', '', '', true);
	$page_title = "Товары бренда <b>".$db->getFieldOnID('brends', $id, 'title')."</b>";
	$status = "<a href='/admin'>Главная</a> > <a href='?view=brends'>Бренды товаров</a> > $page_title";?>
	<div id="total" style="margin: 0">Всего: <?=$all?></div>
	<div class="actions">
		<form style="margin-top: -3px;float: left;margin-bottom: 10px;" action="?view=brends&act=items_search&id=<?=$id?>" method="post">
			<input style="width: 264px;" required type="text" name="search" value="" placeholder="Поиск по артикулу, vid и названию">
			<input type="submit" value="Искать">
		</form>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Название</td>
			<td>Штрих-код</td>
		</tr>
		<?if ($res_items->num_rows){
			foreach($res_items as $item){?>
				<tr>
					<td><?=$db->getFieldOnID('brends', $item['brend_id'], 'title')?></td>
					<td><a href="?view=item&id=<?=$item['id']?>"><?=$item['article']?></a></td>
					<td><?=$item['title_full']?></td>
					<td><?=$item['barcode']?></td>
				</tr>
			<?}
		}
		else{?>
			<tr><td colspan="5">Товаров данного бренда не найдено</td></tr>
		<?}?>
	</table>
	<?pagination($chank, $page, ceil($all / $perPage), $href = "?view=brends&act=items&id=$id&page=");
}
function show_form($act){
	global $status, $db, $page_title;
	$id = $_GET['id'];
	switch($act){
		case 's_change':
			$brend = $db->select('brends', '*', "`id`=$id");
			$brend = $brend[0];
			$page_title = "Редактирование бренда";
			break;
		case 's_add':
			$page_title = "Добавление нового бренда";
			foreach ($_POST as $key => $value) $brend[$key] = $value;
			break;
	}
	$status = "<a href='/admin'>Главная</a> > <a href='?view=brends'>Бренды товаров</a> > $page_title"
	?>
	<div class="t_form">
		<div class="bg">
			<form action="?view=brends&id=<?=$id?>&act=<?=$act?>&from_item=<?=$_GET['from_item']?>" method="post" enctype="multipart/form-data">
				<div class="field">
					<div class="title">Название</div>
					<div class="value"><input type=text name="title" value="<?=$brend['title']?>"></div>
				</div>
				<div class="field">
					<div class="title">Описание</div>
					<div class="value"><textarea type=text name="short_desc"><?=$brend['short_desc']?></textarea></div>
				</div>
				<div class="field">
					<?if ($id){?>
					<div class="title">Подбренды</div>
					<div class="value" style="overflow: auto">
						<?$subbrends = $db->select('brends', 'id,title', "`parent_id`=$id");
						if (count($subbrends)){
							foreach ($subbrends as $subbrend){?>
								<span class="subbrend" subbrend_id=<?=$subbrend['id']?>>
									<?=$subbrend['title']?>
									<span class="subbrend_delete"></span>
								</span>
							<?}
						}
						else{?>
							<span id="no_brends">Подбрендов не найдено</span>
						<?}?>
							<a href="" id="add_subbrend" brend_id="<?=$id?>">Добавить</a>
					</div>
					<?}?>
				</div>
				<div class="field">
					<div class="title">Сайт</div>
					<div class="value"><input type=text name="site" value="<?=$brend['site']?>"></div>
				</div>
				<div class="field">
					<div class="title">Страна</div>
					<div class="value"><input type=text name="country" value="<?=$brend['country']?>"></div>
				</div>
				<div class="field">
					<div class="title"></div>
					<div class="value"><input type="submit" class="button" value="Сохранить"></div>
				</div>
			</form>
		</div>
	</div>
<?}?>