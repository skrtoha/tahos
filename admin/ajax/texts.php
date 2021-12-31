<?php

use core\Setting;

require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/DataBase.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/templates/functions.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

switch($_GET['act']){
    case 'changeColumn':
        $titles = json_decode(Setting::get('texts', 'titles'), true);
        $titles[$_GET['number']] = $_GET['title'];
        Setting::update('texts', 'titles', json_encode($titles, JSON_UNESCAPED_UNICODE));
        break;
    case 'changeTextRubric':
        if (strlen($_GET['title']) == 0) $db->delete('text_rubrics', "`id` = {$_GET['id']}");
        else{
            $db->update('text_rubrics', ['title' => $_GET['title']], "`id` = {$_GET['id']}");
        }
        break;
}