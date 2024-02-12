<?php
namespace core\Marketplaces;

use core\Cache;
use core\Category;
use core\Database;
use core\Provider;
use core\Setting;
use Katzgrau\KLogger\Logger;

class Ozon extends Marketplaces{
    const API_URL = 'https://api-seller.ozon.ru/';
    const CATEGORY_AUTO_GOODS = 17027495;
    const ATTRIBUTE_TYPE = 8229;
    const ATTRIBUTE_BREND = 85;
    const ATTRUBUTE_MODEL = 9048;
    const ATTRIBUTE_DESCRIPTION = 4191;
    const ATTRIBUTE_ARTICLE = 7236;

    const STATUS_IMPORTED = 'imported';
    const STATUS_PENDING = 'pending';

    public static $oilDangerClass = [
        "attribute_id" => 9782,
        "values" => [
            [
                "id" => 970593901,
                "value" => "Класс 1. Взрывчатые материалы"
            ],
            [
                "id" => 970593902,
                "value" => "Класс 2. Газы"
            ],
            [
                "id" => 970593903,
                "value" => "Класс 3. Легковоспламеняющиеся жидкости"
            ],
            [
                "id" => 970593904,
                "value" => "Класс 4. Легковоспламеняющиеся вещества"
            ],
            [
                "id" => 970593905,
                "value" => "Класс 5. Окисляющие вещества"
            ],
            [
                "id" => 970593906,
                "value" => "Класс 6. Ядовитые и инфекционные вещества"
            ],
            [
                "id" => 970593907,
                "value" => "Класс 7. Радиоактивные вещества"
            ],
            [
                "id" => 970593908,
                "value" => "Класс 8. Едкие и коррозионные вещества"
            ],
            [
                "id" => 970593909,
                "value" => "Класс 9. Прочие опасные вещества"
            ],
            [
                "id" => 970661099,
                "value" => "Не опасен"
            ]
        ]
    ];

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

    private static function showCat($data, $str, $category_id): string
    {
        $string = '';
        foreach($data as $item){
            $string .= self::tplMenu($item, $str, $category_id);
        }
        return $string;
    }

    public static function getTplCategory($tree, $category_id = null): string
    {
        return self::showCat($tree, '', $category_id);
    }

