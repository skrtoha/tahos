<?php
namespace core\Provider;
use core\Provider;
use core\Log;
use core\OrderValue;
use core\Item;

class Autoeuro extends Provider{
    private $items, $mainItemId, $providerStores;
    
	public static $fieldsForSettings = [
		"isActive",	// is required	
		"email",
		"password",
		"apiKey",
		"url",
		"delivery_key",
		"subdivision_key",
		"provider_id",
		"mainStoreID",
		"minPriceStoreID",
		"minDeliveryStoreID",
        "priceYar"
	];
    
    public function __construct($mainItemId = false){
        $this->mainItemId = $mainItemId;
    }
	
	public static function getUrlString($action, $typeOrganization = 'entity'){
		return self::getParams($typeOrganization)->url . "$action/" . self::getParams($typeOrganization)->apiKey;
	}

	public static function getParams($typeOrganization = 'entity'){
		return parent::getApiParams([
			'api_title' => 'Autoeuro', 
			'typeOrganization' => $typeOrganization
		]);
	}
 
	public static function getBrends(){
		$response = parent::getUrlData(self::getUrlString('brends'));
		return json_decode($response);
	}
 
	private static function getStockItems($brend, $article, $with_crosses = 0, $typeOrganization = 'entity'){
		return parent::getUrlData(self::getUrlString('search_items'), [
            'delivery_key' => self::getParams($typeOrganization)->delivery_key,
			'brand' => $brend, 
			'code' => $article, 
			'with_crosses' => $with_crosses,
            'with_offers' => 1
		]);
	}
	
    public static function getPrice($params){
        $response = self::getStockItems(strtoupper($params['brend']), $params['article'], 0);
        $result = json_decode($response);
        if (empty($result->DATA)) return [
            'price' => 0,
            'available' => -1
        ];
        $aeok = $GLOBALS['db']->select_one(
            'autoeuro_order_keys',
            'offer_key',
            "`store_id` = {$params['store_id']} AND `item_id` = {$params['item_id']}"
        );
        foreach($result->DATA as $row){
            if ($row->offer_key != $aeok['offer_key']) continue;
            return [
                'price' => $row->price,
                'available' => $row->amount
            ];
        }
        return [
            'price' => 0,
            'available' => -1
        ];
	}
	
    public static function getItemsToOrder($provider_id){}
	
    private function insertItem($o){
        $key = "{$o['brand']}{$o['code']}";
        if (isset($this->items[$key])) return $this->items[$key];
        
		$brend = self::getProviderBrend(self::getParams()->provider_id, $o['brand']);
        $brend_id = Armtek::getBrendId($brend);
		if (!$brend_id) return false;
  
		$article = Item::articleClear($o['code']);
        $resInsertItem = Item::insert([
            'brend_id' => $brend_id,
            'article' => $article,
            'article_cat' => $o['code'],
            'title' => $o['name'],
            'title_full' => $o['name'],
            'source' => 'Autoeuro'
        ]);
		if ($resInsertItem === true){
			$item_id = Item::$lastInsertedItemID;
			$this->items[$key] = $item_id;
			return $item_id;
		}
		elseif (parent::isDuplicate($resInsertItem)){
			$resItem = parent::getInstanceDataBase()->select_one('items', '*', "`brend_id` = $brend_id AND `article` = '$article'");
			if (empty($resItem)) return false;
			$item_id = $resItem['id'];
			$this->items[$key] = $item_id;
			return $item_id;
		}
		return false;
	}
 
	public function setArticle($brend, $article){
		if (!parent::getIsEnabledApiSearch(self::getParams()->provider_id)) return false;
		if (!parent::isActive(self::getParams()->provider_id)) return false;
        $providerBrend = parent::getProviderBrend(self::getParams()->provider_id, $brend);
		$response = self::getStockItems(strtoupper($providerBrend), $article, 1);
		if (!$response || $response == 'Пустой ключ покупателя'){
			$providerBrend = parent::getProviderBrend(self::getParams()->provider_id, $brend);
			$response = self::getStockItems(strtoupper($providerBrend), $article);
		}
		if (!$response) return false;
		$object = json_decode($response, false);
		$codes = [];
        foreach($object->DATA as $o){
            $array = [];
            $key = "{$o->brand}{$o->code}";
            $array['brand'] = $o->brand;
            $array['code'] = $o->code;
            $array['name'] = $o->name;
            $array['cross'] = $o->cross;
            $array['packing'] = $o->packing;
            $offer = [
                'offer_key' => $o->offer_key,
                'price' => $o->price,
                'amount' => $o->amount,
                'return' => $o->return,
                'dealer' => $o->dealer,
                'delivery' => self::getDelivery($o),
                'warehouse_name' => $o->warehouse_name,
                'warehouse_key' => $o->warehouse_key
            ];
            if (!isset($codes[$key])) $codes[$key] = $array;
            $codes[$key]['offers'][] = $offer;
        }
        foreach($codes as $code) $this->parseCode($code);
	}
    
