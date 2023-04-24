<?php
/** @global string $act */
/** @global string $method */
/** @global array $queryParams */
/** @global array $result */
/** @global Database $db */

use core\Database;
use core\Exceptions\NotFoundException;
use core\Synchronization;

switch ($act){
    case 'create':
        $result = json_encode(Synchronization::createItem($queryParams));
        break;
    case 'get':
        $result = [];
        $query = \core\Item::getQueryItemInfo();
        $query .= " WHERE i.id in (".implode(',', $queryParams).")";
        $mysqli_result = $db->query($query);
        foreach($mysqli_result as $row){
            $result[] = [
                'article_cat' => $row['article_cat'] ? $row['article_cat'] : $row['article'],
                'brend' => $row['brend'],
                'brend_id' => $row['brend_id'],
                'title_full' => $row['title_full'],
                'item_id' => $row['id'],
            ];
        }
        break;
    case 'getAnalogies':
        $result = core\Search::articleStoreItems(
            $queryParams[0],
            $queryParams[1],
            [],
            'analogies'
        );
        break;
    default:
        throw new NotFoundException('Действие не найдено');
}