<?php 
ini_set('error_reporting', E_PARSE);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

use core\Log;
use core\Managers;

// set_exception_handler('error_handler',);
// function error_handler($e){
// 	if (get_class($e) == 'ParseError') return debug($e);
// 	Log::insertThroughException($e);
// }

require_once('../core/DataBase.php');
require_once('templates/functions.php');
session_start();
$view = $_GET['view'];
if (!$view) header("Location: /admin/?view=index");

$db = new core\DataBase();

$connection = new core\Connection($db);
$db->setProfiling($connection->connection_id);
// $settings = $db->select('settings', '*'); $settings = $settings[0];
Managers::$permissions = json_decode(Managers::getPermissions($_SESSION['manager']['group_id']), true);

if (core\Managers::isAccessForbidden($_GET['view'])){
	$view = 'forbidden';
}

if ($_GET['view'] == 'orders' && ($_GET['act'] == 'print' || isset($_GET['automaticOrder']))){
	require_once('functions/orders.function.php');
	require_once 'templates/orders.php';
	exit();
}
if (
	!$_SESSION['auth'] && 
	$_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest' && 
	$view != 'cron'
) $view = 'authorization';
if (file_exists("functions/$view.function.php")) require_once("functions/$view.function.php");

ob_start();
require_once ("templates/$view.php");	
$content = ob_get_contents();
ob_clean();
require_once('templates/main.php');

?>