<?php
/** @var $db \core\Database $act */

use core\UserAddress;

$act = $_GET['act'];
$id = $_GET['id'];
$GLOBALS['user'] = $_GET['id'];
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
			case 'phone':
				$array['phone'] = str_replace(array('(', ')', ' ', '-'), '', $value);
				break;
			default: 
				if ($key != 'pass' and $key != 'form_submit') $array[$key] = $value;
		}
	}
	if (!isset($array['show_all_analogies'])) $array['show_all_analogies'] = 0;
	if (!isset($array['bonus_program'])) $array['bonus_program'] = 0;
	if (!isset($array['allow_request_delete_item'])) $array['allow_request_delete_item'] = 0;
	if (!isset($array['showProvider'])) $array['showProvider'] = 0;
	// debug($array, 'array'); exit();
	if ($array['user_type'] == 'entity' and !$array['organization_name']){
		message('Введите название организации!', false);
		$saveble = false;
	}
	if ($array['delivery_type'] == 'Самовывоз' and !$array['issue_id']){
		message('Выберите точку выдачи!', false);
		$saveble = false;
	}
    if ($array['user_type'] == 'private' && $array['pay_type'] == 'Безналичный'){
        $array['pay_type'] = 'Наличный';
    }
	if ($saveble) {
		if ($_POST['form_submit'] == 1){
			if (core\User::update($id, $array)){
                \core\User::setAddress(
                        $_GET['id'],
                        $_POST['addressee'],
                        $_POST['default_address'],
                        $_POST['address_id']
                );
                message('Изменения успешно сохранены!');
            }
			header("Location: ?view=users&id=$id&act=change");
            die();
		}
		else{
			if ($db->insert('users', $array)) message('Пользователь успешно добавлен!');
			header("Location: ?view=users&id={$db->last_id()}&act=change");
		}
	}
}
if (isset($_GET['ajax'])){
    switch($_GET['ajax']){
        case 'history_search':
            $start = ($_GET['pageNumber'] - 1) * $_GET['pageSize'];
            $params = [
                'fields_vin' => "
                    'VIN' AS type,
                    NULL AS item_id,
                    CONCAT(sv.vin, '-', sv.title) AS search,
                    sv.date
                ",
                'fields_items' => "
                    'Номенклатура' AS type,
                    i.id AS item_id,
                    CONCAT(b.title, '-', i.article) AS search,
                    si.date
                ",
                'where_vin' => getWhere('vin', $_GET),
                'where_items' => getWhere('items', $_GET),
                'order' => 'date DESC',
                'having' => '',
                'limit' => "$start, {$_GET['pageSize']}"
            ];
            $type = $_GET['type'] ?? '';
            $resultDb = $db->query(buildQuery($params, $type));
            $result = [];
            if (!$resultDb->num_rows) break;
            foreach($resultDb as $value){
                $dateTime = new DateTime($value['date']);
                $value['date'] = $dateTime->format('d.m.Y H:i:s');
                $result[] = $value;
            }
            break;
        case 'history_search_count':
            echo getTotalCount($_GET);
            die();
    }
    echo json_encode($result);
    exit();
};
switch ($act) {
	case 'add': show_form('s_add'); break;
	case 'change': show_form('s_change'); break;
	case 'funds': funds(); break;
	case 'user_order_add': user_order_add(); break;
	case 'form_operations': form_operations('add'); break;
	case 'search_history':
        $totalCount = getTotalCount($_GET);
	    search_history($totalCount);
	    break;
	case 'basket': basket(); break;
	case 'checkOrderedWithReserved':
		$res = $db->query("
			SELECT
				o.user_id,
				SUM(ov.price * ov.ordered) AS sum
			FROM
				#orders_values ov
			LEFT JOIN
				#orders o ON o.id = ov.order_id
			WHERE
				ov.status_id IN (11, 3)
			GROUP BY
				o.user_id
		", '');
		$updatedUsers = [];
		foreach($res as $row){
			$updatedUsers[] = $row['user_id'];
			core\User::update($row['user_id'], ['reserved_funds' => $row['sum']]);
		}
		$db->update('users', ['reserved_funds' => 0], '`id` NOT IN (' . implode(',', $updatedUsers) . ')');
		message('Успешно обновлено!');
		header("Location: /admin/?view=users");
		break;
	case 'delete':
		if ($db->delete('users', "`id`=".$_GET['id'])){
			message('Пользователь успешно удален!');
			header('Location: ?view=users');
		}
		break;
	case 'usersWithWithdraw':
		$page_title = 'Отрицательный баланс';
		$status = '<a href="/">Главная</a> > <a href="/admin/?view=users">Пользователи</a> > ' . $page_title;
		$res_users = core\User::get(['withWithdraw' => true]);
		usersWithWithdraw($res_users);
		break;
	default:
		view();
}
function usersWithWithdraw(mysqli_result $res_users){?>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>ФИО</td>
			<td>Телефон</td>
			<td>Баланс</td>
		</tr>
		<?if ($res_users->num_rows){
			foreach($res_users as $user){?>
				<tr class="users_box" user_id="<?=$user['id']?>">
					<td label="ФИО"><?=$user['full_name']?></td>
					<td label="E-mail"><?=$user['email']?></td>
					<td label="Баланс"><?=$user['bill']?></td>
				</tr>
			<?}
		}
		else{?>
			<tr><td colspan="3">Пользователей не найдено</td></tr>
		<?}?>
	</table>
<?}
function getWhere($type, $filters){
    $output = '';
    switch($type){
        case 'vin': $output .= "sv.user_id = {$filters['id']} AND "; break;
        case 'items': $output .= "si.user_id = {$filters['id']} AND "; break;
    }
    foreach($filters as $key => $value){
        switch($key){
            case 'search':
                switch($type){
                    case 'vin':
                        $output .= "CONCAT(sv.vin, '-', sv.title) LIKE '%$value%' AND ";
                        break;
                    case 'items':
                        $output .= "CONCAT(b.title, '-', i.article) like '%$value%' AND ";
                        break;
                }
                break;
        }
    }
    return substr($output, 0, -5);
}
function getTotalCount($filters){
    global $db;
    $params  = [
        'fields_vin' => "COUNT(*) as cnt",
        'fields_items' => "COUNT(*) as cnt",
        'where_vin' => getWhere('vin', $_GET),
        'where_items' => getWhere('items', $_GET),
        'order' => '',
        'having' => '',
        'limit' => ''
    ];
    $type = isset($filters['type']) ? $filters['type'] : '';
    $resultCount = $db->query(buildQuery($params, $type));
    $totalCount = 0;
    foreach($resultCount as $value) $totalCount += $value['cnt'];
    return $totalCount;
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
			" . core\User::getUserFullNameForQuery() . " AS name,
			u.phone,
			u.email,
			DATE_FORMAT(u.created, '%d.%m.%Y %H:%i:%s') AS created
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
	$page = $_GET['page'] ?: 1;
	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ?? 0;
    $direction = $_GET['direction'] ?? 'asc';
    $directionHref = $direction == 'asc' ? 'desc' : 'asc';
    $sort = $_GET['sort'] ?? 'name';
	$query = "
		$query
		ORDER BY
			$sort $direction
		LIMIT
			$start, $perPage
	";
	$query = str_replace('SQL_CALC_FOUND_ROWS', '', $query);
	$users = $db->query($query, '');
    $direction = $_GET['direction'] == 'asc' ? 'desc' : 'asc';
    $urlSort = "/admin/?view=users&direction=$directionHref";
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
		<a style="position: relative;left: 14px;top: 5px;" href="?view=users&act=checkOrderedWithReserved">Сверить "заказано"</a>
		<a style="position: relative;left: 14px;top: 5px;" href="?view=users&act=usersWithWithdraw">Отрицательный баланс</a>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>
                <a class="sort" title="Сортировка" href="<?=$urlSort?>&sort=name">ФИО</a>
            </td>
			<td><a class="sort" title="Сортировка" href="<?=$urlSort?>&sort=phone">Телефон</a></td>
			<td><a class="sort" title="Сортировка" href="<?=$urlSort?>&sort=email">E-mail</a></td>
			<td><a class="sort" title="Сортировка" href="<?=$urlSort?>&sort=u.created">Дата создания</a></td>
		</tr>
		<?if (count($users)){
			foreach($users as $user){?>
				<tr class="users_box" user_id="<?=$user['id']?>">
					<td label="ФИО"><?=$user['name']?></td>
					<td label="Телефон"><?=$user['phone']?></td>
					<td label="E-mail"><?=$user['email']?></td>
					<td label="Дата создания"><?=$user['created']?></td>
				</tr>
			<?}
		}
		else{?>
			<tr><td colspan="3">Пользователей не найдено</td></tr>
		<?}?>
	</table>
    <?
        $href = '/admin/?view=users';
        if ($_GET['sort']) $href .= "&sort={$_GET['sort']}";
        if ($_GET['direction']) $href .= "&direction={$_GET['direction']}";
        $href .= '&page=';
    ?>
	<?pagination($chank, $page, ceil($all / $perPage), $href);
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
		<?=User::getHtmlActions($id)?>
	<?}?>
	<input type="hidden" name="user_id" value="<?=$_GET['id']?>">
	<div class="t_form">
		<div class="bg">
			<form method="post" enctype="multipart/form-data" id="user">
				<input type="hidden" name="form_submit" value="<?=$act == 's_change' ? 1 : 2?>">
				<div class="field">
					<div class="title">Фамилия</div>
					<div class="value"><input type=text name="name_1" value="<?=$_POST['name_1'] ?: $user['name_1']?>"></div>
				</div>
				<div class="field">
					<div class="title">Имя</div>
					<div class="value"><input type=text name="name_2" value="<?=$_POST['name_2'] ?: $user['name_2']?>"></div>
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
							$selected = $array['pay_type'] == 'Наличный' ? 'selected' : '';?>
							<option <?=$selected?> value="Наличный">Наличный</option>
							<?$selected = $array['pay_type'] == 'Безналичный' ? 'selected' : '';
							$disabled = $array['organization_name'] ? '' : 'disabled';?>
							<option <?=$disabled?> <?=$selected?> value="Безналичный">Безналичный</option>
                            <?$selected = $array['pay_type'] == 'Онлайн' ? 'selected' : '';?>
                            <option <?=$selected?> value="Онлайн">Онлайн</option>
						</select>
					</div>
				</div>
				<div class="field">
					<div class="title">Наценка при заказе вручную</div>
					<div class="value"><input type=text name="markup_handle_order" value="<?=$array['markup_handle_order']?>"></div>
				</div>
                <div class="field">
                    <div class="title">Максимальное кол-во соединений</div>
                    <div class="value"><input type="text" name="max_connections" value="<?=$array['max_connections']?>"></div>
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
					<div class="value"><input type=text name="phone" value="<?=$_POST['phone'] ? $_POST['phone'] : $user['phone']?>"></div>
				</div>
				<div class="field">
					<div class="title">Фактический адрес</div>
					<div class="value"><input type=text name="address" value="<?=$_POST['address'] ? $_POST['address'] : $user['address']?>"></div>
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
					<div class="title">Автоматически <br>отправлять в заказ</div>
					<div class="value">
						<select name="isAutomaticOrder">
							<option <?=$array['isAutomaticOrder'] == 0 ? 'selected' : ''?> value="0">нет</option>
							<option <?=$array['isAutomaticOrder'] == 1 ? 'selected' : ''?> value="1">да</option>
						</select>
					</div>
				</div>
				<div class="field">
					<div class="title">Показывать поставщика</div>
					<div class="value">
						<?if ($_POST['showProvider']) $checked = $_POST['showProvider'] ? 'checked' : '';
						else $checked = $user['showProvider'] ? 'checked' : '';?>
						<input type="checkbox" name="showProvider" <?=$checked?> value="1">
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
                    <div class="title">Адреса доставки</div>
                    <div class="value">
                        <div class="input-wrap set-addresses ">
                            <button>Показать</button>
                        </div>
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
					<div class="title">Кредитный лимит</div>
					<div class="value"><input type="text" name="credit_limit" value="<?=$_POST['credit_limit'] ? $_POST['credit_limit'] : $user['credit_limit']?>"></div>
				</div>
				<div class="field">
					<div class="title">Отсрочка платежа</div>
					<div class="value">
						<input type="text" name="defermentOfPayment" value="<?=$_POST['defermentOfPayment'] ? $_POST['defermentOfPayment'] : $user['defermentOfPayment']?>">
					</div>
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
		$array = array(
			'sum' => $_POST['sum'], 
			'remainder' => $curr_bill, 
			'user_id' => $id, 
			'comment' => 'Пополнение '.$_POST['replenishment']
		);
		core\Fund::insert(1, $array);
		$db->insert('funds', $array);
		$db->update('users', array('bill' => $curr_bill), '`id`='.$id);
		core\User::checkOverdue($id, $_POST['sum']);
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

	$res_user = core\User::get(['user_id' => $id]);
	$user = $res_user->fetch_assoc();

	$page_title = 'Движение средств';
	$status = "<a href='/admin'>Главная</a> > <a href='?view=users'>Пользователи</a> > ";
	$status .= "<a href='?view=users&act=change&id=$id'>{$user['full_name']}</a> > $page_title";
	$where =  "`user_id`=$id AND `type_operation` NOT IN (3,4)";
	$all = $db->getCount('funds', $where);
	$perPage = 30;
	$linkLimit = 10;
	$page = $_GET['page'] ? $_GET['page'] : 1;
	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ? $chank[$page] : 0;
	$funds = $db->select('funds', '*', $where, 'id', false, "$start,$perPage", true);?>
	<input type="hidden" name="user_id" value="<?=$_GET['id']?>">
	<div id="total" style="margin-top: 10px;">Всего операций: <?=$all?></div>

	<?=User::getHtmlActions($id)?>

	<div class="actions users">
		<a href="?view=users&act=form_operations&id=<?=$id?>">Пополнить счет</a>
		<?$bill = $user['bill'] ? '<span class="price_format">'.$user['bill'].'</span> руб.' : 'пусто';?>
		<span>На счету: <b><?=$bill?></b></span>
		<?$reserved_funds = $user['reserved_funds'] ? '<span class="price_format">'.$user['reserved_funds'].'</span> руб.' : 'пусто';?>
		<span>Зарезервировано: <b><?=$reserved_funds?></b></span>
		<?$value = $user['bill'] - $user['reserved_funds'];
		$available =  $value ? '<span class="price_format">'.$value.'</span> руб.' : 'пусто';?>
		<span>Доступно: <b><?=$available?></b></span>
		<span>Кредитный лимит: <b><?=$user['credit_limit']?> руб.</b></span>
	</div>
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
				Наценка: <input form="added_items" style="width: 38px" type="text" readonly name="markup" value="<?=$db->getFieldOnID('users', $_GET['id'], 'markup_handle_order')?>">
				<input class="intuitive_search" style="width: 264px;" type="text" name="items" value="<?=$_GET['items']?>" placeholder="Поиск по артикулу, vid и названию" required>
				<form id="added_items" method="post">
					<p><strong>Добавленные товары:</strong></p>
					<table class="t_table">
						<thead>
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
						</thead>
						<tbody>
							<tr class="hiddable">
								<td colspan="9">Товары не добавлены</td>
							</tr>
						</tbody>
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
	//debug($_POST); exit();
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
			'withoutMarkup' => $value['withoutMarkup'],
			'price' => $value['price'],
			'quan' => $value['quan'],
			'comment' => $value['comment']
		]);
	};
	message('Заказ успешно сохранен!');
	header("Location: /admin/?view=orders&act=change&id=$order_id");
}
function buildQuery($params, $type){
    $queryVin = '
        SELECT
            {fields_vin}
        FROM #search_vin sv
        {where_vin}
    ';
    $queryItems = '
        SELECT
            {fields_items}
        FROM
            #search_items si
        LEFT JOIN
            #items i ON si.item_id = i.id
        LEFT JOIN
            #brends b ON i.brend_id = b.id
        {where_items}
    ';
    if ($type){
        if ($type == 'items') $query = $queryItems;
        if ($type == 'vin') $query = $queryVin;
    }
    else $query = "
        $queryVin
        UNION
        $queryItems
    ";
    $query .= "
        {order}
        {having}
        {limit}
    ";
    foreach($params as $key => $value){
        switch($key){
            case 'where_vin':
            case 'where_items':
                $replacement = $value ? "WHERE $value" : '';
                break;
            case 'having':
                $replacement = $value ? "HAVING $value" : '';
                break;
            case 'order':
                $replacement = $value ? "ORDER BY $value" : '';
                break;
            case 'limit':
                $replacement = $value ? "LIMIT $value" : '';
                break;
            default:
                $replacement = $value;
        }
        $query = str_replace('{'.$key.'}', $replacement, $query);
    }
    return $query;
}
function search_history($totalCount){
	global $status, $page_title;
	
	$res_user = core\User::get(['user_id' => $_GET['id']]);
	if (is_object($res_user)) $user = $res_user->fetch_assoc();
	else $user = $res_user;
	
	$page_title = 'История поиска';
	$status = "<a href='/admin'>Главная</a> > <a href='?view=users'>Пользователи</a> > ";
	$status .= "<a href='?view=users&act=change&id={$_GET['id']}'>{$user['full_name']}</a> > $page_title";
	?>
    <input type="hidden" name="user_id" value="<?=$_GET['id']?>">
    <input type="hidden" name="page" value="<?=isset($_GET['page']) ? $_GET['page'] : 1?>">
    <input type="hidden" name="totalNumber" value="<?=$totalCount?>">
    <div id="actions">
        <form action="">
            <select name="type">
                <option value=""></option>
                <option value="items">Номенклатура</option>
                <option value="vin">VIN</option>
            </select>
            <input type="text" name="search">
            <input type="submit" value="Искать">
        </form>
    </div>
    <table id="history_search" class="t_table" cellspacing="1"></table>
    <div id="pagination-container"></div>
<?}
function basket(){
	global $db, $status, $page_title;
	$basket = core\Basket::get($_GET['id']);
	$res_user = core\User::get(['user_id' => $_GET['id']]);
	if (is_object($res_user)) $user = $res_user->fetch_assoc();
	else $user = $res_user;
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
					<td>
                        <a href="/admin/?view=items&act=item&id=<?=$value['item_id']?>">
                            <?=$value['article']?>
                        </a>
                    </td>
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
