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
    case "get":
        $result = $db->query("
            SELECT
                u.id,
                'user' AS type,
                ".User::getUserFullNameForQuery()." AS name
            FROM
                #users u
            LEFT JOIN
                #organizations_types ot on ot.id = u.organization_type
            
            UNION
            
            SELECT
                p.id,
                'provider' AS type,
                p.title AS name
            FROM
                #providers p
            
            ORDER BY name
        ")->fetch_all(MYSQLI_ASSOC);
        break;
    default:
        throw new NotFoundException('Действие не найдено');
}