    public static function getType($category_id, $selected = []): array
    {
        $output = [];
        if (!$category_id) return $output;
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
                'Api-Key' => '6b92b35b-fa41-46e7-9ca3-b22c5d6f73af',
                'Client-Id' => '259990'
            ]
        );
        return json_decode($json, true);
    }

    public static function setItemOzon($items){
        foreach($items as $item){
            $duplicate = $item;
            unset($duplicate['offer_id']);
            self::getDBInstance()->insert(
                'ozon_item',
                $item,
                ['duplicate' => $duplicate]
            );
        }
    }

    public static function getInStock($fbs_sku){
        $result = self::getResponse('v1/product/info/stocks-by-warehouse/fbs', [
            'fbs_sku' => [$fbs_sku]
        ]);
        return $result['result'];
    }

    public static function setInStock($offer_id, $amount){
        $result = self::getResponse('v1/product/import/stocks', [
            'stocks' => [
                [
                    'offer_id' => $_POST['offer_id'],
                    'stock' => $_POST['amount']
                ]
            ]
        ]);
        return $result['result'];
    }

    public static function getItemOzon($params){
        $where = "";
        foreach($params as $field => $value){
            $where .= "`$field` = {$value} AND ";
        }
        $where = substr($where, 0, -5);
        return parent::getDBInstance()->select_one('ozon_item', '*', $where);
    }

    public static function updateAttributes($offer_id, $attributes){
        $items = [];
        $items[] = [
            'attributes' => $attributes,
            'offer_id' => $offer_id
        ];
        $result = self::getResponse('v1/product/attributes/update', ['items' => $items]);
        return $result;
    }

    public static function getQueryStoreItems(){
        return "
            select
                si.price as store_price,
                ps.id,
                p.title,
                ps.cipher,
                si.in_stock,
                m.title as measure
            from
                #store_items si
            left join
                #ozon_item oz on oz.offer_id = si.item_id
            left join
                #provider_stores ps on si.store_id = ps.id
            left join
                #providers p on p.id = ps.provider_id
            left join
                #item_options io on io.item_id = si.item_id
            left join
                #measures m on m.id = io.measure_id
        ";
    }

    public static function getOzonProductList(){
        $result = self::getResponse('v2/product/list', [
            "last_id" => "",
            "limit" => 100
        ]);
        return $result['result']['items'];
    }

    public static function getProductList(): array
    {
        $result = self::getOzonProductList();
        $itemIdList = array_map(function ($element){
            return "'{$element['offer_id']}'";
        }, $result);

        $itemResult = self::getDBInstance()->query("
            SELECT 
                i.id,
                b.title AS brend,
                i.brend_id,
                i.title_full,
                i.article
            FROM #items i
            LEFT JOIN #brends b ON b.id = i.brend_id
            WHERE i.id IN (".implode(',', $itemIdList).")
            ORDER BY b.title
            
        ");
        self::$countProduct = $itemResult->num_rows;
        return $itemResult->fetch_all(MYSQLI_ASSOC);
    }

    public static function importInfo($task_id){
        return Ozon::getResponse('v1/product/import/info', [
            'task_id' => $task_id
        ]);
    }

    public static function getProductInfo($offer_id, $withAttributes = true){
        $productInfo = self::getResponse('v2/product/info', [
            'offer_id' => $offer_id
        ]);
        $output = $productInfo['result'];
        unset($productInfo);

        Ozon::setItemOzon([
            [
                'offer_id' => $offer_id,
                'fbs_sku' => $output['fbs_sku']
            ]
        ]);

        if($withAttributes){
            $productAttributes = self::getProductAttributes($offer_id);
            if (!empty($productAttributes)){
                foreach($productAttributes['attributes'] as $row){
                    $output['attributes'][$row['attribute_id']] = $row;
                }
            }
            unset($productAttributes);

            $output['types'] = Ozon::getType(
                $output['category_id'],
                $output['attributes'][self::ATTRIBUTE_TYPE]['values']
            );
        }

        return $output;
    }

    public static function checkResult($result){
        if (isset($result['code'])){
            echo json_encode([
                'success' => false,
                'errors' => $result['message']
            ]);
            die();
        }
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

    public static function getProductIdByOfferId($offer_id){
        $result = self::getDBInstance()->select_one('ozon_item', '*', "`offer_id` = $offer_id");
        if ($result) return $result['product_id'];
        return false;
    }

    public static function archiveProduct($product_id){
        if (!is_array($product_id)) $product_id = [$product_id];
        return Ozon::getResponse(
            'v1/product/archive',
            ['product_id' => $product_id]
        );
    }

    public static function deleteProduct($offer_id){
        return Ozon::getResponse('v2/products/delete', [
            'products' => [
                [
                    'offer_id' => $offer_id
                ]
            ]
        ]);
    }

    public static function updatePrices(Logger $logger = null){
        $start = 0;
        $offset = 100;
        $nextIterator = true;
        $items = [];

        $ozonMarkup = Setting::get('marketplaces', 'markup');
        while($nextIterator){
            $result = self::getDBInstance()->query("
                select
                    oi.offer_id,
                    i.article,
                    si.price as store_price,
                    si.in_stock,
                    @firstMarkup := si.price + (si.price * 20 / 100) as first_markup,
                    @withMarkupMarketplace := round(@firstMarkup + (@firstMarkup * oi.marketplace_markup / 100)) as price
                from
                    #ozon_item oi
                left join
                    #store_items si on si.item_id = oi.offer_id and si.store_id = oi.store_id
                left join 
                    #items i on i.id = oi.offer_id
                left join 
                    #brends b on b.id = i.brend_id
                LIMIT $start, $offset
            ");

            if ($result->num_rows < $offset) {
                $nextIterator = false;
            }
            $start += $offset;
            $prices = [];
            $stocks = [];
            foreach($result as $row){
                if ($logger){
                    $items[$row['offer_id']] = "{$row['brend']} {$row['article']} {$row['title_full']}";
                }
                $stocks[] = [
                    'offer_id' => $row['offer_id'],
                    'stock' => $row['in_stock']
                ];
                $old_price = ceil($row['price'] + $row['price'] * ($ozonMarkup / 100));
                $price = ceil($row['price']);
                $prices[] = [
                    'offer_id' => $row['offer_id'],
                    'old_price' => "$old_price",
                    'price' => "$price"
                ];
            }

            $resStocks = Ozon::getResponse('v1/product/import/stocks', [
                'stocks' => $stocks
            ]);
            $resPrices = Ozon::getResponse('v1/product/import/prices', [
                'prices' => $prices
            ]);
            if ($logger){
                self::setLogger($resStocks, $items, $logger, 'stocks');
                self::setLogger($resPrices, $items, $logger, 'prices');
            }
        }

    }

    private static function setLogger($array, array $items, Logger $logger, string $type){
        $updated = 0;
        foreach($array['result'] as $value){
            if ($value['updated']){
                $updated++;
                continue;
            }
            $error = '';
            foreach($value['errors'] as $e) $error .= $e['message'].'; ';
            $logger->emergency($items[$value['offer_id']].': '.$error);
        }
        $message = 'Обновлено ';
        if ($type == 'prices') $message .= " $updated цен.";
        if ($type == 'stocks') $message .= " $updated остатков.";
        $logger->alert($message);
    }

    public static function getFbsSku($offer_id){
        $productInfo = self::getProductInfo($offer_id, false);
        Ozon::setItemOzon([
            [
                'offer_id' => $offer_id,
                'fbs_sku' => $productInfo['fbs_sku']
            ]
        ]);
        return $productInfo['fbs_sku'];
    }

    public static function getOzonTplTreeCategories($category_id): string
    {
        $tree = Cache::get('ozon_tree_categories');
        if (!$tree){
            $tree = Ozon::getTreeCategories();
            Cache::set('ozon_tree_categories', $tree);
        }

        $tpl = Ozon::getTplCategory($tree['result'][0]['children'], $category_id);
        $output = "
            <select name='category_id'>
                <option selected value='0'>выберите...</option>
                $tpl
            </select>";
        return $output;
    }

    public static function getTahosTplCategories($category_id = 0){
        $tree = Cache::get('tahos_tree_categories');
        if (!$tree){
            $tree = Category::getTreeCategories();
            Cache::set('tahos_tree_categories', $tree);
        }

        $tpl = self::getTplCategory($tree, $category_id);
        return "
            <select name='tahos_category_id'>
                <option selected value='0'>выберите...</option>
                $tpl
            </select>";
    }

    public static function setMatchCategory($params, $checkUnique = false){
        if ($checkUnique){
            $result = self::getMatchedCategories("omc.tahos_category_id = {$params['tahos_category_id']}");
            if (!empty($result)){
                return self::updateMatchCategory($params['tahos_category_id'], [
                    'ozon_category_id' => $params['category_id'],
                    'ozon_category_title' => $params['title_category_id']
                ]);
            }
        }
        return Database::getInstance()->insert(
            'ozon_match_category',
            [
                'tahos_category_id' => $params['tahos_category_id'],
                'ozon_category_id' => $params['category_id'],
                'ozon_category_title' => $params['title_category_id']
            ]
        );
    }

    public static function updateMatchCategory($tahos_category_id, $params): bool
    {
        return Database::getInstance()->update(
            'ozon_match_category',
            $params,
            "`tahos_category_id` = $tahos_category_id"
        );
    }

    public static function getMatchedCategories($where = ''): array
    {
        if ($where){
            $where = "WHERE $where";
        }
        $result = Database::getInstance()->query("
            SELECT
                omc.tahos_category_id,
                omc.ozon_category_id AS category_id,
                omc.ozon_category_title AS title_category_id,
                c.title AS title_tahos_category_id
            FROM
                #ozon_match_category omc
            LEFT JOIN
                #categories c ON c.id = omc.tahos_category_id
            $where
        ")->fetch_all(MYSQLI_ASSOC);
        return $result;
    }

    public static function deleteMatchCategory($params): bool
    {
        $where = '';
        foreach($params as $key => $value){
            switch ($key){
                case 'tahos_category_id':
                case 'ozon_category_id':
                    $where .= "`$key` = '$value' AND ";
            }
        }
        if (!$where){
            return false;
        }
        $where = substr($where, 0, -5);
        return Database::getInstance()->delete('ozon_match_category', $where);
    }
}