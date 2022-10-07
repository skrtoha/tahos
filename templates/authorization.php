<?php
use core\Authorize;
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
$user = $db->select_one('users', "id,email", "(`email`='$login' OR `phone`='$login') AND `password`='$password'");
if (empty($user)){
    setcookie('message', 'Неверный логин или пароль!');
    setcookie('message_type', 'error');
}
else{
    $_SESSION['user'] = $user['id'];
    if (isset($_POST['remember']) && $_POST['remember'] == 'on'){
        $jwt = Authorize::getJWT([
            'user_id' => $user['id'],
            'login' => $user['email']
        ]);
        setcookie('jwt', $jwt, time()+60*60*24*30);
    }
    $db->update('user_ips', ['user_id' => $user['id']], "ip = '{$_SERVER['SERVER_ADDR']}'");
    message('Вы успешно авторизовались!');
}
if (strpos($_SERVER['HTTP_REFERER'], 'exceeded_connections') > 0){
    header('Location: /');
}
else header("Location: {$_SERVER['HTTP_REFERER']}");
die();
?>