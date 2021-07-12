<?php
use core\Log;
ini_set('error_reporting', E_ERROR | E_PARSE);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once('core/DataBase.php');
require_once('core/functions.php');
require_once('vendor/autoload.php');

$db = new core\Database();

$limit = 100;
$count = 0;
do{
    $res_search = $db->query("
        SELECT
            c.id,
            @temp := REPLACE(c.url, '/article/', '') as temp,
            REGEXP_REPLACE (c.url, '^/article/.*?-', '') AS article,
            @item_id := REGEXP_REPLACE(@temp, '-.*$', '') AS item_id
        FROM
            #connections c
        WHERE
            c.url REGEXP '^/article/[[:digit:]]+-[[:alnum:]]+' and
            c.brend_article is null
        LIMIT 0, $limit
    ", '');
    foreach($res_search as $value){
        $itemInfo = \core\Item::getByID($value['item_id']);
        if (!$itemInfo) continue;
        $db->update(
            'connections',
            ['brend_article' => "{$itemInfo['brend']}-{$itemInfo['article']}"],
            "`id` = {$value['id']}"
        );
    }
    $count += $limit;
    echo "id: {$value['id']}, count:".$count; echo "\n";
}while($res_search->num_rows >= $limit);
