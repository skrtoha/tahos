<?php
namespace core\Provider;
use core\Cache;
use core\Database;
use core\Item;
use core\Log;
use core\OrderValue;
use core\Provider;

class Absel extends Provider {
    public static $fieldsForSettings = [
        'isActive',
        'provider_id',
        'login',
        'password',
        'url',
        'with_cross'
    ];

    public function getParams($typeOrganization = 'entity'){
        $cacheId = 'Absel-api-params';
        Cache::delete($cacheId);
        $result = Cache::get($cacheId);
        if ($result) {
            return $result;
        }
        $result = Provider::getApiParams([
            'api_title' => 'Absel',
            'typeOrganization' => $typeOrganization
        ]);
        Cache::set($cacheId, $result);
        return $result;
    }

    private function getAuthParameter(): string
    {
        $params = self::getInstance()->getParams();
        return strtolower(md5($params->login.strtolower(md5($params->password))));
    }

    private function getUrl($method, $data = []): string
    {
        $self = self::getInstance();
        $params = $self->getParams();
        $data['auth'] = $self->getAuthParameter();

        return $params->url."-$method?".http_build_query($data);
    }

    private function getUserContext() {
        $cacheId = 'Absel-api-user-context';
        $result = Cache::get($cacheId);

        if ($result) {
            return $result;
        }

        $url = self::getInstance()->getUrl('get_user_context');
        $result = json_decode(parent::getUrlData($url));
        Cache::set($cacheId, $result);
        return $result;
    }

    public static function getSearch($search) {
        $params = self::getInstance()->getParams();
        if (!parent::getIsEnabledApiSearch($params->provider_id)) return false;
        if (!parent::isActive($params->provider_id)) return false;

        $url = self::getInstance()->getUrl('search', [
            'article' => $search,
            'agreement_id' => $params->agreement_id
        ]);
        $result = json_decode(parent::getUrlData($url));

        if ($result->status != 'OK') {
            return [];
        }

        $coincidences = array();
        foreach ($result->data as $item) {
            $coincidences[$item->brand] = $item->product_name;
        }

        return $coincidences;
    }

    public static function getInstance(): Absel
    {
        static $self;
        if ($self) return $self;

        $self = new static();
        return $self;
    }

    protected static function getItemsToOrder(int $provider_id): array
    {
        $basket = self::getInstance()->getBasket();
        $params = self::getInstance()->getParams();
        return Emex::getItemsToOrder($params->provider_id, 'description', $basket->products);
    }

    public static function sendOrder(){
        $params = self::getInstance()->getParams();
        $basket = self::getInstance()->getBasket();

        $prods = [];
        $p_desc = [];
        foreach($basket->products as $product){
            $prods[$product->product_id] = $product->quantity;
            $p_desc[$product->product_id] = $product->description;
        }
        $url = self::getInstance()->getUrl('create_order', [
            'ua_id' => $params->agreement_id,
            'uda_id' => $params->uda_id,
            'dt_id' => $params->dt_id,
            'prods' => $prods,
            'p_desc' => $p_desc
        ]);
        $result = json_decode(parent::getUrlData($url));

        $osiList = array_column($basket->products, 'description');
        $orderInfoList = OrderValue::get(['osi' => $osiList], '');
        if ($result->status != 'OK') {
            foreach($orderInfoList as $orderInfo){
                Log::insert([
                    'text' => $result->status,
                    'additional' => "osi: ".Autoeuro::getStringBasketComment($orderInfo)
                ]);
            }
            return 0;
        }

        foreach($orderInfoList as $ov){
            self::removeFromBasket($ov);
        }

        return count($basket->products);
    }

    private function getStoreId($warehouse_name, $delivery_duration): int
    {
        $params = self::getInstance()->getParams();
        $key =  Item::articleClear("{$params->provider_id}-{$warehouse_name}");

        $result = Cache::get($key);
        if ($result) {
            return $result;
        }

        $db = Database::getInstance();

        $store = $db->select_one(
            'provider_stores',
            'id,cipher,title',
            "`provider_id` = {$params->provider_id} AND `title` = '{$warehouse_name}'"
        );
        if ($store) {
            Cache::set($key, $store['id']);
            return $store['id'];
        }

        if (!$store && !$params->create_store) {
            Cache::set($key, false);
            return false;
        }

        Database::getInstance()->insert('provider_stores', [
            'provider_id' => $params->provider_id,
            'title' => $warehouse_name,
            'cipher' => strtoupper(parent::getRandomString()),
            'delivery' => $delivery_duration,
            'delivery_max' => $delivery_duration,
        ]);
        $lastId = Database::getInstance()->last_id();
        Cache::set($key, $lastId);
        return $lastId;
    }

