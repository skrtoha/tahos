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
    default:
        throw new NotFoundException('Действие не найдено');
}