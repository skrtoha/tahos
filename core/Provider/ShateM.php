<?php
namespace core\Provider;

use core\Cache;
use core\Database;
use core\Provider;

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
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        ));

        switch ($type) {
            case 'POST':
                curl_setopt_array($curl, [
                    CURLOPT_URL => $params->url.$method,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => http_build_query($data),
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
        return json_decode($response, true);
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
}