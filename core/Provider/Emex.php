<?php
namespace core\Provider;

use core\Brend;
use core\Database;
use core\Log;
use core\Mailer;
use core\OrderValue;
use core\Provider;
use PHPMailer\PHPMailer\Exception;

class Emex extends Provider{
    private $type_organization;

    /** @var string */
    private $error;

    /** @var Database  */
    private $db;

    const PROVIDER_ID = 38;
    const SERVICE_SEARCH = 'EmExService';
    const SERVICE_DICTIONARY = 'EmExDictionaries';
    const SERVICE_BASKET = 'EmEx_Basket';

    const GROUP_ORIGINAL = 'Original';
    const GROUP_ANALOGY = 'ReplacementNonOriginal';

    public static $fieldsForSettings = [
        'isActive',
        'login',
        'password',
        'provider_id',
        'wsdl'
    ];

    /** @var Emex */
    private static $_self;

    public function __construct($type_organization = 'entity'){
        ini_set('soap.wsdl_cache_enabled',0);
        ini_set('soap.wsdl_cache_ttl',0);
        $this->type_organization = $type_organization;
        $this->db = $GLOBALS['db'];
    }

    private static function getInfstance(): Emex
    {
        if (self::$_self) return self::$_self;
        return self::$_self = new self();
    }

    public static function getItemsToOrder(int $provider_id){
        $output = [];
        $basketContent = self::getInfstance()->getBasket();
        $osiList = array_column($basketContent, 'Reference');
        $orderInfoList = OrderValue::get(['osi' => $osiList], '');

        foreach($orderInfoList as $ov){
            $output[] = [
                'provider_id' => $ov['provider_id'],
                'provider' => $ov['api_title'],
                'store_id' => $ov['store_id'],
                'store' => $ov['cipher'],
                'order_id' => $ov['order_id'],
                'brend' => $ov['brend'],
                'item_id' => $ov['item_id'],
                'article' => $ov['article'],
                'title_full' => $ov['title_full'],
                'price' => $ov['price'],
                'count' => $ov['quan']
            ];
        }
        return $output;
    }

    /**
     * @param $service
     * @param $method
     * @param array $params
     * @return mixed
     */
    public function getResponse($service, $method, array $params = [])
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

        if (isset($result->BasketData)) return $result->BasketData;

        if (isset($result->BasketChangingResult)) return $result->BasketChangingResult;

        if (isset($result->BasketReturnData)) return $result->BasketReturnData;

        if (isset($result->IsSuccess) && !$result->IsSuccess){
            $this->error = $result->ErrorMessage;
            return [];
        }

        if (isset($result->ShortMakeInfo)) return $result->ShortMakeInfo;

        if (isset($result->Details->DetailItem)) return $result->Details->DetailItem;

