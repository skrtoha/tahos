<?php

use core\Exceptions\NotFoundException;
use core\Log;
use core\Setting;

ini_set('error_reporting', E_ERROR | E_PARSE);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once('core/DataBase.php');
require_once('core/functions.php');
require_once('vendor/autoload.php');

$db = new core\Database();

session_start();
$connection = new core\Connection($db);
if ($connection->denyAccess) die('Доступ к данной странице с Вашего ip запрещен');
$db->connection_id = $connection->connection_id;
$settings = $db->select('settings', '*', '`id`=1'); $settings = $settings[0];
$db->setProfiling();

$detect = new \Mobile_Detect;
$device = 'desktop';
if ($detect->isTablet()) $device = 'tablet';
if ($detect->isMobile() && !$detect->isTablet()) $device = 'mobile';
// echo "$device<br>";

$view = $_GET['view'] ?: 'index';
if ($view == 'exit'){
	session_destroy();
	setcookie('jwt', '');
	header("Location: ".$_SERVER['HTTP_REFERER']);
}
if($_GET['act'] == 'unbind'){
	$db->delete(
		'users_socials', 
		"`user_id`={$_SESSION['user']} AND `social_id`={$_GET['id']}"
	);
	message('Социальная сеть успешно отвязана!');
	header('Location: /settings');
}


if (!empty($_COOKIE['jwt']) && !$_SESSION['user']){
    $jwtInfo = \core\Authorize::getJWTInfo($_COOKIE['jwt']);
    $_SESSION['user'] = $jwtInfo['user_id'];
}

/** @var mysqli_result $res_user */
$res_user = core\User::get(['user_id' => $_SESSION['user'] ?: false]);

if ($res_user->num_rows){
    $user = $res_user->fetch_assoc();
    $debt = \core\User::getDebt($user);
}
else $user = $res_user;

if (isset($user['markupSettings'])) $user['markupSettings'] = json_decode($user['markupSettings'], true);

//blockSite
$blockData = json_decode(Setting::get('blockSite', 'is_blocked'), true);
$time_espiration = $blockData['time'] + $blockData['count_seconds'];
$count_seconds = $time_espiration - time();
if ($count_seconds < 0 && $blockData['is_blocked']){
    $data = [
        'is_blocked' => 0,
        'count_seconds' => 0,
        'time' => 0
    ];
    Setting::update('blockSite', 'is_blocked', json_encode($data));
}
if ($count_seconds > 0 && $blockData['is_blocked']){
	$view = 'blocked';
}

core\UserIPS::registerIP([
	'user_id' => $_SESSION['user'] ?: null,
	'ip' => $_SERVER['REMOTE_ADDR'],
	'view' => $view
]);

$basket = get_basket();

$path = "templates/$view.php";
if (file_exists($path)){
	ob_start();
    try{
        require_once($path);
    }
    catch (NotFoundException $e){
        $message = $e->getMessage();
        require_once ('404.php');
        die();
    }
	$content = ob_get_contents();
	ob_clean();
}
require_once('templates/main.php');
?>
