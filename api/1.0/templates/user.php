<?php
/** @global string $act */
/** @global string $method */
/** @global array $queryParams */
/** @global array $result */
/** @global Database $db */

use core\Database;
use core\Exceptions\NotFoundException;
use core\Synchronization;
use core\User;

switch ($act){
    case 'replenishBill':
        if (isset($queryParams['bill_type'])){
            $bill_type = $queryParams['bill_type'];
        }
        else{
            $userArrangement = Database::getInstance()->select_one(
                'user_1c_arrangements',
                '*',
                "`user_id` = {$queryParams['user_id']} AND `uid` = '{$queryParams['arrangement']}'"
            );
            if (!$userArrangement){
                break;
            }
            $bill_type = $userArrangement['bill_type'];
        }

        $document = Synchronization::get1CDocument($queryParams['document_title']);
        if (!empty($document) && $queryParams['act'] == 'plus'){
            if ($document['sum'] == $queryParams['sum']){
                break;
            }
            $queryParams['previous_sum'] = $document['sum'];
            $queryParams['bill_type'] = $bill_type;
            $queryParams['fund_id'] = $document['fund_id'];
            User::changeReplenishBill($queryParams);
            break;
        }

        if ($queryParams['act'] == 'unset'){
            $countFundDistribution = $db->getCount('fund_distribution', "`replenishment_id` = {$document['fund_id']}");
            if ($countFundDistribution){
                throw new Exception('Имеются оплаченные выдачи. Отмена проведения невозможна!');
            }
            User::unsetReplenishBill($queryParams);
            break;
        }

        if ($queryParams['act'] == 'minus'){
            User::returnMoney(
                $queryParams['user_id'],
                $queryParams['sum'],
                $queryParams['comment'],
                $bill_type
            );
            break;
        }

        User::replenishBill([
            'user_id' => $queryParams['user_id'],
            'sum' => $queryParams['sum'],
            'comment' => $queryParams['comment'],
            'bill_type' => $bill_type,
            'document_title' => $queryParams['document_title']
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
    case 'getArrangement':
        $bill_type = '';
        if ($queryParams[1] == 'Наличный') $bill_type = User::BILL_CASH;
        if ($queryParams[1] == 'Безналичный') $bill_type = User::BILL_CASHLESS;
        $result = $db->select_one(
            'user_1c_arrangements',
            '*',
            "`user_id` = {$queryParams[0]} AND `bill_type` = $bill_type"
        );
        break;
    case 'getAddresses':
        $result = [];
        $addressList = $db->select('user_addresses', '*', "`user_id` = {$queryParams[0]}");
        foreach($addressList as $value){
            $addressData = json_decode($value['json'], true);
            $result[] = [
                'address_id' => $value['id'],
                'title' => \core\UserAddress::getString($value['id'], $addressData)
            ];
        }
        break;
    default:
        throw new NotFoundException('Действие не найдено');
}