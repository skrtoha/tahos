<?php 
require_once('class/database_class.php');
require_once('core/functions.php');
// print_r($_POST);
$login = $_POST['login'];
$password = md5($_POST['password']);
if (!preg_match("/.+@.+/", $login)) $login = str_replace(array(' ', ')', '(', '-'), '', $login);
$user = $db->select('users', "id", "(`email`='$login' OR `telefon`='$login') AND `password`='$password'");
// print_r($_GET);
if (count($user)){
	$_SESSION['user'] = $user[0]['id'];
	setcookie('message', 'Вы успешно авторизовались!');
	setcookie('message_type', 'ok');
}
else{
	setcookie('message', 'Неверный логин или пароль!');
	setcookie('message_type', 'error');	
}
header("Location: ".$_SERVER['HTTP_REFERER']);
?>