    private function getItemId($item, $mainItemId) {
        $params = self::getInstance()->getParams();
        $armtek = new Armtek(false);
        $article = preg_replace('/[\W_]+/', '', $item->article);
        $key = Item::articleClear("{$params->provider_id}-{$article}-{$item->brand}");

        $result = Cache::get($key);
        if ($result) {
            return $result;
        }

        $stdClass = new \stdClass();
        $stdClass->BRAND = parent::getProviderBrend($params->provider_id, $item->brand);
        $stdClass->PIN = $item->article;
        $stdClass->NAME = $item->product_name;
        $item_id = $armtek->getItemId($stdClass, 'Absel');

        if ($item->is_cross && $params->with_cross) {
            parent::getInstanceDataBase()->insert('item_analogies', ['item_id' => $mainItemId, 'item_diff' => $item_id]);
            parent::getInstanceDataBase()->insert('item_analogies', ['item_id' => $item_id, 'item_diff' => $mainItemId]);
        }

        Cache::set($key, $item_id);
        return $item_id;
    }

    public static function setArticle($brend, $article, $mainItemId) {
        $params = self::getInstance()->getParams();
        if (!parent::getIsEnabledApiSearch($params->provider_id)) return false;
        if (!parent::isActive($params->provider_id)) return false;

        $providerBrend = parent::getProviderBrend($params->provider_id, $brend);

        $url = self::getInstance()->getUrl('search', [
            'article' => $article,
            'agreement_id' => $params->agreement_id,
            'brand' => $providerBrend,
            'with_cross' => $params->with_cross
        ]);
        $result = json_decode(parent::getUrlData($url));

        if ($result->status != 'OK') {
            return false;
        }

        foreach ($result->data as $item) {
            $item_id = self::getInstance()->getItemId($item, $mainItemId);
            if (!$item_id) {
                continue;
            }

            $store_id = self::getInstance()->getStoreId($item->warehouse_name, $item->delivery_duration);
            if (!$store_id) {
                continue;
            }

            Database::getInstance()->insert('store_items',
                [
                    'store_id' => $store_id,
                    'item_id' => $item_id,
                    'price' => $item->price,
                    'packaging' => $item->mult_sale,
                    'in_stock' => $item->quantity
                ],
                [
                    'duplicate' => [
                        'price' => $item->price,
                        'in_stock' => $item->quantity
                    ]
                ]
            );

        }
        return true;
    }

    public static function getPrice(array $data)
    {
        $params = self::getInstance()->getParams();

        $providerBrend = parent::getProviderBrend($params->provider_id, $data['brend']);

        $url = self::getInstance()->getUrl('search', [
            'article' => $data['article'],
            'agreement_id' => $params->agreement_id,
            'brand' => $providerBrend,
            'show_unavailable' => 1
        ]);
        $result = json_decode(parent::getUrlData($url));

        if ($result->status != 'OK') {
            return false;
        }

        foreach ($result->data as $item) {
            if ($item->warehouse_name != $data['providerStore']) {
                continue;
            }

            return [
                'product_id' => $item->product_id,
                'price' => $item->price,
                'available' => $item->quantity
            ];
        }

        return [];
    }

    private function getBasket() {
        $url = self::getInstance()->getUrl('cart_get');
        return json_decode(parent::getUrlData($url));
    }
    public static function isInBasket($ov): bool
    {
        $basket = self::getInstance()->getBasket();

        foreach($basket->products as $product) {
            if ($product->description == Autoeuro::getStringBasketComment($ov)){
                return true;
            }
        }
        return false;
    }

    public static function addToBasket($ov): int
    {
        $params = self::getInstance()->getParams();
        $priceInfo = self::getPrice($ov);

        $url = self::getInstance()->getUrl('cart_add', [
            'agreement_id' => $params->agreement_id,
            'prod' => [$priceInfo['product_id'] => $ov['quan']],
            'desc' => [$priceInfo['product_id'] => Autoeuro::getStringBasketComment($ov)]
        ]);
        $result = json_decode(parent::getUrlData($url));

        if ($result->status != 'OK') {
            Log::insert([
                'text' => $result->result[0]['status_msg'],
                'additional' => "osi: ".Autoeuro::getStringBasketComment($ov)
            ]);
            return 0;
        }

        OrderValue::changeStatus(11, $ov);

        return $ov['quan'];
    }

    public static function removeFromBasket($ov): void
    {
        $orderInfo = OrderValue::get(['osi' => Autoeuro::getStringBasketComment($ov)])->fetch_assoc();
        $ov['brend'] = $orderInfo['brend'];
        $ov['article'] = $orderInfo['article'];
        $ov['providerStore'] = $orderInfo['providerStore'];

        $priceInfo = self::getPrice($ov);
        $url = self::getInstance()->getUrl('cart_delete', [
            'prod' => [$priceInfo['product_id']],
        ]);
        parent::getUrlData($url);
    }
}