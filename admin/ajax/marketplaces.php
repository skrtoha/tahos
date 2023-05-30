<?php

use core\Marketplaces\Ozon;

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
    case 'getCategoryOzon':
//        $tree = Ozon::getTreeCategories();
        $tree = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/tmp/ozon_tree.json'), true);
        $tpl = Ozon::getTplCategory($tree['result'][0]['children']);
        echo "<select name='category_id'>
                <option selected value='0'>выберите...</option>
                $tpl
            </select>";
        break;
    case 'ozon_get_type':
        $result = Ozon::getType($_POST['category_id']);
        echo json_encode($result['result']);
        break;
    case 'ozon_product_import':
        $items = [];
        $attributes = [];

        $attributeType = [];
        $attributeType['id'] = Ozon::ATTRIBUTE_TYPE;
        foreach($_POST['type'] as $type){
            $attributeType['values'][] = [
                'dictionary_value_id' => $type
            ];
        }
        $attributes[] = $attributeType;

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
        $item['vat'] = $_POST['vat'];
        $item['weight'] = $_POST['weight'];
        $item['weight_unit'] = 'g';
        $item['width'] = $_POST['width'];

        $item['images'] = [];
        $photoNames = scandir(core\Config::$imgPath . "/items/big/{$_POST['offer_id']}/");
        foreach($photoNames as $name) {
            if (!preg_match('/.+\.jpg/', $name)) continue;
            $item['images'][] = core\Config::$imgUrl . '/items/big/'.$_POST['offer_id'].'/'.$name;
        }

        $items[] = $item;
        $result = Ozon::getResponse('v2/product/import', ['items' => $items]);
        break;
}