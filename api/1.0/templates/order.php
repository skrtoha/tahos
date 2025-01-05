<?php
/** @global string $act */
/** @global string $method */
/** @global array $queryParams */
/** @global array $result */
/** @global Database $db */

use core\Database;
use core\Exceptions\NotFoundException;
use core\OrderValue;
use core\StoreItem;
use core\Synchronization;
use core\User;

switch ($act){
    case 'get':
        $result = Synchronization::getNoneSynchronizedOrders();
        break;
    case 'setSynchronizedOSI':
        Synchronization::setOrdersSynchronized($queryParams);
        break;
    case 'createOrderAndSendOrdered':
        $changedOrders = [];
        $orderValues = core\OrderValue::get(['osi' => $queryParams], '');
        foreach($orderValues as $ov){
            core\OrderValue::setStatusInWork($ov, true);
            $ov['ordered'] = $ov['quan'];
            $changedOrders[] = $ov;
        }
        $result = $changedOrders;
        break;
    case 'setStatusIssued':
        $arrangement = User::getUserArrangement1C($queryParams['user_id'], $queryParams['arrangement']);
        if (!$arrangement){
            break;
        }

        $osi = array_keys($queryParams['items']);

        require_once ($_SERVER['DOCUMENT_ROOT'].'/admin/functions/order_issues.function.php');
        $issuesObject = new Issues(Database::getInstance());
        $issued = $issuesObject->getByOSI($osi);

        $isAllItemsIssued = [];
        foreach($issued as $ov){
            $osi = "{$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}";
            if ($ov['issued'] >= $queryParams['items'][$osi]){
                $isAllItemsIssued[$osi] = true;
            }
            else {
                $isAllItemsIssued[$osi] = false;
            }
        }

        $income = [];
        foreach($queryParams['items'] as $key => $value){
            $array = explode('-', $key);
            if ($isAllItemsIssued[$key]){
                continue;
            }
            $income[$arrangement['bill_type']]["{$array[0]}:{$array[2]}:{$array[1]}"] = $value;
        }

        if (empty($income)) break;

        $issues = new \Issues($db, $queryParams['user_id']);
        $issues->setIncome($income);
        break;
    case 'changeOrder':
        $orderValueList = OrderValue::get([
            'osi' => array_keys($queryParams['commonList'])
        ], '')->fetch_all(MYSQLI_ASSOC);
        Synchronization::cancelItemsFromOrder($queryParams['cancelled'], $orderValueList);
        Synchronization::setStatusArrived($queryParams['arrived'], $orderValueList);
        Synchronization::changePriceOrder($queryParams['commonList']);
        break;
    case 'returnOrderValues':
        for ($i = 0; $i < count($queryParams); $i++) {
            $items = [];
            $osi = Synchronization::getArrayOSIFromString($queryParams['osi'][$i]);
            $ov_result = core\OrderValue::get($osi);
            $ov = $ov_result->fetch_assoc();

            if ($ov['returned'] >= $queryParams['quan'][$i]) continue;

            $items[] = [
                'order_id' => $ov['order_id'],
                'store_id' => $ov['store_id'],
                'item_id' => $ov['item_id'],
                'title' => "<b class=\"brend_info\" brend_id=\"{$ov['brend_id']}\">{$ov['brend']}</b> 
					<a href=\"/search/article/{$ov['article']}\" class=\"articul\">{$ov['article']}</a> 
					{$ov['title_full']}",
                'summ' => $ov['quan'] * $ov['price'],
                'return_price' => $queryParams['return_price'][$i],
                'days_from_purchase' => 14,
                'packaging' => $ov['packaging'],
                'reason_id' => 1,
                'quan' => $queryParams['quan'][$i]
            ];
            core\Returns::createReturnRequest($items);
            $paramsReturn = [
                'return_price' => $queryParams['return_price'][$i],
                'quan' => $queryParams['quan'][$i],
                'status_id' => 3
            ];
            $res_return = core\Returns::get($osi);
            $return = $res_return->fetch_assoc();
            core\Returns::processReturn($paramsReturn, $return);
        }
        break;
    case 'create':
        $dateTime = DateTime::createFromFormat('d.m.Y 00:00:00', "{$queryParams['date_issue']} 00:00:00");
        $resOrder = $db->insert('orders', [
            'user_id' => $queryParams['user_id'],
            'delivery' => $queryParams['delivery'],
            'pay_type' => $queryParams['pay_type'],
            'address_id' => $queryParams['address_id'],
            'date_issue' => $dateTime->format('Y-m-d H:i:s'),
            'entire_order' => $queryParams['entire_order'] == 'Да' ? 1 : 0,
            'bill_type' => $queryParams['bill_type']
        ]);

        if ($resOrder !== true){
            throw new Exception('Ошибка создания заказа');
        }

        $res_user = User::get(['user_id' => $queryParams['user_id']]);
        foreach($res_user as $value) $user = $value;

        $order_id = $db->last_id();
        foreach($queryParams['order_values'] as $row){
            $array = explode('-', $row['osi']);
            $query = StoreItem::getQueryStoreItem($user['discount']);
            $query .= "
                WHERE si.store_id = {$array[1]} AND si.item_id = {$array[2]}
            ";
            $resultQuery = $db->query($query, '');
            $storeItemInfo = $resultQuery->fetch_assoc();

            $res = $db->insert(
                'orders_values',
                [
                    'order_id' => $order_id,
                    'store_id' => $array[1],
                    'item_id' => $array[2],
                    'withoutMarkup' => $storeItemInfo['priceWithoutMarkup'],
                    'price' => $storeItemInfo['price'],
                    'quan' => $row['quan'],
                    'synchronized' => 1
                ]
            );
            $result['order_id'] = $order_id;
        }

        break;
    default:
        throw new NotFoundException('Действие не найдено');
}
