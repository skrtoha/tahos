<?php

require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/DataBase.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/templates/functions.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

switch($_POST['act']){
    case 'delete_item':
        switch($_POST['tab']){
            case 'avito':
                $db->delete(
                    'categories_items',
                    "`item_id` = {$_POST['item_id']} AND `category_id` = {$_POST['category_id']}"
                );
                break;
        }
        break;
}