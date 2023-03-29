<?php
header('Content-Type: application/json; charset=utf-8');

use core\Exceptions\NotFoundException;
use core\Setting;

ini_set('error_reporting', E_ERROR | E_PARSE);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$root = $_SERVER['DOCUMENT_ROOT'];

require_once($root.'/core/DataBase.php');
require_once($root.'/core/functions.php');
require_once($root.'/vendor/autoload.php');

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$synchronization_token = Setting::get('site_settings', 'synchronization_token');
if ($synchronization_token != getallheaders()['token']) die("Неверный токен");

$path = "$root/api/{$_GET['version']}/templates/{$_GET['view']}.php";

if (!file_exists($path)) die('Метод не найден');

$act = $_GET['act'];
$method = $_SERVER['REQUEST_METHOD'];

$queryParams = [];
if ($_GET['params']){
    $params = $_GET['params'];
    $params = substr($params, 0, -1);
    $queryParams = explode('/', $params);
}
if ($method == 'POST'){
    $body = file_get_contents('php://input');
    $queryParams = array_merge($queryParams, json_decode($body, true));
}

$output = [];
$result = [];
try{
    require_once($path);
}
catch (NotFoundException $e){
    $output['errors'][] = $e->getMessage();
}
if (!isset($output['errors'])){
    $output['result'] = $result;
}

echo json_encode($output, JSON_UNESCAPED_UNICODE);
die();
