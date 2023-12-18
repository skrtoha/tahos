<?php

ini_set('error_reporting', E_ERROR);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

use core\Messengers\Telegram;

require_once('core/Database.php');

try{
    $telegram = new Telegram($_SERVER['REMOTE_ADDR']);
    $data = file_get_contents('php://input');
    $data = json_decode($data, true);

    $telegram->parseMessage($data['message']);
    Telegram::writeLogFile($data['message']);
}
catch (Throwable $exception){
    Telegram::writeLogFile(
        $exception->getMessage()."\n"
        .$exception->getFile()." "
        .$exception->getLine()."\n"
        .$exception->getTraceAsString());
}
