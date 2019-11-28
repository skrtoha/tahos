<?php
$act = $_GET['act'];
$id = $_GET['id'];
if ($_POST['form_submit']){
	debug($_POST); exit();
	$saveble = true;
	$id = $_GET['id'];
	foreach($_POST as $key => $value){
		switch ($key) {
			case 'user_type':
				$array['user_type'] = $value;
				if ($value == 'entity') $array['organization_name'] = $_POST['organization_name'];
				else $array['organization_name'] = '';
				break;
			case 'delivery_type':
				$array['delivery_type'] = $value;
				if ($_POST['delivery_type'] == 'Самовывоз') $array['issue_id'] = $_POST['issue_id'];
				else $array['issue_id'] = '';
				break;
			case 'pass':
				if ($value) $array['password'] = md5($value);
				break;
			case 'telefon':
				$array['telefon'] = str_replace(array('(', ')', ' ', '-'), '', $value);
				break;
			default: 
				if ($key != 'pass' and $key != 'form_submit') $array[$key] = $value;
		}
	}
	if (!$array['hide_analogies']) $array['hide_analogies'] = 0;
	// print_r($array);
	// exit();
	if ($array['user_type'] == 'entity' and !$array['organization_name']){
		message('Введите название организации!', false);
		$saveble = false;
	}
	if ($array['delivery_type'] == 'Самовывоз' and !$array['issue_id']){
		message('Выберите точку выдачи!', false);
		$saveble = false;
	}
	if ($saveble) {
		if ($_POST['form_submit'] == 1){
			if ($db->update('users', $array, "`id`=".$id)) message ('Изменения успешно сохранены!');
		}
		else{
			$array['date'] = time();
			if ($db->insert('users', $array)) message('Пользователь успешно добавлен!');
		}
		header("Location: ?view=users&id=$id&act=change");
	}
}
switch ($act) {
	case 'add': show_form('s_add'); break;
	case 'change': show_form('s_change'); break;
	case 'funds': funds(); break;
	case 'form_operations': form_operations('add'); break;
	case 'delete':
		if ($db->delete('users', "`id`=".$_GET['id'])){
			message('Пользователь успешно удален!');
			header('Location: ?view=users');
		}
		break;
	default:
		view();
}
function view(){
	global $status, $db, $page_title;
	require_once('templates/pagination.php');
	if ($_POST['search']){
		$search = $_POST['search'];
		$where = "`name_1` LIKE '%$search%' OR `name_2` LIKE '%$search%' OR `name_3` LIKE '%$search%'";
		$page_title = 'Поиск по пользователям';
		$status = "<a href='/admin'>Главная</a> > <a href='?view=users'>Пользователи</a> > $page_title";
	}
	else{
		$page_title = "Пользователи";
		$status = "<a href='/admin'>Главная</a> > $page_title";
	}
	$all = $db->getCount('users', $where);
	$perPage = 30;
	$linkLimit = 10;
	$page = $_GET['page'] ? $_GET['page'] : 1;
	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ? $chank[$page] : 0;
	$users = $db->select('users', '*', $where, 'date', false, "$start,$perPage", true);?>
	<div id="total" style="margin-top: 10px;">Всего: <?=$all?></div>
	<div class="actions">
		<form style="margin-top: -3px;float: left;margin-bottom: 10px;" action="?view=users&act=search" method="post">
			<input style="width: 264px;"  required type="text" name="search" value="<?=$search?>" placeholder="Поиск по пользователям">
			<input type="submit" value="Искать">
		</form>
		<a style="position: relative;left: 14px;top: 5px;" href="?view=users&act=add">Добавить</a>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>ФИО</td>
			<td>Телефон</td>
			<td>E-mail</td>
		</tr>
		<?if (count($users)){
			foreach($users as $id => $user){?>
				<tr class="users_box" user_id="<?=$id?>">
					<td><?=$user['name_1']?> <?=$user['name_2']?> <?=$user['name_3']?></td>
					<td><?=$user['telefon']?></td>
					<td><?=$user['email']?></td>
				</tr>
			<?}
		}
		else{?>
			<tr><td colspan="3">Пользователей не найдено</td></tr>
		<?}?>
	</table>
	<?pagination($chank, $page, ceil($all / $perPage), $href = "?view=users&page=");
}
function show_form($act){
	global $status, $db, $page_title;
	$id = $_GET['id'];
	switch($act){
		case 's_change':
			$user = $db->select('users', '*', "`id`=$id");
			$user = $user[0];
			// print_r($user); echo "<br><br>";
			$page_title = "Редактирование пользователя";
			break;
		case 's_add':
			$page_title = "Добавление пользователя";
			break;
	}
	$status = "<a href='/admin'>Главная</a> > <a href='?view=users'>Пользователи</a> > $page_title";
	if ($act == 's_change'){?>
		<a href="?view=users&act=funds&id=<?=$id?>">Движение средств</a>
		<a href="?view=orders&act=user_orders&id=<?=$id?>">Заказы</a>
		<a href="?view=correspond&user_id=<?=$id?>">Написать сообщение</a>
		<a style="float: right" href="?view=users&id=<?=$id?>&act=delete" class="delete_item">Удалить</a>
		<div style="width: 100%; height: 10px"></div>
	<?}?>
	<div class="t_form">
		<div class="bg">
			<form method="post" enctype="multipart/form-data">
				<input type="hidden" name="form_submit" value="<?=$act == 's_change' ? 1 : 2?>">
				<div class="field">
					<div class="title">Фамилия</div>
					<div class="value"><input type=text name="name_1" value="<?=$_POST['name_1'] ? $_POST['name_1'] : $user['name_1']?>"></div>
				</div>
				<div class="field">
					<div class="title">Имя</div>
					<div class="value"><input type=text name="name_2" value="<?=$_POST['name_2'] ? $_POST['name_2'] : $user['name_2']?>"></div>
				</div>
				<div class="field">
					<div class="title">Отчество</div>
					<div class="value"><input type=text name="name_3" value="<?=$_POST['name_3'] ? $_POST['name_3'] : $user['name_3']?>"></div>
				</div>
				<div class="field">
					<div class="title">Тип</div>
					<div class="value">
						<?if ($_POST['form_submit']){
							$checked_1 = $_POST['user_type'] == 'private' ? 'checked' : '';
							$checked_2 = $_POST['user_type'] == 'entity' ? 'checked' : '';
							$disabled = $_POST['user_type'] == 'private' ? 'disabled' : ''; 
							$organization_name = $_POST['organization_name'];
						}
						else{
							$checked_1 = $user['user_type'] == 'private' ? 'checked' : '';
							$checked_2 = $user['user_type'] == 'entity' ? 'checked' : '';
							$disabled = $user['user_type'] == 'private' ? 'disabled' : ''; 
							$organization_name = $user['organization_name'];
						}?>
						<input type="radio" <?=$act == 's_add' ? 'checked' : ''?> value="private" name="user_type" id="user_type_1" <?=$checked_1?>>
						<label for="user_type_1">Физическое лицо</label><br>
						<div style="height: 10px"></div>
						<input type="radio" value="entity" name="user_type" id="user_type_2" <?=$checked_2?>>
						<label for="user_type_2">Организация</label>
						<br>
						<div style="height: 10px"></div>
						<?$organizations_types = $db->select('organizations_types', '*');?>
						<select <?=$disabled?> name="organization_type">
						<option value="">ничего не выбрано</option>
							<?foreach($organizations_types as $organization_type){
								if ($_POST['form_submit']) $selected = $_POST['organization_type'] == $organization_type['id'] ? 'selected' : '';
								else $selected = $user['organization_type'] == $organization_type['id'] ? 'selected' : '';
								?>
								<option <?=$selected?> value="<?=$organization_type['id']?>"><?=$organization_type['title']?></option>
							<?}?>
						</select>
						<input type="text"  <?=$act == 's_add' ? 'disabled' : ''?> style="margin-top: 10px" name="organization_name" value="<?=$organization_name?>" <?=$disabled?>>
					</div>
				</div>
				<div class="field">
					<div class="title">E-mail</div>
					<div class="value"><input type=text name="email" value="<?=$_POST['email'] ? $_POST['email'] : $user['email']?>"></div>
				</div>
				<div class="field">
					<div class="title">Телефон</div>
					<div class="value"><input type=text name="telefon" value="<?=$_POST['telefon'] ? $_POST['telefon'] : $user['telefon']?>"></div>
				</div>
				<div class="field">
					<div class="title">Фактический адрес</div>
					<div class="value"><input type=text name="adres" value="<?=$_POST['adres'] ? $_POST['adres'] : $user['adres']?>"></div>
				</div>
				<div class="field">
					<div class="title">Тип доставки</div>
					<div class="value">
						<?if ($_POST['form_submit'] == 1){
							$checked_1 = $_POST['delivery_type'] == 'Доставка' ? 'checked' : '';
							$checked_2 = $_POST['delivery_type'] == 'Самовывоз' ? 'checked' : '';
						}
						else{
							$checked_1 = $user['delivery_type'] == 'Доставка' ? 'checked' : '';
							$checked_2 = $user['delivery_type'] == 'Самовывоз' ? 'checked' : '';
						}?>
						<input <?=$act == 's_add' ? 'checked' : ''?> type="radio" value="Доставка" name="delivery_type" id="delivery_type_1" <?=$checked_1?>>
						<label for="delivery_type_1">Доставка</label>
						<input type="radio" value="Самовывоз" name="delivery_type" id="delivery_type_2" <?=$checked_2?>>
						<label for="delivery_type_2">Самовывоз</label>
					</div>
				</div>
				<div class="field">
					<div class="title">Точка выдачи</div>
					<div class="value">
						<?if ($_POST['form_submit'] == 1) $disabled = $_POST['delivery_type'] == 'Доставка' ? 'disabled' : '';
						else $disabled = $user['delivery_type'] == 'Доставка' ? 'disabled' : '';?>
						<select <?=$act == 's_add' ? 'disabled' : ''?> <?=$disabled?> name="issue_id">
							<option value="">ничего не выбрано</option>
							<?$issues = $db->select('issues', 'id,title', '', '', '', '');
							if (count($issues)){
								foreach($issues as $issue){
									if ($_POST['form_submit'] == 1) $selected = $_POST['issue_id'] == $issue['id'] ? 'selected' : '';
									else $selected = $user['issue_id'] == $issue['id'] ? 'selected' : ''?>
									<option <?=$selected?> value="<?=$issue['id']?>"><?=$issue['title']?></option>
								<?}
							}?>
						</select>
					</div>
				</div>
				<div class="field">
					<div class="title">Валюта</div>
					<div class="value">
						<?$currencies = $db->select('currencies', '*');?>
						<select name="currency_id">
							<?foreach($currencies as $currency){
								if ($_POST['form_submit']) $selected = $_POST['currency_id'] == $currency['id'] ? 'selected' : '';
								else $selected = $user['currency_id'] == $currency['id'] ? 'selected' : ''?>
								<option <?=$selected?> value="<?=$currency['id']?>"><?=$currency['title']?></option>
							<?}?>
						</select>
					</div>
				</div>
				<div class="field">
					<div class="title">Показывать все аналоги</div>
					<div class="value">
						<?if ($_POST['hide_analogies']) $checked = $_POST['hide_analogies'] ? 'checked' : '';
						else $checked = $user['hide_analogies'] ? 'checked' : '';?>
						<input type="checkbox" name="hide_analogies" <?=$checked?> value="1">
					</div>
				</div>
				<div class="field">
					<div class="title">Наценка</div>
					<div class="value"><input type=text name="markup" value="<?=$_POST['markup'] ? $_POST['markup'] : $user['markup']?>"></div>
				</div>
				<div class="field">
					<div class="title">Пароль
						<?if ($act == 's_change'){?>
							<span style="display:block;font-size:12px;margin-top: 5px;font-weight: 400">Заполните поле для сброса пароля</span>
						<?}?>
					</div>
					<div class="value">
						<input type="text" name="pass">
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
function form_operations($act){
	global $status, $db, $page_title;
	$id = $_GET['id'];
	$user = $db->select('users', 'name_1,name_2,name_3,bill', '`id`='.$id);
	if ($_POST['form_operations_submit']){
		$_POST['sum'] = str_replace(array(' ', ','), '', $_POST['sum']);
		$curr_bill = $user[0]['bill'] + $_POST['sum'];
		$array = array('date' => time(), 
									'type_operation' => 1, 
									'sum' => $_POST['sum'], 
									'remainder' => $curr_bill, 
									'user_id' => $id, 
									'comment' => 'Пополнение '.$_POST['replenishment']);
		$db->insert('funds', $array);
		$db->update('users', array('bill' => $curr_bill), '`id`='.$id);
		message('Счет успешно пополнен!');
		header('Location: ?view=users&act=funds&id='.$id);
	}
	$fio = $user[0]['name_1'].' '.$user[0]['name_2'].' '.$user[0]['name_3'];
	$page_title = 'Пополение счета';
	$status = "<a href='/admin'>Главная</a> > <a href='?view=users'>Пользователи</a> > ";
	$status .= "<a href='?view=users&act=change&id=$id'>$fio</a> > ";
	$status .= "<a href='?view=users&act=funds&id=1'>Движение средств</a> > $page_title";?>
	<div class="t_form">
		<div class="bg">
			<form method="post" enctype="multipart/form-data">
				<input type="hidden" user_id=<?=$id?>>
				<input type="hidden" name="form_operations_submit" value="<?=$act == 'add' ? 1 : 2?>">
				<div class="field">
					<div class="title">Сумма, руб.</div>
					<div class="value"><input class="price_format" type="text" name="sum" required value="<?=$_POST['sum']?>"></div>
				</div>
				<div class="field">
					<div class="title">Источник пополнеия</div>
					<div class="value">
						<?$replenishments = $db->select('replenishments', '*');?>
						<select name="replenishment">
							<option value="">...ничего не выбрано</option>
						<?foreach($replenishments as $replenishment){?>
							<option value="<?=$replenishment['title']?>"><?=$replenishment['title']?></option>
						<?}?>
						</select>
					</div>
				</div>
				<div class="field">
					<div class="title"></div>
					<div class="value"><input type="submit" value="Сохранить"></div>
				</div>
			</form>
		</div>
	</div>
<?}
function funds(){
	global $status, $db, $page_title;
	$operations_types = array(1 => 'Пополнение счета', 2 => 'Списание средств', 3 => 'Резервирование средств', 4 => 'Отмена резервирования');
	$id = $_GET['id'];
	require_once('templates/pagination.php');
	$user = $db->select('users', 'name_1,name_2,name_3', "`id`=$id");
	$fio = $user[0]['name_1'].' '.$user[0]['name_2'].' '.$user[0]['name_3'];
	$page_title = 'Движение средств';
	$status = "<a href='/admin'>Главная</a> > <a href='?view=users'>Пользователи</a> > ";
	$status .= "<a href='?view=users&act=change&id=$id'>$fio</a> > $page_title";
	$where =  "`user_id`=$id AND `type_operation` NOT IN (3,4)";
	$all = $db->getCount('funds', $where);
	$perPage = 30;
	$linkLimit = 10;
	$user = $db->select('users', 'name_1,name_2,name_3,bill,reserved_funds', "`id`=$id");
	$page = $_GET['page'] ? $_GET['page'] : 1;
	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ? $chank[$page] : 0;
	$funds = $db->select('funds', '*', $where, 'date', false, "$start,$perPage", true);?>
	<div id="total" style="margin-top: 10px;">Всего операций: <?=$all?></div>
	<div class="actions">
		<a href="?view=users&act=form_operations&id=<?=$id?>">Пополнить счет</a>
		<?$bill = $user[0]['bill'] ? '<span class="price_format">'.$user[0]['bill'].'</span> руб.' : 'пусто';?>
		<span>На счету: <b><?=$bill?></b></span>
		<?$reserved_funds = $user[0]['reserved_funds'] ? '<span class="price_format">'.$user[0]['reserved_funds'].'</span> руб.' : 'пусто';?>
		<span>Зарезервировано: <b><?=$reserved_funds?></b></span>
		<?$value = $user[0]['bill'] - $user[0]['reserved_funds'];
		$available =  $value ? '<span class="price_format">'.$value.'</span> руб.' : 'пусто';?>
		<span style="margin-left: 10px">Доступно: <b><?=$available?></b></span>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Дата</td>
			<td>Тип операции</td>
			<td>Сумма</td>
			<td>Отстаток</td>
			<td>Комментарий</td>
		</tr>
		<?if (count($funds)){
			foreach($funds as $id => $fund){?>
				<tr>
					<td><?=date('d.m.Y H:i', $fund['date'])?></td>
					<td><?=$operations_types[$fund['type_operation']]?></td>
					<td class="price_format"><?=$fund['sum']?> руб.</td>
					<td class="price_format"><?=$fund['remainder']?></td>
					<td><?=stripslashes($fund['comment'])?></td>
				</tr>
			<?}
		}
		else{?>
			<tr><td colspan="6">Движений средст не найдено</td></tr>
		<?}?>
	</table>
	<?pagination($chank, $page, ceil($all / $perPage), $href = "?view=users&act=funds&id=$id&page=");
}
?>