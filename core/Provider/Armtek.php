<?php
namespace core\Provider;
use core\Provider;
use core\OrderValue;
use core\Log;
use ArmtekRestClient\Http\Config\Config as ArmtekRestClientConfig;
use ArmtekRestClient\Http\ArmtekRestClient as ArmtekRestClient;
use core\User;

//добавлен в связи с тем, что не работало в тестах для Росско
if ($_SERVER['DOCUMENT_ROOT']) $path = $_SERVER['DOCUMENT_ROOT'].'/';
else $path = '';

require_once $path.'vendor/Armtek/autoloader.php';
require_once $path.'vendor/autoload.php';


class Armtek extends Provider{
	public static $fieldsForSettings = [
		"isActive",	// is required	
		'user_login',
		'user_password',
		'KUNNR_RG',
		'provider_id'
	];
	public static $provider_id = 2;
	public static $keyzak = array();
	private $mainItemId;
	private static $params = [
		'VKORG' => '4000',
		'KUNWE' => '',
		'KUNZA' => '',
		'INCOTERMS' => '',
		'PARNR' => '',
		'VBELN' => '',
		'TEXT_ORD' => '',
		'TEXT_EXP' => '',
		'DBTYP' => 3,
		'format' => 'json'
	];
	public static function getConfig($typeOrganization = 'entity', $isReturnObject = false){
		$config = parent::getApiParams([
			'api_title' => 'Armtek',
			'typeOrganization' => $typeOrganization
		]);
		if ($isReturnObject) return $config;
		else return (array) $config;
	}
	public function __construct($getArmtekClient = true){
        if ($getArmtekClient) $this->armtek_client = self::getClientArmtek();
	}
	public static function getPrice(array $fields){
		$params = self::$params;
		$config = self::getConfig();
		$params['PIN'] = $fields['article'];
		$params['BRAND']	 = $fields['brend'];
		$params['QUERY_TYPE']	= 1;
		$params['KUNNR_RG'] = $config['KUNNR_RG'];
		$request_params = [
			'url' => 'search/search',
			'params' => $params
		];
		$response = self::getClientArmtek()->post($request_params);
		$data = $response->json();
		if ($data->STATUS == '401') return false;

		$storeInfo = parent::getStoreInfo($fields['store_id']);

		foreach($data->RESP as $value){
			if ($value->KEYZAK == $storeInfo['title']) return [
				'price' => $value->PRICE,
				'available' => $value->RVALUE
			];
		}
		return false;
	}
	private static function getClientArmtek($typeOrganization = 'entity'){
		$armtek_client_config = new ArmtekRestClientConfig(self::getConfig($typeOrganization));
		return new ArmtekRestClient($armtek_client_config);
	}
	public static function getItemsToOrder(int $provider_id){
		return Abcp::getItemsToOrder($provider_id);
	}
	public static function getDaysDelivery($str){
		$year = substr($str, 0, 4);
		$month = substr($str, 4, 2);
		$day = substr($str, 6, 2);
		$hour = substr($str, 8, 2);
		$currentTime = time();
		$endTime = mktime($hour, 0, 0, $month, $day, $year);
		return bcdiv($endTime - $currentTime, 86400);
	}
		/**
		* @param $object
		* @return bool|mixed
		*/
	private function getStoreId($object){
 		if (!$object->KEYZAK) return false;
		if (array_key_exists($object->KEYZAK, self::$keyzak)) return self::$keyzak[$object->KEYZAK];
        
        $deliveryDate = $object->WRNTDT ?: $object->DLVDT;
        $delivery = self::getCountDelivery($deliveryDate);
        $delivery_max = $object->WRNTDT ? self::getCountDelivery($object->WRNTDT) : $delivery;
        
        $GLOBALS['db']->insert(
            'provider_stores',
            [
                'provider_id' => self::$provider_id,
                'title' => $object->KEYZAK,
                'cipher' => strtoupper(self::getRandomString(4)),
                'percent' => 11,
                'currency_id' => 1,
                'delivery' => $delivery,
                'delivery_max' => $delivery_max,
                'under_order' => 2,
                'noReturn' => 0,
            ],
            ['duplicate' => [
                'delivery' => $delivery,
                'delivery_max' => $delivery_max
            ]]
        );
        $last_id = $GLOBALS['db']->last_id();
        self::$keyzak[$object->KEYZAK] = $last_id;
        return $last_id;
	}
	
