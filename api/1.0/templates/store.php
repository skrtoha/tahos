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
        $queryByItemId = [];
        foreach($queryParams['items'] as $row){
            $itemIDList[] = $row['item_id'];
            $queryByItemId[$row['item_id']] = [
                'price' => $row['price'],
                'quan' => $row['quan']
            ];
        }

        $selfStores = Tahos::getSelfStores();
        $selfStoresIDs = array_column($selfStores, 'id');

        $result = Database::getInstance()->query("
            select
                si.item_id,
                min(si.price) as price
            from
                tahos_store_items si
            where
                si.item_id in (".implode(",", $itemIDList).")
                AND si.store_id not in (".implode(",", $selfStoresIDs).")
                and si.in_stock > 0
            group by
                si.item_id
        ");

        $where = "";
        while($row = $result->fetch_assoc()){
            $where .= "(si.item_id = {$row['item_id']} and si.price = {$row['price']}) or ";
        }
        $where = substr($where, 0, -4);
        $result = Database::getInstance()->query("
            select
                min(si.item_id) as item_id,
                min(si.store_id) as store_id,
                min(si.price) as price
            from
                tahos_store_items si
            left join
                #provider_stores ps on ps.id = si.store_id
            where ($where)
            group by si.item_id;
        ");
        $minPriceByItemId = [];
        while($row = $result->fetch_assoc()){
            $minPriceByItemId[$row['item_id']] = [
                'store_id' => $row['store_id'],
                'price' => $row['price']
            ];
        }

        foreach($queryByItemId as $item_id => $row){
            $price = 0;
            if (
                isset($minPriceByItemId[$item_id]) &&
                $minPriceByItemId[$item_id]['price'] >= $queryByItemId[$item_id]['price']
            ){
                $price = $minPriceByItemId[$item_id]['price'];
            }
            else $price = $queryByItemId[$item_id]['price'];

            Database::getInstance()->insert(
                'store_items',
                [
                    'store_id' => $queryParams['store_id'],
                    'item_id' => $item_id,
                    'price' => $price,
                    'in_stock' => $row['quan']
                ],
                ['duplicate' => [
                    'in_stock' => $row['quan']
                ]]
            );

            $store_id = $minPriceByItemId[$item_id]['store_id'] ?? $queryParams['store_id'];
            Database::getInstance()->insert(
                'main_store_item',
                [
                    'store_id' => $store_id,
                    'item_id' => $item_id,
                    'min_price' => $queryByItemId[$item_id]['price']
                ]
                ,
                ['duplicate' => [
                    'min_price' => $queryByItemId[$item_id]['price'],
                    'store_id' => $store_id
                ]]
            );
            Database::getInstance()->insert(
                'required_remains',
                [
                    'item_id' => $item_id,
                    'self_store_id' => $queryParams['store_id'],
                    'requiredRemain' => 1
                ]
            );

        }
        break;
    default:
        throw new NotFoundException('Действие не найдено');
}