<?php
session_start();

ini_set('error_reporting', E_ERROR);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once($_SERVER['DOCUMENT_ROOT'].'/core/Database.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/core/functions.php');
require_once ($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');
$db = new core\Database();

$result = \core\User::get(['confirm_email_token' => $_GET['token']]);
if (!$result->num_rows){
    message('Email не подтвержден', false);
}
else{
    $userInfo = $result->fetch_assoc();
    \core\User::update($userInfo['id'], ['email_confirmed' => 1]);
    message('Email успешно подтвержден');
    $_SESSION['user'] = $userInfo['id'];
}
header("Location: /");
die();
