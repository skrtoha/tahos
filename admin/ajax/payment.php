<?php
session_start();
use core\Payment\Paykeeper;

require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/Database.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/templates/functions.php");
error_reporting(E_ERROR | E_PARSE);

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();
switch($_POST['act']){
    case 'get_link':
        echo Paykeeper::getLinkReplenishBill($_SESSION['user'], $_POST['amount']);
        break;
}

?>