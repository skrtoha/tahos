<?php
namespace core\Provider;

use core\Database;
use core\Provider;

class Emex extends Provider{
    private $type_organization;

    private $error;

    const PROVIDER_ID = 38;
    const SERVICE_SEARCH = 'EmExService';
    const SERVICE_DICTIONARY = 'EmExDictionaries';

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
     * @param $service
     * @param $method
     * @param array $params
     * @return array
     */
    public function getResponse($service, $method, array $params = []): array
    {
        $apiParams = parent::getApiParams([
            'provider_id' => self::PROVIDER_ID,
            'typeOrganization' => $this->type_organization
        ]);
        $params['login'] = $apiParams->login;
        $params['password'] = $apiParams->password;

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

        if (isset($result->IsSuccess) && !$result->IsSuccess){
            $this->error = $result->ErrorMessage;
            return [];
        }

        if (isset($result->ShortMakeInfo)) return $result->ShortMakeInfo;

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
    public static function getCoincidences(string $detailNum, $makeLogo = null): array
    {
        $output = [];
        $self = new self();
        $params = [];

        if ($makeLogo) $params[$makeLogo] = $makeLogo;

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

    public static function getMakesDict(){
        $self = new static();
        $output = [];
        $response = $self->getResponse(self::SERVICE_DICTIONARY, 'GetMakesDict');
        foreach($response as $row) $output[$row->MakeName] = $row->MakeLogo;
        return $output;
    }

    /**
     * Используется только для первоначального парсинга брендов
     * @return void
     */
    public static function parseBrends(){
        /** @var Database $db */
        $db = $GLOBALS['db'];

        $emexBrands = Provider\Emex::getMakesDict();
        $resultGetBrends = $db->query("
            SELECT b.id,
                   b.title,
                   b.parent_id
            FROM tahos_brends b

        ");

        $ourBrands = [];
        foreach($resultGetBrends as $row){
            $ourBrands[$row['title']]['id'] = $row['id'];
            $ourBrands[$row['title']]['parent_id'] = $row['parent_id'];
        }
        foreach($emexBrands as $title => $logo){
            if (array_key_exists($title, $ourBrands)){
                $insert = [];
                $insert['logo'] = $logo;
                if ($ourBrands[$title]['parent_id']) $insert['brend_id'] = $ourBrands[$title]['parent_id'];
                else $insert['brend_id'] = $ourBrands[$title]['id'];

                $db->insert('emex_brends', $insert);
            }
        }
    }
}