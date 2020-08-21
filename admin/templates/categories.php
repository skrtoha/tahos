<?php
use core\Managers;

if (isset($_FILES['photo'])){
	copy($_FILES['photo']['tmp_name'], $_SERVER['DOCUMENT_ROOT'].'/tmp/'.$_FILES['photo']['name']);?>
		<img id="uploadedPhoto" src="/tmp/<?=$_FILES['photo']['name']?>">
		<button id="savePhoto">Сохранить</button>
	<?
	exit();
}

$act = $_GET['act'];
if ($_POST['submit']){
	$id = $_GET['id'];
	$href = !$_POST['href'] ? translite($_POST['title']) : $_POST['href'];
	$href = str_replace(' ', '-', $href);
	$array = [
		'title' => $_POST['title'],
		'href' => $href,
		'pos' => $_POST['pos'] ? $_POST['pos'] : 0,
		'isShowOnMainPage' => $_POST['isShowOnMainPage']
	];
	$bl = true;
	if ($id) $where = "`href`='{$array['href']}' AND `id`!=$id";
	else $where = "`href`='{$array['href']}'";
	$c_href = $db->getCount('categories', $where);
	if ($c_href && !$_POST['href']){
		$bl = false;
		message('При получении автоматической ссылки произошла ошибка!', false);
	}
	elseif ($c_href){
		$bl = false;
		message('Такая ссылка уже присутствует!', false);
	}
	if ($bl){
		if ($id){
			if (Managers::isActionForbidden('Категории товаров', 'Изменение')){
				Managers::handlerAccessNotAllowed();
			} 
			$db->update('categories', $array, "`id`=$id");
		} 
		else{
			if (Managers::isActionForbidden('Категории товаров', 'Добавление')){
				Managers::handlerAccessNotAllowed();
			} 
			$db->insert('categories', $array, ['print_query' => false]);
			$id = $db->last_id();
		} 

		debug($_POST);
		$imgPath =  core\Config::$imgPath . '/categories/' . $id . '.jpg';
		if (!isset($_POST['photo']) && file_exists($imgPath)) unlink($imgPath);
		if (isset($_POST['photo']) && preg_match('/\/tmp\//', $_POST['photo'])){
			copy($_SERVER['DOCUMENT_ROOT'] . $_POST['photo'], $imgPath);
		}

		message('Изменения успешно сохранены!');
		header('Location: ?view=categories');
	}
}
// debug($_POST);
switch ($act) {
	case 'change': 
		if (Managers::isActionForbidden('Категории товаров', 'Изменение')){
			Managers::handlerAccessNotAllowed();
		} 
		show_form(); 
		break;
	case 'save': 
		show_form(); 
		break;
	case 'add': 
		if (Managers::isActionForbidden('Категории товаров', 'Добавление')){
			Managers::handlerAccessNotAllowed();
		} 
		show_form(); 
		break;
	case 'delete':
		if (Managers::isActionForbidden('Категории товаров', 'Удаление')){
			Managers::handlerAccessNotAllowed();
		} 
		if ($db->delete('categories', "`id`=".$_GET['id'])){
			message('Категория успешно удалена!');
			header('Location: ?view=categories');	
		}
		break;
	case 'subs':
		if (!empty($_POST)){
			$db->update('categories', ['parent_id' => $_POST['parent_id']], "`id` = {$_POST['id']}");
		}
		$page_title = 'Подкатегории';
		$status = '<a href="/">Главная</a> > <a href="?view=categories">Категории товаров</a>';
		$status .= " > $page_title";
		$res_subs = $db->query("
			SELECT
				sub.id,
				sub.title AS sub,
				sub.pos,
				sub.parent_id,
				main.title AS main,
				sub.href
			FROM
				#categories sub
			LEFT JOIN
				#categories main ON main.id = sub.parent_id
			WHERE
				sub.parent_id > 0
			ORDER BY
				sub.title
		", '');
		$subs = [];
		$mains = [];
		foreach($res_subs as $sub){
			$subs[$sub['id']]['title'] = $sub['sub'];
			$subs[$sub['id']]['parent_id'] = $sub['parent_id'];
			$mains[$sub['parent_id']] = $sub['main'];
		}
		subs($subs, $mains);
		break;
	case 'changeIsShowOnMainPage':
		$db->update('categories', ['isShowOnMainPage' => $_GET['isShowOnMainPage']], "`id` = {$_GET['category_id']}");
		header("Location: {$_SERVER['HTTP_REFERER']}");
		break;
	default: view();
}
function view(){
	global $db, $page_title, $status;
	$categories = $db->select('categories', '*', "`parent_id`=0");
	$page_title = "Категории товаров";
	$status = "<a href='/admin'>Главная</a> > $page_title";
	$count_categories = count($categories);?>
	<div id="total" style="margin: 0">Всего: <?=$count_categories?></div>
	<div class="actions">
		<a href="?view=categories&act=add">Добавить</a>
		<a href="?view=categories&act=subs">Подкатегории</a>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Заголовок</td>
			<td>Ссылка</td>
			<td>Поз.</td>
			<td></td>
			<td></td>
		</tr>
		<?foreach ($categories as $category) {?>
			<tr>
				<td><?=$category['title']?></td>
				<td>
					<?if ($category['href']){?>
						<a href="/category/<?=$category['href']?>"><?=$category['href']?></a>
					<?}
					else{?>
						Ссылка не задана
					<?}?>
				</td>
				<td><?=$category['pos']?></td>
				<td>
					<?$count = $db->getCount('categories', "`parent_id`=".$category['id']);?>
					<a href="?view=category&id=<?=$category['id']?>">Подкатегорий (<?=$count?>)</a>
				</td>
				<td>
					<a href="?view=categories&act=change&id=<?=$category['id']?>">Изменить</a>
					<a class="delete_item" href="?view=categories&act=delete&id=<?=$category['id']?>">Удалить</a>
				</td>
			</tr>
		<?}?>
	</table>
<?}
function show_form(){
	global $db, $page_title, $status;
	$id = $_GET['id'];
	if ($id){
		$page_title = "Редактирование категории";
		$category = $db->select('categories', '*', "`id`=$id");
		$array = $_POST['submit'] ? $_POST : $category[0];
	}
	else{
		$page_title = "Добавление новой категории";
		$array = $_POST;
	}
	$status = "<a href='/admin'>Главная</a> > <a href='?view=categories'>Категории товаров</a> > $page_title";
	?>
	<div class="t_form">
		<div class="bg">
			<form action="?view=categories&act=save&id=<?=$_GET['id']?>" method="post" enctype="multipart/form-data">
				<input type="hidden" name="submit" value="1">
				<div class="field">
					<div class="title">Название</div>
					<div class="value"><input type="text" name="title" value="<?=$array['title']?>" required></div>
				</div>
				<div class="field">
					<div class="title">Ссылка</div>
					<div class="value"><input type="text" name="href" value="<?=$array['href']?>"></div>
				</div>
				<div class="field">
					<div class="title">Позиция</div>
					<div class="value"><input type="text" name="pos" value="<?=$array['pos']?>"></div>
				</div>
				<div class="field">
					<div class="title">Показывать на<br>основной странице</div>
					<div class="value">
						<select name="isShowOnMainPage">
							<option <?=$array['isShowOnMainPage'] == 0 ? 'selected' : ''?> value="0">нет</option>
							<option <?=$array['isShowOnMainPage'] == 1 ? 'selected' : ''?> value="1">да</option>
						</select>
					</div>
				</div>
				<div class="field">
					<div class="title">Изображение на <br>главной странице</div>
					<div class="value">
						<?$imgPath = core\Config::$imgPath . '/categories/' . $id . '.jpg';?>
						<input class="<?=file_exists($imgPath) ? 'hidden' : ''?>" type="button" accept="image/*" value="Загрузить фото" id="buttonLoadPhoto">
						<ul class="photo" id="photos">
							<?if(file_exists($imgPath)){?>
								<li big="<?=core\Config::$imgUrl?>/categories/<?=$id?>.jpg">
									<div>
										<a table="fotos" class="delete_foto" href="#">Удалить</a>
									</div>
									<img src="<?=core\Config::$imgUrl?>/categories/<?=$id?>.jpg">
									<input type="hidden" name="photo" value="<?=core\Config::$imgUrl?>/categories/<?=$id?>.jpg">
								</li>
							<?}?>
						</ul>
					</div>
				</div>
				<div class="field">
					<div class="title"></div>
					<div class="value"><input type="submit" value="Сохранить"></div>
				</div>
				</div>
			</form>
			<form style="display: none" action="?view=categories&act=change&id=<?=$_GET['id']?>" enctype="multipart/form-data" method="post">
			<input id="loadPhoto" name="photo" type="file">
		</form>
		</div>	
	</div>
	<div class="actions"><a href="<?=$_SERVER['HTTP_REFERER']?>">Назад</a></div>
<?}
function subs($subs, $mains){?>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Название</td>
			<td>Категория</td>
		</tr>
		<?foreach ($subs as $sub_id => $sub){?>
			<tr>
				<td><?=$sub['title']?></td> 
				<td>
					<form method="post" class="subs">
						<input type="hidden" name="id" value="<?=$sub_id?>">
						<select name="parent_id">
							<?foreach($mains as $id => $title){?>
								<option <?=$sub['parent_id'] == $id ? 'selected' : ''?> value="<?=$id?>"><?=$title?></option>
							<?}?>
						</select>
					</form>
				</td>
			</tr>
		<?}?>
	</table>
<?}?>
