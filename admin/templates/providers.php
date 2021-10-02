<?php
/* @var $db \core\Database */

use core\Managers;
use core\Provider;
$act = $_GET['act'];
$id = $_GET['id'];
if ($_POST['store_id']) items_submit();
switch ($act) {
	case 'provider': 
		if (Managers::isActionForbidden('Поставщики', 'Изменение')){
			Managers::handlerAccessNotAllowed();
		}
		provider(); 
		break;
	case 'stores': stores(); break;
	case 'orders': orders(); break;
	case 'provider_delete':
		if (Managers::isActionForbidden('Поставщики', 'Удаление')){
			Managers::handlerAccessNotAllowed();
		}
		$res = $db->delete('providers', "`id`=".$_GET['id']);
		if ($res === true){
			message('Поставщик успешно удален!');
			header('Location: ?view=providers');
		}
		else{
			message($res, false);
			header("Location: ?view=providers&act=provider&id={$_GET['id']}");
		}
		break;
	case 'priceEmail': priceEmail(); break;
	case 'itemsToOrder': itemsToOrder(); break;
	case 'calendar':
		if(isset($_GET['provider_id'])){
			$provider = $db->select_one('providers', ['id', 'title', 'workSchedule', 'calendar'], "`id` = {$_GET['provider_id']}");
			$workSchedule = json_decode($provider['workSchedule'], true);
			$calendar = json_decode($provider['calendar'], true);
		}
		else{
			$providerStore = core\Provider::getStoreInfo($_GET['store_id']);
			$workSchedule = json_decode($providerStore['workSchedule'], true);
			$calendar = json_decode($providerStore['calendar'], true);
		}

		// debug($workSchedule, 'workSchedule');

		$page_title = 'График поставок';

		$status = '<a href="/">Главная</a> > ';
		$status .= '<a href="?view=providers">Поставщики</a> > ';
		if (isset($_GET['provider_id'])){
			$status .= '<a href="?view=providers&act=provider&id=' . $_GET['provider_id'] . '">' . $provider['title'] . '</a> > ';
		}
		else{
			$status .= '<a href="?view=providers&act=stores&id=' . $providerStore['provider_id'] . '">' . $providerStore['provider'] . '</a> > ';
			$status .= '<a href="?view=providers&id=' . $providerStore['provider_id'] . '&act=stores#' . $_GET['store_id'] . '">' . $providerStore['cipher'] . '</a> > ';
		}
		$status .= $page_title;

		if (!empty($_POST)){
			$toSave = [];
			foreach($_POST as $date => $value){
				$toSave[str_replace('_', '.', $date)] = [
					'isWorkDay' => isset($value['isWorkDay']) ? $value['isWorkDay'] : 0,
					'hours' => str_pad($value['hours'], 2, 0, STR_PAD_LEFT),
					'minutes' => str_pad($value['minutes'], 2, 0, STR_PAD_LEFT)
				];
			}
			if (isset($_GET['provider_id'])) $db->update('providers', ['calendar' => json_encode($toSave)], "`id` = {$_GET['provider_id']}");
			if (isset($_GET['store_id'])) $db->update('provider_stores', ['calendar' => json_encode($toSave)], "`id` = {$_GET['store_id']}");
			header("Location: {$_SERVER['HTTP_REFERER']}");
		}
		$dateTime = new DateTime();
		$dateTime->sub(new DateInterval('P1D'));
		calendar($dateTime, $workSchedule, $calendar);
		break;
	case 'workSchedule':
		if(isset($_GET['provider_id'])){
			$provider = $db->select_one('providers', ['id', 'title', 'workSchedule'], "`id` = {$_GET['provider_id']}");
			$workSchedule = json_decode($provider['workSchedule'], true);
		}
		else{
			$providerStore = core\Provider::getStoreInfo($_GET['store_id']);
			$workSchedule = json_decode($providerStore['workSchedule'], true);
		}

		$page_title = 'Расписание';

		$status = '<a href="/">Главная</a> > ';
		$status .= '<a href="?view=providers">Поставщики</a> > ';
		if (isset($_GET['provider_id'])){
			$status .= '<a href="?view=providers&act=provider&id=' . $_GET['provider_id'] . '">' . $provider['title'] . '</a> > ';
			$status .= '<a href="?view=providers&act=calendar&provider_id=' . $_GET['provider_id'] . '">График поставок</a> > ';
		}
		else{
			$status .= '<a href="?view=providers&act=stores&id=' . $providerStore['provider_id'] . '">' . $providerStore['provider'] . '</a> > ';
			$status .= '<a href="?view=providers&id=' . $providerStore['provider_id'] . '&act=stores#' . $_GET['store_id'] . '">' . $providerStore['cipher'] . '</a> > ';
			$status .= '<a href="?view=providers&act=calendar&store_id=' . $_GET['store_id'] . '">График поставок</a> > ';
		}
		$status .= $page_title;

		if (!empty($_POST)){
			$toSave = [];
			foreach($_POST as $dayWeek => $value){
				$toSave[$dayWeek] = [
					'isWorkDay' => isset($value['isWorkDay']) ? $value['isWorkDay'] : 0,
					'hours' => str_pad($value['hours'], 2, 0, STR_PAD_LEFT),
					'minutes' => str_pad($value['minutes'], 2, 0, STR_PAD_LEFT)
				];
			}
			debug($toSave);
			if (isset($_GET['provider_id'])) $db->update('providers', ['workSchedule' => json_encode($toSave)], "`id` = {$_GET['provider_id']}");
			if (isset($_GET['store_id'])) $db->update('provider_stores', ['workSchedule' => json_encode($toSave)], "`id` = {$_GET['store_id']}");
			header("Location: {$_SERVER['HTTP_REFERER']}");
		}
		workSchedule($workSchedule);
		break;
	case 'mainStores':
		$page_title = 'Список основных складов';
		$status = "<a href='/'>Главная</a> > <a href='?view=providers'>Поставщики </a> > $page_title";
		$handlePrices = [
			24 => 'priceRossko',
			25 => 'priceRossko',
			14 => 'priceMikado',
			13 => 'priceMikado',
			35 => 'priceMikado',
			276 => 'BERG_Yar',
		];
		$res_main_stores = $db->query("
			SELECT
				ps.id,
				ps.title,
				ps.cipher,
			    ps.id as store_id,
				p.title AS provider,
				p.api_title,
				IF (ep.settings IS NOT NULL, 1, 0) AS isSetEmailPrice,
				DATE_FORMAT(ps.price_updated, '%d.%m.%Y %H:%i:%s') AS price_updated
			FROM
				#provider_stores ps
			LEFT JOIN
				#providers p ON PS.provider_id = p.id
			LEFT JOIN
				#email_prices ep ON ep.store_id = ps.id
			WHERE
				ps.is_main = 1
			ORDER BY
				ps.price_updated DESC
		", '');
		mainStores($res_main_stores, $handlePrices);
		break;
	case 'removeFromBasket':
		// debug($_GET); exit();
		core\Provider::removeFromBasket($_GET);
		message('Успешно удалено!');
		header("Location: {$_SERVER['HTTP_REFERER']}");
		break;
    case 'set_address':
        $params = [];
        $params['page_title'] = 'Сопоставить адреса доставки';
        $params['user_id'] = $_GET['user_id'] ?? $_GET['user_id'];
        if (!empty($_POST)){
            $db->delete('provider_addresses', "`provider_id` = {$_GET['id']} AND `user_id` = {$params['user_id']}");
            $countPost = count($_POST['address_provider_id']);
            for($i = 0; $i < $countPost; $i++){
                if (!$_POST['address_site_id'][$i]) continue;
                $db->insert('provider_addresses', [
                    'address_site_id' => $_POST['address_site_id'][$i],
                    'address_provider_id' => $_POST['address_provider_id'][$i],
                    'provider_id' => $_GET['id'],
                    'user_id' => $_POST['user_id'][$i]
                ]);
            }
        }
        $providers = Provider::get();
        $params['providerInfo'] = $providers[$_GET['id']];
        $params['addressProviderList'] = Provider::getProviderAddressList($_GET['id']);
        if ($params['user_id']){
            $res_user = \core\User::get(['user_id' => $params['user_id']]);
            $params['userInfo'] = $res_user->fetch_assoc();
            $params['page_title'] = " для {$params['userInfo']['full_name']}";
            $params['addressList'] = $db->query("
                SELECT
                    ua.id AS address_site_id,
                    ua.user_id,
                    ua.json,
                    pa.address_provider_id
                from
                    #user_addresses ua
                left join
                    #provider_addresses pa ON pa.user_id = ua.user_id AND ua.id = pa.address_site_id
                where
                    ua.user_id = {$params['user_id']}
        ", '');
        }
        else $userInfo = [];
        setAddress($params);
        break;
    default:
		view();
} 
function mainStores($res_main_stores, $handlePrices){?>
	<div style="float: right;margin-bottom: 5px;">Всего: <?=$res_main_stores->num_rows?></div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Шифр</td>
			<td>Название</td>
			<td>Поставщик</td>
			<td>Дата<br>обновления</td>
			<td></td>
		</tr>
		<?if ($res_main_stores->num_rows){
			foreach($res_main_stores as $row){?>
				<tr>
					<td class="storeInfo" label="Шифр">
                        <a href="#" class="store" store_id="<?=$row['store_id']?>">
                            <?=$row['cipher']?>
                        </a>
                    </td>
					<td><?=$row['title']?></td>
					<td><?=$row['provider']?></td>
					<td><?=$row['price_updated']?></td>
					<td>
						<?if ($row['isSetEmailPrice']){?>
							<a href="/cron.php?act=emailPrice&store_id=<?=$row['id']?>">Обновить прайс</a>
						<?}?>
						<?if (isset($handlePrices[$row['id']])){?>
							<a href="/cron.php?act=<?=$handlePrices[$row['id']]?>">Обновить прайс</a>
						<?}?>
					</td>
				</tr>
			<?}
		}
		else{?>
			<tr><td colspan="3">Ничего не найдено</td></tr>
		<?}?>
	</table>
<?}
function calendar($dateTime, $workSchedule, $calendar){
	//debug($calendar, 'calendar');
	?>
	<div id="actions">
		<?if (isset($_GET['provider_id'])){?>
			<a href="?view=providers&act=workSchedule&provider_id=<?=$_GET['provider_id']?>">Расписание</a>
		<?}?>
		<?if (isset($_GET['store_id'])){?>
			<a href="?view=providers&act=workSchedule&store_id=<?=$_GET['store_id']?>">Расписание</a>
		<?}?>
	</div>
	<form id="workSchedule" method="post">
		<table class="t_table" cellspacing="1">
			<tr class="head">
				<td>Дата, день недели</td>
				<td>Рабочий<br> день</td>
				<td>Прием заказов</td>
			</tr>
			<?for($i = 0; $i < 14; $i++){
				$dateTime->add(new DateInterval('P1D'));
				$isWorkDay = !in_array($dateTime->format('l'), ['Saturday', 'Sunday']);
				$date = $dateTime->format('d.m.Y');
				$dayWeek = $dateTime->format('l');

				if (isset($calendar[$date])){
					$isChecked = $calendar[$date]['isWorkDay'] ? 'checked' : '';
				}
				else{
					$isChecked = $workSchedule[$dayWeek]['isWorkDay'] ? 'checked' : '';
				}
				?>
				<tr class="<?=$isWorkDay ? '' : 'dayOff'?>">
					<td>
						<?=$date?>, <?=mb_strtolower(core\Config::$daysWeek[$dateTime->format('l')])?>
					</td>
					<td>
						<input <?=$isChecked ? 'checked' : ''?> type="checkbox" name="<?=$date?>[isWorkDay]" value="1">
					</td>
					<td>
						<select <?=$isChecked ? '' : 'disabled'?> name="<?=$date?>[hours]">
							<?for($h = 0; $h <= 23; $h++){
								if (isset($calendar[$date])){
									$selected = $calendar[$date]['hours'] == $h ? 'selected' : '';
								}
								else{
									$selected = $workSchedule[$dayWeek]['hours'] == $h ? 'selected' : '';
								}?>
								<option <?=$selected?> value="<?=$h?>"><?=$h?></option>
							<?}?>
						</select>
						:
						<select <?=$isChecked ? '' : 'disabled'?> name="<?=$date?>[minutes]">
							<?for($m = 0; $m <= 59; $m++){
								if (isset($calendar[$date])){
									$selected = $calendar[$date]['minutes'] == $m ? 'selected' : '';
								}
								else{
									$selected = $workSchedule[$dayWeek]['minutes'] == $m ? 'selected' : '';
								}?>
								<option <?=$selected?> value="<?=$m?>"><?=$m?></option>
							<?}?>
						</select>
					</td>
				</tr>
			<?}?>
		</table>
		<input style="margin-top: 10px" type="submit" value="Сохранить ">
	</form>
<?}
function workSchedule($workSchedule){
	//debug($workSchedule);
	?>
	<div id="actions">
		<?if (isset($_GET['provider_id'])){?>
			<a href="?view=providers&act=calendar&provider_id=<?=$_GET['provider_id']?>">График поставок</a>
		<?}?>
		<?if (isset($_GET['store_id'])){?>
			<a href="?view=providers&act=calendar&store_id=<?=$_GET['store_id']?>">График поставок</a>
		<?}?>
	</div>
	<form id="workSchedule" method="post">
		<table class="t_table" cellspacing="1">
			<tr class="head">
				<td>День недели</td>
				<td>Рабочий<br> день</td>
				<td>Окончание<br>рабочего дня</td>
			</tr>
			<?foreach(core\Config::$daysWeek as $en => $rus){?>
				<tr>
					<td><?=$rus?></td>
					<td><input type="checkbox" <?=$workSchedule[$en]['isWorkDay'] ? 'checked' : ''?> name="<?=$en?>[isWorkDay]" value="1"></td>
					<td>
						<select <?=$workSchedule[$en]['isWorkDay'] ? '' : 'disabled'?> name="<?=$en?>[hours]">
							<?for($h = 0; $h <= 23; $h++){?>
								<option <?=$h == $workSchedule[$en]['hours'] ? 'selected' : ''?> value="<?=$h?>"><?=$h?></option>
							<?}?>
						</select>
						:
						<select <?=$workSchedule[$en]['isWorkDay'] ? '' : 'disabled'?> name="<?=$en?>[minutes]">
							<?for($m = 0; $m <= 59; $m++){?>
								<option <?=$m == $workSchedule[$en]['minutes'] ? 'selected' : ''?> value="<?=$m?>"><?=$m?></option>
							<?}?>
						</select>
					</td>
				</tr>
			<?}?>
		</table>
		<input style="margin-top: 10px" type="submit" value="Сохранить">
	</form>
<?}
function view(){
	global $status, $db, $page_title;
	require_once('templates/pagination.php');
	$search = $_POST['search'] ? $_POST['search'] : $_GET['search'];
	$where = 'is_active = 1';
	if ($search){
		$where .= " AND `title` LIKE '%$search%'";
		$page_title = 'Поиск по названию';
		$status = "<a href='/admin'>Главная</a> > <a href='?view=providers'>Поставщики</a> > $page_title";
	}
	else{
		$page_title = "Поставщики";
		$status = "<a href='/admin'>Главная</a> > $page_title";
	}
	$all = $db->getCount('providers', $where);
	$perPage = 30;
	$linkLimit = 10;
	$page = $_GET['page'] ? $_GET['page'] : 1;
	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ? $chank[$page] : 0;
	$providers = $db->select('providers', 'id,title,legal_region', $where, 'title', true, "$start,$perPage", true);?>
	<div id="total" style="margin-top: 10px;">Всего: <?=$all?></div>
	<div class="actions">
		<form style="float: left;margin-bottom: 10px;" action="?view=providers&act=search" method="post">
			<input style="width: 264px;" required type="text" name="search" value="<?=$search?>" placeholder="Поиск по поставщикам">
			<input type="submit" value="Искать">
		</form>
		<a href="?view=providers&act=provider">Добавить</a>
		<a href="?view=providers&act=itemsToOrder">Товары, ожидающие отправку в заказ</a>
		<a href="?view=providers&act=mainStores">Основные склады</a>
		<a href="?view=providers&act=clearAllPrices">Очистить все прайсы</a>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Название</td>
			<td>Регион</td>
		</tr>
		<?if (count($providers)){
			foreach($providers as $id => $provider){?>
				<tr provider_id="<?=$id?>" class="providers_box" href="?view=providers&id=<?=$id?>&act=stores">
					<td><?=$provider['title']?></td>
					<td><?=$provider['legal_region']?></td>
				</tr>
			<?}
		}
		else{?>
			<tr><td colspan="3">Поставщиков не найдено</td></tr>
		<?}?>
	</table>
	<?pagination($chank, $page, ceil($all / $perPage), $href = "?view=providers&search=$search&page=");
}
function stores(){
	global $db, $page_title, $status;
	$provider = $db->select_one('providers', '*', "`id`={$_GET['id']}");
	$page_title = "Склады поставщика {$provider['title']}";
	$status = "<a href='/admin'>Главная</a> > <a href='?view=providers'>Поставщики</a> > $page_title";
	$res_stores = $db->query("
		SELECT
			ps.id,
			ps.title,
			ps.cipher,
		    ps.is_main,
			DATE_FORMAT(ps.price_updated, '%d.%m.%Y %H:%i:%s') AS price_updated
		FROM
			#provider_stores ps
		WHERE
			ps.provider_id={$_GET['id']}
        ORDER BY
            ps.is_main DESC
	", '');?>
	<div id="total">Всего: <?=$res_stores->num_rows?></div>
	<div class="actions">
		<a href="#" id="store_add">Добавить</a>
	</div>
	<input type="hidden" name="provider_id" value="<?=$_GET['id']?>">
	<input type="hidden" name="store_id" value="<?=isset($_GET['id']) ? $_GET['id'] : ''?>">
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Название</td>
			<td>Обновлено</td>
			<td>Шифр</td>
		</tr>
		<?if ($res_stores->num_rows){
			while($row = $res_stores->fetch_assoc()){
                $class = $row['is_main'] ? 'bold' : ''; ?>
				<tr id="someTr" class="store <?=$class?>" store_id="<?=$row['id']?>">
					<td><?=$row['title']?></td>
					<td><?=$row['price_updated']?></td>
					<td><?=$row['cipher']?></td>
				</tr>
			<?}
		}
		else{?>
			<tr class="removable store"><td colspan="3">Складов не найдено</td></tr>
		<?}?>
	</table>
<?}
function provider(){
	global $status, $db, $page_title;
	if (!empty($_POST)) provider_save();
	if ($_GET['id']) $array = $db->select_one('providers', '*', "`id`={$_GET['id']}");
	else{
		$array = $_POST;
		$page_title = 'Добавление поставщика';
	} 
	$page_title = 'Редактирование';
	$status = "<a href='/admin'>Главная</a> > Настройки > ";
	$status .= "<a href='?view=settings&act=providers'>Настройки поставщиков</a> > $page_title";
	if ($_GET['id']){?>
		<a href="?view=providers&id=<?=$_GET['id']?>&act=provider_delete" class="delete_item">Удалить</a>
		<a href="?view=providers&act=calendar&provider_id=<?=$_GET['id']?>">График поставок</a>
		<?if (core\Provider::getProviderAPITitle($_GET['id'])){?>
			<a href="?view=settings&act=api_settings&provider_id=<?=$_GET['id']?>">Настройки API</a>
		<?}?>
        <?if ($_GET['id'] == 15){?>
            <a href="/admin/?view=providers&act=set_address&id=<?=$_GET['id']?>">Сопоставить адреса</a>
        <?}?>
		<div style="width: 100%; height: 10px"></div>
	<?}?>
	<div class="t_form">
		<div class="bg">
			<form method="post" enctype="multipart/form-data">
				<div class="field">
					<div class="title">Название</div>
					<div class="value"><input type=text name="title" value="<?=$array['title']?>"></div>
				</div>
				<div class="field">
					<div class="title">E-mail</div>
					<div class="value"><input type=text name="email" value="<?=$array['email']?>"></div>
				</div>
				<div class="field">
					<div class="title">Телефон</div>
					<div class="value"><input type=text name="telephone" value="<?=$array['telephone']?>"></div>
				</div>
				<div class="field">
					<div class="title">Дополнительный телефон</div>
					<div class="value"><input type=text name="telephone_extra" value="<?=$array['telephone_extra']?>"></div>
				</div>
				<div class="field">
					<div class="title">ОГРН</div>
					<div class="value"><input type=text name="ogrn" value="<?=$array['ogrn']?>"></div>
				</div>
				<div class="field">
					<div class="title">ОКПО</div>
					<div class="value"><input type=text name="okpo" value="<?=$array['okpo']?>"></div>
				</div>
				<div class="field">
					<div class="title">ИНН</div>
					<div class="value"><input type=text name="inn" value="<?=$array['inn']?>"></div>
				</div>
				<div class="field">
					<div class="title">Юридический адрес</div>
					<div class="value">
						<span>Индекс:</span>
						<input type=text name="legal_index" value="<?=$array['legal_index']?>">
						<span style="display: block; margin-top: 10px">Регион:</span>
						<input type=text name="legal_region" value="<?=$array['legal_region']?>">
						<span style="display: block; margin-top: 10px">Адрес:</span>
						<input type=text name="legal_adres" value="<?=$array['legal_adres']?>">
						<input checked style="margin-top: 10px" type="checkbox" name="is_legal" value="1" id="is_legal">
						<label for="is_legal">Совпадает с фактическим</label>
					</div>
				</div>
				<div class="field">
					<div class="title">Фактический адрес</div>
					<div class="value">
						<span>Индекс:</span>
						<input <?=$disabled?> type=text name="fact_index" value="<?=$array['fact_index']?>">
						<span style="display: block; margin-top: 10px">Регион:</span>
						<input type=text <?=$disabled?> name="fact_region" value="<?=$array['fact_region']?>">
						<span style="display: block; margin-top: 10px">Адрес:</span>
						<input type=text <?=$disabled?> name="fact_adres" value="<?=$array['fact_adres']?>">
					</div>
				</div>
				<div class="field">
					<div class="title">Процент возврата</div>
					<div class="value">
						<input type="text" name="return_percent" value="<?=$array['return_percent']?>">
					</div>
				</div>
				<?if ($array['api_title'] && file_exists($_SERVER['DOCUMENT_ROOT']."/core/Provider/{$array['api_title']}.php")){?>
					<div class="field">
						<div class="title">Включить API поиска</div>
						<div class="value">
							<input type="checkbox" name="is_enabled_api_search" <?=$array['is_enabled_api_search'] ? 'checked' : ''?> value="1">
						</div>
					</div>
					<div class="field">
						<div class="title">Включить API заказов</div>
						<div class="value">
							<input type="checkbox" name="is_enabled_api_order" <?=$array['is_enabled_api_order'] ? 'checked' : ''?> value="1">
						</div>
					</div>
				<?}?>
				<div class="field">
					<div class="title">Время отправки заказов</div>
					<div class="value">
						<select name="cron_hours">
							<?for($h = 0; $h <= 23; $h++){?>
								<option <?=$h == $array['cron_hours'] ? 'selected' : ''?> value="<?=$h?>"><?=$h?></option>
							<?}?>
						</select>
						:
						<select name="cron_minutes">
							<?for($m = 0; $m <= 59; $m++){?>
								<option <?=$m == $array['cron_minutes'] ? 'selected' : ''?> value="<?=$m?>"><?=$m?></option>
							<?}?>
						</select>
					</div>
				</div>
				<div class="field">
					<div class="title">Активирован</div>
					<div class="value">
						<input <?=$array['is_active'] ? 'checked' : ''?> type="checkbox" name="is_active" value="1">
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
function orders(){
	global $status, $db, $page_title;
	$id = $_GET['id'];
	$db->update('orders', array('is_new' => 0), "`id`=$id");
	$order_values = $db->select('orders_values', '*', '`provider_id`='.$id);
	$page_title = "Заказы поставщика '".$db->getFieldOnID('providers', $id, 'cipher')."'";
	$status = "<a href='/admin'>Главная</a> > <a href='?view=providers'>Поставщики</a> > $page_title";?>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Наименование</td>
			<td>Закуп</td>
			<td>Розница</td>
			<td>Кол-во</td>
			<td>Сумма (З)</td>
			<td>Сумма (Р)</td>
			<td>Разница</td>
			<td>Статус</td>
		</tr>
		<?if (!count($order_values)){?>
			<td colspan="12">Заказов поставщика не найдено</td>
		<?}
		else{
			foreach ($order_values as $order_value){
				$provider_item = $db->select('providers_items', 'provider_id,item_id,price,provider_id', '`id`='.$order_value['provider_item_id']);
				$provider_item = $provider_item[0];
				$provider_markup = $db->getFieldOnID('providers', $order_value['provider_id'], 'percent') / 100;
				$brend_id = $db->getFieldOnID('items', $provider_item['item_id'], 'brend_id')?>
				<tr class="status_<?=$order_value['status']?>">
					<td><?=$db->getFieldOnID('brends', $brend_id, 'title')?></td>
					<td><?=$order_value['article']?></td>
					<td><?=$order_value['title']?></td>
					<?$price = get_price($provider_item);
					$price_markup = round($price - $price * $provider_markup);?>
					<td class="price_format"><?=$price_markup?></td>
					<td class="price_format"><?=$price?></td>
					<td><?=$order_value['quan']?></td>
					<?$summ = $order_value['quan'] * $price;
					$summ_markup = $price_markup * $order_value['quan'];
					$total += $summ_markup ?>
					<td class="price_format"><?=$summ_markup?></td>
					<td class="price_format"><?=$summ?></td>
					<td class="price_format"><?=$summ - $summ_markup?></td>
					<td class="change_status">
							<b><?=$db->getFieldOnID('orders_statuses', $order_value['status'], 'title')?></b>
					</td>
				</tr>
			<?}
		}?>
		<tr>
			<td style="text-align: right" colspan="10">Итого: <b><span class="price_format"><?=$total?></span></b> руб.</td>
		</tr>
	</table>
<?}
function priceEmail(){
	global $status, $db, $page_title;
	$array = array();
	if (!empty($_POST)){
		$db->insert(
			'email_prices',
			[
				'store_id' => $_GET['store_id'],
				'settings' => json_encode($_POST)
			],
			['duplicate' =>[
				'settings' => json_encode($_POST)
			], 'print_query' => false]
		);
		message("Успешно сохранено!");
		$array = $_POST;
	}
	else{
		$emailPrice = $db->select_one('email_prices', '*', "`store_id`={$_GET['store_id']}");
		if (!empty($emailPrice)) $array = json_decode($emailPrice['settings'], true);
	}
	$store = $db->select_unique("
		SELECT
			ps.id AS store_id,
			ps.title AS store,
			ps.provider_id,
			p.title AS provider
		FROM
			#provider_stores ps
		LEFT JOIN
			#providers p ON p.id = ps.provider_id
		WHERE
			ps.id = {$_GET['store_id']}
	");
	$store = $store[0];
	$page_title = "Загрузка с E-mail для {$store['store']}";
	$status = "<a href='/admin'>Главная</a> > <a href='?view=providers'>Поставщики</a> >";
	$status .= "<a href='/admin/?view=providers&act=stores&id={$store['provider_id']}'>{$store['provider']}</a> > $page_title";
	?>
	<div class="actions"></div>
	<div class="t_form">
		<div class="bg">
			<form method="post" enctype="multipart/form-data">
				<div class="field">
					<div class="title">Наименование для лога</div>
					<div class="value"><input required type="text" name="title" value="<?=$array['title']?>"></div>
				</div>
				<div class="field">
					<div class="title">E-mail</div>
					<div class="value"><input required type="text" name="from" value="<?=$array['from']?>"></div>
				</div>
				<div class="field">
					<div class="title">Наименование файла</div>
					<div class="value"><input type="text" name="name" value="<?=$array['name']?>"></div>
				</div>
				<div class="field">
					<div class="title">Является архивом</div>
					<div class="value">
						<label>
							<input type="radio" <?=$array['isArchive'] ? "checked" : ""?> name="isArchive" value="1">
							Да
						</label>
						<label>
							<input type="radio" <?=!$array['isArchive'] ? "checked" : ""?> name="isArchive" value="0">
							Нет
						</label>
					</div>
				</div>
				<div class="field">
					<div class="title">Наименование файла в архиве</div>
					<div class="value">
						<input type="text" name="nameInArchive" value="<?=$array['nameInArchive']?>">
					</div>
				</div>
				<div class="field">
					<div class="title">Индекс файла в архиве</div>
					<div class="value">
						<input type="text" name="indexInArchive" value="<?=$array['indexInArchive']?>">
					</div>
				</div>
				<div class="field">
					<div class="title">Тип файла</div>
					<div class="value">
						<label>
							<input type="radio" <?=$array['fileType'] == 'csv' ? 'checked' : ''?> name="fileType" value="csv" checked>
							CSV
						</label>
						<label>
							<input type="radio" <?=$array['fileType'] == 'excel' ? 'checked' : ''?>  name="fileType" value="excel">
							Excel
						</label>
					</div>
				<div class="field">
					<div class="title">Очищать прайс</div>
					<div class="value">
						<label>
							<input type="radio" <?=$array['clearPrice'] == 'onlyStore' ? 'checked' : ''?> name="clearPrice" value="onlyStore" checked>
							Только этого склада
						</label>
						<label>
							<input type="radio" <?=$array['clearPrice'] == 'provider' ? 'checked' : ''?> name="clearPrice" value="provider">
							Полностью поставщика
						</label>
						<label>
							<input type="radio" <?=$array['clearPrice'] == 'noClear' ? 'checked' : ''?>  name="clearPrice" value="noClear">
							Не очищать
						</label>
					</div>
				</div>
				<div class="field">
					<div class="title">Добавлять отсутствующие бренды</div>
					<div class="value">
						<label>
							<input <?=$array['isAddBrend'] == 1 ? 'checked' : ''?> type="radio" name="isAddBrend" value="1">
							Да
						</label>
						<label>
							<input <?=$array['isAddBrend'] == 0 ? 'checked' : ''?> type="radio" name="isAddBrend" value="0">
							Нет
						</label>
					</div>
				</div>
                <div class="field">
                    <div class="title">Вести лог</div>
                    <div class="value">
                        <label>
                            <input <?=$array['isLogging'] == 1 ? 'checked' : ''?> type="radio" name="isLogging" value="1">
                            Да
                        </label>
                        <label>
                            <input <?=$array['isLogging'] == 0 ? 'checked' : ''?> type="radio" name="isLogging" value="0">
                            Нет
                        </label>
                    </div>
                </div>
				<div class="field">
					<div class="title">Добавлять отсутствующую номенклатуру</div>
					<div class="value">
						<label>
							<input type="radio" <?=$array['isAddItem'] == '1' ? 'checked' : ''?> name="isAddItem" value="1">
							Да
						</label>
						<label>
							<input type="radio" <?=$array['isAddItem'] == '0' ? 'checked' : ''?> name="isAddItem" value="0">
							Нет
						</label>
					</div>
				</div>
				<div class="field">
					<div class="title">Наименование полей</div>
					<div class="value">
						<div>
							<span>Бренд</span>
							<input required type="text" name="fields[brend]" value="<?=$array['fields']['brend']?>">
						</div>
						<div>
							<span>Артикул</span>
							<input required type="text" name="fields[article]" value="<?=$array['fields']['article']?>">
						</div>
						<div>
							<span>Артикул по каталогу</span>
							<input required type="text" name="fields[article_cat]" value="<?=$array['fields']['article_cat']?>">
						</div>
						<div>
							<span>Наименование</span>
							<input type="text" name="fields[title]" value="<?=$array['fields']['title']?>">
						</div>
						<div>
							<span>В наличии</span>
							<input type="text" name="fields[inStock]" value="<?=$array['fields']['inStock']?>">
						</div>
						<div>
							<span>Упаковка</span>
							<input type="text" name="fields[packaging]" value="<?=$array['fields']['packaging']?>">
						</div>
						<div>
							<span>Прайс</span>
							<input required type="text" name="fields[price]" value="<?=$array['fields']['price']?>">
						</div>
					</div>
					<div class="field">
						<div class="title">Ссылка для крон</div>
						<div class="value">
							<input type="text" value="http://<?=$_SERVER['HTTP_HOST']?>/admin/?view=cron&act=emailPrice&store_id=<?=$_GET['store_id']?>">
						</div>
					</div>
					<div class="field">
						<div class="title"></div>
						<div class="value"><input type="submit" value="Сохранить"></div>
					</div>
				</div>
			</form>
		</div>
	</div>
<?}
function itemsToOrder(){
	global $status, $page_title, $db;
	$page_title = "Товары, ожидающие отправку в заказы";
	$status = "<a href='/admin'>Главная</a> > <a href='?view=providers'>Поставщики</a> > $page_title";
	$items = core\Provider::getCommonItemsToOrders();
	$countItems = core\Provider::getCountItemsToOrders($items);
	?>
	<div id="total" style="margin-top: 0;">Всего: <?=$countItems?></div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Поставщик</td>
			<td>Склад</td>
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Название</td>
			<td>Цена</td>
			<td>Количество</td>
			<td></td>
		</tr>
		<?if (count($items)){
			foreach($items as $providerTitle => $item){
				$showNextTd = true;
				foreach($item as $i){?>
					<tr class="sendOrder" title="Отправить в заказ">
						<?if ($showNextTd){?>
							<td rowspan="<?=count($item)?>">
								<a title="Отправить заказ" class="sendOrder" href="/admin/?view=cron&act=order<?=$providerTitle?>&from=providers"><?=$i['provider']?></a>
							</td>
							<?
							$showNextTd = false;
						}?>
						<td><?=$i['store']?></td>
						<td><?=$i['brend']?></td>
						<td><?=$i['article']?></td>
						<td><?=$i['title_full']?></td>
						<td><?=$i['price']?></td>
						<td><?=$i['count']?></td>
						<td>
							<a href="?view=providers&act=removeFromBasket&<?=http_build_query($i)?>" class="remove">
								<span class="icon-cancel-circle1"></span>
							</a>
						</td>
					</tr>
				<?}?>
			<?}
		}
		else{?>
			<tr><td colspan="7">Товаров не найдено</td></tr>
		<?}?>
	</table>
	<a style="display: block;margin-top: 10px" href="<?=$_SERVER['HTTP_REFERER']?>">Назад</a>
<?}
function setAddress($params){?>
    <div id="status" class="t_form" style="">
        <div class="bg">
            <a href="/admin">Главная</a>
            &gt; Настройки &gt;
            <a href="?view=settings&amp;act=providers">Настройки поставщиков</a>
            > <a href="/admin/?view=providers&act=provider&id=<?=$_GET['id']?>"><?=$params['providerInfo']['title']?></a>
            > Сопоставить адреса <?=isset($params['userInfo']) ? ' для '.$params['userInfo']['full_name'] : ''?>
        </div>
    </div>

    <input class="intuitive_search" type="text" name="items" value="" placeholder="Выбрать пользователя" required>
    
    <?if ($params['userInfo']['id']){?>
        <form method="post" action="/admin/?view=providers&act=set_address&id=<?=$_GET['id']?>">
            <table class="t_table" style="margin-top: 15px">
                <thead>
                <tr>
                    <th>Адрес сайта</th>
                    <th>Адрес сайта <?=$params['providerInfo']['title']?></th>
                </tr>
                </thead>
                <tbody>
                <?foreach($params['addressProviderList'] as $providerAddress){?>
                    <tr>
                        <td label="Адрес сайта <?=$params['providerInfo']['title']?>">
                            <select name="address_site_id[]">
                                <option value="">...ничего не выбрано</option>
                                <?foreach($params['addressList'] as $address){
                                    $selected = $address['address_provider_id'] == $providerAddress['id'] ? 'selected' : ''?>
                                    <option <?=$selected?> value="<?=$address['address_site_id']?>">
                                        <?=\core\UserAddress::getString(
                                            $address['id'],
                                            json_decode($address['json'], true)
                                        )?>
                                    </option>
                                <?}?>
                            </select>
                        </td>
                        <td label="Адрес сайта">
                            <input type="hidden" name="user_id[]" value="<?=$params['userInfo']['id']?>">
                            <input type="hidden" value="<?=$providerAddress['id']?>" name="address_provider_id[]">
                            <span><?=$providerAddress['address']?></span>
                        </td>
                    </tr>
                <?}?>
                <tr>
                    <td colspan="2">
                        <input type="submit" value="Сохранить">
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
    <?}?>
<?}?>

