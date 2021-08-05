<?php
namespace core\Provider;
use core\Database;
use core\Provider;

class Berg extends Provider{
	public static $fieldsForSettings = [
		'isActive',	// is required
		'key',
        'url'
	];
	
	public static function getUrlString($action, $typeOrganization = 'entity'){
		return self::getParams($typeOrganization)->url . "$action.json?key=" . self::getParams($typeOrganization)->key;
	}

	public static function getParams($typeOrganization = 'entity'){
		return parent::getApiParams([
			'api_title' => 'Berg',
			'typeOrganization' => $typeOrganization
		]);
	}
	
	public static function setArticle($brend, $article, $item_id){
        if (!parent::getIsEnabledApiSearch(self::getParams()->provider_id)) return false;
        if (!parent::isActive(self::getParams()->provider_id)) return false;
        
        $providerBrend = $brend;
        
        $url = self::getUrlString('/ordering/get_stock')."&items[0][resource_article]=$article&items[0][brand_name]=$providerBrend";
        $result = parent::getCurlUrlData($url);
        $data = json_decode($result);
        
        if (empty($data->resources)) return $output;
        
        //todo  сделано так из-за того, что если бренд не найден, то возвращается весь список совпадений
        if (count($data->resources) > 1) return $output;
        
        foreach($data->resources as $obj){
            if (empty($obj->offers)) continue;
            
            foreach($obj->offers as $offer){
                $GLOBALS['db']->insert('store_items', [
                    'store_id' => self::getStoreId($offer),
                    'item_id' => $item_id,
                    'price' => $offer->price,
                    'in_stock' => $offer->quantity,
                    'packaging' => $offer->multiplication_factor
                ],
                [
                    'duplicate' => [
                        'price' => $offer->price,
                        'in_stock' => $offer->quantity
                    ]
                ]);
            }
        }
        return true;
	}
    
    private static function getStoreId($offer){
        if ($warehouse->type == 2) return self::getParams()->mainStoreId;
        $res = $GLOBALS['db']->insert(
            'provider_stores',
            [
                'title' => $offer->warehouse->name,
                'provider_id' => self::getParams()->provider_id,
                'cipher' => strtoupper(parent::getRandomString(4)),
                'currency_id' => 1,
                'delivery' => $offer->assured_period,
                'percent' => 10
            ],
            [
                'print_query' => false,
                'deincrement_duplicate' => true
            ]
        );
        if (parent::isDuplicate($res)){
            $where = "`title`='{$offer->warehouse->name}' AND `provider_id` = " . self::getParams()->provider_id;
            $GLOBALS['db']->update('provider_stores', ['delivery' => $offer->assured_period], $where);
            $array = $GLOBALS['db']->select_one('provider_stores', 'id', $where);
            return $array['id'];
        }
        return $GLOBALS['db']->last_id();
    }
    
    public static function getCoincidences($search){
        if (!parent::getIsEnabledApiSearch(self::getParams()->provider_id)) return false;
        if (!parent::isActive(self::getParams()->provider_id)) return false;
    
        $output = [];
        $url = self::getUrlString('/ordering/get_stock')."&items[0][resource_article]=$search";
        $result = parent::getCurlUrlData($url);
        $data = json_decode($result);
        
        if (empty($data->resources)) return $output;
        
        foreach($data->resources as $obj){
            $output[$obj->brand->name] = $obj->name;
        }
        return $output;
    }
	
	public static function getItemsToOrder($provider_id){
	    return false;
    }
    
    public static function getPrice(array $params)
    {
        $url = self::getUrlString('/ordering/get_stock')."&items[0][resource_article]={$params['article']}&items[0][brand_name]={$params['brend']}";
        $result = parent::getCurlUrlData($url);
        $data = json_decode($result);
        
        if (!$data->resources[0]->offers) return false;
        
        foreach($data->resources[0]->offers as $offer){
            if ($offer->warehouse->name == $params['providerStore']) return [
                'price' => $offer->price,
                'available' => $offer->quantity
            ];
        }
        
        return false;
    }
    
}

