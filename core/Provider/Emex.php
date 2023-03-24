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
    private function getSoap($method){
        try{
            $soap = new \SoapClient(
                "{$this->connect['wsdl']}/$method.asmx?WSDL"
            );
        }
        catch (\SoapFault $e){
            return false;
        }
        return $soap;
    }


    public function testConnection(){
        $soap = $this->getSoap('TestConnect');
        $result = $soap->TestConnect([
            'login' => '3275108',
            'password' => 'bc75b5f0'
        ]);
        return $result;
    }

    public function __construct(){
        ini_set('soap.wsdl_cache_enabled',0);
        ini_set('soap.wsdl_cache_ttl',0);
    }
}