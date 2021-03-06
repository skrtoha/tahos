<?php 
define('SITE_KEY', '6LcfUd0ZAAAAAP-DnIWD44bV79Q-tOtXy7DmEs95');
define('SECRET_KEY', '6LcfUd0ZAAAAAMrCgklmnmhOVijPrjQi0BQYSAvd');
function getCaptcha($SecretKey) {
  $Response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".SECRET_KEY."&response={$SecretKey}");
  $Return = json_decode($Response);
  return $Return;
}

$title = "Регистрация";
if ($_SESSION['user']) header("Location: /settings");
if ($_POST['token']){
	$s = file_get_contents('http://ulogin.ru/token.php?token=' . $_POST['token'] . '&host=' . $_SERVER['HTTP_HOST']);
	$user = json_decode($s, true);
	$array = [
		'user_id' => $_SESSION['user'],
		'uid' => $user['uid'],
		'social_id' => $db->getField('socials', 'id', 'title', $user['network'])
	];
	$db->insert('users_socials', $array);
	// exit();
	message('Социальная сеть успешно привязана!');
	header('Location: '.$_SERVER['HTTP_REFERER']);
}
if ($_POST['form_submit']){
	$b_registration = true;

	$Return = getCaptcha($_POST['g-recaptcha-response']);
	if($Return->success != true || $Return->score < 0.5){
		message('Подвердите, что вы не робот');
		$b_registration = false;
	}
	
	if (!$_POST['accept_checkbox']){
		message('Ознакомтесь с пользовательским соглашением!', false);
		$b_registration = false;
	}
	// print_r($_POST);
	// exit();
	$user_type = $_POST['user_type'];
	$password_1 = $_POST['password_1'];
	$password_2 = $_POST['password_2'];
	$preg_password = "/.{5,}/";
	if (!preg_match($preg_password, $password_1)){
		message ('Пароль должен содержать не менее 5-ти символов', false);
		$b_registration = false;
	}
	if ($password_1 != $password_2){
		message('Пароли не совпадают!');
		$b_registration = false;
	};
	$name_1 = $_POST['name_1'];
	$name_2 = $_POST['name_2'];
	$name_3 = $_POST['name_3'];
	$preg_name = "/[А-яа-яA-Za-z]{2,}/";
	if (!preg_match($preg_name, $name_1) or !preg_match($preg_name, $name_2)){
		message('Фамилия и имя должны содержать не менее 2-х символов!', false);
		$b_registration = false;
	}
	$email = $_POST['email'];
	$preg_email = "/^[a-z-\._0-9]+@[\w]+\.[\w]+$/";
	if (!preg_match($preg_email, $email)){
		message('Неверный формат e-mail!', false);
		$b_registration = false;
	}
	$telefon = $_POST['telefon'];
	$telefon = str_replace(array(" ", ")", "(", "-"), "", $telefon);
	if ($db->getCount('users', "`telefon` LIKE '%$telefon'")){
		message('Такой номер телефона уже зарегистрирован!', false);
		$b_registration = false;
	}
	if ($db->getCount('users', "`email`='$email'")){
		message('Такой e-mail уже зарегистрирован!', false);
		$b_registration = false;
	}
	$organization_type = $_POST['organization_type'];
	$organization_name = $_POST['organization_name'];
	$delivery_type = $_POST['delivery_type'];
	$insert = array(
		'user_type' => $user_type,
		'organization_type' => $organization_type,
		'organization_name' => $organization_name,
		'name_1' => $name_1,
		'name_2' => $name_2,
		'name_3' => $name_3,
		'email' => $email,
		'delivery_type' => $delivery_type,
		'issue_id' => $_POST['issue_id'],
		'password' => md5($password_1),
		'telefon' => $telefon
	);
	if ($b_registration){
		$res = $db->insert('users', $insert, ['print_query' => false]);
		if ($res === true){
			message('Вы успешно зарегистрированы!');
			$_SESSION['user'] = $db->last_id();
			header("Location: /");
		}
		else die("$db->last_query | $res");
	}
}
$res_issues = $db->query("
	SELECT
		i.id,
		i.title
	FROM
		#issues i
");?>
<div class="registration">
	<h1>Регистрация</h1>
	<form action="" method="post" >
		<input type="hidden" name="form_submit" value="1">
		<div class="user_type clearfix">
			<?if ($_POST['form_submit']){?>
				<input type="radio" name="user_type" id="type_user_1" value="private" <?=$_POST['user_type'] == 'private' ? 'checked' : ''?>>
				<label for="type_user_1">Частное лицо</label>
				<input type="radio" name="user_type" id="type_user_2" value="entity" <?=$_POST['user_type'] == 'entity' ? 'checked' : ''?>>
				<label for="type_user_2">Юридическое лицо</label>
			<?}
			else{?>
				<input type="radio" name="user_type" id="type_user_1" value="private" checked>
				<label for="type_user_1">Частное лицо</label>
				<input type="radio" name="user_type" id="type_user_2" value="entity">
				<label for="type_user_2">Юридическое лицо</label>
			<?}?>
		</div>
		<?$style = ($_POST['form_submit'] and $_POST['user_type'] == 'entity') ? "display: block" : ""?>
		<div class="input_box company_name clearfix" style="<?=$style?>">
			<p>Наименование организации</p>
			<div class="input">
				<div class="select">
					<?$organizations_types = $db->select('organizations_types', '*');?>
					<select name="organization_type">
						<?foreach ($organizations_types as $value){
							$selected = $_POST['organization_type'] == $value['id'] ? 'selected' : '';?>
							<option <?=$selected?> value="<?=$value['id']?>"><?=$value['title']?></option>
						<?}?>
					</select>
				</div>
				<input type="text" name="organization_name" value="<?=$_POST['organization_name']?>">
			</div>
		</div>
		<div class="input_box clearfix">
			<p>Фамилия</p>
			<div class="input">
				<input type="text" name="name_1" value="<?=$name_1?>">
			</div>
		</div>
		<div class="input_box clearfix">
			<p>Имя</p>
			<div class="input">
				<input type="text" name="name_2" value="<?=$name_2?>">
			</div>
		</div>
		<div class="input_box clearfix">
			<p>Отчество</p>
			<div class="input">
				<input type="text" name="name_3" value="<?=$name_3?>">
			</div>
		</div>
		<div class="input_box input_phone clearfix">
			<p>Мобильный телефон <span class="info_btn"></span></p>
			<div class="info">
				<p class="title">Мобильный телефон</p>
				<p>Ваш мобильный телефон нужен нам исключительно для оперативной связи с вами.
					При регистрации вам однократно высылается пароль который вы сможете сменить в личном
					кабинете. Там же вы сможете настроить SMS оповещения о движении заказа.</p>
			</div>
			<div class="input" name="telefon">
				<input type="text" name="telefon" placeholder="+7 (___) ___-__-__" value="<?=$telefon?>">
			</div>
		</div>
		<div class="input_box input_email clearfix">
			<p>E-mail <span class="info_btn"></span></p>
			<div class="info">
				<p class="title">E-mail</p>
				<p>Ваш мобильный телефон нужен нам исключительно для оперативной связи с вами.
					При регистрации вам однократно высылается пароль который вы сможете сменить в личном
					кабинете. Там же вы сможете настроить SMS оповещения о движении заказа.</p>
			</div>
			<div class="input">
				<input type="text" name="email" value="<?=$email?>">
			</div>
		</div>
		<?if ($res_issues->num_rows){?>
			<div id="div_issue" class="input_box issue_div clearfix" style="">
				<p>Пункт выдачи</p>
				<div class="input">
					<div class="select">
						<select name="issue_id">
							<option value="">...выберите</option>
							<?while($row = $res_issues->fetch_assoc()){?>
								<option value="<?=$row['id']?>"><?=$row['title']?></option>
							<?}?>
						</select>
					</div>
				</div>
			</div>
			<div class="input_box delivery_method clearfix">
				<p>Способ доставки</p>
				<div class="input">
					<div class="select">
						<select name="delivery_type">
							<option selected value="Доставка">Доставка</option>
							<option value="Самовывоз" >Самовывоз</option>
						</select>
					</div>
				</div>
			</div>
		<?}?>
		<div class="input_box clearfix">
			<p>Пароль</p>
			<div class="input">
				<input type="password" name="password_1" value="<?=$password_1?>">
			</div>
		</div>
		<div class="input_box clearfix">
			<p>Повторите пароль</p>
			<div class="input">
				<input type="password" name="password_2" value="<?=$password_2?>">
			</div>
		</div>
		<div class="accept clearfix">
			<input type="checkbox" id="accept_checkbox" name="accept_checkbox">
			<label for="accept_checkbox">С <a target="_blank" href="/page/agreement">пользовательским соглашением</a> ознакомлен и согласен</label>
		</div>
		<input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response" />
		<script src="https://www.google.com/recaptcha/api.js?render=<?=SITE_KEY?>"></script>
		 <script>
			  grecaptcha.ready(function() {
					grecaptcha.execute('<?php echo SITE_KEY;?>', {action: 'homepage'}).then(function(token) {
						 console.log(token);
						 document.getElementById('g-recaptcha-response').value=token;
					});
			  });
	  </script>
		<button>Зарегистрироваться</button>
	</form>
</div>
<div id="map" class="">
	<!-- <script type="text/javascript" charset="utf-8" async src="https://api-maps.yandex.ru/services/constructor/1.0/js/?sid=z7VNSevfo6D6GHSqRPVSqExzHwOlBrQN&amp;width=100%25&amp;height=720&amp;lang=ru_RU&load=package.geoObjects&sourceType=constructor&amp;scroll=true"></script> -->
</div>
<div class="clear"></div>
