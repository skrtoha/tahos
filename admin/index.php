<?php 
use core\Log;

set_exception_handler('error_handler');
function error_handler(Exception $e){
	Log::insertThroughException($e);
}

// ini_set('error_reporting', E_PARSE | E_ERROR);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);


require_once('../core/DataBase.php');
require_once('templates/functions.php');
session_start();
$view = $_GET['view'] ? $_GET['view'] : 'items';

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$settings = $db->select('settings', '*'); $settings = $settings[0];
$db->setProfiling();

// debug($_SERVER);
if ($view == 'orders' && $_GET['act'] == 'print'){
	require_once('functions/orders.function.php');
	require_once 'templates/orders.php';
	exit();
}
if (!$_SESSION['auth'] && $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest' && $view != 'cron') $view = 'authorization';
if (file_exists("functions/$view.function.php")) require_once("functions/$view.function.php");

ob_start();
require_once ("templates/$view.php");	
$content = ob_get_contents();
ob_clean();
require_once('templates/main.php');

?>