    private static function getDelivery(object  $o){
        if (!$o->order_before || !$o->delivery_time) return 1;
        $beforeTime = \DateTime::createFromFormat('Y-m-d H:i', $o->order_before);
        $deliveryTime = \DateTime::createFromFormat('Y-m-d H:i', $o->delivery_time);
        $diff = $deliveryTime->diff($beforeTime);
        return $diff->d + 1;
    }
    
	private function parseCode($code){
		$item_id = $this->insertItem($code);
		if (!$item_id) return;

		if ($code['cross'] === "0"){
			parent::getInstanceDataBase()->insert('item_analogies', ['item_id' => $item_id, 'item_diff' => $this->mainItemId]);
			parent::getInstanceDataBase()->insert('item_analogies', ['item_id' => $this->mainItemId, 'item_diff' => $item_id]);
		}
        
        $minPrice = 100000000000000000000;
        $minDelivery = 10000000000000000000;
        $minPriceObject = [];
        $minDeliveryObject = [];
        
        foreach($code['offers'] as $offer){
            //если склад основной
            if ($offer['dealer'] && $offer['warehouse_name']){
                $store_id = $this->getStoreId($offer);
                if (!$store_id) continue;
                
                $GLOBALS['db']->insert('store_items', [
                    'store_id' => $store_id,
                    'item_id' => $item_id,
                    'price' => $offer['price'],
                    'in_stock' => $offer['amount'],
                    'packaging' => $code['packing']
                ]);
                $GLOBALS['db']->insert('autoeuro_order_keys', [
                    'store_id' => $store_id,
                    'item_id' => $item_id,
                    'offer_key' => $offer['offer_key']
                ]);
                continue;
            }
            
            if ($offer['price'] < $minPrice){
                $minPriceObject = $offer;
                $minPrice = $offer['price'];
            }
            if ($offer['delivery'] < $minDelivery){
                $minDeliveryObject = $offer;
                $minDelivery = $offer['delivery'];
            }
        }
        
        $GLOBALS['db']->insert('store_items', [
            'store_id' => self::getParams()->minPriceStoreID,
            'item_id' => $item_id,
            'price' => $minPriceObject['price'],
            'in_stock' => $minPriceObject['amount'],
            'packaging' => $code['packing']
        ]);
        $GLOBALS['db']->insert('autoeuro_order_keys', [
            'store_id' => self::getParams()->minPriceStoreID,
            'item_id' => $item_id,
            'offer_key' => $minPriceObject['offer_key'],
            'order_term' => $minPriceObject['delivery']
        ]);
        
        if (count($code['offers']) > 1){
            $GLOBALS['db']->insert('store_items', [
                'store_id' => self::getParams()->minDeliveryStoreID,
                'item_id' => $item_id,
                'price' => $minDeliveryObject['price'],
                'in_stock' => $minDeliveryObject['amount'],
                'packaging' => $code['packing']
            ]);
            $GLOBALS['db']->insert('autoeuro_order_keys', [
                'store_id' => self::getParams()->minDeliveryStoreID,
                'item_id' => $item_id,
                'offer_key' => $minDeliveryObject['offer_key'],
                'order_term' => $minDeliveryObject['delivery']
            ], 'result');
        }
	}
	
    private function getStoreId($offer){
        if (isset($this->providerStores['warehouse_name'])) return $this->providerStores['warehouse_name'];
        $providerStore = $GLOBALS['db']->select_one(
            'provider_stores',
            ['id', 'cipher'],
            "`provider_id` = ".self::getParams()->provider_id." AND `title` = '{$offer['warehouse_name']}'"
        );
        if (empty($providerStore)){
            $GLOBALS['db']->insert('provider_stores', [
                'title' => $offer['warehouse_name'],
                'cipher' => strtoupper(self::getRandomString()),
                'percent' => 10,
                'currency_id' => 1,
                'provider_id' => self::getParams()->provider_id,
                'delivery' => $offer['delivery'],
                'delivery_max' => $offer['delivery'] + 1,
                'under_order' => $offer['delivery'],
                'noReturn' => $offer['return'] ? 0 : 1,
                'is_main' => 0,
                'daysForReturn' => 14
            ]);
            $store_id = $GLOBALS['db']->last_id();
            $this->providerStores[$offer['warehouse_name']] = $store_id;
            return $store_id;
        }
        else{
            $this->providerStores[$providerStore['warehouse_name']] = $providerStore['id'];
            return $providerStore['id'];
        }
    }
    
