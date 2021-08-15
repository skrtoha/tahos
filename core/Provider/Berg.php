<?php
namespace core\Provider;
use core\Log;
use core\OrderValue;
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
        
        $providerBrend = parent::getProviderBrend(self::getParams()->provider_id, $brend);
        
        $url = self::getUrlString('/ordering/get_stock')."&items[0][resource_article]=$article&items[0][brand_name]=$providerBrend";
        $result = parent::getCurlUrlData($url);
        $data = json_decode($result);
        
        if (empty($data->resources)) return true;
        
        //todo  сделано так из-за того, что если бренд не найден, то возвращается весь список совпадений
        if (count($data->resources) > 1) return false;
        
        foreach($data->resources as $obj){
            if (empty($obj->offers)) continue;
            
            foreach($obj->offers as $offer){
                $stores[$offer->warehouse->type][$offer->warehouse->name] = $offer->warehouse;
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
        if ($offer->warehouse->name == 'BERG MSK') return self::getParams()->mainStoreId;
        if ($offer->warehouse->name == 'BERG YAR') return self::getParams()->storeYar;
        if ($offer->warehouse->name == 'BERG MSK2') return self::getParams()->storeMsk2;
    
        $array = $GLOBALS['db']->select_one(
            'provider_stores',
            'id',
            "`title` = '{$offer->warehouse->name}' AND `provider_id` = ".self::getParams()->provider_id
        );
        if (!empty($array)) return $array['id'];
        
        $GLOBALS['db']->insert(
            'provider_stores',
            [
                'title' => $offer->warehouse->name,
                'provider_id' => self::getParams()->provider_id,
                'cipher' => strtoupper(parent::getRandomString(4)),
                'currency_id' => 1,
                'delivery' => $offer->assured_period,
                'percent' => 10
            ]);
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
	    return Abcp::getItemsToOrder($provider_id);
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
    
    public static function isInBasket($ov){
        return Armtek::isInBasket($ov);
    }
    
    public static function removeFromBasket($ov){
	    return Armtek::removeFromBasket($ov);
    }
    
    public static function sendOrder(){
	    $providerBasket = parent::getProviderBasket(self::getParams()->provider_id);
	    if (!$providerBasket->num_rows) return 0;
	    
	    $items = [];
	    $ordered = 0;
	    foreach($providerBasket as $row){
	        $items['items'][] = [
	            'resource_article' => $row['article'],
                'brand_name' => $row['brend']
            ];
            $url = self::getUrlString('/ordering/get_stock').'&'.http_build_query($items);
            $result = parent::getCurlUrlData($url);
            $data = json_decode($result);
            
            if (empty($data->resources)) Log::insert([
                'text' => "Берг: не удалось отправить в заказ",
                'additional' => "osi: {$row['order_id']}-{$row['store_id']}-{$row['item_id']}"
            ]);
        
            if (count($data->resources) > 1) Log::insert([
                'text' => "Берг: слишком много совпадений, проверьте бренды",
                'additional' => "osi: {$row['order_id']}-{$row['store_id']}-{$row['item_id']}"
            ]);
            
            $order = [];
            $order['force'] = 0;
            $order['order']['payment_type'] = $row['typeOrganization'] == 'private' ? 1 : 2;
            $order['order']['dispatch_type'] = 3;
            $order['order']['dispatch_time'] = 2;
            
            //todo при развертывании закоментировать
            //$order['order']['is_test'] = 1;
            
            foreach($data->resources[0]->offers as $offer){
                if ($offer->warehouse->name != $row['store']) continue;
                $order['order']['dispatch_at'] = self::getDateDispatch($offer->average_period);
                $order['order']['items'][] = [
                    'resource_id' => $data->resources[0]->id,
                    'warehouse_id' => $offer->warehouse->id,
                    'quantity' => $row['quan'],
                    'comment' => "{$row['order_id']}-{$row['store_id']}-{$row['item_id']}"
                ];
                break;
            }
            $url = self::getUrlString('/ordering/place_order');
            $json = parent::getCurlUrlData($url, $order);
            $result = json_decode($json);
            
            OrderValue::changeStatus(11, [
                'order_id' => $row['order_id'],
                'store_id' => $row['store_id'],
                'item_id' => $row['item_id'],
                'price' => $result->order->items[0]->price,
                'quan' => $result->order->items[0]->quantity
            ]);
        
            parent::updateProviderBasket(
                [
                    'order_id' => $row['order_id'],
                    'store_id' => $row['store_id'],
                    'item_id' => $row['item_id']
                ],
                ['response' => 'OK']
            );
            
            $ordered += $result->order->items[0]->quantity;
	    }
	    
        return $ordered;
    }
    
    public static function getDateDispatch($period){
        $dateFrom = new \DateTime();
        $dateFrom->add(new \DateInterval('P'.$period.'D'));
        return $dateFrom->format('Y-m-d');
    }
}

