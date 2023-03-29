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
        $result = Search::searchItemDatabase($queryParams[0], Search::TYPE_SEARCH_ARTICLE);
        break;
    case 'articleDetail':
        $result = Search::articleStoreItems($queryParams[0], $queryParams[1]);
        break;
    default:
        throw new NotFoundException('Действие не найдено');
}