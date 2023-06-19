<?php

use core\Item;
use core\Marketplaces\Ozon;
use core\Setting;

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
            case 'ozon':
                $product_id = Ozon::getProductIdByOfferId($_POST['item_id']);
                if ($product_id){
                    Ozon::archiveProduct($product_id);
                    Ozon::deleteProduct($_POST['item_id']);
                }
                $db->delete('item_ozon', "`offer_id` = {$_POST['item_id']}");
                break;
        }
        break;
    case 'getCategoryOzon':
        //todo изменить при релизе
        $tree = Ozon::getTreeCategories();
//        $tree = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/tmp/ozon_tree.json'), true);

        $category_id = $_POST['category_id'] ?? null;
        $tpl = Ozon::getTplCategory($tree['result'][0]['children'], $category_id);
        echo "<select name='category_id'>
                <option selected value='0'>выберите...</option>
                $tpl
            </select>";
        break;
    case 'ozon_get_type':
        $result = Ozon::getType($_POST['category_id']);
        echo json_encode($result);
        break;
    case 'ozon_product_import':
        $items = [];
        $attributes = [];

        if (!empty($_POST['type'])){
            $attributeType = [];
            $attributeType['id'] = Ozon::ATTRIBUTE_TYPE;
            foreach($_POST['type'] as $type){
                $attributeType['values'][] = [
                    'dictionary_value_id' => $type
                ];
            }
            $attributes[] = $attributeType;
        }

        $attributeBrend = [];
        $attributeBrend['id'] = Ozon::ATTRIBUTE_BREND;
        $attributeBrend['values'][] = [
            'value' => $_POST['brend']
        ];
        $attributes[] = $attributeBrend;

        $attributeModel = [];
        $attributeModel['id'] = Ozon::ATTRUBUTE_MODEL;
        $attributeModel['values'][] = [
            'value' => $_POST['article']
        ];
        $attributes[] = $attributeModel;

        $attributeArticle = [];
        $attributeArticle['id'] = Ozon::ATTRIBUTE_ARTICLE;
        $attributeArticle['values'][] = [
            'value' => $_POST['article']
        ];
        $attributes[] = $attributeArticle;

        $attributeDescription = [];
        $attributeDescription['id'] = Ozon::ATTRIBUTE_DESCRIPTION;
        $attributeDescription['values'][] = [
            'value' => $_POST['marketplace_description']
        ];
        $attributes[] = $attributeDescription;

        $item['attributes'] = $attributes;

        $item['category_id'] = $_POST['category_id'];
        $item['depth'] = $_POST['depth'];
        $item['dimension_unit'] = 'mm';
        $item['height'] = $_POST['height'];
        $item['name'] = $_POST['name'];
        $item['offer_id'] = $_POST['offer_id'];
        $item['price'] = $_POST['price'];
        $item['old_price'] = $_POST['old_price'];
        $item['vat'] = $_POST['vat'];
        $item['weight'] = $_POST['weight'];
        $item['weight_unit'] = 'g';
        $item['width'] = $_POST['width'];
        if ($_POST['barcode']) $item['barcode'] = $_POST['barcode'];

        $fields = $item;
        $fields['weight'] = $item['weight'] / 1000;
        Item::setAdditionalOptions($fields, $item['offer_id']);
        unset($fields);

        Setting::update('marketplaces', 'markup', $_POST['ozon_markup']);

        $ozonItemArray = [];
        $ozonItemArray[$item['offer_id']]['offer_id'] = $item['offer_id'];

        $item['images'] = [];
        $photoNames = scandir(core\Config::$imgPath . "/items/big/{$_POST['offer_id']}/");
        foreach($photoNames as $name) {
            if (!preg_match('/.+\.jpg/', $name)) continue;
            $item['images'][] = core\Config::$imgUrl . '/items/big/'.$_POST['offer_id'].'/'.$name;
        }

        $items[] = $item;

        $product_id = Ozon::getProductIdByOfferId($item['offer_id']);
        if ($product_id){
            Ozon::archiveProduct($product_id);
            Ozon::deleteProduct($item['offer_id']);
        }

        $resultImport = Ozon::getResponse('v2/product/import', ['items' => $items]);

        Ozon::checkResult($resultImport);

        $task_id = $resultImport['result']['task_id'];
        $ozonItemArray[$item['offer_id']]['task_id'] = $task_id;

        $checkStatus = Ozon::importInfo($task_id);

        $output = [
            'success' => true,
            'errors' => '',
            'status' => ''
        ];
        foreach($checkStatus['result']['items'] as $row){
            $output['status'] = $row['status'];
            if (in_array($row['status'], [Ozon::STATUS_IMPORTED, Ozon::STATUS_PENDING])) continue;
            $output['success'] = false;
            foreach($row['errors'] as $e){
                $output['errors'] .= $e['message'];
            }
        }

        Ozon::setItemOzon($ozonItemArray);

        echo json_encode($output);
        break;
    case 'ozonGetProductInfo':
        $productInfo = Ozon::getProductInfo($_POST['offer_id']);
        echo json_encode($productInfo);
        break;
    case 'ozonGetInStock':
        $itemOzon = Ozon::getItemOzon(['offer_id' => $_POST['offer_id']]);
        $fbs_sku = $itemOzon['fbs_sku'];
        if (!$fbs_sku){
            $productInfo = Ozon::getProductInfo($_POST['offer_id'], false);
            Ozon::setItemOzon([
                [
                    'offer_id' => $_POST['offer_id'],
                    'fbs_sku' => $productInfo['fbs_sku']
                ]
            ]);
            $fbs_sku = $productInfo['fbs_sku'];
        }
        $result = Ozon::getInStock($fbs_sku);
        echo json_encode([
            'success' => true,
            'result' => $result[0]['present']
        ]);
        break;
    case 'ozonSetInStock':
        $output = [
            'success' => false,
            'result' => ''
        ];
        $result = Ozon::setInStock($_POST['offer_id'], $_POST['amount']);
        if ($result[0]['updated']){
            $output['success'] = true;
        }
        else{
            foreach($result[0]['errors'] as $row){
                $output['result'] .= $row."<br>";
            }
        }
        echo json_encode($output);
        break;
}