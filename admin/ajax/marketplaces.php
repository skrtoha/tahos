<?php

use core\Database;
use core\Item;
use core\Marketplaces\Ozon;
use core\Provider\Tahos;
use core\Setting;

require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/Database.php");
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
                $db->delete('ozon_item', "`offer_id` = {$_POST['item_id']}");
                break;
        }
        break;
    case 'getCategoryOzon':
        $category_id = false;
        if (isset($_POST['tahos_category_id']) && !isset($_POST['category_id'])){
            $result = Database::getInstance()->select_one('ozon_match_category', '*', "`tahos_category_id` = {$_POST['tahos_category_id']}");
            $category_id = $result['ozon_category_id'];
        }
        elseif(isset($_POST['category_id'])){
            $category_id = $_POST['category_id'];
        }

        echo Ozon::getOzonTplTreeCategories($category_id);
        break;
    case 'ozon_get_type':
        if (isset($_POST['item_id'])){
            $ozonProductInfo = Ozon::getProductInfo($_POST['item_id']);
            $result = $ozonProductInfo['types'];
        }
        else{
            $result = Ozon::getType($_POST['category_id']);
        }
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

        if (isset($_POST['danger_class_id'])){
            $attributeDangerClass = [];
            $attributeDangerClass['id'] = Ozon::$oilDangerClass['attribute_id'];
            $attributeDangerClass['values'][] = [
                'value' => $_POST['danger_class_id']
            ];
            $attributes[] = $attributeDangerClass;
        }

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

        Setting::update('marketplaces', 'ozon_markup_old_price', $_POST['ozon_markup_old_price']);
        Setting::update('marketplaces', 'ozon_markup_common', $_POST['ozon_markup_old_price']);

        $ozonItemArray = [];
        $ozonItemArray[$item['offer_id']]['offer_id'] = $item['offer_id'];

        $item['images'] = [];
        $photoNames = scandir(core\Config::$imgPath . "/items/big/{$_POST['offer_id']}/");
        foreach($photoNames as $name) {
            if (!preg_match('/.+\.jpg/', $name)) continue;
            $item['images'][] = core\Config::$imgUrl . '/items/big/'.$_POST['offer_id'].'/'.$name;
        }

        $items[] = $item;

        /*$product_id = Ozon::getProductIdByOfferId($item['offer_id']);
        if ($product_id){
            Ozon::archiveProduct($product_id);
            Ozon::deleteProduct($item['offer_id']);
        }*/

        $resultImport = Ozon::getResponse('v2/product/import', ['items' => $items]);

        Ozon::checkResult($resultImport);

        $task_id = $resultImport['result']['task_id'];

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

        $ozonItemArray[$item['offer_id']]['store_id'] = $_POST['store_id'];
        $ozonItemArray[$item['offer_id']]['markup_marketplace'] = $_POST['markup_marketplace'];
        $ozonItemArray[$item['offer_id']]['item_title'] = $_POST['name'];
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
            $fbs_sku = Ozon::getFbsSku($_POST['offer_id']);
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
    case 'ozonGetSelfStores':
        $currentMainStore = Setting::get('marketplaces', 'main_store');
        $selfStores = Tahos::getSelfStores();
        $output = [];
        foreach($selfStores as & $store){
            if ($store['id'] == $currentMainStore){
                $store['active'] = 1;
            }
            else{
                $store['active'] = 0;
            }
        }
        echo json_encode($selfStores);
        break;
    case 'ozonSetMainStore':
        Setting::update('marketplaces', 'main_store', $_POST['value']);
        echo json_encode([]);
        break;
    case 'ozonGetTahosCategory':
        $tahos_category_id = false;
        if (isset($_POST['category_id'])){
            $result = Database::getInstance()->select_one(
                'ozon_match_category',
                '*',
                "`ozon_category_id` = {$_POST['category_id']}"
            );
            if (isset($result['tahos_category_id'])){
                $tahos_category_id = $result['tahos_category_id'];
            }
        }
        elseif (isset($_POST['tahos_category_id'])){
            $tahos_category_id = $_POST['tahos_category_id'];
        }
        echo Ozon::getTahosTplCategories($tahos_category_id);
        break;
    case 'ozonSetMatchCategory':
        $output = [
            'result' => [],
            'error' => ''
        ];
        $checkUnique = isset($_POST['check_unique']) && $_POST['check_unique'];
        $result = Ozon::setMatchCategory($_POST, $checkUnique);
        if ($result !== true){
            $output['error'] = $result;
        }
        else {
            $output['result'] = $_POST;
        }
        echo json_encode($output);
        break;
    case 'ozonGetMatchedCategories':
        $result = Ozon::getMatchedCategories();
        echo json_encode($result);
        break;
    case 'ozonDeleteMatchCategory':
        echo json_encode([
            'success' => Ozon::deleteMatchCategory($_POST)
        ]);
        break;
    case 'ozonGetOneMatchCategory':
        $where = '';
        if (isset($_POST['category_id'])){
            $where = "`ozon_category_id` = {$_POST['category_id']}";
        }
        if (isset($_POST['tahos_category_id'])){
            $where = "`tahos_category_id` = {$_POST['tahos_category_id']}";
        }
        $result = Database::getInstance()->select_one('ozon_match_category', '*', $where);
        echo json_encode($result);
        break;
    case 'getMainStores':
        $output = '<select name="store_id">';
        $output .= "<option value='0' data-price='0'>...выберите</option>";
        $query = Ozon::getQueryStoreItems();
        $result = Database::getInstance()->query("
            $query
            where si.item_id = {$_POST['item_id']}
            order by si.price
        ");
        foreach($result as $row){
            $string = "{$row['title']} - {$row['cipher']} ({$row['store_price']} руб. {$row['in_stock']}";
            if ($row['measure']){
                $string .= " {$row['measure']})";
            }
            else{
                $string .= " шт.)";
            }
            $selected = isset($_POST['store_id']) && $_POST['store_id'] == $row['id'] ? 'selected': '';
            $output .= "<option {$selected} data-price='{$row['store_price']}' value='{$row['id']}'>{$string}</option>";
        }
        $output .= '</select>';
        echo $output;
        break;
    case 'get_oil_danger_class':
        echo json_encode(Ozon::$oilDangerClass);
        break;

}