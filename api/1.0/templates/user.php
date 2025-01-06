<?php
/** @global string $act */
/** @global string $method */
/** @global array $queryParams */
/** @global array $result */
/** @global Database $db */

use core\Database;
use core\Exceptions\NotFoundException;
use core\Fund;
use core\Payment\Paykeeper;
use core\Synchronization;
use core\User;

switch ($act){
    case 'replenishBill':
        if (isset($queryParams['bill_type'])){
            $bill_type = $queryParams['bill_type'];
        }
        else{
            $userArrangement = User::getUserArrangement1C($queryParams['user_id'], $queryParams['arrangement']);
            if (!$userArrangement){
                break;
            }
            $bill_type = $userArrangement['bill_type'];
        }

        if (!$bill_type){
            break;
        }

        if ($queryParams['document_date'] && $queryParams['act'] != 'refund') {
            $dateTime = DateTime::createFromFormat('d.m.Y H:i:s', $queryParams['document_date']);
            $from = $dateTime->format('Y-m-d').' 00:00:00';
            $to = $dateTime->format('Y-m-d').' 23:59:59';
            $result = Database::getInstance()->query("
                SELECT
                    *
                    FROM #funds
                WHERE
                    `user_id` = {$queryParams['user_id']} 
                    AND `sum` = {$queryParams['sum']}
                    AND `bill_type` = $bill_type
                    AND `document_date` BETWEEN '$from' and '$to'
            ");
            if ($result->num_rows) {
                if (isset($queryParams['type_operation']) && $queryParams['type_operation'] == 'cancel'){
                    Synchronization::cancelOperation($queryParams, $result, $bill_type);
                }
                break;
            }
        }

        $document = Synchronization::get1CDocument($queryParams['document_title']);
        if (
            ($queryParams['act'] == 'payment' || $queryParams['act'] == 'refund')
            && !empty($document)
        ){
            if ($document['sum'] == $queryParams['sum']){
                break;
            }

            $queryParams['previous_sum'] = $document['sum'];
            $queryParams['bill_type'] = $bill_type;
            $queryParams['fund_id'] = $document['fund_id'];
            User::changePayment($queryParams);
            break;
        }
        if (!empty($document)){
            break;
        }

        if ($queryParams['act'] == 'refund'){
            if ($queryParams['paykeeper_id']) {
                $result = Paykeeper::refundMoney(
                    $queryParams['paykeeper_id'],
                    $queryParams['sum'],
                    (bool)$queryParams['partial']
                );
                if ($result['result'] != 'fail') {
                    Synchronization::set1CDocument($queryParams['document_title'], $queryParams);
                }
                break;

            }
            User::returnMoney(
                $queryParams['user_id'],
                $queryParams['sum'],
                $queryParams['comment'],
                $bill_type,
                $queryParams['document_date']
            );
            Synchronization::set1CDocument($queryParams['document_title'], [
                'sum' => $queryParams['sum'],
                'fund_id' => Fund::$last_id,
            ]);
            break;
        }

        User::replenishBill([
            'user_id' => $queryParams['user_id'],
            'sum' => $queryParams['sum'],
            'comment' => $queryParams['comment'],
            'bill_type' => $bill_type,
            'document_title' => $queryParams['document_title'],
            'document_date' => $queryParams['document_date'] ?? ''
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