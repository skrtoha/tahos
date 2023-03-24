<?php
namespace core\Provider;

use core\Provider;

class Emex extends Provider{
    private $type_organization;

    private $error;

    const SERVICE_SEARCH = 'EmExService';

    public static $fieldsForSettings = [
        'isActive',
        'login',
        'password',
        'provider_id',
        'wsdl'
    ];

    public function __construct($type_organization = 'entity'){
        ini_set('soap.wsdl_cache_enabled',0);
        ini_set('soap.wsdl_cache_ttl',0);
        $this->type_organization = $type_organization;
    }
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
                "{$apiParams->wsdl}/$service.asmx?wsdl"
            );
        }
        catch (\SoapFault $e){
            $this->error = $e->getMessage();
            return [];
        }

        if (!$params) return $soap->$method();

        $resultName = $method."Result";
        $result = $soap->$method($params)->{$resultName};

        if (!$result->IsSuccess){
            $this->error = $result->ErrorMessage;
            return [];
        }

        return $result->Details->DetailItem;
    }

    public function testConnect(): array
    {
        return $this->getResponse(self::SERVICE_SEARCH, 'TestConnect', ['some string']);
    }

    /**
     * Получает список совпадений по номеру детали. Возвращает массив вида [бренд => имя детали]
     * @param string $detailNum
     * @return array
     */
    public static function getCoincidences(string $detailNum): array
    {
        $output = [];
        $self = new self();
        $params = [];
        $params['detailNum'] = $detailNum;
        $params['substLevel'] = 'OriginalOnly';
        $params['substFilter'] = 'None';
        $params['deliveryRegionType'] = 'PRI';
        $params['minDeliveryPercent'] = null;
        $params['maxADDays'] = null;
        $params['minQuantity'] = null;
        $params['maxResultPrice'] = null;
        $params['maxOneDetailOffersCount'] = null;
        $params['detailNumsToLoad'] = null;

        $response = $self->getResponse(self::SERVICE_SEARCH, 'FindDetailAdv5', $params);
        if ($self->error) return [];

        foreach($response as $row){
            $output[$row->MakeName] = $row->DetailNameRus;
        }

        return $output;
    }

}