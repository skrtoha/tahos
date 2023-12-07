<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 0);

use core\Messengers\Telegram;

require_once('core/DataBase.php');

$telegram = new Telegram();
$data = file_get_contents('php://input');
$data = json_decode($data, true);
$telegram->writeLogFile($data);
