<?if (!$_SESSION['user']) header('Location: /');
$title = 'Настройки';
if (!empty($_POST)){
    $result = \core\User::updateSettings($_POST, $user);
    
    if ($result === true){
        message('Успешно сохранено!');
        header("Location: /settings");
        die();
    }
	message($result, false);
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
$data = !empty($_POST) ? $_POST['data'] : $user;
?>
<div class="settings-page" style="margin-top: 20px">
	<h1>Настройки</h1>
    <form id="personal-data" method="post">
        <div class="col">
            <h3>Персональные данные</h3>
            <div class="input-wrap">
                <label for="organisation">Организация:</label>
                <?if ($user['organization_name']){
                    $organization_type = $db->getFieldOnID('organizations_types', $user['organization_type'], 'title');
                    $organization = $organization_type.' '.$user['organization_name'];
                }?>
                <input type="text" name="type_organization" value="<?=$organization?>" disabled>
            </div>
            <div class="input-wrap">
                <label for="name">Имя:</label>
                <input type="text" id="name" value="<?=$data['name_1']?>" disabled>
            </div>
            <div class="input-wrap">
                <label for="surname">Фамилия:</label>
                <input type="text" id="surname" value="<?=$data['name_2']?>" disabled>
            </div>
            <div class="input-wrap">
                <label for="fathername">Отчество:</label>
                <input type="text" id="fathername" value="<?=$data['name_3']?>" disabled>
            </div>
            <div class="form" id="contact-info">
                <h3>Контактные данные</h3>
                <div class="input-wrap">
                    <label for="email">
                        Электронная почта:
                    </label>
                    <input type="email" id="email" name="data[email]" value="<?=$data['email']?>">
                </div>
                <div class="input-wrap">
                    <label for="phone">
                        Телефон:
                    </label>
                    <input type="tel" name="data[phone]" id="phone" placeholder="+7 (___) ___-__-__" value="<?=$data['phone']?>">
                </div>
                <div class="input-wrap">
                    <label for="address">
                        Фактический адрес:
                    </label>
                    <input type="text" id="address" name="data[address]" value="<?=$data['address']?>">
                </div>
            </div>
            <h3 style="margin-top: 40px">Способ доставки</h3>
            <div class="form" id="delivery">
                <div class="input-wrap">
                    <label for="pay_type">
                        Способ оплаты:
                    </label>
                    <select id="pay_type" name="data[pay_type]">
                        <?foreach(\core\User::getPayType($user['user_type']) as $type){?>
                            <option <?=$data['pay_type'] == $type ? 'selected' : ''?> value="<?=$type?>">
                                <?=$type?>
                            </option>
                        <?}?>
                    </select>
                </div>
                <div class="input-wrap">
                    <label for="delivery-way">
                        Выбранный способ:
                    </label>
                    <select id="delivery-way" name="data[delivery_type]">
                        <option <?=$data['delivery_type'] == 'Доставка' ? 'selected' : ''?> value="Доставка">Доставка</option>
                        <option <?=$data['delivery_type'] == 'Самовывоз' ? 'selected' : ''?> value="Самовывоз">Самовывоз</option>
                    </select>
                </div>
                <?$classHidden = $data['delivery_type'] == 'Доставка' ? 'hidden' : ''?>
                <div class="input-wrap pickup-addreses <?=$classHidden?>">
                    <label for="pickup-address">
                        Пункт выдачи:
                    </label>
                    <?$issues = $db->select('issues', 'id,title', '', 'title', true);?>
                    <select id="pickup-points" name="data[issue_id]">
                        <option value="">...выберите</option>
                        <?foreach ($issues as $issue){?>
                            <option <?=$data['issue_id'] == $issue['id'] ? 'selected' : ''?> value="<?=$issue['id']?>"><?=$issue['title']?></option>
                        <?}?>
                    </select>
                    <button id="bt_show_map">Выбрать на карте</button>
                </div>
                <?$classHidden = $data['delivery_type'] == 'Самовывоз' ? 'hidden' : ''?>
                <div class="input-wrap set-addresses <?=$classHidden?>">
                    <button>Адреса доставки</button>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="form" id="additional">
                <h3>Дополнительно</h3>
                <div class="input-wrap">
                    <label for="get_news">
                        Хочу получать новости от Tahos
                    </label>
                    <label class="switch">
                        <input value="1" id="get_news" name="data[get_news]" type="checkbox" <?=$data['get_news'] ? 'checked' : ''?>>
                        <div class="slider round"></div>
                    </label>
                </div>
                <div class="input-wrap">
                    <label for="get_news">
                        Показывать все аналоги
                    </label>
                    <label style="position: relative;top: 5px" class="switch">
                        <input value="1" id="hide_analogies" name="data[show_all_analogies]" type="checkbox" <?=$data['show_all_analogies'] ? 'checked' : ''?>>
                        <div class="slider round"></div>
                    </label>
                </div>
                <div class="input-wrap">
                    <label for="get_notifications">
                        Получать sms-оповещение при отказе поставщика
                    </label>
                    <label style="position: relative;top: 5px" class="switch">
                        <input value="1" id="get_notifications" name="data[get_sms_provider_refuse]" type="checkbox" <?=$data['get_sms_provider_refuse'] ? 'checked' : ''?>>
                        <div class="slider round"></div>
                    </label>
                </div>
                <div class="input-wrap">
                    <label for="get_notifications">
                        Получать дополнительные оповещения
                    </label>
                    <label style="position: relative;top: 5px" class="switch">
                        <input value="1" id="get_notifications" name="data[get_notifications]" type="checkbox" <?=$data['get_notifications'] ? 'checked' : ''?>>
                        <div class="slider round"></div>
                    </label>
                </div>
                <div class="additional-functions">
                    <div class="input-wrap currency">
                        <label for="currency">
                            Валюта:
                        </label>
                        <?$currencies = $db->select('currencies', 'id,charcode');?>
                        <select id="currency" name="data[currency_id]">
                            <?foreach ($currencies as $currency){
                                $selected = $data['currency_id'] == $currency['id'] ? 'selected' : ''?>?>
                                <option <?=$selected?> value="<?=$currency['id']?>"><?=$currency['charcode']?></option>
                            <?}?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form" id="change-password">
                <h3>Сменить пароль</h3>
                <input type="hidden">
                <div class="input-wrap">
                    <label for="old_password">
                        Старый пароль:
                    </label>
                    <input type="password" name="password[old_password]" id="old_password">
                </div>
                <div class="input-wrap">
                    <label for="new_password">
                        Новый пароль:
                    </label>
                    <input type="password" id="new_password" name="data[password]">
                </div>
                <div class="input-wrap">
                    <label for="repeat_new_password">
                        Повторить пароль:
                    </label>
                    <input type="password" name="password[repeat_new_password]" id="repeat_new_password">
                </div>
            </div>
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
            <div class="form" id="subscribe">
                <h3>Рассылка</h3>
                <div class="input-wrap">
                    <label for="is_subscribe">
                        Рассылка прайсов
                    </label>
                    <label style="position: relative;top: 5px" class="switch">
                        <input name="data[is_subscribe]" type="checkbox" <?=$user['is_subscribe'] ? 'checked' : ''?> value="1">
                        <div class="slider round"></div>
                    </label>
                </div>
                <div class="input-wrap">
                    <label for="email">
                        Электронная почта:
                    </label>
                    <input type="email" name="data[subscribe_email]" value="<?=$user['subscribe_email']?>">
                </div>
                <div class="input-wrap">
                    <label for="delivery-way">
                        Формат:
                    </label>
                    <select name="data[subscribe_type]">
                        <option <?=$user['subscribe_type'] == 'csv' ? 'selected' : ''?>  value="csv">CSV</option>
                        <option <?=$user['subscribe_type'] == 'xls' ? 'selected' : ''?> value="xls">XLS</option>
                    </select>
                </div>
                <input type="submit" value="Сохранить">
            </div>
        </div>
    </form>
</div>
<div id="overlay"></div>
<div id="show_map" class="popup">
	<div>
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
<?
$user_id = $_SESSION['user'];
$form = 'personal-data';
require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/addressee/template.php';
?>
