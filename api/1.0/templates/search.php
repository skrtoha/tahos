<?php
/** @global string $act */
/** @global string $method */
/** @global array $queryParams */
/** @global array $result */
/** @global Database $db */

use core\Database;
use core\Exceptions\NotFoundException;
use core\Search;

switch ($act){
    case 'article':
        $article = \core\Item::articleClear($queryParams[0]);
        $result = Search::searchItemDatabase($article, Search::TYPE_SEARCH_ARTICLE);
        break;
    case 'articleDetail':
        $result = Search::articleStoreItems($queryParams[0], $queryParams[1]);
        foreach($result['store_items'] as $item_id => & $value){
            if ($item_id == $queryParams[0]) $value['is_main'] = 1;
            else $value['is_main'] = 0;
        }
        break;
    default:
        throw new NotFoundException('Действие не найдено');
}