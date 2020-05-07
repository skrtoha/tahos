<?php
$act = $_GET['act'];
$id = $_GET['id'];
if ($_POST['form_submit']){
	$saveble = true;
	$id = $_GET['id'];
	// debug($_POST); //exit();
	foreach($_POST as $key => $value){
		// if (!$value) continue;
		// var_dump($value); //continue;
		switch ($key) {
			case 'user_type':
				$array['user_type'] = $value;
				if ($value == 'entity') $array['organization_name'] = $_POST['organization_name'];
				else $array['organization_name'] = '';
				break;
			case 'delivery_type':
				$array['delivery_type'] = $value;
				if ($_POST['delivery_type'] == 'Самовывоз') $array['issue_id'] = $_POST['issue_id'];
				// else $array['issue_id'] = '';
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
	if (!isset($array['show_all_analogies'])) $array['show_all_analogies'] = 0;
	if (!isset($array['bonus_program'])) $array['bonus_program'] = 0;
	if (!isset($array['allow_request_delete_item'])) $array['allow_request_delete_item'] = 0;
	// debug($array, 'array'); //exit();
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
			if (core\User::update($id, $array)) message('Изменения успешно сохранены!');
			// header("Location: ?view=users&id=$id&act=change");
		}
		else{
			if ($db->insert('users', $array)) message('Пользователь успешно добавлен!');
			// header("Location: ?view=users&id={$db->last_id()}&act=change");
		}
	}
}
switch ($act) {
	case 'add': show_form('s_add'); break;
	case 'change': show_form('s_change'); break;
	case 'funds': funds(); break;
	case 'user_order_add': user_order_add(); break;
	case 'form_operations': form_operations('add'); break;
	case 'search_history': search_history(); break;
	case 'basket': basket(); break;
	case 'checkOrderedWithReserved':
		debug($_GET);
		$res = $db->query("
			SELECT
				SUM(ov.price * ov.ordered) AS sum
			FROM
				#orders_values ov
			WHERE
				ov.user_id = {$_GET['user_id']} AND ov.status_id = 11
		", '');
		if ($res->num_rows) $array = $res->fetch_assoc();
		else $array['sum'] = 0;
		if (core\User::update($_GET['user_id'], ['reserved_funds' => $array['sum']]) === true){
			message("В зарезервировано установлено {$array['sum']}");
			header("Location: /admin/?view=users&act=change&id={$_GET['user_id']}");
		}
		else die("Произошла ошибка");
		break;
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
	$having = '';
	if (isset($_GET['search']) && $_GET['search']){
		$having = "HAVING `name` LIKE '%{$_GET['search']}%'";
		$page_title = 'Поиск по пользователям';
		$status = "<a href='/admin'>Главная</a> > <a href='?view=users'>Пользователи</a> > $page_title";
	}
	else{
		$page_title = "Пользователи";
		$status = "<a href='/admin'>Главная</a> > $page_title";
	}
	$query = "
		SELECT SQL_CALC_FOUND_ROWS
			u.id,
			IF(
				u.organization_name <> '',
				CONCAT_WS (' ', u.organization_name, ot.title),
				CONCAT_WS (' ', u.name_1, u.name_2, u.name_3)
			) AS name,
			u.telefon,
			u.email
		FROM 
			#users u
		LEFT JOIN 
			#organizations_types ot ON ot.id=u.organization_type
		$having
	";
	$db->query($query);
	$all = $db->found_rows();
	$perPage = 30;
	$linkLimit = 10;
	$page = $_GET['page'] ? $_GET['page'] : 1;
	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ? $chank[$page] : 0;
	$query = "
		$query
		ORDER BY
			name
		LIMIT
			$start, $perPage
	";
	$query = str_replace('SQL_CALC_FOUND_ROWS', '', $query);
	$users = $db->query($query, '');
	?>
	<div id="total" style="margin-top: 10px;">Всего: <?=$all?></div>
	<div class="actions">
		<form style="float: left;margin-bottom: 10px;">
			<input type="hidden" name="view" value="users">
			<input style="width: 264px;"  required type="text" name="search" value="<?=$_GET['search']?>" placeholder="Поиск по пользователям">
			<input type="submit" value="Искать">
		</form>
		<a style="position: relative;left: 14px;top: 5px;" href="?view=users&act=add">Добавить</a>
		<a style="position: relative;left: 14px;top: 5px;" href="?view=managers">Менеджеры</a>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>ФИО</td>
			<td>Телефон</td>
			<td>E-mail</td>
		</tr>
		<?if (count($users)){
			foreach($users as $user){?>
				<tr class="users_box" user_id="<?=$user['id']?>">
					<td label="ФИО"><?=$user['name']?></td>
					<td label="Телефон"><?=$user['telefon']?></td>
					<td label="E-mail"><?=$user['email']?></td>
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
			$array = $user;
			// debug($user);
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
		<a href="?view=users&act=user_order_add&id=<?=$id?>">Добавить заказ</a>
		<a href="?view=correspond&user_id=<?=$id?>">Написать сообщение</a>
		<a href="?view=order_issues&user_id=<?=$id?>">На выдачу</a>
		<a href="?view=order_issues&user_id=<?=$id?>&issued=1">Выданные</a>
		<a href="?view=users&act=checkOrderedWithReserved&user_id=<?=$id?>">Сверить "заказано"</a>
		<a href="?view=users&id=<?=$id?>&act=search_history">История поиска</a>
		<a href="?view=users&id=<?=$id?>&act=basket">Товары в корзине</a>
		<a href="?view=users&id=<?=$id?>&act=delete" class="delete_item">Удалить</a>
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
					<div class="title">Тип расчета</div>
					<div class="value">
						<select name="pay_type">
							<?$array = empty($_POST) ? $user : $_POST;
							$selected = $array['pay_type'] == 'наличный' ? 'selected' : '';?>
							<option <?=$selected?> value="наличный">наличный</option>
							<?$selected = $array['pay_type'] == 'безналичный' ? 'selected' : '';
							$disabled = $array['organization_name'] ? '' : 'disabled';?>
							<option <?=$disabled?> <?=$selected?> value="безналичный">безналичный</option>
						</select>
					</div>
				</div>
				<div class="field">
					<div class="title">Наценка при заказе вручную</div>
					<div class="value"><input type=text name="markup_handle_order" value="<?=$array['markup_handle_order']?>"></div>
				</div>
				<div class="field">
					<div class="title">Отсрочка платежа</div>
					<div class="value"><input type=text name="deferment_of_payment" value="<?=$array['deferment_of_payment']?>"></div>
				</div>
				<div class="field">
					<div class="title">Скидка</div>
					<div class="value"><input type=text name="discount" value="<?=$array['discount']?>"></div>
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
						<?if ($_POST['show_all_analogies']) $checked = $_POST['show_all_analogies'] ? 'checked' : '';
						else $checked = $user['show_all_analogies'] ? 'checked' : '';?>
						<input type="checkbox" name="show_all_analogies" <?=$checked?> value="1">
					</div>
				</div>
				<div class="field">
					<div class="title">Разрешить отправлять запрос на удаление товара</div>
					<div class="value">
						<?if ($_POST['allow_request_delete_item']) $checked = $_POST['allow_request_delete_item'] ? 'checked' : '';
						else $checked = $user['allow_request_delete_item'] ? 'checked' : '';?>
						<input type="checkbox" name="allow_request_delete_item" <?=$checked?> value="1">
					</div>
				</div>
				<div class="field">
					<div class="title">Бонусная программа</div>
					<div class="value">
						<?if ($_POST['bonus_program']) $checked = $_POST['bonus_program'] ? 'checked' : '';
						else $checked = $user['bonus_program'] ? 'checked' : '';?>
						<input type="checkbox" name="bonus_program" <?=$checked?> value="1">
					</div>
				</div>
				<div class="field">
					<div class="title">Количество бонусов</div>
					<div class="value">
						<?if ($_POST['bonus_count']) $bonus_count = $_POST['bonus_program'] ? $_POST['bonus_count'] : '0';
						else $bonus_count = $user['bonus_program'] ? $user['bonus_count'] : '0'?>
						<input type="text" name="bonus_count" value="<?=$bonus_count?>">
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
	debug($user, 'user');
	debug($_POST, 'post');
	if ($_POST['form_operations_submit']){
		$_POST['sum'] = str_replace(array(' ', ','), '', $_POST['sum']);
		$curr_bill = $user[0]['bill'] + $_POST['sum'];
		$array = array(
			'type_operation' => 1, 
			'sum' => $_POST['sum'], 
			'remainder' => $curr_bill, 
			'user_id' => $id, 
			'comment' => 'Пополнение '.$_POST['replenishment']
		);
		$db->insert('funds', $array, ['print_query' => false]);
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
	$funds = $db->select('funds', '*', $where, 'id', false, "$start,$perPage", true);?>
	<div id="total" style="margin-top: 10px;">Всего операций: <?=$all?></div>
	<div class="actions users">
		<a href="?view=users&act=form_operations&id=<?=$id?>">Пополнить счет</a>
		<?$bill = $user[0]['bill'] ? '<span class="price_format">'.$user[0]['bill'].'</span> руб.' : 'пусто';?>
		<span>На счету: <b><?=$bill?></b></span>
		<?$reserved_funds = $user[0]['reserved_funds'] ? '<span class="price_format">'.$user[0]['reserved_funds'].'</span> руб.' : 'пусто';?>
		<span>Зарезервировано: <b><?=$reserved_funds?></b></span>
		<?$value = $user[0]['bill'] - $user[0]['reserved_funds'];
		$available =  $value ? '<span class="price_format">'.$value.'</span> руб.' : 'пусто';?>
		<span>Доступно: <b><?=$available?></b></span>
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
					<td label="Дата"><?=date('d.m.Y H:i', strtotime($fund['created']))?></td>
					<td label="Тип операции"><?=$operations_types[$fund['type_operation']]?></td>
					<td label="Сумма" class="price_format"><?=$fund['sum']?> руб.</td>
					<td label="Остаток" class="price_format"><?=$fund['remainder']?></td>
					<td label="Комментарий"><?=stripslashes($fund['comment'])?></td>
				</tr>
			<?}
		}
		else{?>
			<tr><td colspan="6">Движений средст не найдено</td></tr>
		<?}?>
	</table>
	<?pagination($chank, $page, ceil($all / $perPage), $href = "?view=users&act=funds&id={$_GET['id']}&page=");
}
function user_order_add(){
	global $status, $db, $page_title;
	if (!empty($_POST)) setUserOrder();
	$page_title = 'Добавить заказ';
	$status = "
		<a href='/admin'>Главная</a> > <a href='?view=users'>Пользователи</a> > 
		<a href='/admin/?view=users&act=change&id={$_GET['id']}'>Редактирование пользователя</a> > $page_title
		";
	?>
	<div class="bg">
		<div class="field">
			<div class="value" id="user_order_add">
				<a href="#" class="show_form_search">Добавить</a>
				Наценка: <input style="width: 30px" type="text" readonly name="markup" value="<?=$db->getFieldOnID('users', $_GET['id'], 'markup_handle_order')?>">
				<div class="item_search">	
					<form name="search_items" method="post">
						<input type="text" value="" name="search" placeholder="Поиск по артикулу или каталожному номеру">
						<label>
							<input type="checkbox" name="exact_match" checked>
							Точное совпадение
						</label>
						<input type="submit" value="Поиск">
					</form>
					<div class="response"></div>
					<ul class="found_items"></ul>
				</div>
				<form class="added_items" method="post">
					<p><strong>Добавленные товары:</strong></p>
					<table class="t_table">
						<tr class="head">
							<th>Поставщик</th>
							<th>Бренд</th>
							<th>Артикул</th>
							<th>Название</th>
							<th>Цена</th>
							<th>Количество</th>
							<th>Сумма</th>
							<th>Комментарий</th>
							<th></th>
						</tr>
						<tr class="hiddable">
							<td colspan="9">Товары не добавлены</td>
						</tr>
					</table>
					<p>Итого: <span class="total">0</span> руб.</p>
					<div class="value">
						<input type="hidden" name="is_draft" value="0">
						<input is_draft="1" user_id="<?=$_GET['id']?>" type="submit" class="button" value="Сохранить как черновик">
						<input user_id="<?=$_GET['id']?>" type="submit" class="button" value="Сохранить и добавить">
					</div>
				</form>
			</div>
		</div>
	</div>
<?}
function setUserOrder(){
	global $db;
	// debug($_POST); exit();
	foreach($_POST as $name => $value){
		foreach($value as $item_id => $v){
			$array[$item_id][$name] = $v;
		}
	}
	$db->insert('orders', [
		'user_id' => $_GET['id'],
		'is_draft' => $_POST['is_draft'],
		'is_new' => $_POST['is_draft'] ? 0 : 1
	]);
	$order_id = $db->last_id();
	foreach($array as $item_id => $value){
		$db->insert('orders_values', [
			'order_id' => $order_id,
			'store_id' => $value['store_id'],
			'item_id' => $item_id,
			'user_id' => $_GET['id'],
			'price' => $value['price'],
			'quan' => $value['quan'],
			'comment' => $value['comment']
		]);
	};
	message('Заказ успешно сохранен!');
	header("Location: /admin/?view=orders&act=change&id=$order_id");
}
function search_history(){
	global $db, $status, $page_title;
	$res_search = $db->query("
		SELECT
			CASE
				WHEN s.type = 1 THEN \"article\"
				WHEN s.type = 2 THEN \"barcode\"
				WHEN s.type = 3 THEN \"VIN\"
			END as type,
			s.text,
			s.title,
			DATE_FORMAT(`date`, '%d.%m.%Y %H:%i:%s') as date
		FROM
			#search s
		WHERE
			user_id = {$_GET['id']}
	", '');
	$user = core\User::get($_GET['id']);
	$page_title = 'История поиска';
	$status = "<a href='/admin'>Главная</a> > <a href='?view=users'>Пользователи</a> > ";
	$status .= "<a href='?view=users&act=change&id={$_GET['id']}'>{$user['full_name']}</a> > $page_title";
	?>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Тип</td>
			<td>Текст</td>
			<td>Название</td>
			<td>Дата</td>
		</tr>
		<?if ($res_search->num_rows){
			foreach($res_search as $value){?>
				<tr>
					<td><?=$value['type']?></td>
					<td><?=$value['text']?></td>
					<td><?=$value['title']?></td>
					<td><?=$value['date']?></td>
				</tr>
			<?}
		}
		else{?>
			<tr><td colspan="4">Историю поиска не найдено</td></tr>
		<?}?>
	</table>
<?}
function basket(){
	global $db, $status, $page_title;
	$basket = core\Basket::get($_GET['id']);
	$user = core\User::get($_GET['id']);
	$page_title = 'Корзина';
	$status = "<a href='/admin'>Главная</a> > <a href='?view=users'>Пользователи</a> > ";
	$status .= "<a href='?view=users&act=change&id={$_GET['id']}'>{$user['full_name']}</a> > $page_title";
	?>
	<table style="margin-top: 10px;" class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Наименование</td>
			<td>Поставщик</td>
			<td>Срок</td>
			<td>Количество</td>
			<td>Цена</td>
			<td>Сумма</td>
		</tr>
		<?if (count($basket)){
			foreach($basket as $value){?>
				<tr>
					<td><?=$value['brend']?></td>
					<td><?=$value['article']?></td>
					<td><?=$value['title']?></td>
					<td><?=$value['cipher']?></td>
					<td><?=$value['delivery']?></td>
					<td><?=$value['quan']?></td>
					<td><?=$value['price']?></td>
					<td><?=$value['quan'] * $value['price']?></td>
				</tr>
			<?}
		}
		else{?>
			<tr><td colspan="4">Историю поиска не найдено</td></tr>
		<?}?>
	</table>
<?}
?>