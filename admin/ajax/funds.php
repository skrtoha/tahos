<?php

use core\Database;
use core\Fund;
use core\User;

require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/Database.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/templates/functions.php");

$db = Database::getInstance();

switch($_POST['act']){
    case 'get':
        $fundInfo = Fund::getById($_POST['fund_id']);
        echo json_encode($fundInfo, JSON_UNESCAPED_UNICODE);
        break;
    case 'remove':
        Fund::removeById($_POST['fund_id']);
        break;
    case 'change':
        $db->startTransaction();
        $db->update(
            'funds',
            [
                'sum' => $_POST['sum'],
                'paid' => $_POST['paid'],
                'remainder' => $_POST['remainder'],
                'bill_type' => $_POST['bill_type']
            ],
            "`id` = {$_POST['fund_id']}"
        );
        if (isset($_POST['set_for_user']) && $_POST['set_for_user']) {
            $fundInfo = Fund::getById($_POST['fund_id']);
            $field = $_POST['bill_type'] == User::BILL_CASH ? 'bill_cash' : 'bill_cashless';
            $db->update(
                'users',
                [$field => $_POST['remainder']],
                "`id` = {$fundInfo['user_id']}"
            );
        }
        $db->commit();
        break;
}

?>