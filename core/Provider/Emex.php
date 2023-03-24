<?php
namespace core\Provider;

use core\Provider;

class Emex extends Provider{
    private $connect = [
        'wsdl' => 'http://ws.emex.ru',
        'login' => '3275108',
        'password' => 'bc75b5f0'
    ];
    public static function getItemsToOrder(int $provider_id){
        // TODO: Implement getItemsToOrder() method.
    }

    /**
     * @param $method
     * @return false|\SoapClient
     */
    private function getResponse($service, $method, $params = null){
        try{
            $soap = new \SoapClient(
                "{$this->connect['wsdl']}/$service.asmx?wsdl"
            );
        }
        catch (\SoapFault $e){
            return false;
        }

        if (!$params) return $soap->$method();

        return $soap->$method($params);
    }


    public function testConnect(){
        return $this->getResponse('EmExService', 'TestConnect', ['some string']);
    }

    public function __construct(){
        ini_set('soap.wsdl_cache_enabled',0);
        ini_set('soap.wsdl_cache_ttl',0);
    }
}