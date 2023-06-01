<?php
namespace core\Marketplaces;

use core\Provider;

class Ozon extends Marketplaces{
    const API_URL = 'https://api-seller.ozon.ru/';
    const CATEGORY_AUTO_GOODS = 17027495;
    const ATTRIBUTE_TYPE = 8229;
    const ATTRIBUTE_BREND = 85;
    const ATTRUBUTE_MODEL = 9048;
    const ATTRIBUTE_DESCRIPTION = 4191;
    const ATTRIBUTE_ARTICLE = 7236;

    public static $countProduct = 0;

    public static function getTreeCategories($category_id = self::CATEGORY_AUTO_GOODS){
        $result = self::getResponse(
            'v2/category/tree',
            [
                'category_id' => $category_id,
                'language' => 'DEFAULT'
            ]
        );
        return $result;
    }

    private static function tplMenu($category, $str, $category_id = null){
        $selected = $category_id && $category['category_id'] == $category_id ? 'selected' : '';
        $disabled = 'disabled';
        if(empty($category['children'])) $disabled = '';
        $menu = '<option '.$selected.' '.$disabled.' value="'.$category['category_id'].'">'.$str.' '.$category['title'].'</option>';
        if(!empty($category['children'])){
            $i = 1;
            for($j = 0; $j < $i; $j++) $str .= '→';
            $menu .= self::showCat($category['children'], $str, $category_id);
        }
        return $menu;
    }

    private static function showCat($data, $str, $category_id){
        $string = '';
        foreach($data as $item){
            $string .= self::tplMenu($item, $str, $category_id);
        }
        return $string;
    }

    public static function getTplCategory($tree, $category_id = null){
        return self::showCat($tree, '', $category_id);
    }

    public static function getType($category_id, $selected = []){
        $output = [];
        $types = self::getResponse('v2/category/attribute/values', [
                "attribute_id" => self::ATTRIBUTE_TYPE,
                "category_id" => $category_id,
                "language" => "DEFAULT",
                "last_value_id" => 0,
                "limit" => 100
        ]);
        foreach($types['result'] as $row){
            $checked = '';
            if (!empty($selected)){
                foreach($selected as $v){
                    if ($v['dictionary_value_id'] == $row['id']){
                        $checked = 'checked';
                    }
                }
            }
            $output[] = [
                'id' => $row['id'],
                'name' => $row['value'],
                'checked' => $checked
            ];
        }
        return $output;
    }

    public static function getResponse($method, $params){
        $json = Provider::getCurlUrlData(
            self::API_URL.$method,
            json_encode($params),
            [
                'Api-Key' => 'c7836d34-9ead-4199-a038-1803da0d897a',
                'Client-Id' => '259990'
            ]
        );
        return json_decode($json, true);
    }

    public static function addProductId($offer_id, $product_id){
        return self::getDBInstance()->insert(
            'item_ozon',
            [
                'offer_id' => $offer_id,
                'product_id' => $product_id
            ],
            ['duplicate' => [
                'product_id' => $product_id
            ]]
        );
    }

    public static function getProductList(){
        $itemOzonResult = self::getDBInstance()->query("
            SELECT 
                io.offer_id,
                i.id,
                b.title AS brend,
                i.brend_id,
                i.title_full,
                i.article
            FROM #item_ozon io
            LEFT JOIN #items i ON i.id = io.offer_id
            LEFT JOIN #brends b ON b.id = i.brend_id
            ORDER BY b.title
        ");
        self::$countProduct = $itemOzonResult->num_rows;
        return $itemOzonResult->fetch_all(MYSQLI_ASSOC);
    }

    public static function getProductInfo($offer_id){
        $productInfo = self::getResponse('v2/product/info', [
            'offer_id' => $offer_id
        ]);
        $output = $productInfo['result'];
        unset($productInfo);

        $productAttributes = self::getProductAttributes($offer_id);
        foreach($productAttributes['attributes'] as $row){
            $output['attributes'][$row['attribute_id']] = $row;
        }
        unset($productAttributes);

        $output['types'] = Ozon::getType(
            $output['category_id'],
            $output['attributes'][self::ATTRIBUTE_TYPE]['values']
        );

        return $output;
    }

    public static function getProductAttributes($offer_id){
        $result = self::getResponse('v3/products/info/attributes', [
            'filter' => [
                'offer_id' => [$offer_id],
                'visibility' => 'ALL'
            ],
            'limit' => 1000
        ]);
        return $result['result'][0];
    }
}