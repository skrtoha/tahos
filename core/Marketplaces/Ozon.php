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
        $selected = $category_id && $category['id'] == $category_id ? 'selected' : '';
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

    public static function getType($category_id){
        $result = self::getResponse('v2/category/attribute/values', [
                "attribute_id" => self::ATTRIBUTE_TYPE,
                "category_id" => $category_id,
                "language" => "DEFAULT",
                "last_value_id" => 0,
                "limit" => 100
        ]);
        return $result;
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
}