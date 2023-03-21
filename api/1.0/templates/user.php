<?php
/** @global string $act */
/** @global string $method */
/** @global array $queryParams */
/** @global array $result */
/** @global Database $db */

use core\Database;
use core\Exceptions\NotFoundException;
use core\User;

switch ($act){
        case 'replenishBill':
            $queryParams['comment'] = mb_substr($queryParams['comment'], 0, -8);

            User::replenishBill([
                'user_id' => $queryParams['user_id'],
                'sum' => $queryParams['sum'],
                'comment' => $queryParams['comment'],
                'bill_type' => User::BILL_CASHLESS
            ]);
        break;
        case 'setArrangement':
            foreach($queryParams as $d) User::setUserArrangement1C($d);
            $result = [];
            break;
    default:
        throw new NotFoundException('Действие не найдено');
}