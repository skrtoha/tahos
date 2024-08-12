<?php
/** @var $db \core\Database $act */

use core\Basket;
use core\Setting;
use core\User;

$act = $_GET['act'];
$id = $_GET['id'];
$GLOBALS['user'] = $_GET['id'];
$array = [];
if ($_POST['form_submit']){
	$saveble = true;

    if (
        $_POST['bill_mode'] != User::BILL_MODE_CASH_AND_CASHLESS &&
        $_POST['previous_bill_mode'] != $_POST['bill_mode'] &&
        in_array($_POST['bill_mode'], [User::BILL_CASH, User::BILL_MODE_CASHLESS])
    ){
        if ($_POST['bill_mode'] == User::BILL_MODE_CASH) $bill_mode = User::BILL_CASHLESS;
        if ($_POST['bill_mode'] == User::BILL_MODE_CASHLESS) $bill_mode = User::BILL_CASH;
        if (!User::checkOrdersBillModeExists($_GET['id'], $bill_mode)){
            $saveble = false;
        };
    }

    if (
        $_POST['previous_bill_mode'] == User::BILL_MODE_CASH_AND_CASHLESS &&
        $_POST['bill_mode'] == User::BILL_MODE_CASH
    ){
        if (!User::checkOrdersBillModeExists($_GET['id'], User::BILL_CASHLESS)){
            $saveble = false;
        }
    }
    if (
        $_POST['previous_bill_mode'] == User::BILL_MODE_CASH_AND_CASHLESS &&
        $_POST['bill_mode'] == User::BILL_MODE_CASHLESS
    ){
        if (!User::checkOrdersBillModeExists($_GET['id'], User::BILL_CASH)){
            $saveble = false;
        }
    }
    if (!$saveble){
        message('Имеются не оплаченные заказы!', false);
        header("Location: /admin/?view=users&act=change&id={$_GET['id']}");
        die();
    }
    unset($_POST['previous_bill_mode']);

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
				// else $array['issue_id'] = '';
				break;
			case 'pass':
				if ($value) $array['password'] = md5($value);
				break;
			case 'phone':
				$array['phone'] = str_replace(array('(', ')', ' ', '-'), '', $value);
				break;
            case 'pay_type':
                if ($_POST['bill_mode'] == User::BILL_MODE_CASHLESS) $array['pay_type'] = 'Безналичный';
                elseif ($_POST['bill_mode'] == User::BILL_MODE_CASH && $_POST['pay_type'] != 'Онлайн') $array['pay_type'] = 'Наличный';
                else $array['pay_type'] = $_POST['pay_type'];
                break;
            case 'arrangement':
                if (empty($value['list'])) break;

                if (
                    !$_POST['arrangement'][User::BILL_CASH] && !$_POST['arrangement'][User::BILL_CASHLESS] ||
                    $_POST['bill_mode'] == 1 && !$_POST['arrangement'][1] ||
                    $_POST['bill_mode'] == 2 && !$_POST['arrangement'][2] ||
                    $_POST['bill_mode'] == 3 && (!$_POST['arrangement'][1] || !$_POST['arrangement'][2]) ||
                    $_POST['arrangement'][1] == $_POST['arrangement'][2]
                ){
                    $saveble = false;
                    message('Неверно указан договор(ы)!', false);
                    break;
                }
                $db->delete('user_1c_arrangements', "`user_id` = {$_GET['id']}");

                $arrangementList = [];
                if ($value['list']){
                    $arrangements = json_decode($value['list'], true);
                    foreach($arrangements as $v) $arrangementList[$v['uid']] = $v['title'];
                }
                else {
                    if (isset($value[User::BILL_CASH])) $arrangementList[User::BILL_CASH] = $value[User::BILL_CASH];
                    if (isset($value[User::BILL_CASHLESS])) $arrangementList[User::BILL_CASHLESS] = $value[User::BILL_CASHLESS];
                }

                if (in_array($_POST['bill_mode'], [User::BILL_MODE_CASH, User::BILL_MODE_CASH_AND_CASHLESS])){
                    $uid = $_POST['arrangement'][User::BILL_CASH];
                    User::setUserArrangement1C([
                        'user_id' => $_GET['id'],
                        'bill_type' => User::BILL_CASH,
                        'uid' => $uid,
                        'title' => $arrangementList[$uid]
                    ]);
                }

                if (in_array($_POST['bill_mode'], [User::BILL_MODE_CASHLESS, User::BILL_MODE_CASH_AND_CASHLESS])){
                    $uid = $_POST['arrangement'][User::BILL_CASHLESS];
                    User::setUserArrangement1C([
                        'user_id' => $_GET['id'],
                        'bill_type' => User::BILL_CASHLESS,
                        'uid' => $uid,
                        'title' => $arrangementList[$uid]
                    ]);
                }

                break;
			default:
				if ($key != 'pass' and $key != 'form_submit') $array[$key] = $value;
		}
	}
	if (!isset($array['show_all_analogies'])) $array['show_all_analogies'] = 0;
	if (!isset($array['bonus_program'])) $array['bonus_program'] = 0;
	if (!isset($array['allow_request_delete_item'])) $array['allow_request_delete_item'] = 0;
	if (!isset($array['showProvider'])) $array['showProvider'] = 0;

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
			if (core\User::update($id, $array)){
                User::setAddress(
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
				SUM(ov.price * ov.ordered) AS sum,
				CASE
                    WHEN o.pay_type = 'Наличный' or o.pay_type = 'Онлайн' THEN 'Наличный'
                    WHEN o.pay_type = 'Безналичный' THEN 'Безналичный'
                END as type_pay
			FROM
				#orders_values ov
			LEFT JOIN
				#orders o ON o.id = ov.order_id
			WHERE
				ov.status_id IN (11, 3)
			GROUP BY
				o.user_id,
				type_pay
		", '');
		$updatedUsers = [];
		foreach($res as $row){
            $updatedUsers[$row['type_pay']][] = $row['user_id'];
            if ($row['type_pay'] == 'Наличный'){
                core\User::update($row['user_id'], ['reserved_cash' => $row['sum']]);
            }
            elseif ($row['type_pay'] = 'Безналичный'){
                core\User::update($row['user_id'], ['reserved_cashless' => $row['sum']]);
            }
		}
        foreach($updatedUsers as $type_pay => $userListID){
            if ($type_pay == 'Наличный') $db->update(
                'users',
                ['reserved_cash' => 0],
                '`id` NOT IN ('.implode(',', $userListID).')'
            );
            if ($type_pay == 'Безналичный') $db->update(
                'users',
                ['reserved_cashless' => 0],
                '`id` NOT IN ('.implode(',', $userListID).')'
            );
        }
        if (!empty($updatedUsers['Наличный']))

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

        /** @var mysqli_result $res_users */
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
					<td label="Баланс"><?=$user['bill_total']?></td>
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
        case 'items': $output .= "si.user_id = {$filters['id']} AND si.item_id <> 0 AND "; break;
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
            if (!empty($_POST)) $user = $_POST;
			else $user = $db->select_one('users', '*', "`id`=$id");
			$page_title = "Редактирование пользователя";
			break;
		case 's_add':
			$page_title = "Добавление пользователя";
            $user = $_POST;
			break;
	}
	$status = "<a href='/admin'>Главная</a> > <a href='?view=users'>Пользователи</a> > $page_title";
	if ($act == 's_change'){?>
		<?=\User::getHtmlActions($user)?>
	<?}?>
	<input type="hidden" name="user_id" value="<?=$_GET['id']?>">
    <input type="hidden" name="1c_url" value="<?= Setting::get('site_settings', '1c_url')?>">
	<div class="t_form">
		<div class="bg">
			<form method="post" enctype="multipart/form-data" id="user">
				<input type="hidden" name="form_submit" value="<?=$act == 's_change' ? 1 : 2?>">
				<div class="field">
					<div class="title">Фамилия</div>
					<div class="value"><input type=text name="name_1" value="<?=$user['name_1'] ?? ''?>"></div>
				</div>
				<div class="field">
					<div class="title">Имя</div>
					<div class="value"><input type=text name="name_2" value="<?=$user['name_2'] ?? ''?>"></div>
				</div>
				<div class="field">
					<div class="title">Отчество</div>
					<div class="value"><input type=text name="name_3" value="<?=$user['name_3'] ?? ''?>"></div>
				</div>
				<div class="field">
					<div class="title">Тип</div>
					<div class="value">
						<?
							$checked_1 = $user['user_type'] == 'private' ? 'checked' : '';
							$checked_2 = $user['user_type'] == 'entity' ? 'checked' : '';
							$disabled = $user['user_type'] == 'private' ? 'disabled' : ''; 
							$organization_name = $user['organization_name'];
						?>
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
								$selected = $user['organization_type'] == $organization_type['id'] ? 'selected' : '';
								?>
								<option <?=$selected?> value="<?=$organization_type['id']?>"><?=$organization_type['title']?></option>
							<?}?>
						</select>
						<input type="text"  <?=$act == 's_add' ? 'disabled' : ''?> style="margin-top: 10px" name="organization_name" value="<?=$organization_name?>" <?=$disabled?>>
					</div>
				</div>
				<div class="field">
					<div class="title">Вид платежа<br>по умолчанию</div>
					<div class="value">
						<select name="pay_type">
							<?$array = empty($_POST) ? $user : $_POST;
							$selected = $array['pay_type'] == 'Наличный' ? 'selected' : '';?>
							<option <?=$selected?> value="Наличный">Наличный</option>
							<?$selected = $array['pay_type'] == 'Безналичный' ? 'selected' : '';?>
							<option <?=$selected?> value="Безналичный">Безналичный</option>
                            <?$selected = $array['pay_type'] == 'Онлайн' ? 'selected' : '';?>
                            <option <?=$selected?> value="Онлайн">Онлайн</option>
						</select>
					</div>
				</div>
                <div class="field">
                    <div class="title">Режим расчетов</div>
                    <input type="hidden" name="previous_bill_mode" value="<?=$user['bill_mode']?>">
                    <div class="value">
                        <label>
                            <?$checked = $user['bill_mode'] == User::BILL_MODE_CASH ? 'checked' : ''?>
                            <input <?=$checked?> name="bill_mode" type="radio" value="<?=User::BILL_MODE_CASH?>">
                            <span>Только наличный</span>
                        </label>
                        <label>
                            <?$checked = $user['bill_mode'] == User::BILL_MODE_CASHLESS ? 'checked' : ''?>
                            <input <?=$checked?> name="bill_mode" type="radio" value="<?=User::BILL_MODE_CASHLESS?>">
                            <span>Только безналичный</span>
                        </label>
                        <label>
                            <?$checked = $user['bill_mode'] == User::BILL_MODE_CASH_AND_CASHLESS ? 'checked' : ''?>
                            <input <?=$checked?> name="bill_mode" type="radio" value="<?=User::BILL_MODE_CASH_AND_CASHLESS?>">
                            <span>Наличный и безналичный</span>
                        </label>
                    </div>
                </div>
                <div class="field">
                    <div class="title">Связь с договорами 1С</div>
                    <div class="value">
                        <a href="#" class="get_arrangements">Обновить список</a>
                        <?$db->indexBy = 'bill_type';
                        $arrangements = $db->select('user_1c_arrangements', '*', "`user_id` = {$_GET['id']}");
                        ?>
                        <input type="hidden" name="arrangement[list]" value="">
                        <div class="ut_arrangement cash">
                            <span>Наличный</span>
                            <?$disabled = in_array($user['bill_mode'], [User::BILL_CASH, User::BILL_MODE_CASH_AND_CASHLESS]) ? '' : 'disabled';?>
                            <select <?=$disabled?> name="arrangement[<?=User::BILL_CASH?>]">
                                <?if (!empty($arrangements[User::BILL_CASH])){?>
                                    <option selected value="<?=$arrangements[User::BILL_CASH]['uid']?>">
                                        <?=$arrangements[User::BILL_CASH]['title']?>
                                    </option>
                                <?}?>
                            </select>
                        </div>
                        <div class="ut_arrangement cashless">
                            <span>Безналичный</span>
                            <?$disabled = in_array($user['bill_mode'], [User::BILL_CASHLESS, User::BILL_MODE_CASH_AND_CASHLESS]) ? '' : 'disabled';?>
                            <select <?=$disabled?> name="arrangement[<?=User::BILL_CASHLESS?>]">
                                <?if (!empty($arrangements[User::BILL_CASHLESS]['uid'])){?>
                                    <option selected value="<?=$arrangements[User::BILL_CASHLESS]['uid']?>">
                                        <?=$arrangements[User::BILL_CASHLESS]['title']?>
                                    </option>
                                <?}?>
                            </select>
                        </div>
                    </div>
                </div>
				<div class="field">
					<div class="title">Наценка при заказе вручную</div>
					<div class="value"><input type=text name="markup_handle_order" value="<?=$user['markup_handle_order'] ?? ''?>"></div>
				</div>
                <div class="field">
                    <div class="title">Максимальное кол-во соединений</div>
                    <div class="value"><input type="text" name="max_connections" value="<?=$user['max_connections'] ?? ''?>"></div>
                </div>
				<div class="field">
					<div class="title">Скидка</div>
					<div class="value"><input type=text name="discount" value="<?=$user['discount'] ?? ''?>"></div>
				</div>
				<div class="field">
					<div class="title">E-mail</div>
					<div class="value"><input type=text name="email" value="<?=$user['email'] ?? ''?>"></div>
				</div>
				<div class="field">
					<div class="title">Телефон</div>
					<div class="value"><input type=text name="phone" value="<?=$user['phone'] ?? ''?>"></div>
				</div>
				<div class="field">
					<div class="title">Фактический адрес</div>
					<div class="value"><input type=text name="address" value="<?=$user['address'] ?? ''?>"></div>
				</div>
				<div class="field">
					<div class="title">Тип доставки</div>
					<div class="value">
                        <?
                        $checked_1 = $user['delivery_type'] == 'Доставка' ? 'checked' : '';
                        $checked_2 = $user['delivery_type'] == 'Самовывоз' ? 'checked' : '';
                        ?>
						<input <?=$act == 's_add' ? 'checked' : ''?> type="radio" value="Доставка" name="delivery_type" id="delivery_type_1" <?=$checked_1?>>
						<label for="delivery_type_1">Доставка</label>
						<input type="radio" value="Самовывоз" name="delivery_type" id="delivery_type_2" <?=$checked_2?>>
						<label for="delivery_type_2">Самовывоз</label>
					</div>
				</div>
				<div class="field">
					<div class="title">Точка выдачи</div>
					<div class="value">
						<?$disabled = $user['delivery_type'] == 'Доставка' ? 'disabled' : '';?>
						<select <?=$act == 's_add' ? 'disabled' : ''?> <?=$disabled?> name="issue_id">
							<option value="">ничего не выбрано</option>
							<?$issues = $db->select('issues', 'id,title', '', '', '', '');
							if (count($issues)){
								foreach($issues as $issue){
									$selected = isset($user['issue_id']) && $user['issue_id'] == $issue['id'] ? 'selected' : ''?>
									<option <?=$selected?> value="<?=$issue['id']?>"><?=$issue['title'] ?? ''?></option>
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
								$selected = isset($user['currency_id']) && $user['currency_id'] == $currency['id'] ? 'selected' : ''?>
								<option <?=$selected?> value="<?=$currency['id']?>"><?=$currency['title']?></option>
							<?}?>
						</select>
					</div>
				</div>
				<div class="field">
					<div class="title">Показывать все аналоги</div>
					<div class="value">
						<?$checked = $user['show_all_analogies'] ? 'checked' : '';?>
						<input type="checkbox" name="show_all_analogies" <?=$checked?> value="1">
					</div>
				</div>
				<div class="field">
					<div class="title">Запрос на удаление товара</div>
					<div class="value">
						<?$checked = $user['allow_request_delete_item'] ? 'checked' : '';?>
						<input type="checkbox" name="allow_request_delete_item" <?=$checked?> value="1">
					</div>
				</div>
				<div class="field">
					<div class="title">Автоматически <br>отправлять в заказ</div>
					<div class="value">
						<select name="isAutomaticOrder">
							<option <?=$user['isAutomaticOrder'] == 0 ? 'selected' : ''?> value="0">нет</option>
							<option <?=$user['isAutomaticOrder'] == 1 ? 'selected' : ''?> value="1">да</option>
						</select>
					</div>
				</div>
				<div class="field">
					<div class="title">Показывать поставщика</div>
					<div class="value">
						<?$checked = isset($user['showProvider']) && $user['showProvider'] ? 'checked' : '';?>
						<input type="checkbox" name="showProvider" <?=$checked?> value="1">
					</div>
				</div>
				<div class="field">
					<div class="title">Бонусная программа</div>
					<div class="value">
						<?$checked = isset($user['bonus_program']) && $user['bonus_program'] ? 'checked' : '';?>
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
						<?$bonus_count = $user['bonus_program'] ? $user['bonus_count'] : '0'?>
						<input type="text" name="bonus_count" value="<?=$bonus_count?>">
					</div>
				</div>
				<div class="field">
					<div class="title">Наценка</div>
					<div class="value"><input type=text name="markup" value="<?=$user['markup'] ?? ''?>"></div>
				</div>
				<div class="field">
					<div class="title">Кредитный лимит<br>наличные</div>
					<div class="value">
                        <input type="text" name="credit_limit_cash" value="<?=$user['credit_limit_cash'] ?? ''?>">
                    </div>
				</div>
                <div class="field">
                    <div class="title">Кредитный лимит<br>безналичные</div>
                    <div class="value">
                        <input type="text" name="credit_limit_cashless" value="<?=$user['credit_limit_cashless'] ?? ''?>">
                    </div>
                </div>
				<div class="field">
					<div class="title">Отсрочка платежа</div>
					<div class="value">
						<input type="text" name="defermentOfPayment" value="<?=$user['defermentOfPayment'] ?? ''?>">
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

    /** @var mysqli_result $res_user */
	$res_user = User::get(['user_id' => $id]);
    foreach($res_user as $value) $user = $value;

	if ($_POST['form_operations_submit']){
		$_POST['sum'] = str_replace(array(' ', ','), '', $_POST['sum']);

        $bill_type = 0;
        if ($_POST['pay_type'] == 'Наличный') $bill_type = 1;
        if ($_POST['pay_type'] == 'Безналичный') $bill_type = 2;
        if (!$bill_type) die('Ошибка пополнения счета');

        User::replenishBill([
            'user_id' => $id,
            'sum' => $_POST['sum'],
            'comment' => 'Пополнение '.$_POST['replenishment'],
            'bill_type' => $bill_type
        ]);
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
					<div class="title">Источник пополнения</div>
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
                    <div class="title">Тип счета</div>
                    <div class="value">
                        <select name="pay_type">
                            <?foreach(User::getListByBillMode($user['bill_mode']) as $pay_type){?>
                                <option value="<?=$pay_type?>"><?=$pay_type?></option>
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

    /** @var mysqli_result $res_user */
	$res_user = core\User::get(['user_id' => $id]);
	$user = $res_user->fetch_assoc();

	$page_title = 'Движение средств';
	$status = "<a href='/admin'>Главная</a> > <a href='?view=users'>Пользователи</a> > ";
	$status .= "<a href='?view=users&act=change&id=$id'>{$user['full_name']}</a> > $page_title";
	$where =  "`user_id`=$id AND `type_operation` NOT IN (3,4)";
	$all = $db->getCount('funds', $where);
	$perPage = 30;
	$linkLimit = 10;
	$page = $_GET['page'] ?: 1;
	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ?: 0;
	$funds = $db->select('funds', '*', $where, 'id', false, "$start,$perPage", true);?>
	<input type="hidden" name="user_id" value="<?=$_GET['id']?>">
	<div id="total" style="margin-top: 10px;">Всего операций: <?=$all?></div>

	<?=\User::getHtmlActions($user)?>

	<div class="actions users">
		<a href="?view=users&act=form_operations&id=<?=$id?>">Пополнить счет</a>
	</div>
    <?if (in_array($user['bill_mode'], [User::BILL_MODE_CASH, User::BILL_MODE_CASH_AND_CASHLESS])){?>
        <div class="bill">
            <span class="title">НАЛИЧНЫЙ:</span>
            <span class="bill_describe"><b>На счету:</b> <?=$user['bill_cash']?></span>
            <span class="bill_describe"><b>Зарезервировано: </b><?=$user['reserved_cash']?></span>
            <span class="bill_describe"><b>Доступно:</b> <?=$user['bill_cash'] - $user['reserved_cash']?></span>
            <span class="bill_describe"><b>Кредитный лимит: </b><?=$user['credit_limit_cash']?></span>
        </div>
    <?}?>
    <?if (in_array($user['bill_mode'], [User::BILL_MODE_CASHLESS, User::BILL_MODE_CASH_AND_CASHLESS])){?>
        <div class="bill">
            <span class="title">БЕЗНАЛИЧНЫЙ:</span>
            <span class="bill_describe"><b>На счету:</b> <?=$user['bill_cashless']?></span>
            <span class="bill_describe"><b>Зарезервировано: </b><?=$user['reserved_cashless']?></span>
            <span class="bill_describe"><b>Доступно:</b> <?=$user['bill_cashless'] - $user['reserved_cashless']?></span>
            <span class="bill_describe"><b>Кредитный лимит: </b><?=$user['credit_limit_cashless']?></span>
        </div>
    <?}?>

	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Дата</td>
			<td>Тип операции</td>
            <td>Счет</td>
			<td>Сумма</td>
			<td>Оплачено</td>
			<td>Отстаток</td>
			<td>Комментарий</td>
		</tr>
		<?if (count($funds)){
            $stringPayment = 'Поступление оплаты от клиента: Платежное поручение №';
			foreach($funds as $fund){
                $class = $fund['issue_id'] && $fund['paid'] < $fund['sum'] ? 'not-paid' : ''?>
				<tr class="<?=$class?>" <?=$fund['issue_id'] ? "data-issue-id='{$fund['issue_id']}'" : ''?>>
					<td label="Дата"><?=date('d.m.Y H:i', strtotime($fund['created']))?></td>
					<td label="Тип операции">
                        <?=$operations_types[$fund['type_operation']]?>
                        <?if (mb_strpos($fund['comment'], $stringPayment) !== false) {
                            $string = str_replace($stringPayment, '', $fund['comment']);
                            $paymentId = preg_replace('/ от.*/', '', $string);
                            ?>
                            <br>
                            <a nohref data-id="<?=$paymentId?>" data-amount="<?=$fund['sum']?>" class="refund-money">
                                Вернуть
                            </a>
                        <?}?>
                    </td>
                    <td label="Счет">
                        <?if ($fund['bill_type'] == User::BILL_CASH){?>
                            Наличный
                        <?}
                        else{?>
                            Безналичный
                        <?}?>
                    </td>
					<td label="Сумма" class="price_format"><?=$fund['sum']?></td>
					<td label="Оплачено" class="price_format">
                        <?if ($fund['issue_id']){?>
                            <?=$fund['paid']?>
                        <?}?>
                    </td>
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

/**
 * @throws Exception
 */
function basket(){
	global $status, $db, $page_title;
	if (!empty($_POST)){
        if ($_POST['save_basket']){
            $db->delete('basket', "`user_id` = {$_GET['id']}");
            foreach($_POST['comment'] as $key => $value){
                $array = explode('-', $key);
                $item_id = $array[0];
                Basket::addToBasket([
                    'user_id' => $_GET['id'],
                    'store_id' => $_POST['store_id'][$key],
                    'item_id' => $item_id,
                    'quan' => $_POST['quan'][$key],
                    'price' => $_POST['price'][$key],
                    'comment' => $_POST['comment'][$key],
                    'isToOrder' => $_POST['toOrder'][$key] ?? "0"
                ]);
            }
            header("Location: {$_SERVER['HTTP_REFERER']}");
            die();
        }

        /** @var mysqli_result $res_user */
        $res_user = User::get(['user_id' => $_GET['id']]);

        if ($res_user->num_rows){
            $user = $res_user->fetch_assoc();
            $debt = User::getDebt($user);
        }
        if ($debt['blocked']){
            message('Возможность отправки заказов ограничена!', false);
            header("Location: {$_SERVER['HTTP_REFERER']}");
            die();
        }
        $order_id = Basket::sendToOrder($user);
        message('Успешно отправлено в заказы!');
        header("Location: /admin/?view=orders&id={$order_id}&act=change");
        die();
    }
	$page_title = 'Корзина';

	$status = "
		<a href='/admin'>Главная</a> > <a href='?view=users'>Пользователи</a> > 
		<a href='/admin/?view=users&act=change&id={$_GET['id']}'>Редактирование пользователя</a> > $page_title
		";
	?>
    <input type="hidden" value="<?=$_GET['id']?>" name="user_id">
	<div class="bg">
		<div class="field">
			<div class="value" id="user_order_add">
				<a href="#" class="show_form_search">Добавить</a>
				Наценка: <input form="added_items" style="width: 38px" type="text" readonly name="markup" value="<?=$db->getFieldOnID('users', $_GET['id'], 'markup_handle_order')?>">
				<input class="intuitive_search" style="width: 264px;" type="text" name="items" value="<?=$_GET['items']?>" placeholder="Поиск по артикулу, vid и названию" required>
				<form id="added_items" method="post" act="">
					<p><strong>Добавленные товары:</strong></p>
					<table class="t_table">
						<thead>
							<tr class="head">
								<th></th>
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
                        <input type="hidden" name="save_basket" value="0">
                        <input type="submit" class="save" disabled value="Сохранить">
						<input type="submit" class="send" class="button" value="Отправить в заказ">
					</div>
				</form>
			</div>
		</div>
	</div>
    <script>
        let items = JSON.parse('<?=json_encode(Basket::getWithFullListOfStoreItems($_GET['id']))?>');
    </script>
<?}
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
?>
