<?php
use core\Managers;
$act = $_GET['act'];
if ($_POST['submit']){
	$id = $_GET['id'];
	$href = !$_POST['href'] ? translite($_POST['title']) : $_POST['href'];
	$href = str_replace(' ', '-', $href);
	$array = [
		'title' => $_POST['title'],
		'href' => $href,
		'pos' => $_POST['pos'] ? $_POST['pos'] : 0,
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
		} 
		message('Изменения успешно сохранены!');
		header('Location: ?view=categories');
	}
	// debug($array);
	// exit();
}
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
	default: view();
}
function view(){
	global $db, $page_title, $status;
	$categories = $db->select('categories', '*', "`parent_id`=0");
	$page_title = "Категории товаров";
	$status = "<a href='/admin'>Главная</a> > $page_title";
	$count_categories = count($categories);?>
	<div id="total" style="margin: 0">Всего: <?=$count_categories?></div>
	<div class="actions"><a href="?view=categories&act=add">Добавить</a></div>
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
					<div class="title"></div>
					<div class="value"><input type="submit" value="Сохранить"></div>
				</div>
			</form>
		</div>	
	</div>
	<div class="actions"><a href="<?=$_SERVER['HTTP_REFERER']?>">Назад</a></div>
<?}?>
