<?php
$act = $_GET['act'];
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])){
	print_r($_POST);
	exit();
};
error_reporting(E_ERROR);
switch ($act){
	case 'delete':
		$res = $db->delete('brends', "`id`=".$_GET['id']);
		if ($res === true){
			$db->delete('brends', '`parent_id`='.$_GET['id']);
			array_map('unlink', glob(core\Config::$imgPath . "/brends/{$_GET['id']}.*"));
			message('Бренд успешно удален!');
		}
		else message('Данный бренд привязан к номенклатуре!', false);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		break;
	case 'items': items(); break;
	case 'items_search': items_search(); break;
	case 'change': show_form('s_change');break;
	case 'add': show_form('s_add');break;
	case 'image_delete':
		array_map('unlink', glob(core\Config::$imgPath . "/brends/{$_GET['id']}.*"));
		header("Location: /admin/?view=brends&id={$_GET['id']}&act=change");
		break;
	case 's_change':
		foreach ($_POST as $key => $value) $_POST[$key] = $value;
		if (!empty($_FILES['image'])){
			$f = $_FILES['image'];
			if ($f['type'] == 'image/svg+xml'){
				move_uploaded_file($f['tmp_name'], core\Config::$imgPath . "/brends/{$_GET['id']}.svg");
			} 
			else brend_set_image($f);
		}
		if ($db->update('brends', $_POST, "`id`=".$_GET['id'])){
			message('Бренд успешно изменен!');
			// echo get_from_uri($_GET['from']);
			// debug($_GET);
			if ($_GET['from']) header("Location: ".get_from_uri($_GET['from']));
			else header("Location: ?view=brends");
		}
		break;
	case 's_add':
		foreach ($_POST as $key => $value) $_POST[$key] = $value;
		// print_r($_GET);
		// exit();
		$brend_title = $_POST['title'];
		$_POST['href'] = translite($brend_title);
		$_POST['href'] = preg_replace('/[\W\s]+/', '-', $_POST['href']);
		if ($db->getCount('brends', "`title`='$brend_title'")){
			message('Такое имя уже присутствует!', false);
			show_form('s_add');
		}
		elseif ($db->insert('brends', $_POST)){
			if (!empty($_FILES['image'])) brend_set_image($_FILES['image'], $db->last_id());
			message('Бренд успешно добавлен!');
			if ($_GET['from_item'] == 'new_item') header('Location: ?view=item&act=add&new_brend='.$db->getMax('brends', 'id'));
			elseif ($_GET['from_item']) header('Location: ?view=item&id='.$_GET['from_item'].'&new_brend='.$db->getMax('brends', 'id'));
			else header("Location: ?view=brends");
		}
		break;
	case 'subbrends':
		subbrends();
		break;
	case 'chb': chb(); break;
	default:
		view();
}
function view(){
	global $status, $db, $page_title;
	require_once('templates/pagination.php');
	$search = $_GET['search'] ? $_GET['search'] : $_POST['search'];
	$search = str_replace("'", "\'", $search);
	$select = "
		SELECT b.id, b.title
		FROM #brends b 
		WHERE b.parent_id=0
	";
	$count = "
		SELECT COUNT(b.id) as count
		FROM #brends b 
		WHERE b.parent_id=0
	";
	if ($search){
		$select .= " AND `title` LIKE '%$search%'";
		$count .= " AND `title` LIKE '%$search%'";
	} 
	$all = $db->count_unique($count, false);
	$perPage = 30;
	$linkLimit = 10;
	$page = $_GET['page'] ? $_GET['page'] : 1;
	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ? $chank[$page] : 0;
	$select .= " ORDER BY b.title LIMIT $start,$perPage";
	$brends = $db->select_unique($select, '');
	$page_title = "Бренды товаров";
	$status = "<a href='/admin'>Главная</a> > $page_title"?>
	<div id="total" style="margin-top: 10px;">Всего: <?=$all?></div>
	<div class="actions">
		<form style="margin-top: -3px;float: left;margin-bottom: 10px;" action="?view=brends" method="post">
			<input style="width: 264px;" required type="text" name="search" value="<?=$search?>" placeholder="Поиск по бренду">
			<input type="submit" value="Искать">
		</form>
		<a style="position: relative;left: 14px;top: 5px;" href="?view=brends&act=add">Добавить</a>
		<a style="position: relative;left: 14px;top: 5px;" href="?view=brends&act=subbrends">Подбренды</a>
		<a style="position: relative;left: 14px;top: 5px;" href="?view=brends&act=chb">Перевести из одного бренда в другой</a>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Название</td>
			<td></td>
			<td></td>
		</tr>
		<?if (count($brends)){
			// p_arr($brends);
			foreach($brends as $brend){?>
				<tr>
					<td><?=$brend['title']?></td>
					<td>
						<a href="?view=brends&act=items&id=<?=$brend['id']?>">Товары</a>
					</td>
					<td>
						<a href="?view=brends&id=<?=$brend['id']?>&act=change">Изменить</a>
						<a href="?view=brends&id=<?=$brend['id']?>&act=delete" class="delete_item">Удалить</a>
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
	$search = str_replace("'", "\'", $search);
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
	require_once('templates/pagination.php');
	$perPage = 30;
	$linkLimit = 10;
	$where = "i.brend_id = {$_GET['id']}";
	if (isset($_GET['type_list'])){
		if ($_GET['type_list'] == 'blocked') $where .= " AND i.is_blocked = 1"; 
		if ($_GET['type_list'] == 'non_blocked') $where .= " AND i.is_blocked = 0"; 
	}
	if (isset($_GET['search']) && $_GET['search']) $where .= " AND i.article = '{$_GET['search']}'";
	$db->query("
		SELECT SQL_CALC_FOUND_ROWS
			i.id
		FROM
			#items i
		WHERE
			$where
	");
	$all = $db->found_rows();
	$page = $_GET['page'] ? $_GET['page'] : 1;
	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ? $chank[$page] : 0;
	$res_items = $db->query("
		SELECT
			i.id,
			i.brend_id,
			b.title AS brend,
			i.article,
			i.article_cat,
			i.title_full,
			i.barcode,
			GROUP_CONCAT(c.title SEPARATOR '; ') AS categories,
			i.is_blocked
		FROM
			#items i
		LEFT JOIN
			#brends b ON b.id = i.brend_id
		LEFT JOIN
			#categories_items ci ON ci.item_id = i.id
		LEFT JOIN
			#categories c ON c.id = ci.category_id
		WHERE
			$where
		GROUP BY
			i.id
		ORDER BY
			i.article
		LIMIT
			$start, $perPage
	", '');
	$page_title = "Товары бренда ".$db->getFieldOnID('brends', $id, 'title')."";
	$status = "<a href='/admin'>Главная</a> > <a href='?view=brends'>Бренды товаров</a> > $page_title";?>
	<div id="total" style="margin: 0">Всего: <?=$all?></div>
	<div class="actions">
		<form style="margin-top: -3px;float: left;margin-bottom: 10px;" method="get">
			<input type="hidden" name="view" value="brends">
			<input type="hidden" name="act" value="items">
			<input type="hidden" name="id" value="<?=$_GET['id']?>">
			<div class="radio">
				<label>
					<input <?=$_GET['type_list'] == 'all' || !isset($_GET['type_list']) ? 'checked' : ''?> type="radio" name="type_list" value="all">
					все
				</label>
				<label>
					<input <?=$_GET['type_list'] == 'blocked' ? 'checked' : ''?> type="radio" name="type_list" value="blocked">
					заблокированные
				</label>
				<label>
					<input <?=$_GET['type_list'] == 'non_blocked' ? 'checked' : ''?> type="radio" name="type_list" value="non_blocked">
					незаблокированные
				</label>
			</div>
			<input style="width: 264px;" type="text" name="search" value="<?=$_GET['search']?>" placeholder="Поиск по артикулу, vid и названию">
			<input type="submit" value="Искать">
		</form>
	</div>
	<table class="t_table" cellspacing="1" view="item">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Каталожный номер</td>
			<td>Название</td>
			<td>Штрих-код</td>
			<td>Категории</td>
		</tr>
		<?if ($res_items->num_rows){
			foreach($res_items as $item){?>
				<tr class="clickable_1 <?=$item['is_blocked'] ? 'is_blocked' : ''?>" value_id="<?=$item['id']?>">
					<td><?=$item['brend']?></td>
					<td><a target="_blank" href="?view=item&id=<?=$item['id']?>"><?=$item['article']?></a></td>
					<td><a target="_blank" href="?view=item&id=<?=$item['id']?>"><?=$item['article_cat']?></a></td>
					<td><?=$item['title_full']?></td>
					<td><a href="?view=item&id=<?=$item['id']?>"><?=$item['barcode']?></a></td>
					<td><?=$item['categories']?></td>
				</tr>
			<?}
		}
		else{?>
			<tr><td colspan="6">Товаров данного бренда не найдено</td></tr>
		<?}?>
	</table>
	<?pagination($chank, $page, ceil($all / $perPage), $href = "?view=brends&act=items&id={$_GET['id']}&page=");
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
	<div class="action">
		<a href="<?=$_SERVER['HTTP_REFERER']?>">Назад</a>
		<input type="file" multiple="multiple" name="" style="display: none">
		<input type="hidden" name="brend_id" value="<?=$_GET['id']?>">
		<a class="upload_files" href="#">Загрузить изображения товаров</a>
	</div>
	<div class="t_form">
		<div class="bg">
			<form action="?view=brends&id=<?=$id?>&act=<?=$act?>&from_item=<?=$_GET['from_item']?>&from=<?=$_GET['from']?>" method="post" enctype="multipart/form-data">
				<div class="field">
					<div class="title">Название</div>
					<div class="value"><input type=text name="title" value="<?=$brend['title']?>"></div>
				</div>
				<div class="field">
					<div class="title">Фото</div>
					<div class="value">
						<?if ($brend['id']){
							$filePath = array_shift(glob(core\Config::$imgPath . "/brends/{$brend['id']}.*"));
							$pathinfo = pathinfo($filePath);
							$src = core\Config::$imgUrl . "/brends/{$pathinfo['basename']}";
						} 
						?>
						<?if ($src){?>
							<img style="height: 150px" src="<?=$src?>" alt=""><br>
							<a href="?view=<?=$_GET['view']?>&id=<?=$brend['id']?>&act=image_delete">Удалить</a>
						<?}
						else{?>
							<input type="file" name="image">
						<?}?>
					</div>
				</div>
				<div class="field">
					<div class="title">Описание</div>
					<div class="value"><textarea type=text name="short_desc"><?=$brend['short_desc']?></textarea></div>
				</div>
				<div class="field">
					<?if ($id){?>
					<div class="title">Подбренды</div>
					<div class="value subbrends">
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
				<?if ($id){?>
					<div class="field">
						<div class="title">Поставщики</div>
						<div class="value subbrends">
							<a href="#" id="addProviderBrend">Добавить</a>
							<?$providerBrends = $db->query("
								SELECT
									pb.provider_id,
									p.title AS provider,
									pb.title
								FROM
									#provider_brends pb
								LEFT JOIN
									#providers p ON p.id = pb.provider_id
								WHERE
									 pb.brend_id = {$_GET['id']}
							", '');
							if ($providerBrends->num_rows){
								foreach($providerBrends as $value){?>
									<span class="subbrend" provider_id=<?=$value['provider_id']?>>
										<b><?=$value['provider']?>:</b> <?=$value['title']?>
										<span class="providerBrendDelete"></span>
									</span>
								<?}
							}?>
						</div>
					</div>
				<?}?>
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
	<div class="action"><a href="<?=$_SERVER['HTTP_REFERER']?>">Назад</a></div>
<?}
function chb(){
	global $status, $db, $page_title;
	if ($_POST['submit_chb']){
		$submit = submit_chb();
		message("Успешно выполнено! Изменено $submit товаров");
	}
	$page_title = 'Перевод из одного бренда в другой';
	$status = "<a href='/admin'>Главная</a> > <a href='?view=brends'>Бренды товаров</a> > $page_title";
	$brends = $db->select('brends', 'id,title', '`parent_id`=0');?>
	<div class="t_form">
		<div class="bg">
			<form method="post" enctype="multipart/form-data">
				<input type="hidden" name="submit_chb" value="1">
				<div class="field">
					<div class="title">Перевод из бренда в бренд</div>
					<div class="value">
						Из бренда
						<select name="brend_from">
							<option value="">выберите....</option>
							<?foreach($brends as $brend){?>
								<option value="<?=$brend['id']?>"><?=$brend['title']?></option>
							<?}?>
						</select>
						в бренд
						<select name="brend_to">
							<option value="">выберите....</option>
							<?foreach($brends as $brend){?>
								<option value="<?=$brend['id']?>"><?=$brend['title']?></option>
							<?}?>
						</select>
						<input type="submit" value="Перевести">
					</div>
				</div>
			</form>
		</div>
	</div>
<?}
function submit_chb(){
	global $db;
	$updated = 0;
	if (!$_POST['brend_from'] || !$_POST['brend_to']) return 'Выберите оба бренда!';
	$res = $db->query("
		SELECT
			id,
			article
		FROM
			#items
		WHERE
			brend_id={$_POST['brend_from']}
	", '');
	if (!$res->num_rows) return false;
	while ($row = $res->fetch_assoc()){
		$r = $db->update(
			'items',
			['brend_id' => $_POST['brend_to']],
			"id={$row['id']}"
		);
		if ($r === true) $updated++;
	}
	return $updated;
}
function brend_set_image($file, $id = 0){
	global $db;
	if (!$id) $id = $_GET['id'];
	$array = [];
	$name = $file['name'];
	if (!$name) {
		$array['error'] = '';
		return $array;
	}
	$dir = core\Config::$imgPath . "/brends/";
	require_once('../vendor/class.upload.php');
	if (!file_exists($dir)) mkdir($dir);
	$handle = new upload($file);
	if (!$handle->file_is_image){
		$array['error'] = 'Запрещенный вид файла!';
		return $array;
	}
	$need_ratio = [
		'x' => 250,
		'y' => 250
	];
	if ($handle->uploaded){
		$handle->file_new_name_body = $id;
		$handle->image_resize = true;
		$src_x = $handle->image_src_x;
		$src_y = $handle->image_src_y;
		if (($need_ratio['x'] / $need_ratio['y']) >= ($src_x / $src_y)){
			$handle->image_x = $need_ratio['x'];
			$handle->image_y = floor($need_ratio['x'] * $src_y / $src_x);
			$t = floor($handle->image_y / 2 - $need_ratio['y'] / 2);
			$handle->image_crop = "$t 0";
		}
		else{
			$handle->image_y = $need_ratio['y'];
			$handle->image_x = floor($need_ratio['y'] * $src_x / $src_y);
			$t = floor($handle->image_x / 2 - $need_ratio['x'] / 2);
			$handle->image_crop = "0 $t";
		}
		$handle->process($dir);
		if ($handle->processed) $array['img_ext'] = $handle->file_dst_name_ext;
		$handle->clean();
		$array['error'] = '';
		return $array['img_ext'];
	}
	else{
		$array['error'] = 'Произошла ошибка';
		return $array;
	}
}
?>
