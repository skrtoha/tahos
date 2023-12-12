<?php

use core\Database;

require_once('../../core/DataBase.php');

switch ($_REQUEST['act']){
    case 'get_reasons':
        $result = Database::getInstance()->select('return_reasons', '*');
        echo json_encode($result);
        break;
    case 'createReturn':
        core\Returns::createReturnRequest($_POST['items']);
        break;
}