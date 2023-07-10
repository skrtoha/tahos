<?php
/** @global string $act */
/** @global string $method */
/** @global array $queryParams */
/** @global array $result */
/** @global Database $db */

use core\Database;
use core\Exceptions\NotFoundException;
use core\Provider\Tahos;

switch ($act){
    case 'getSelf':
        $result = Tahos::getSelfStores();
        break;
    case 'addStoreItem':
        if (!Tahos::isSelfStore($queryParams['store_id'])) break;
        if ($queryParams['store_id']){
            $db->delete('store_items', "`store_id` = {$queryParams['store_id']}");
        }
        foreach($queryParams['items'] as $row){
            Database::getInstance()->insert(
                'store_items',
                [
                    'store_id' => $queryParams['store_id'],
                    'item_id' => $row['item_id'],
                    'price' => $row['price'],
                    'in_stock' => $row['quan']
                ],
                ['duplicate' => [
                    'price' => $row['price'],
                    'in_stock' => $row['quan']
                ]]
            );
        }
        break;
    default:
        throw new NotFoundException('Действие не найдено');
}