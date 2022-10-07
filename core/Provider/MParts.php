<?php
namespace core\Provider;

use core\Log;

if ($_SERVER['DOCUMENT_ROOT']) $path = $_SERVER['DOCUMENT_ROOT'].'/';
else $path = '';
require_once $path.'vendor/autoload.php';


class MParts extends Abcp{
    private static $provider_id = 13;

    public static function sendOrder($provider_id = 13){
        self::$provider_id = $provider_id;
        $providerBasket = parent::getProviderBasket($provider_id, '');
        $param = self::getParam($provider_id);
        $private = [];
        $entity = [];

        foreach($providerBasket as $value){
            $key = "{$value['order_id']}-{$value['store_id']}-{$value['item_id']}";

            if (!self::checkBasket()){
                Log::insert([
                    'text' => 'Ошибка отправки заказа - корзина не пуста',
                    'additional' => "osi: $key"
                ]);
                continue;
            }

            if (!parent::getIsEnabledApiOrder($provider_id)){
                Log::insert([
                    'text' => "API заказов для {$param['title']} отключено",
                    'additional' => "osi: $key"
                ]);
                continue;
            }

            if ($value['pay_type'] == 'Безналичный'){
                $entity[$key] = $value;
            }
            else $private[$key] = $value;
        }

        $responseAddToBasket = self::addToBasket($entity, 'entity');
//        parent::parseResponseAddToBasket($responseAddToBasket, $entity);

        $responseAddToBasket = self::addToBasket($private, 'private');
//        parent::parseResponseAddToBasket($responseAddToBasket, $private, 'private');

    }

    private static function addToBasket($items, $typeOrganization){
        self::clearBasket();
        $param = self::getParam(self::$provider_id, $typeOrganization);
        $url = self::getUrlGetQuery('organizationAddress/getList');
//        $url .= "&addressId={$param['addressId']}&partner_id={$param['partnerId']}";
        $response = json_decode(parent::getUrlData($url), true);
        return $response;

    }

    private static function clearBasket(){
        $param = self::getParam(self::$provider_id);
        $response = parent::getUrlData(
            "{$param['url']}/basket/clear",
            [
                'userlogin' => $param['userlogin'],
                'userpsw' => md5($param['userpsw'])
            ]
        );
        return json_decode($response);
    }

    private static function getUrlGetQuery($method){
        $param = self::getParam(self::$provider_id);
        return  "{$param['url']}/$method/?userlogin={$param['userlogin']}&userpsw=".md5($param['userpsw']);
    }

    private static function checkBasket(){
        static $response = null;

        if (!is_null($response)) return $response;

        $url = self::getUrlGetQuery('basket/content');
        $result = self::getUrlData($url);
        $response = json_decode($result, true);
        $response = empty($response);
        return $response;
    }
}
