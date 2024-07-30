<?php
namespace core\Provider;

use core\Cache;
use core\Database;
use core\Log;
use core\OrderValue;
use core\Provider;
use core\User;

class ShateM extends Provider
{
    public static $fieldsForSettings = [
        'isActive',
        'provider_id',
        'login',
        'password',
        'url',
        'with_cross'
    ];

    public function getParams($typeOrganization = 'entity'){
        $cacheId = 'ShateM-api-params';
        $result = Cache::get($cacheId);
        if ($result) {
            return $result;
        }
        $result = Provider::getApiParams([
            'api_title' => 'ShateM',
            'typeOrganization' => $typeOrganization
        ]);
        Cache::set($cacheId, $result);
        return $result;
    }
    public static function getInstance(): ShateM
    {
        static $self;
        if ($self) return $self;

        $self = new static();
        return $self;
    }

    private function sendRequest($method, $data, $type = 'POST'): array
    {
        $params = $this->getParams();
        $curl = curl_init();
        $headers = [];
        if (is_array($data)) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        else {
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYPEER => false
        ));

        switch ($type) {
            case 'POST':
                curl_setopt_array($curl, [
                    CURLOPT_URL => $params->url.$method,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                ]);
                if (is_array($data)) {
                    $fields = http_build_query($data);
                }
                else {
                    $fields = $data;
                }
                curl_setopt_array($curl, [
                    CURLOPT_POSTFIELDS => $fields
                ]);
                break;
            case 'GET':
                curl_setopt_array($curl, [
                    CURLOPT_URL => $params->url.$method.'?'.http_build_query($data),
                    CURLOPT_CUSTOMREQUEST => 'GET',
                ]);
                break;
        }

        if ($method != 'auth/loginByapiKey') {
            $headers[] = "Authorization: Bearer ".$this->getAccessToken();
        }

        curl_setopt_array($curl, [
            CURLOPT_HTTPHEADER => $headers
        ]);

        $response = curl_exec($curl);
        if (!$response) {
            return [];
        }

