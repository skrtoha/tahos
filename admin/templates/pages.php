<?php
$act = $_GET['act'];
switch ($act) {
	case 'change':
		show_form();
		break;
	case 'save':
		// print_r($_POST);
		$id = $_GET['id'];
		$title = $_POST['title'];
		$article = $_POST['article'];
		if ($db->update('pages', array('title' => $title, 'article' => $article), "`id`=$id")){
			setcookie('message', "Изменения успешно сохранены!");
			setcookie('message_type', "ок");
			header("Location: "."?view=pages");
		}
		break;
	default:
		view();
}
function view(){
	global $db, $page_title, $status;
	$page_title = 'Страницы сайта';
	$status = "<a href='/admin'>Главная</a> > Страницы сайта";
	$pages = $db->select('pages', '*');
	$count_pages = count($pages);?>
	<div id="total">Всего: <?=$count_pages?></div>
	<div class="actions"></div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Заголовок</td>
			<td>Действия</td>
		</tr>
		<?for($i = 0; $i < $count_pages; $i++){?>
			<tr>
				<td><?=$pages[$i]['title']?></td>
				<td><a href="?view=pages&id=<?=$pages[$i]['id']?>&act=change">Изменить</a></td>
			</tr>
		<?}?>
	</table>
<?}
function show_form(){
	global $db, $page_title, $status;
	$id = $_GET['id'];
	$page = $db->select('pages','*', '`id` = '.$id);
	$page_title = $page[0]['title'];
	$status = "<a href='/admin'>Главная</a> > <a href='?view=pages'>Страницы сайта</a> > $page_title";?>
	<div class="t_form">
		<div class="bg">
			<form action="?view=pages&act=save&id=<?=$id?>" method="post" enctype="multipart/form-data">
			<input type="hidden" value="<?=$page[0]['id']?>" name="id">
			<input type="hidden" value="pages" name="view">
			<div class="field">
				<div class="title">Заголовок</div>
				<div class="value"><input type=text name="title" value="<?=$page[0]['title']?>"></div>
			</div>
			<div class="field">
				<div class="title">Текст</div>
				<div class="value"><textarea name="article" class="htmlarea" style=""><?=$page[0]['article']?></textarea></div>
				<input type="hidden" id="tinyMCE" value="">
			</div>
			<div class="field">
				<div class="title"></div>
				<div class="value"><input type=submit class=button value="Сохранить"></div>
			</div>
			</form>
		</div>
	</div>
	<div class="actions"><a href="?view=pages">Назад</a></div>
<?}?>