	private static function getCountDelivery($sting){
	    $currentDate = new \DateTime();
	    $targetDate = \DateTime::createFromFormat('YmdHis', $sting);
	    $totalDays = $targetDate->diff($currentDate)->days;
	    $days = 1;
	    for($i = 1; $i <= $totalDays; $i++){
	        $dayWeek = $currentDate->add(new \DateInterval('P1D'))->format('l');
	        if ($dayWeek != 'Sunday') $days++;
        }
	    return $days;
    }
	
	public static function getBrendId($brand, $from = 'armtek'){
		/**
		 * ['brend' => brend_id]
		 * @var array
		 */
		static $brends;
		if (isset($brends[$brand])) return $brends[$brand];
		$brend = parent::getInstanceDataBase()->select_one('brends', 'id,parent_id', "`title`='$brand'");
		if (empty($brend)) {
			parent::getInstanceDataBase()->insert(
				'log_diff',
				[
					'type' => 'brends',
					'from' => $from,
					'text' => "Бренд $brand отсутствует в базе",
					'param1' => $brand,
					'param2' => $brand
				]
			);
			return false;
		}
		if ($brend['parent_id']) $brend_id = $brend['parent_id'];
		else $brend_id = $brend['id'];
		$brends[$brand] = $brend_id;
		return $brend_id;
	}
	public function getItemId($object, $from = NULL){
		if (!$from) $brend_id = $this->getBrendId($object->BRAND);
		else $brend_id = $this->getBrendId($object->BRAND, $from);
		if (!$brend_id) return false;
		$article = preg_replace('/[\W_]+/', '', $object->PIN);
		$item = $GLOBALS['db']->select('items', 'id', "`article`='{$article}' AND `brend_id`= $brend_id");
		if (empty($item)){
			$res = $GLOBALS['db']->insert('items', [
				'brend_id' => $brend_id,
				'article' => $article,
				'article_cat' => $object->PIN,
				'title' => $object->NAME,
				'title_full' => $object->NAME,
				'source' => $from
			], ['print_query' => false]);
			if ($res === true){
				$item_id = $GLOBALS['db']->last_id();
				$GLOBALS['db']->insert('item_articles', ['item_id' => $item_id, 'item_diff' => $item_id]);
				if ($from == 'mikado') $GLOBALS['db']->insert('mikado_zakazcode', ['item_id' => $item_id, 'ZakazCode' => $object->ZakazCode]);
				return $item_id;
			} 
			else return false;
		}
		else{
			if ($from == 'mikado') $GLOBALS['db']->insert('mikado_zakazcode', ['item_id' => $item[0]['id'], 'ZakazCode' => $object->ZakazCode]);
			return $item[0]['id'];
		} 
	}
	private function render($array){
		foreach($array as $value){
			$store_id = $this->getStoreId($value);
			if (!$store_id) continue;
			$item_id = $this->getItemId($value);
			if (!$value->ANALOG) $this->mainItemId = $item_id;
			else{
				$GLOBALS['db']->insert('item_analogies', ['item_id' => $this->mainItemId, 'item_diff' => $item_id]);
				$GLOBALS['db']->insert('item_analogies', ['item_id' => $item_id, 'item_diff' => $this->mainItemId]);
			}
			$GLOBALS['db']->insert('store_items', [
				'store_id' => $store_id,
				'item_id' => $item_id,
				'price' => $value->PRICE,
				'in_stock' => $value->RVALUE,
				'packaging' => $value->RDPRF
			],
			[
				'print_query' => false,
				'duplicate' => [
					'in_stock' => $value->RVALUE
				]
			]
			);
		}
	}
	public function setArticle($brand, $article){
		if (!parent::getIsEnabledApiSearch(self::$provider_id)) return false;
		if (!parent::isActive(self::$provider_id)) return false;

        $cacheId = "Armtek-$brand-$article";
        if (Provider::getCacheData($cacheId)) {
            return false;
        }

		$config = self::getConfig();
		$params = self::$params;
		$params['PIN'] = $article;
		$params['BRAND']	 = $brand;
		$params['QUERY_TYPE']	= 1;
		$params['KUNNR_RG'] = $config['KUNNR_RG'];
		$request_params = [
			'url' => 'search/search',
			'params' => $params
		];
		$response = self::getClientArmtek()->post($request_params);
		$data = $response->json();
		$this->render($data->RESP);
        Provider::setCacheData($cacheId);
        return true;
	}
	public function getSearch($search){
		if (!parent::getIsEnabledApiSearch(self::$provider_id)) return false;
		if (!parent::isActive(self::$provider_id)) return false;
		$config = self::getConfig();
		$params = self::$params;
		$params['PIN'] = $search;
		$params['KUNNR_RG'] = $config['KUNNR_RG'];
		$request_params = [
			'url' => 'search/search',
			'params' => $params
		];
		$response = $this->armtek_client->post($request_params);
		$data = $response->json();
		if ($data->RESP->ERROR || $data->RESP->MSG){
			$text = $data->RESP->ERROR ? $data->RESP->ERROR : $data->RESP->MSG;
			$errorMessage = "Артикул: $search";
			foreach($data->MESSAGES as $msg) $errorMessage .= "{$msg->TEXT}\n";
			Log::insert([
				'url' => $_SERVER['REQUEST_URI'],
				'text' => "Армтек: $text",
				'additional' => $errorMessage
			]);
			return false;
		}
		if ($data->RESP->MSG) return false;
		$coincidences = array();
		foreach($data->RESP as $value){
			if (self::getComparableString($search) == self::getComparableString($value->PIN)){
				$coincidences[$value->BRAND] = $value->NAME;
			}
		}
		return $coincidences;
	}
	private function getStoreIdByKeyzak($keyzak){
		if (array_key_exists($keyzak, self::$keyzak)) return self::$keyzak[$keyzak];
		$array = $GLOBALS['db']->select_one('provider_stores', 'id', "`title`='$keyzak` AND `provider_id`= " . self::$provider_id);
		if (empty($array)) return false;
		self::$keyzak[$keyzak] = $array['id'];
		return $array['id'];
	}
	public function isKeyzak($store_id){
		if ($temp = array_search($store_id, self::$keyzak)) return $temp;
		$array = $GLOBALS['db']->select_one('provider_stores', 'id,title,provider_id', "`id`=$store_id");
		if (empty($array)) return false;
		if ($array['provider_id'] == self::$provider_id){
			self::$keyzak[$array['title']] = $array['id'];
			return true;
		} 
		return false;
	}
	private static function getKeyzakByStoreId($store_id){
		if ($temp = array_search($store_id, self::$keyzak)) return $temp;
		$array = parent::getInstanceDataBase()->select_one('provider_stores', 'id,title,provider_id', "`id`=$store_id");
		if (empty($array)) return false;
		self::$keyzak[$array['title']] = $array['id'];
		return $array['title'];
	}
	public function isOrdered($value, $type = 'armtek'){
		$where = self::getWhere($value);
		$where .= " AND `type`='$type'";
		$ov = $GLOBALS['db']->select_one('other_orders', '*', $where);
		if (!empty($ov)) return $ov;
		else return false;
	}
	public function deleteFromOrder($value, $type = 'armtek'){
		$where = self::getWhere($value);
		$GLOBALS['db']->delete('other_orders', "$where AND `type`='$type'");
		OrderValue::changeStatus(5, $value);
	}
	public static function clearString($str){
		$str = mb_strtolower($str);
		return preg_replace('/[^\wА-Яа-я]+/', '', $str);
	}
	/**
	 * executes a sending to order
	 * @param  [type] $items     [description]
	 * @param  [type] $user_type [description]
	 * @return [type]            [description]
	 */
	public static function executeSendOrder($items, $typeOrganization){
	    if (empty($items)) return false;
		$params = self::$params;
		$config = self::getConfig($typeOrganization);
		$params['VKORG'] = self::$params['VKORG'];
		$params['KUNRG'] = $config['KUNNR_RG'];
		if (empty($items)){
			Log::insert([
				'url' => $_SERVER['REQUEST_URI'],
				'text' => "Армтек: не найдено товаров для отправки для $typeOrganization"
			]);
			return 0;
		} 
		$itemsForSending = array();
		foreach($items as $i){
		    $key = strtoupper($i['brend']) . ":" . strtoupper($i['article']) . ":" .strtoupper($i['store']);
			$itemsForSending[$key] = [
				'order_id' => $i['order_id'],
				'item_id' => $i['item_id'],
				'store_id' => $i['store_id'],
				'user_id' => $i['user_id'],
				'price' => $i['price'],
				'PIN' => $i['article'],
				'BRAND' => $i['brend'],
				'KWMENG' => $i['quan'],
				'KEYZAK' => $i['store'],
                'bill_type' => $typeOrganization == 'entity' ? User::BILL_CASHLESS : User::BILL_CASH
			];
		}
		$params['ITEMS'] = $itemsForSending;
		$params['format'] = 'json';

        //тестовый заказ createTestOrder
		$request_params = [
			'url' => 'order/createOrder',
			'params' => $params
		];
        $response = self::getClientArmtek($typeOrganization)->post($request_params);
        return [
			'itemsForSending' => $itemsForSending,
			'responseData' => $response->json()
		];
	}
	
