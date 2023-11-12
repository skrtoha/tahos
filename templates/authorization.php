<?php
use core\Authorize;
use core\Database;

/** @global $db Database */

if ($_POST['token']){
	$s = file_get_contents('http://ulogin.ru/token.php?token=' . $_POST['token'] . '&host=' . $_SERVER['HTTP_HOST']);
	$user = json_decode($s, true);
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