    public static function getSearch($code){
		if (!parent::getIsEnabledApiSearch(self::getParams('entity')->provider_id)) return false;
		if (!parent::isActive(self::getParams('entity')->provider_id)) return false;
		$response = parent::getUrlData(self::getUrlString('stock_items'), ['code' => $code]);
		if (!$response) return false;
		$itemsList = json_decode($response);
		$output = [];
		if (isset($itemsList->DATA->VARIANTS)){
			foreach($itemsList->DATA->VARIANTS as $item) $output[$item->brand] = $item->name;
		}
		if (isset($itemsList->DATA->CODES)){
			foreach($itemsList->DATA->CODES as $item) $output[$item->maker] = $item->name;
		}
		return $output;
	}

	public static function isInBasket($ov){
        return Armtek::isInBasket($ov);
	}
	
    public static function getStringBasketComment($params): string
	{
		return "{$params['order_id']}-{$params['store_id']}-{$params['item_id']}";
	}
	
    public static function removeFromBasket($ov){
        return Armtek::removeFromBasket($ov);
	}

    private static function getPayerKey($pay_type = 'Наличный'){
        $type_organization = in_array($pay_type, ['Наличный', 'Онлайн']) ? 'private' : 'entity';
        return self::getParams($type_organization)->payer_key;
    }
    
    private static function getOrderKeys($store_id, $item_id){
        return $GLOBALS['db']->select_one(
            'autoeuro_order_keys',
            'offer_key',
            "`store_id` = {$store_id} AND `item_id` = {$item_id}"
        );
    }
    
	public static function sendOrder(){
        $sentItems = 0;
        $providerBasket = parent::getProviderBasket(self::getParams()->provider_id, '');
        if (!$providerBasket->num_rows) return false;

        $providerBasketPayType = [
            'Наличный' => [],
            'Безналичный' => []
        ];
        while($pb = $providerBasket->fetch_assoc()){
            switch ($pb['pay_type']){
                case 'Наличный':
                case 'Онлайн':
                $providerBasketPayType['Наличный'][] = $pb;
                    break;
                case 'Безналичный':
                    $providerBasketPayType['Безналичный'][] = $pb;
            }
        }

        foreach($providerBasketPayType as $pay_type => $providerBasket){
            $sentStockItems = [];
            $stock_items = [];
            foreach($providerBasket as $pb){
                $aeok = self::getOrderKeys($pb['store_id'], $pb['item_id']);

                if (empty($aeok)){
                    $class = new static($pb['item_id']);
                    $class->setArticle($pb['brend'], $pb['article']);
                    $aeok = self::getOrderKeys($pb['store_id'], $pb['item_id']);
                }

                $stock_items[] = [
                    'offer_key' => $aeok['offer_key'],
                    'quantity' => $pb['quan'],
                    'comment' => "{$pb['order_id']}-{$pb['store_id']}-{$pb['item_id']}"
                ];
                $sentStockItems[$aeok['offer_key']] = $pb;
            }
            $url = self::getUrlString('create_order');
            $response = parent::getUrlData($url, [
                'delivery_key' => self::getParams()->delivery_key,
                'payer_key' => self::getPayerKey($pay_type),
                'comment' => '',
                'wait_all_goods' => 0,
                'stock_items' => $stock_items
            ]);
            $result = json_decode($response);

            if ($result->DATA[0]->result === false){
                foreach($stock_items as $si){
                    $osi = explode('-', $si['comment']);
                    Log::insert([
                        'text' => "Ошибка отправки заказа",
                        'additional' => "osi: {$osi['order_id']}-{$osi['store_id']}-{$osi['item_id']}"
                    ]);
                }

            }

            foreach($result->META->request->parameters->stock_items as $value){
                $pb = $sentStockItems[$value->offer_key];
                $sentItems += $pb['quan'];
                OrderValue::changeStatus(11, $pb);
                parent::updateProviderBasket(
                    [
                        'order_id' => $pb['order_id'],
                        'store_id' => $pb['store_id'],
                        'item_id' => $pb['item_id']
                    ],
                    ['response' => 'OK']
                );
            }
        }
        return $sentItems;
	}
}