        return json_decode($response, true);
    }

    public static function setArticle($brend, $article, $mainItemId) {
        $params = self::getInstance()->getParams();
        if (!parent::getIsEnabledApiSearch($params->provider_id)) {
            return false;
        }
        if (!parent::isActive($params->provider_id)) {
            return false;
        }

        $cacheId = "ShateM-$brend-$article";
        if (Provider::getCacheData($cacheId)) {
            return true;
        }

        $self = self::getInstance();
        $articleId = $self->getArticleId($brend, $article);
        $queryParams = [
            'deliveryAddressCode' => $self->getAddress(),
            'agreementCode' => $params->agreementCode,
            'articleId' => $articleId
        ];
        if ($params->with_cross) {
            $queryParams['includeAnalogs'] = true;
        }
        $queryParams = [$queryParams];
        $response = $self->sendRequest('prices/search', json_encode($queryParams));

        $mainItems = [];
        $crossItems = [];
        foreach($response as $item) {
            if (!$item['addInfo']['city']) {
                continue;
            }
            if ($item['articleId'] == $articleId) {
                $mainItems[] = $item;
            }
            else {
                $crossItems[] = $item;
            }
        }
        foreach($mainItems as $item) {
            $store_id = self::getInstance()->getStoreId($item['addInfo']['city']);
            self::getInstance()->setStoreItem($store_id, $mainItemId, $item);
        }

        if ($params->with_cross) {
            self::getInstance()->processCrosses($crossItems, $mainItemId);
        }

        Provider::setCacheData($cacheId);

        return true;
    }

    private function processCrosses(array $crossItems, $mainItemId) {
        $ids = array_column($crossItems, 'articleId');
        $ids = array_unique($ids);
        $ids = array_values($ids);
        $queryParams = [
            'ids' => $ids
        ];
        $response = self::getInstance()->sendRequest('articles/search', json_encode($queryParams));
        $articles = [];
        foreach($response as $item) {
            $articles[$item['article']['id']] = $item['article'];
        }

        foreach($crossItems as $item) {
            $item_id = self::getInstance()->getItemId($articles[$item['articleId']], $mainItemId);
            $store_id = self::getInstance()->getStoreId($item['addInfo']['city']);
            self::getInstance()->setStoreItem($store_id, $item_id, $item);
        }
    }

    private function setStoreItem($store_id, $item_id, $item) {
        Database::getInstance()->insert('store_items',
            [
                'store_id' => $store_id,
                'item_id' => $item_id,
                'price' => $item['price']['value'],
                'packaging' => $item['quantity']['multiplicity'],
                'in_stock' => $item['quantity']['available']
            ],
            [
                'duplicate' => [
                    'price' => $item['price']['value'],
                    'in_stock' => $item['quantity']['available']
                ]
            ]
        );
    }

    private function getItemId($item, $mainItemId) {
        $cacheId = "Shate-M-item-id-{$item['code']}-{$item['tradeMarkName']}";
        $result = Cache::get($cacheId);
        if ($result) {
            return $result;
        }

        $params = self::getInstance()->getParams();

        $armtek = new Armtek(false);
        $stdClass = new \stdClass();
        $stdClass->BRAND = parent::getProviderBrend($params->provider_id, $item['tradeMarkName']);
        $stdClass->PIN = $item['code'];
        $stdClass->NAME = $item['name'];
        $item_id = $armtek->getItemId($stdClass, 'ShateM');

        parent::getInstanceDataBase()->insert('item_analogies', ['item_id' => $mainItemId, 'item_diff' => $item_id]);
        parent::getInstanceDataBase()->insert('item_analogies', ['item_id' => $item_id, 'item_diff' => $mainItemId]);

        Cache::set($cacheId, $item_id);
        return $item_id;
    }

    private function getStoreId($city) {
        $cacheId = 'ShateM-api-stores';
        $result = Cache::get($cacheId);
        if ($result) {
            return $result[$city];
        }

        $params = self::getInstance()->getParams();
        $stores = Database::getInstance()->select('provider_stores', 'id,title', "`provider_id` = ".$params->provider_id);
        $output = [];
        foreach($stores as $store) {
            $output[$store['title']] = $store['id'];
        }
        Cache::set($cacheId, $output);
        return $output[$city];
    }

    private function getCustomerAgreements() {
        $cacheId = "ShateM-api-custormer-agreements";
        $output = Cache::get($cacheId);
        if ($output) {
            return $output;
        }
        $result = self::getInstance()->sendRequest('customer/agreements', '', 'GET');
        Cache::set($cacheId, $result);
        return $result;
    }

    private function getAddress() {
        $customerInfo = self::getInstance()->getCustormerInfo();
        return $customerInfo['defaultDeliveryAddressCode'];
    }

    private function getCustormerInfo() {
        $cacheId = "ShateM-api-custormer-info";
        $output = Cache::get($cacheId);
        if ($output) {
            return $output;
        }
        $result = self::getInstance()->sendRequest('customer/info', '', 'GET');
        Cache::set($cacheId, $result);
        return $result;
    }

    private function getArticleId($brend, $article) {
        $cacheId = "ShateM-api-$brend-$article";
        $output = Cache::get($cacheId);
        if ($output) {
            return $output;
        }

        $params = self::getInstance()->getParams();
        $providerBrend = parent::getProviderBrend($params->provider_id, $brend);

        $array = ['keys' => [
            [
                'ArticleCode' => $article,
                'tradeMarkName' => strtoupper($providerBrend)
            ]
        ]];
        $result = self::getInstance()->sendRequest("articles/search", json_encode($array));
        $articleId = $result[0]['article']['id'];
        Cache::set($cacheId, $articleId);
        return $articleId;
    }

    public static function getSearch($search) {
        $params = self::getInstance()->getParams();
        if (!parent::getIsEnabledApiSearch($params->provider_id)) {
            return false;
        }
        if (!parent::isActive($params->provider_id)) {
            return false;
        }

        $cacheId = "ShateM-api-search-$search";
        $output = Cache::get($cacheId);
        if ($output) {
            return $output;
        }

        $result = self::getInstance()->sendRequest("articles/search", [
            'searchString' => $search
        ], 'GET');

        $coincidences = [];
        foreach($result as $row) {
            if (!$row['article']['name']) {
                continue;
            }
            $coincidences[$row['article']['tradeMarkName']] = $row['article']['name'];
        }
        Cache::set($cacheId, $coincidences);
        return $coincidences;
    }

    private function getAccessToken() {
        $cacheId = 'ShateM-api-access-token';
        $result = Cache::get($cacheId);
        if ($result) {
            return $result;
        }
        $params = $this->getParams();
        $result = self::getInstance()->sendRequest('auth/loginByapiKey', [
            'apikey' => $params->apikey
        ]);
        Cache::set($cacheId, $result['access_token'], 3600);
        return $result['access_token'];
    }

    protected static function getItemsToOrder(int $provider_id)
    {
        // TODO: Implement getItemsToOrder() method.
    }

    public static function getPrice(array $params, $returnItem = false) {
        $articleId = self::getInstance()->getArticleId($params['brend'], $params['article']);
        $apiParams = self::getInstance()->getParams();
        $queryParams = [
            'deliveryAddressCode' => self::getInstance()->getAddress(),
            'agreementCode' => $apiParams->agreementCode,
            'articleId' => $articleId
        ];
        $queryParams = [$queryParams];
        $response = self::getInstance()->sendRequest('prices/search', json_encode($queryParams));

        foreach($response as $item) {
            if (!$item['addInfo']['city'] || $item['addInfo']['city'] != $params['providerStore']) {
                continue;
            }
            if ($returnItem) {
                return $item;
            }
            else {
                return [
                    'price' => $item['price']['value'],
                    'available' => $item['quantity']['available'],
                ];
            }
        }
        return [];
    }

    public static function isInBasket($ov){
        return Armtek::isInBasket($ov);
    }

    public static function removeFromBasket($ov) {
        return Armtek::removeFromBasket($ov);
    }

    public static function sendOrder() {
        $apiParams = self::getInstance()->getParams();
        $providerBasket = parent::getProviderBasket($apiParams->provider_id, '');
        foreach($providerBasket as $pb){
            $item = self::getPrice([
                'brend' => $pb['brend'],
                'article' => $pb['article'],
                'providerStore' => $pb['store']
            ], true);

            $queryParams = [
                'agreementCode' => $apiParams->agreementCode,
                'deliveryInfo' => [
                    'deliveryType' => 'ManyDelivery',
                    'deliveryAddressCode' => self::getInstance()->getAddress(),
                ],
                'agreeWithTermsOfDelivery' => true,
                'agreeWithPersonalDataProcessingPolicyAndUserAgreement' => true,
                'priceItems' => [
                    [
                        'priceId' => $item['id'],
                        'quantity' => $pb['quan'],
                        'comment' => "{$pb['order_id']}-{$pb['store_id']}-{$pb['item_id']}"
                    ]
                ]
            ];

            $agreements = self::getInstance()->getCustomerAgreements();
            $agreementGroup = '';
            foreach($agreements as $agreement) {

                if ($agreement['code'] == $apiParams->agreementCode) {
                    $agreementGroup = $agreement['agreementGroup'];
                }
            }
            if ($agreementGroup == 'НАЛ') {
                $orderInfo = OrderValue::getOrderInfo($pb['order_id']);
                $userInfo = User::get(['user_id' => $orderInfo['user_id']])->fetch_assoc();
                $queryParams['fio'] = $userInfo['full_name'];
                $queryParams['phone'] = $userInfo['phone'];
            }

            $response = self::getInstance()->sendRequest('orders/bypriceitems', json_encode($queryParams));

            if ($response['id']) {
                OrderValue::changeStatus(11, $pb);
            }
            else {
                Log::insert([
                    'text' => 'Ошибка отправки заказа: '.json_encode($response),
                    'additional' => "osi: ".Autoeuro::getStringBasketComment($pb)
                ]);
            }
        }
    }
}