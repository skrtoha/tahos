<?php
set_time_limit(0);
require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/DataBase.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/templates/functions.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

function addXMLValue(& $node, $type, $value){
    global $dom;
    $element = $dom->createElement($type);
    $text = $dom->createTextNode($value);
    $element->appendChild($text);
    $node->appendChild($element);
}

switch ($_GET['act']){
    case 'avito':
        $issue = $db->select_one('issues', "*", "`is_main`=1");
        $issue['issue_id'] = $issue['id'];

        $query = "
            SELECT
                si.item_id,
                b.title AS brend,
                i.article,
                i.title_full,
                i.full_desc,
                si.in_stock,
                MIN(CEIL(si.price * c.rate + si.price * c.rate * ps.percent / 100)) as price,
                si.packaging,
                cat.parent_id,
                cat.title as category,
                cat_parent.title as category_parent
            FROM
                #store_items si
                    LEFT JOIN
                #categories_items ci on ci.item_id = si.item_id
                    LEFT JOIN
                #categories cat on cat.id = ci.category_id
                    LEFT JOIN
                #categories cat_parent on cat_parent.id = cat.parent_id
                    LEFT JOIN
                #items i ON i.id = si.item_id
                    LEFT JOIN
                #brends b ON b.id = i.brend_id
                    LEFT JOIN
                #provider_stores ps ON ps.id = si.store_id
                    LEFT JOIN
                #currencies c ON c.id=ps.currency_id
            WHERE
                    si.store_id IN (23) AND
                    si.price > 0 AND
                    i.is_blocked = 1 AND
                    i.full_desc is not null AND
                    cat.parent_id in (132, 136, 138)
            GROUP BY
                si.item_id
        ";
        $res_store_items = $db->query($query);

        $dom = new DOMDocument('1.0', 'utf-8');
        $Ads = $dom->createElement('Ads');
        $Ads->setAttribute('formatVersion', 3);
        $Ads->setAttribute('target', 'Avito.ru');

        foreach($res_store_items as $row){
            $Ad = $dom->createElement('Ad');

            addXMLValue($Ad, 'Id', $row['item_id']);
            addXMLValue($Ad, 'Address', $issue['adres']);
            addXMLValue($Ad, 'Category', 'Запчасти и аксессуары');
            addXMLValue($Ad, 'Title', $row['title_full']);
            addXMLValue($Ad, 'Description', $row['full_desc']);
            addXMLValue($Ad, 'GoodsType', 'Запчасти');
            addXMLValue($Ad, 'AdType', 'Товар от производителя');
            addXMLValue($Ad, 'ProductType', 'Для автомобилей');
            addXMLValue($Ad, 'Price', $row['price']);

            $Images = $dom->createElement('Images');
            $photoNames = scandir(core\Config::$imgPath . "/items/big/{$row['item_id']}/");
            foreach($photoNames as $name) {
                if (!preg_match('/.+\.jpg/', $name)) continue;
                $Image = $dom->createElement('Image');
                $Image->setAttribute('url', core\Config::$imgUrl . '/items/big/' . $row['item_id'] . '/' . $name);
                $Images->appendChild($Image);
            }
            $Ad->appendChild($Images);

            if (in_array($row['parent_id'], [132])){
                addXMLValue($Ad, 'SparePartType', $row['category']);
            }
            else{
                addXMLValue($Ad, 'SparePartType', $row['category_parent']);
            }

            //заполняется, если выбран двигатель
            if (in_array($row['parent_id'], [136])) addXMLValue($Ad, 'EngineSparePartType', $row['category']);

            //заполняется если выбран кузов
            if (in_array($row['parent_id'], [138])) addXMLValue($Ad, 'BodySparePartType', $row['category']);

            addXMLValue($Ad, 'Condition', 'Новое');

            $Ads->appendChild($Ad);
        }

        $dom->appendChild($Ads);
        header('Content-Type: text/xml');
        echo $dom->saveXML();
        break;
}