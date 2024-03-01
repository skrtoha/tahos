<?php

use core\Database;
use core\Managers;
$act = $_GET['act'];
switch ($act) {
	case 'add': 
		if (Managers::isActionForbidden('Точки выдачи', 'Добавление')){
			Managers::handlerAccessNotAllowed();
		}
		show_form('s_add'); 
		break;
	case 's_add':
		if (Managers::isActionForbidden('Точки выдачи', 'Добавление')){
			Managers::handlerAccessNotAllowed();
		}
		if (Database::getInstance()->insert('issues', $_POST)){
			message('Точка выдачи успешно сохранена!');
			header('Location: ?view=issues');
		}
	case 'change': show_form('s_change'); break;
	case 'delete':
		if (Managers::isActionForbidden('Точки выдачи', 'Удаление')){
			Managers::handlerAccessNotAllowed();
		}
		$res = Database::getInstance()->delete('issues', "`id`=".$_GET['id']);
		if ($res === true){
			message('Точка выдачи успешно удалена!');
			header('Location: ?view=issues');
		}
		else echo $res;
		break;
	case 's_change':
		if (Managers::isActionForbidden('Точки выдачи', 'Изменение')){
			Managers::handlerAccessNotAllowed();
		}
        $post = $_POST;
        if (!isset($post['is_main'])){
            $post['is_main'] = 0;
        }
        if (!isset($post['accept_cash'])){
            $post['accept_cash'] = 0;
        }
		$res = Database::getInstance()->update('issues', $post, "`id`=".$_GET['id']);
		if ($res === true){
			message('Пункт выдачи успешно изменен!');
			header('Location: ?view=issues');
            die();
		}
		else echo $res;
		break;
	default:
		view();
}
function view(){
	global $status, $db, $page_title;
	$all = $db->getCount('issues');
	$issues = $db->select('issues', '*', '', '', '', "", true);
	$page_title = "Точки выдачи";
	$status = "<a href='/admin'>Главная</a> > $page_title"?>
	<div id="total" style="margin-top: 10px;">Всего: <?=$all?></div>
	<div class="actions">
		<a style="position: relative;left: 14px;top: 5px;" href="?view=issues&act=add">Добавить</a>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Название</td>
			<td>Адрес</td>
			<td></td>
		</tr>
		<?if (count($issues)){
			foreach($issues as $id => $issue){?>
				<tr href="?view=issues&id=<?=$id?>&act=change">
					<td><?=$issue['title']?></td>
					<td><?=$issue['adres']?></td>
					<td>
						<a href="?view=issues&id=<?=$id?>&act=delete" class="delete_item">Удалить</a>
					</td>
				</tr>
			<?}
		}
		else{?>
			<tr><td colspan="4">Точки выдачи не найдено</td></tr>
		<?}?>
	</table>
<?}
function show_form($act){
	global $status, $db, $page_title;
	$id = $_GET['id'];
	switch($act){
		case 's_change':
			$issue = $db->select('issues', '*', "`id`=$id");
			$issue = $issue[0];
			$page_title = "Редактирование точки выдачи";
			break;
		case 's_add':
			$page_title = "Добавление точки выдачи";
			break;
	}
	$status = "<a href='/admin'>Главная</a> > <a href='?view=issues'>Точки выдачи</a> > $page_title"?>
	<div class="t_form">
		<div class="bg">
			<form action="?view=issues&id=<?=$id?>&act=<?=$act?>" method="post" enctype="multipart/form-data">
				<div class="field">
					<div class="title">Название</div>
					<div class="value"><input type=text name="title" value="<?=$issue['title']?>"></div>
				</div>
					<div class="field">
					<div class="title">Описание</div>
					<div class="value"><textarea class="need" name="desc"cols="30" rows="10"><?=$issue['desc']?></textarea></div>
				</div>
				<div class="field">
					<div class="title">Адрес</div>
					<div class="value"><input type=text name="adres" value="<?=$issue['adres']?>"></div>
				</div>
				<div class="field">
					<div class="title">Телефон</div>
					<div class="value"><input type=text name="telephone" value="<?=$issue['telephone']?>"></div>
				</div>
				<div class="field">
					<div class="title">E-mail</div>
					<div class="value"><input type=text name="email" value="<?=$issue['email']?>"></div>
				</div>
				<div class="field">
					<div class="title">Основной</div>
					<div class="value"><input <?=$issue['is_main'] ? 'checked' : ''?> type="checkbox" name="is_main" value="1"></div>
				</div>
                <div class="field">
                    <div class="title">Прием наличной<br>оплаты</div>
                    <div class="value"><input <?=$issue['accept_cash'] ? 'checked' : ''?> type="checkbox" name="accept_cash" value="1"></div>
                </div>
				<div class="field">
					<div class="title">Соцсети</div>
					<div class="value" id="admin_social">
						<table>
							<tr>
								<td>twitter</td>
								<td><input type="text" name="twitter" value="<?=$issue['twitter']?>"></td>
							</tr>
							<tr>
								<td>vk</td>
								<td><input type="text" name="vk" value="<?=$issue['vk']?>"></td>
							</tr>
							<tr>
								<td>facebook</td>
								<td><input type="text" name="facebook" value="<?=$issue['facebook']?>"></td>
							</tr>
							<tr>
								<td>google</td>
								<td><input type="text" name="google" value="<?=$issue['google']?>"></td>
							</tr>
							<tr>
								<td>ok</td>
								<td><input type="text" name="ok" value="<?=$issue['ok']?>"></td>
							</tr>
						</table>
					</div>
				</div>
				<div class="field">
					<div class="title">Место на карте</div>
					<div class="value">
						<p style="margin: 20px 0;">Кликните на карте для выбора места положения и нажмите "Сохранить"</p>
						<div id="map" style="width:100%;height:600px">
							<div id="popup_map" style="display: flex;height:600px"><img src="/images/preload.gif" alt=""></div>
						</div>
						<input type="hidden" name="coords" id="coords" value="<?=$issue['coords']?>">
					</div>
				</div>
				<?
				
				?>
				<div class="field">
					<div class="title"></div>
					<div class="value"><input type="submit" class="button" value="Сохранить"></div>
				</div>
			</form>
		</div>
	</div>
<?}?>