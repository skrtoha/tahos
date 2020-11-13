<?php 
require_once('core/functions.php');
if ($_POST['token']){
	$s = file_get_contents('http://ulogin.ru/token.php?token=' . $_POST['token'] . '&host=' . $_SERVER['HTTP_HOST']);
	$user = json_decode($s, true);
	// debug($socials);
	// debug($user);
	$social_id = $db->getField('socials', 'id', 'title', $user['network']);
	$res_social = $db->query("
		SELECT
			us.*
		FROM
			#users_socials us
		LEFT JOIN #socials s ON s.id=us.social_id
		WHERE
			s.title='{$user['network']}' AND
			us.uid={$user['uid']}
	", '');
	if ($res_social->num_rows){
		$array = $res_social->fetch_assoc();
		$_SESSION['user'] = $array['user_id'];
		message('Вы успешно авторизовались!');
	}
	else message('Ошибка авторизации!', false);
	header("Location: /");
	exit();
}
$login = $_POST['login'];
$password = md5($_POST['password']);
if (!preg_match("/.+@.+/", $login)) $login = str_replace(array(' ', ')', '(', '-'), '', $login);
$user = $db->select('users', "id", "(`email`='$login' OR `telefon`='$login') AND `password`='$password'");
// print_r($_GET);
if (count($user)){
	// if ($login == 'skr_toha@list.ru') $_SESSION['user'] = 4;
	// else 
		$_SESSION['user'] = $user[0]['id'];
	setcookie('message', 'Вы успешно авторизовались!');
	setcookie('message_type', 'ok');
}
else{
	setcookie('message', 'Неверный логин или пароль!');
	setcookie('message_type', 'error');	
}
header("Location: {$_SERVER['HTTP_REFERER']}");
?>