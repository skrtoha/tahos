<?php
session_start();

use core\Authorize;
use core\Basket;
use core\Exceptions\NotFoundException;
use core\Setting;
use core\Category;

ini_set('error_reporting', E_PARSE);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

require_once('core/Database.php');
require_once('core/functions.php');
require_once('vendor/autoload.php');

$db = new core\Database();

$result = $db->query("
    select
        id,
        comment
    from
        tahos.tahos_funds f
    where created >= '2023-07-24' and comment like '%от%'
        and document_date is null
");
foreach($result as $row){
    $str = preg_replace('/.* от/', '', $row['comment']);
    $str = trim($str);
    $dateTime = DateTime::createFromFormat('d.m.Y H:i:s', $str);
    if (!$dateTime){
        continue;
    }
    $db->update('funds', ["document_date" => $dateTime->format('Y-m-d H:i:s')], "id = {$row['id']}");
}

$connection = new core\Connection($db);
if ($connection->denyAccess) die('Доступ к данной странице с Вашего ip запрещен');
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$detect = new \Mobile_Detect;
$device = 'desktop';
if ($detect->isTablet()) $device = 'tablet';
if ($detect->isMobile() && !$detect->isTablet()) $device = 'mobile';

$view = $_GET['view'] ?: 'index';
if ($view == 'exit'){
	session_destroy();
	setcookie('jwt', '');
	header("Location: ".$_SERVER['HTTP_REFERER']);
    die();
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
    $jwtInfo = Authorize::getJWTInfo($_COOKIE['jwt']);
    $_SESSION['user'] = $jwtInfo['user_id'];
}

/** @var mysqli_result $res_user */
$res_user = core\User::get(['user_id' => $_SESSION['user'] ?: false]);

if ($res_user->num_rows){
    foreach($res_user as $value) $user = $value;
    $debt = \core\User::getDebt($user);
}
else $user = $res_user;

if (isset($user['markupSettings'])) $user['markupSettings'] = json_decode($user['markupSettings'], true);

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

/*core\UserIPS::registerIP([
	'user_id' => $_SESSION['user'] ?: null,
	'ip' => $_SERVER['REMOTE_ADDR'],
	'view' => $view
]);*/

if ($_SESSION['user']){
    $basket = Basket::get($_SESSION['user']);
}


$categories = Category::getAll('c.hidden = 0 AND sc.hidden = 0');

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