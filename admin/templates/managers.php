<?php
use core\Managers;
use admin\functions\LeftMenu;
$page_title = "Менеджеры";
$status = "<a href='/admin'>Главная</a> > <a href='?view=users'>Пользователи</a> > ";
switch($_GET['act']){
	case 'manager':
		if(!empty($_POST)){
			if (isset($_GET['id'])){
				if (Managers::isActionForbidden('Менеджеры', 'Изменение')){
					Managers::handlerAccessNotAllowed();
				}
				Managers::update($_GET['id'], $_POST);
			} 
			else{
				if (Managers::isActionForbidden('Менеджеры', 'Добавление')){
					Managers::handlerAccessNotAllowed();
				}
				$res = Managers::add($_POST);
				if (is_numeric($res)){
					message('Успешно добавлено!');
					header("Location: /admin/?view=managers&act=manager&id=$res");
				}
				else message($res, false);
			} 
		}
		if (isset($_GET['id'])){
			$res_manager = Managers::get(['id' => $_GET['id']]);
			$manager = $res_manager->fetch_assoc();
			$manager['permissions'] = json_decode($manager['permissions'], true);
			$page_title = $manager['first_name'];
			if (isset($manager['last_name'])) $page_title .= " {$manager['last_name']}";
		}
		else $page_title = 'Добавление менеджера';
		$array = empty($_POST) ? $manager : $_POST;
		$status .= "<a href='?view=managers'>Менеджеры</a> > $page_title";
		manager($array, Managers::getGroups());
		break;
	case 'add':
		if (Managers::isActionForbidden('Менеджеры', 'Добавление')){
			Managers::handlerAccessNotAllowed();
		}
		manager([], Managers::getGroups());
		$page_title = "Добавление менеджера";
		$status .= "<a href='?view=managers'>Менеджеры</a> > $page_title";
		break;
	case 'groups':
		$page_title = "Группы менеджеров";
		$status .= "<a href='?view=managers'>Менеджеры</a> > $page_title";
		groups(Managers::getGroups());
		break;
	case 'group':
		// debug($_POST);
		if(!empty($_POST)){
			if (isset($_GET['id'])){
				if (Managers::isActionForbidden('Менеджеры', 'Изменение')){
					Managers::handlerAccessNotAllowed();
				}
				Managers::updateGroup($_GET['id'], $_POST);
			} 
			else{
				if (Managers::isActionForbidden('Менеджеры', 'Добавление')){
					Managers::handlerAccessNotAllowed();
				}
				$res = Managers::addGroup($_POST);
				if (is_numeric($res)){
					message('Успешно добавлено!');
					header("Location: /admin/?view=managers&act=group&id=$res");
				}
				else message($res, false);
			} 
		}
		if (isset($_GET['id'])){
			$res_group = Managers::getGroups(['id' => $_GET['id']]);
			$group = $res_group->fetch_assoc();
			$group['permissions'] = json_decode($group['permissions'], true);
			$page_title = $group['title'];
		}
		else $page_title = 'Добавление группы';
		$array = empty($_POST) ? $group : $_POST;
		$status .= "<a href='?view=managers'>Менеджеры</a> >";
		$status .= "<a href='/admin/?view=managers&act=groups'>Группы</a> > $page_title";
		group($array);
		break;
	case 'add_group':
		if (Managers::isActionForbidden('Менеджеры', 'Добавление')){
			Managers::handlerAccessNotAllowed();
		}
		$page_title = "Добавление группы";
		$status .= "<a href='?view=managers'>Менеджеры</a> >";
		$status .= "<a href='/admin/?view=managers&act=groups'>Группы</a> > $page_title";
		group();
		break;
	default:
		$status .= "$page_title";
		managers(Managers::get());
}
function managers(mysqli_result $res_managers){?>
	<div id="total">Всего: <?=$res_managers->num_rows?></div>
	<div class="actions">
		<a href="/admin/?view=managers&act=add">Добавить</a>
		<a href="/admin/?view=managers&act=groups">Группы</a>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Имя</td>
			<td>Фамилия</td>
			<td>Группа</td>
            <td></td>
		</tr>
		<?if ($res_managers->num_rows){
			foreach($res_managers as $m){?>
				<tr data-manager-id="<?=$m['id']?>">
					<td><?=$m['first_name']?></td>
					<td><?=$m['last_name']?></td>
					<td><?=$m['group_title']?></td>
                    <td><span title="Удалить" class="icon-cross1"></span></td>
				</tr>
			<?}
		}?>
	</table>
<?}
function groups($res_groups){?>
	<div id="total">Всего: <?=$res_groups->num_rows?></div>
	<div class="actions">
		<a href="/admin/?view=managers&act=add_group">Добавить</a>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Название</td>
		</tr>
		<?if ($res_groups->num_rows){
			foreach($res_groups as $g){?>
				<tr group_id="<?=$g['id']?>">
					<td><?=$g['title']?></td>
				</tr>
			<?}
		}?>
	</table>
<?}
function manager($manager = [], $res_groups){
	?>
	<div class="t_form">
		<div class="bg">
			<form method="post" action="/admin/?view=managers&act=manager<?=isset($_GET['id']) ? "&id={$_GET['id']}" : ''?>" enctype="multipart/form-data">
				<div class="field">
					<div class="title">Логин</div>
					<div class="value">
						<input type="text" required name="login" value="<?=$manager['login']?>">
					</div>
				</div>
				<div class="field">
					<div class="title">Имя</div>
					<div class="value">
						<input type="text" name="first_name" value="<?=$manager['first_name']?>">
					</div>
				</div>
				<div class="field">
					<div class="title">Фамилия</div>
					<div class="value"><input type="text" name="last_name" value="<?=$manager['last_name']?>"></div>
				</div>
				<div class="field">
					<div class="title">Пароль</div>
					<div class="value"><input <?=empty($manager) ? 'required' : ''?> type="text" name="password" value=""></div>
				</div>
				<div class="field">
					<div class="title">Заблокировать</div>
					<div class="value">
						<input <?=$manager['is_blocked'] ? 'checked' : ''?> type="checkbox" name="is_blocked" value="1">
					</div>
				</div>
				<div class="field">
					<div class="title">Группа</div>
					<div class="value">
						<?if ($res_groups->num_rows){?>
							<select name="group_id">
								<?foreach($res_groups as $group){?>
									<option <?=$group['id'] == $manager['group_id'] ? 'selected' : ''?> value="<?=$group['id']?>"><?=$group['title']?></option>
								<?}?>
							</select>
						<?}?>
					</div>
				</div>
				<div class="field">
					<div class="title"></div>
					<div class="value"><input type="submit" class="button" value="Сохранить"></div>
				</div>
			</form>
		</div>
	</div>
<?}
function group($group = []){
	if ($group) $p = $group['permissions'];?>
	<div class="t_form">
		<div class="bg">
			<form method="post" action="/admin/?view=managers&act=group<?=isset($_GET['id']) ? "&id={$_GET['id']}" : ''?>" enctype="multipart/form-data">
				<div class="field">
					<div class="title">Название</div>
					<div class="value">
						<input type="text" required name="title" value="<?=$group['title']?>">
					</div>
				</div>
				<div class="field">
					<div class="title">Разрешения</div>
					<div class="value permissions">
						<ul>
							<?foreach(LeftMenu::$commonPermisions as $key => $value){
								$title = is_array($value) ? $key : $value;
								?>
								<li class="parent">
									<label>
										<input <?=isset($p[$title]) ? 'checked' : ''?> type="checkbox" name="permissions[<?=$title?>]" value="1">
										<?=$title?>
									</label>
									<?if (is_array($value)){?>
										<ul>
											<?foreach($value as $v){?>
												<li>
													<label>
														<input type="checkbox" <?=isset($p[$title][$v]) ? 'checked' : ''?> name="permissions[<?=$title?>][<?=$v?>]" value="1">
														<?=$v?>
													</label>
												</li>
											<?}?>
										</ul>
									<?}?>
								</li>
							<?}?>
						</ul>
					</div>
				</div>
				<div class="field">
					<div class="title"></div>
					<div class="value"><input type="submit" class="button" value="Сохранить"></div>
				</div>
			</form>
		</div>
	</div>
<?}
?>
