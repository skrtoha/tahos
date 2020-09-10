<?if (!$_SESSION['user']) header('Location: /');
$title = 'Настройки';
function sel_user($column){
	global $user;
	return $_POST[$column] ? $_POST[$column] : $user[$column];
}
if (isset($_POST['form_subscribe_submitted'])){
	unset($_POST['form_subscribe_submitted']);
	$update = $_POST;
	if (!$update['is_subscribe']) $update['is_subscribe'] = 0;
	$db->update('users', $update, "`id` = {$_SESSION['user']}");
	message('Успешно сохранено!');
	header("Location: /settings");
}
$res_user_socials = $db->query("
	SELECT
		s.id,
		s.title,
		us.user_id
	FROM
		#socials s
	LEFT JOIN #users_socials us ON us.social_id=s.id AND us.user_id={$_SESSION['user']}
", '');
?>
<div class="settings-page" style="margin-top: 20px">
	<h1>Настройки</h1>
	<div class="col">
		<form id="personal-data" method="post">
			<h3>Персональные данные</h3>
			<div class="input-wrap">
				<label for="organisation">Организация:</label>
				<?if ($user['organization_name']){
					$organization_type = $db->getFieldOnID('organizations_types', $user['organization_type'], 'title');
					$organization = $organization_type.' '.$user['organization_name'];
				}?>
				<input type="text" id="organization" value="<?=$organization?>" disabled>
			</div>
			<div class="input-wrap">
				<label for="name">Имя:</label>
				<input type="text" id="name" value="<?=sel_user('name_1')?>" disabled>
			</div>
			<div class="input-wrap">
				<label for="surname">Фамилия:</label>
				<input type="text" id="surname" value="<?=sel_user('name_2')?>" disabled>
			</div>
			<div class="input-wrap">
				<label for="fathername">Отчество:</label>
				<input type="text" id="fathername" value="<?=sel_user('name_3')?>" disabled>
			</div>
		</form>
		<form id="contact-info">
			<h3>Контактные данные</h3>
			<div class="input-wrap">
				<label for="email">
					Электронная почта:
				</label>
				<input type="email" id="email" name="email" value="<?=sel_user('email')?>">
			</div>
			<div class="input-wrap">
				<label for="phone">
					Телефон:
				</label>
				<input type="tel" id="phone" placeholder="+7 (___) ___-__-__" value="<?=sel_user('telefon')?>">
			</div>
			<div class="input-wrap">
				<label for="address">
					Фактический адрес:
				</label>
				<input type="text" id="address" name="adres" value="<?=sel_user('adres')?>">
			</div>
		</form>				
		<h3 style="margin-top: 40px">Способ доставки</h3>
		<form action="" id="delivery">
			<div class="input-wrap">
				<label for="delivery-way">
					Выбранный способ:
				</label>
				<select id="delivery-way">
					<option <?=$user['delivery_type'] == 'Доставка' ? 'selected' : ''?> value="delivery">Доставка</option>
					<option <?=$user['delivery_type'] == 'Самовывоз' ? 'selected' : ''?> value="pickup">Самовывоз</option>
				</select>
			</div>
			<div class="input-wrap pickup-addreses">
				<label for="pickup-address">
					Пункт выдачи:
				</label>
				<?$issues = $db->select('issues', 'id,title', '', 'title', true);?>
				<select id="pickup-points" name="issue_id">
					<option value="">...выберите</option>
					<?foreach ($issues as $issue){?>
						<option <?=$user['issue_id'] == $issue['id'] ? 'selected' : ''?> value="<?=$issue['id']?>"><?=$issue['title']?></option>
					<?}?>
				</select>
				<button id="bt_show_map">Выбрать на карте</button>
			</div>
	</form>
	</div>
	<div class="col">
		<form id="additional">
			<h3>Дополнительно</h3>
			<div class="input-wrap">
				<label for="get_news">
					Хочу получать новости от Tahos
				</label>
				<label class="switch">
					<input id="get_news" type="checkbox" <?=$user['get_news'] ? 'checked' : ''?>>
					<div class="slider round"></div>
				</label>
			</div>
			<div class="input-wrap">
				<label for="get_news">
					Показывать все аналоги
				</label>
				<label style="position: relative;top: 5px" class="switch">
					<input id="hide_analogies" type="checkbox" <?=$user['show_all_analogies'] ? 'checked' : ''?>>
					<div class="slider round"></div>
				</label>
			</div>
			<div class="input-wrap">
				<label for="get_notifications">
					Получать дополнительные оповещения
				</label>
				<label style="position: relative;top: 5px" class="switch">
					<input id="get_notifications" type="checkbox" <?=$user['get_notifications'] ? 'checked' : ''?>>
					<div class="slider round"></div>
				</label>
			</div>
			<div class="input-wrap">
				<label for="additional-functions">
					Включить дополнительные функции
				</label>
				<?$bl_addfunctions = ($user['currency_id'] != 1) ? true : false;?>
				<label class="switch">
					<input id="additional-functions" type="checkbox" <?=$bl_addfunctions ? 'checked' : ''?>>
					<div class="slider round"></div>
				</label>
			</div>
			<div style="<?=$bl_addfunctions ? "display: block" : ""?>" class="additional-functions">
				<div class="input-wrap currency">
					<label for="currency">
						Валюта:
					</label>
					<?$currencies = $db->select('currencies', 'id,charcode');?>
					<select id="currency">
						<?foreach ($currencies as $currency){
							$selected = $user['currency_id'] == $currency['id'] ? 'selected' : ''?>?>
							<option <?=$selected?> value="<?=$currency['id']?>"><?=$currency['charcode']?></option>
						<?}?>
					</select>
				</div>
			</div>
		</form>
		<form id="change-password">
			<h3>Сменить пароль</h3>
			<input type="hidden">
			<div class="input-wrap">
				<label for="old_password">
					Старый пароль:
				</label>
				<input type="password" id="old_password" checked>
			</div>
			<div class="input-wrap">
				<label for="new_password">
					Новый пароль:
				</label>
				<input type="password" id="new_password" checked>
			</div>
			<div class="input-wrap">
				<label for="repeat_new_password">
					Повторить пароль:
				</label>
				<input type="password" id="repeat_new_password" checked>
			</div>
			<button id="save_form">Сохранить</button>
		</form>
		<form style="">
			<h3>Привязать социальные сети</h3>
			<script src="//ulogin.ru/js/ulogin.js"></script>
			<div id="uLogin" data-ulogin="display=buttons;fields=first_name,last_name;redirect_uri=http://<?=$_SERVER['HTTP_HOST']?>/registration;mobilebuttons=0">
				<?while ($value = $res_user_socials->fetch_assoc()){
					if ($value['user_id']){?>
						<span class="social <?=$value['title']?> binded" social_id="<?=$value['id']?>" title="Отвязать <?=$value['title']?>"></span>
					<?}
					else{?>
						<span class="social <?=$value['title']?>" data-uloginbutton="<?=$value['title']?>" title="Привязать <?=$value['title']?>"></span>
					<?}
				}?>
			</div>
		</form>
		<form id="subscribe" method="post">
			<h3>Рассылка</h3>
			<input type="hidden" name="form_subscribe_submitted" value="1">
			<div class="input-wrap">
				<label for="is_subscribe">
					Рассылка прайсов
				</label>
				<label style="position: relative;top: 5px" class="switch">
					<input name="is_subscribe" type="checkbox" <?=$user['is_subscribe'] ? 'checked' : ''?> value="1">
					<div class="slider round"></div>
				</label>
			</div>
			<div class="input-wrap">
				<label for="email">
					Электронная почта:
				</label>
				<input type="email" name="subscribe_email" value="<?=$user['subscribe_email']?>">
			</div>
			<div class="input-wrap">
				<label for="delivery-way">
					Формат:
				</label>
				<select name="subscribe_type">
					<option <?=$user['subscribe_type'] == 'csv' ? 'selected' : ''?>  value="csv">CSV</option>
					<option <?=$user['subscribe_type'] == 'xls' ? 'selected' : ''?> value="xls">XLS</option>
				</select>
			</div>
			<input type="submit" value="Сохранить">
		</form>
	</div>
</div>
<div id="overlay"></div>
<div id="show_map">
	<div id="show_map_2">
		<div id="div_issue" class="input_box issue_div clearfix" style="">
				<p>Пункт выдачи</p>
				<select name="issue_id">
					<option value="">...выберите</option>
					<?foreach($issues as $issue){?>
						<option value="<?=$issue['id']?>"><?=$issue['title']?></option>
					<?}?>
				</select>
			</div>
		<div id="issue_check"></div>
		<button title="Close (Esc)" type="button" class="bt_close">×</button>
		<button id="apply">Применить</button>
	</div>
</div>
