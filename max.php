<?php

ini_set('error_reporting', E_ERROR);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

use core\Messengers\Max;

require_once('core/Database.php');

try{
    $data = file_get_contents('php://input');
    $data = json_decode($data, true);

    Max::getInstance()->parseMessage($data);

    Max::writeLogFile($data);
}
catch (Throwable $exception){
    Max::writeLogFile(
        $exception->getMessage()."\n"
        .$exception->getFile()." "
        .$exception->getLine()."\n"
        .$exception->getTraceAsString());
}

