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
    
    public static $provider_id = 16;
	
	public static function getUrlString($action, $typeOrganization = 'entity'){
		return self::getParams($typeOrganization)->url."$action.json?key=" . self::getParams($typeOrganization)->key;
	}

	public static function getParams($typeOrganization = 'entity'){
		return parent::getApiParams([
			'provider_id' => self::$provider_id,
			'typeOrganization' => $typeOrganization
		]);
	}
	
	public static function setArticle($brend, $article, $item_id){
        if (!parent::getIsEnabledApiSearch(self::$provider_id)) return false;
        if (!parent::isActive(self::$provider_id)) return false;
        
        $providerBrend = parent::getProviderBrend(self::$provider_id, $brend);
        
        $url = self::getUrlString('/ordering/get_stock')."&items[0][resource_article]=$article&items[0][brand_name]=$providerBrend";
        $result = parent::getCurlUrlData($url);
        $data = json_decode($result);
        
        if (empty($data->resources)) return true;
        
        //todo  сделано так из-за того, что если бренд не найден, то возвращается весь список совпадений
        if (count($data->resources) > 1) return false;
        
        foreach($data->resources as $obj){
            if (empty($obj->offers)) continue;
            
            foreach($obj->offers as $offer){
                $store_id = self::getStoreId($offer);
                
                if (!$store_id) continue;
                
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
        if ($offer->warehouse->name == 'BERG MSK2') return self::getParams()->storeMsk;
        return false;
    }
    
    public static function getCoincidences($search){
        if (!parent::getIsEnabledApiSearch(self::$provider_id)) return false;
        if (!parent::isActive(self::$provider_id)) return false;
    
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
    
    private static function getWarehouseName($store_id){
        switch($store_id){
            case self::getParams()->storeMsk: return 'BERG MSK2';
            case self::getParams()->storeYar: return 'BERG YAR';
            case self::getParams()->mainStoreId: return 'BERG MSK';
        }
        return false;
    }
    
    public static function getPrice(array $params)
    {
        self::$provider_id = $params['provider_id'];
        $providerBrend = parent::getProviderBrend($params['provider_id'], $params['brend']);
        $url = self::getUrlString('/ordering/get_stock')."&items[0][resource_article]={$params['article']}&items[0][brand_name]={$providerBrend}";
        $result = parent::getCurlUrlData($url);
        $data = json_decode($result);
    
        if (!$data->resources[0]->offers) return false;
        
        foreach($data->resources[0]->offers as $offer){
            $warehouse = self::getWarehouseName($params['store_id']);
            if (!$warehouse) continue;
            if ($offer->warehouse->name == $warehouse) return [
                'price' => $offer->price,
                'available' => $offer->quantity
            ];
        }
        
        return [];
    }
    
    public static function isInBasket($ov){
        return Armtek::isInBasket($ov);
    }
    
    public static function removeFromBasket($ov){
	    return Armtek::removeFromBasket($ov);
    }
    
    public static function sendOrder(){
	    $providerBasket = parent::getProviderBasket([16, 26, 27]);
	    if (!$providerBasket->num_rows) return 0;
	    
	    $items = [];
	    $ordered = 0;

        $providerBasketType = [
            'private' => [],
            'entity' => []
        ];
        foreach($providerBasket as $row){
            switch($row['pay_type']){
                case 'Наличный':
                case 'Онлайн':
                    $providerBasketType['private'][] = $row;
                    break;
                case 'Безналичный':
                    $providerBasketType['entity'][] = $row;
                    break;
            }
        }

	    foreach($providerBasketType as $type_organization => $pb){
            foreach($pb as $row){
                self::$provider_id = $row['provider_id'];
                $items['items'][] = [
                    'resource_article' => $row['article'],
                    'brand_name' => $row['brend']
                ];
                $url = self::getUrlString('/ordering/get_stock').'&'.http_build_query($items);
                $result = parent::getCurlUrlData($url);
                $data = json_decode($result);

                if (empty($data->resources)){
                    Log::insert([
                        'text' => "Берг: не удалось отправить в заказ",
                        'additional' => "osi: {$row['order_id']}-{$row['store_id']}-{$row['item_id']}"
                    ]);
                    continue;
                }

                if (count($data->resources) > 1){
                    Log::insert([
                        'text' => "Берг: слишком много совпадений, проверьте бренды",
                        'additional' => "osi: {$row['order_id']}-{$row['store_id']}-{$row['item_id']}"
                    ]);
                    continue;
                }

                $order = [];
                $order['force'] = 0;
                $order['order']['payment_type'] = self::getPayment($type_organization);
                $order['order']['dispatch_type'] = 3;
                $order['order']['dispatch_time'] = 2;
                $order['order']['shipment_address_id'] = self::getParams($type_organization)->address_id;

                //todo при развертывании закоментировать
//            $order['order']['is_test'] = 1;

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

                if (empty($order['order']['items'])){
                    Log::insert([
                        'text' => "Берг: товар не найден на складе",
                        'additional' => "osi: {$row['order_id']}-{$row['store_id']}-{$row['item_id']}"
                    ]);
                    continue;
                }

                $url = self::getUrlString('/ordering/place_order', $type_organization);
                $json = parent::getCurlUrlData($url, $order);
                $result = json_decode($json);

                if (isset($result->errors) && $result->errors){
                    $str = "Ошибки отправки заказа: ";
                    foreach($result->errors as $r) $str .= mb_strtolower($r->text)." ({$r->code}); ";
                    $str = mb_substr($str, 0, -2);
                    Log::insert([
                        'text' => $str,
                        'additional' => "osi: {$row['order_id']}-{$row['store_id']}-{$row['item_id']}"
                    ]);
                    continue;
                }
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

	    }
	    
        return $ordered;
    }

    private static function getPayment($type_organization){
        if(parent::$statusAPI == parent::ACTIVE_BOTH){
            return $type_organization == 'private' ? 1 : 2;
        }
        if (parent::ACTIVE_ONLY_ENTITY) return 2;
        if (parent::ACTIVE_ONLY_PRIVATE) return 1;
        return false;
    }
    
    public static function getDateDispatch($period){
        $dateFrom = new \DateTime();
        $dateFrom->add(new \DateInterval('P'.$period.'D'));
        return $dateFrom->format('Y-m-d');
    }
}