        return [];
    }

    public function testConnect(): array
    {
        return $this->getResponse(self::SERVICE_SEARCH, 'TestConnect', ['some string']);
    }

    private function FindDetailAdv($params = []): array
    {
        $defaultParams = [
            'substLevel' => 'OriginalOnly',
            'substFilter' => 'None',
            'deliveryRegionType' => 'PRI',
            'minDeliveryPercent' => null,
            'maxADDays' => null,
            'minQuantity' => null,
            'maxResultPrice' => null,
            'maxOneDetailOffersCount' => null,
            'detailNumsToLoad' => null
        ];
        $params = array_merge($defaultParams, $params);

        return self::getInfstance()->getResponse(self::SERVICE_SEARCH, 'FindDetailAdv5', $params);
    }

    /**
     * Получает список совпадений по номеру детали. Возвращает массив вида [бренд => имя детали]
     * @param string $detailNum
     * @return array
     */
    public static function getCoincidences(string $detailNum): array
    {
        $output = [];

        $response = self::getInfstance()->FindDetailAdv([
            'detailNum' => $detailNum,
        ]);

        if (self::getInfstance()->error) return [];

        foreach($response as $row){
            $output[$row->MakeName] = $row->DetailNameRus;
        }

        return $output;
    }

    /**
     * Получает из базы данных makeLogo по brend_id
     * @param $brend_id
     * @return false|string false - если не найдено, отправляет email
     * @throws Exception при ошибке отправки email
     */
    public function getMakeLogo($brend_id){
        static $output = [];

        if (isset($output[$brend_id])) return $output[$brend_id];

        $result = $this->db->select('emex_brends', '*', "`brend_id` = $brend_id");
        if (!$result){
            $brendInfo = Brend::get(['id' => $brend_id])->fetch_assoc();
            $mailer = new Mailer(Mailer::TYPE_INFO);
            $mailer->send([
                'emails' => ['info@tahos.ru'],
                'subject' => "Бренд {$brendInfo['title']} не сопоставлен",
                'body' => "Бренд {$brendInfo['title']} не сопоставлен на tahos.ru"
            ]);
            return false;
        }
        $output[$brend_id] = $result[0]['logo'];
        return $output[$brend_id];
    }

    public static function getMakesDict(){
        return self::getInfstance()->getResponse(self::SERVICE_DICTIONARY, 'GetMakesDict');
    }

    /**
     * Используется только для первоначального парсинга брендов
     * @return void
     */
    public static function parseBrends(){
        $response = Provider\Emex::getMakesDict();

        $emexBrands = [];
        foreach($response as $row) $emexBrands[Provider::getComparableString($row->MakeName)] = $row->MakeLogo;
        $resultGetBrends = self::getInfstance()->db->query("
            SELECT b.id,
                   b.title,
                   b.parent_id
            FROM tahos_brends b

        ");

        $ourBrands = [];
        foreach($resultGetBrends as $row){
            $title = Provider::getComparableString($row['title']);
            $ourBrands[$title]['id'] = $row['id'];
            $ourBrands[$title]['parent_id'] = $row['parent_id'];
        }
        foreach($emexBrands as $title => $logo){
            if (array_key_exists($title, $ourBrands)){
                $insert = [];
                $insert['logo'] = $logo;
                if ($ourBrands[$title]['parent_id']) $insert['brend_id'] = $ourBrands[$title]['parent_id'];
                else $insert['brend_id'] = $ourBrands[$title]['id'];

                self::getInfstance()->db->insert('emex_brends', $insert);
            }
        }
    }

    /**
     * Получает данные о номенклатуре и аналогам и записывает данные по складам
     * @throws Exception
     */
    public static function setArticle($brend_id, $article, $mainItemID): void
    {
        if (!parent::getIsEnabledApiSearch(self::PROVIDER_ID)) return;
        if (!parent::isActive(self::PROVIDER_ID)) return;

        $makeLogo = self::getInfstance()->getMakeLogo($brend_id);
        $result = self::getInfstance()->FindDetailAdv([
            'makeLogo' => $makeLogo,
            'detailNum' => $article,
            'substLevel' => 'All',
            'substFilter' => 'FilterOriginalAndAnalogs',
            'minQuantity' => 1,
            'maxOneDetailOffersCount' => 5
        ]);

        if (self::getInfstance()->error) return;

        foreach ($result as $row){
            self::getInfstance()->parseFindDetailAdv($row, $mainItemID);
        }
    }

    private function parseFindDetailAdv(object $detail, $mainItemID){
        $armtek = new Armtek(false);
        $params = [
            'BRAND' => $detail->MakeName,
            'PIN' => $detail->DetailNum,
            'NAME' => $detail->DetailNameRus,
        ];

        $item_id = $armtek->getItemId((object) $params, 'emex');

        if ($detail->PriceGroup == self::GROUP_ANALOGY){
            self::getInfstance()->db->insert('item_analogies', [
                'item_id' => $mainItemID,
                'item_diff' => $item_id
            ]);
            self::getInfstance()->db->insert('item_analogies', [
                'item_id' => $item_id,
                'item_diff' => $mainItemID
            ]);
        }

        $store_id = self::getInfstance()->getStoreID($detail);
        self::getInfstance()->addStoreItem($store_id, $item_id, $detail);
    }

    private function addStoreItem($store_id, $item_id, $detail){
        return self::getInfstance()->db->insert('store_items', [
            'store_id' => $store_id,
            'item_id' => $item_id,
            'price' => $detail->ResultPrice,
            'in_stock' => $detail->Quantity,
            'packaging' => $detail->LotQuantity
        ]);
    }

    private function getStoreID($detail){
        static $priceLogoList;

        $title = "{$detail->PriceLogo}({$detail->PriceCountry})";
        if (isset($priceLogoList->PriceLogo)) return $priceLogoList[$title];

        $result = self::getInfstance()->db->select_one('provider_stores', 'id', "`title` = '$title' AND `provider_id` = ".self::PROVIDER_ID);

        if ($result){
            $priceLogoList[$title] = $result['id'];
            return $priceLogoList[$title];
        }

        self::getInfstance()->db->insert('provider_stores', [
            'title' => $title,
            'provider_id' => self::PROVIDER_ID,
            'delivery' => $detail->ADDays,
            'delivery_max' => $detail->ADDays,
            'cipher' => $detail->PriceLogo,
            'city' => $detail->PriceCountry
        ]);
        $last_id = self::getInfstance()->db->last_id();
        $priceLogoList[$title] = $last_id;
        return $last_id;
    }

    public static function getPrice(array $params)
    {
        $makeLogo = self::getInfstance()->getMakeLogo($params['brend_id']);

        $result = self::getInfstance()->FindDetailAdv([
            'makeLogo' => $makeLogo,
            'detailNum' => $params['article'],
            'substLevel' => 'OriginalOnly',
            'substFilter' => 'None'
        ]);

        foreach($result as $detail){
            $title = "{$detail->PriceLogo}({$detail->PriceCountry})";
            if ($title == $params['providerStore']) return [
                'price' => $detail->ResultPrice,
                'available' => $detail->Quantity,
            ];
        }
        return false;
    }

    private function getBasket(){
        $result = self::getInfstance()->getResponse(
            self::SERVICE_BASKET,
            'GetBasket',
            ['basketPart' => 'Basket']
        );
        if (!is_array($result)) $result = [$result];
        return $result;
    }

    public static function isInBasket($ov){
        $result = self::getInfstance()->getBasket();
        $osi = "{$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}";
        if (is_array($result)){
            foreach($result as $row){
                if ($row->Reference == $osi) return true;
            }
        }
        else{
            if ($result->Reference == $osi) return true;
        }

        return false;
    }

    public static function removeFromBasket($ov){
        $basketContent = self::getInfstance()->getBasket();
        $osi = "{$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}";
        $array = [];
        $array['queryList'] = [];

        foreach ($basketContent as $row){
            if ($row->Reference == $osi) $array['queryList'][] = [
                'cmd' => 'Delete',
                'Id' => $row->GlobalId
            ];
        }
        self::getInfstance()->getResponse(self::SERVICE_BASKET, 'Basket_ChangeStatus_ById', $array);

        OrderValue::changeStatus(5, $ov);
    }

    public static function addToBasket($ov): void
    {
        $array = [];
        $array['ePrices'][] = [
            'MLogo' => self::getInfstance()->getMakeLogo($ov['brend_id']),
            'DNum' => $ov['article'],
            'Name' => $ov['title_full'],
            'Quan' => $ov['quan'],
            'Price' => $ov['price'],
            'PLogo' => $ov['cipher'],
            'DLogo' => 'AFL',
            'Ref' => "{$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}",
            'Com' => '',
            'Notc' => $ov['typeOrganization'] == 'entity' ? 1 : 0
        ];
        self::getInfstance()->getResponse(self::SERVICE_BASKET, 'InsertToBasket3', $array);
        OrderValue::changeStatus(7, $ov);
    }

    public static function sendOrder(): int
    {
        $ordered = 0;
        $basketContent = self::getInfstance()->getBasket();
        $globalIdOsi = [];
        $array = [];
        $array['queryList'] = [];

        foreach ($basketContent as $row){
            if (!isset($row->Reference)) continue;
            if (!preg_match("/^\d+-\d+-\d+$/", $row->Reference)) continue;

            $globalIdOsi[$row->GlobalId] = $row->Reference;

            $array['queryList'][] = [
                'cmd' => 'InOrder',
                'Id' => $row->GlobalId
            ];
        }
        $result = self::getInfstance()->getResponse(self::SERVICE_BASKET, 'Basket_ChangeStatus_ById', $array);
        if(!is_array($result)) $result = [$result];

        foreach($result as $row){
            $osi = explode('-', $globalIdOsi[$row->Id]);
            if ($row->res == 'res_OK'){
                $resOrderValue = OrderValue::get([
                    'order_id' => $osi[0],
                    'store_id' => $osi[1],
                    'item_id' => $osi[2]
                ]);
                $orderValue = $resOrderValue->fetch_assoc();
                OrderValue::changeStatus(11, $orderValue);
                $ordered += $orderValue['quan'];
            }
            else{
                Log::insert([
                    'text' => "Ошибка отправки в заказ. Код ошибки: {$row->res}",
                    'additional' => "osi: {$globalIdOsi[$row->Id]}"
                ]);
            }

        }

        return $ordered;
    }
}