	private static function getUserInfo(){
        $params = self::$params;
        $request_params = [
            'url' => 'user/getUserInfo',
            'params' => [
                'VKORG' => $params['VKORG']
            ]
        ];
	    $response = self::getClientArmtek('private')->post($request_params);
        return $response->json();
    }
	private static function parseOrderResponse($input){
        $output = 0;
		$items = $input['itemsForSending'];
		$response = $input['responseData'];
		if (!empty($response->MESSAGES)){
			$errorMessage = "";
			foreach($response->MESSAGES as $value) $errorMessage .= "{$value->TYPE} - {$value->TEXT}\n";
			foreach ($items as $i) Log::insert([
				'text' => $errorMessage,
				'additional' => "osi: {$i['order_id']}-{$i['store_id']}-{$i['item_id']}"
			]);
		}

		if (empty($response->RESP->ITEMS)) return false;

		foreach($response->RESP->ITEMS as $value){
			$itemKey = strtoupper($value->BRAND) . ":" . strtoupper($value->PIN) . ":" . strtoupper($value->KEYZAK);
			if (isset($value->ERROR_MESSAGE) && $value->ERROR_MESSAGE){
				Log::insert([
					'text' => $value->ERROR_MESSAGE,
					'additional' => "osi: {$items[$itemKey]['order_id']}-{$items[$itemKey]['store_id']}-{$items[$itemKey]['item_id']}"
				]);
			}
			if (isset($value->RESULT) && !empty($value->RESULT)){
                $i = & $items[$itemKey];
				if ($value->RESULT[0]->ERROR){
					Log::insert([
						'text' => $value->RESULT[0]->ERROR,
						'additional' => "osi: {$i['order_id']}-{$i['store_id']}-{$i['item_id']}"
					]);
					continue;
				}
				if ($value->RESULT->REMAIN) Log::insert([
					'text' => 'Армтек: нехватка остатка для заказа',
					'additional' => "osi: {$i['order_id']}-{$i['store_id']}-{$i['item_id']}"
				]);
				OrderValue::changeStatus(11, [
					'order_id' => $i['order_id'],
					'store_id' => $i['store_id'],
					'item_id' => $i['item_id'],
					'price' => $i['price'],
					'quan' => $value->RESULT[0]->KWMENG,
					'user_id' => $i['user_id']
				]);
				parent::updateProviderBasket(
					[
						'order_id' => $i['order_id'],
						'store_id' => $i['store_id'],
						'item_id' => $i['item_id'],
					],
					[
						'response' => 'OK',
						'successful' => 1
					]
				);
                $output += $value->RESULT[0]->KWMENG;
			}
		}
        return $output;
	}
	/**
	 * prepares data for sending and sends them to order
	 * @return void
	 */
	public static function sendOrder(){
		$items = [];
		$config = self::getConfig();
        $ordered = 0;
		$providerBasket = parent::getProviderBasket($config['provider_id'], '');
		if (!$providerBasket->num_rows) return;
		foreach($providerBasket as $pb){
			$items[$pb['typeOrganization']][] = $pb;
		}
		
		$resultEntity = self::executeSendOrder($items['entity'], 'entity');
		if ($resultEntity) {
            $ordered += self::parseOrderResponse($resultEntity);
        }

        $resultPrivate = self::executeSendOrder($items['private'], 'private');
        if ($resultPrivate) {
            $ordered += self::parseOrderResponse($resultPrivate);
        }
        return $ordered;
	}
	public static function isInBasket($ov){
		return parent::getInstanceDataBase()->getCount('provider_basket', parent::getWhere($ov));
	}
	public static function removeFromBasket($ov){
		return parent::getInstanceDataBase()->delete('provider_basket', parent::getWhere($ov));
	}
}
