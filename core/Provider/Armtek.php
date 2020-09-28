<?php
namespace core\Provider;
use core\Provider;
use core\OrderValue;
use core\Log;
use core\Item;
use ArmtekRestClient\Http\Exception\ArmtekException as ArmtekException; 
use ArmtekRestClient\Http\Config\Config as ArmtekRestClientConfig;
use ArmtekRestClient\Http\ArmtekRestClient as ArmtekRestClient;

//добавлен в связи с тем, что не работало в тестах для Росско
if ($_SERVER['DOCUMENT_ROOT']) $path = $_SERVER['DOCUMENT_ROOT'].'/';
else $path = '';

require_once $path.'vendor/Armtek/autoloader.php';
require_once $path.'vendor/autoload.php';


class Armtek extends Provider{
	public static $provider_id = 2;
	private static $config = [
		'user_login' => 'price@tahos.ru',
		'user_password' => 'tahos10317'
	];
	public static $keyzak = array();
	private $mainItemId;
	private static $params = [
		'VKORG' => '5000',
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
	private static $KUNNR_RG = [
		'private' => '43233624',
		'entity' => '43232305'
	];
	public function __construct($db = NULL){
		if ($db){
			$this->db = $db;
			$this->armtek_client = self::getClientArmtek();
		} 
	}
	public static function getPrice(array $fields){
		$params = self::$params;
		$params['PIN'] = $fields['article'];
		$params['BRAND']	 = $fields['brend'];
		$params['QUERY_TYPE']	= 1;
		$params['KUNNR_RG'] = self::$KUNNR_RG['entity'];
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
	private static function getClientArmtek(){
		$armtek_client_config = new ArmtekRestClientConfig(self::$config);
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
		if (array_key_exists($object->KEYZAK, $this->keyzak)) return $this->keyzak[$object->KEYZAK];
		$array = $this->db->select_one('provider_stores', 'id,delivery', "`provider_id`= " . self::$provider_id . " AND `title`='{$object->KEYZAK}'");
		if (!empty($array)){
			$this->keyzak[$object->KEYZAK] = $array['id'];
			if ($array['delivery'] == 1){
				$delivery = $object->WRNTDT ? $object->WRNTDT : $object->DLVDT;
				$days = self::getDaysDelivery($delivery);
				//добавлено условие из-за того, что для ARMC количество дней доставки было равно нулю
				if ($days){
					$this->db->update('provider_stores', ['delivery' => $days], "`id`={$array['id']}");
				}
			} 
			return $array['id'];
		} 
		else{
			$res = $this->db->insert('provider_stores',[
				'provider_id' => self::$provider_id,
				'title' => $object->KEYZAK,
				'cipher' => strtoupper(self::getRandomString(4)),
				'percent' => 11,
				'currency_id' => 1,
				'delivery' => 1,
				'delivery_max' => 2,
				'under_order' => 2,
				'noReturn' => 0,
			], 
			['print_query' => false]);
			if ($res === true){
				$this->keyzak[$object->KEYZAK] = $this->db->last_id();
				return $this->db->last_id();
			} 
			else{
				Log::insert([
					'text' => "Ошибка Армтек: $res",
					'query' => $this->db->last_query
				]);
				return false;
			}
		}
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
		$item = $this->db->select('items', 'id', "`article`='{$article}' AND `brend_id`= $brend_id");
		if (empty($item)){
			$res = $this->db->insert('items', [
				'brend_id' => $brend_id,
				'article' => $article,
				'article_cat' => $object->PIN,
				'title' => $object->NAME,
				'title_full' => $object->NAME,
				'source' => 'Армтек'
			], ['print_query' => false]);
			if ($res === true){
				$item_id = $this->db->last_id();
				$this->db->insert('articles', ['item_id' => $item_id, 'item_diff' => $item_id]);
				if ($from == 'mikado') $this->db->insert('mikado_zakazcode', ['item_id' => $item_id, 'ZakazCode' => $object->ZakazCode]);
				return $item_id;
			} 
			else return false;
		}
		else{
			if ($from == 'mikado') $this->db->insert('mikado_zakazcode', ['item_id' => $item[0]['id'], 'ZakazCode' => $object->ZakazCode]);
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
				$this->db->insert('analogies', ['item_id' => $this->mainItemId, 'item_diff' => $item_id]);
				$this->db->insert('analogies', ['item_id' => $item_id, 'item_diff' => $this->mainItemId]);
			}
			$this->db->insert('store_items', [
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
		$params = self::$params;
		$params['PIN'] = $article;
		$params['BRAND']	 = $brand;
		$params['QUERY_TYPE']	= 1;
		$params['KUNNR_RG'] = self::$KUNNR_RG['entity'];
		$request_params = [
			'url' => 'search/search',
			'params' => $params
		];
		$response = $this->armtek_client->post($request_params);
		$data = $response->json();
		$this->render($data->RESP);
	}
	private function getKUNNR_RG(int $order_id = NULL): string
	{
		if (!$order_id) return self::$KUNNR_RG['entity'];
		$user_type = parent::getUserTypeByOrderID($order_id);
		return self::$KUNNR_RG[$user_type];
	}
	public function getSearch($search){
		if (!parent::getIsEnabledApiSearch(self::$provider_id)) return false;
		$params = self::$params;
		$params['PIN'] = $search;
		$params['KUNNR_RG'] = self::$KUNNR_RG['entity'];
		$request_params = [
			'url' => 'search/search',
			'params' => $params
		];
		$response = $this->armtek_client->post($request_params);
		$data = $response->json();
		if ($data->RESP->ERROR || $data->RESP->MSG){
			$text = $data->RESP->ERROR ? $data->RESP->ERROR : $data->RESP->MSG;
			$errorMessage .= "Артикул: $search";
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
		// $this->render($data->RESP); return false;
		// $this->renderRESP($data->RESP);
	}
	private function renderRESP($RESP){
		foreach($RESP as $key => $value){
			if (!$this->isKeyzakByTitle($value->KEYZAK)) continue;
			//артикул
			$keyzak[$value->BRAND]['PIN'] = $value->PIN;
			$keyzak[$value->BRAND]['NAME'] = $value->NAME;
			//минимальное количество
			$keyzak[$value->BRAND]['MINBM'] = $value->MINBM;
			//кратность
			$keyzak[$value->BRAND]['RDPRF'] = $value->RDPRF;
			$keyzak[$value->BRAND]['ANALOG'] = $value->ANALOG;
			$keyzak[$value->BRAND]['KEYZAK'][$value->KEYZAK] = [
				//остатки
				'RVALUE' => preg_replace('/[^\d]+/', '', $value->RVALUE),
				'PRICE' => $value->PRICE
			];
		}
		if (empty($keyzak)) return false;
		foreach($keyzak as $key => $value){
			if (empty($value['KEYZAK'])) continue;
			$res_brend_insert = $this->db->insert(
				'brends', 
				[
					'title' => $key,
					'href' => translite($key)
				], 
				['print_query' => false]
			);
			if ($res_brend_insert === true) $brend_id = $this->db->last_id();
			else{
				$array = $this->db->select_one('brends', 'id,parent_id', "`title`='$key'");
				$brend_id = $array['parent_id'] ? $array['parent_id'] : $array['id'];
			} 
			$res_items_insert = $this->db->insert(
				'items',
				[
					'title_full' => $value['NAME'],
					'brend_id' => $brend_id,
					'article' => Item::articleClear($value['PIN']),
					'article_cat' => $value['PIN'],
					'amount_package' => $value['RDPRF']
				],
				['print_query' => false]
			);
			if ($res_items_insert === true){
				$item_id = $this->db->last_id();
				$this->db->insert('articles', ['item_id' => $item_id, 'item_diff' => $item_id]);
			} 
			else {
				$array = $this->db->select_one('items', 'id', "`brend_id`=$brend_id AND `article`='".Item::articleClear($value['PIN'])."'");
				$item_id = $array['id'];
			}
			if ($value['ANALOG']){
				$this->db->insert('analogies', ['item_id' => $_GET['item_id'], 'item_diff' => $item_id]);
				$this->db->insert('analogies', ['item_diff' => $_GET['item_id'], 'item_id' => $item_id]);
			}
			foreach($value['KEYZAK'] as $k => $v){
				$res_store_items_insert = $this->db->insert(
					'store_items',
					[
						'store_id' => $this->getStoreIdByKeyzak($k),
						'item_id' => $item_id,
						'price' => $v['PRICE'],
						'in_stock' => $v['RVALUE'],
						'packaging' => $value['RDPRF']
					]
					// ,['print_query' => true]
				);
				if ($res_store_items_insert !== true) $this->db->update(
					'store_items',
					[
						'price' => $v['PRICE'],
						'in_stock' => $v['RVALUE']
					],
					"`store_id`={$arr_keyzak[$k]['store_id']} AND `item_id`=$item_id"
				);
			}
		}
	}
	private function getStoreIdByKeyzak($keyzak){
		if (array_key_exists($keyzak, self::$keyzak)) return self::$keyzak[$keyzak];
		$array = $this->db->select_one('provider_stores', 'id', "`title`='$keyzak` AND `provider_id`= " . self::$provider_id);
		if (empty($array)) return false;
		self::$keyzak[$keyzak] = $array['id'];
		return $array['id'];
	}
	public function isKeyzak($store_id){
		if ($temp = array_search($store_id, $this->keyzak)) return $temp;
		$array = $this->db->select_one('provider_stores', 'id,title,provider_id', "`id`=$store_id");
		if (empty($array)) return false;
		if ($array['provider_id'] == self::$provider_id){
			$this->keyzak[$array['title']] = $array['id'];
			return true;
		} 
		return false;
	}
	public function toOrder($value, $type = 'armtek'){
		$this->db->insert(
			'other_orders',
			[
				'order_id' => $value['order_id'],
				'store_id' => $value['store_id'],
				'item_id' => $value['item_id'],
				'type' => $type
			],
			['print_query' => false]
		);
		$orderValue = new OrderValue();
		$orderValue->changeStatus(7, $value);
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
		$ov = $this->db->select_one('other_orders', '*', $where);
		if (!empty($ov)) return $ov;
		else return false;
	}
	public function deleteFromOrder($value, $type = 'armtek'){
		$where = self::getWhere($value);
		$this->db->delete('other_orders', "$where AND `type`='$type'");
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
	public static function executeSendOrder($items, $user_type){
		$params = self::$params;
		$params['VKORG'] = self::$params['VKORG'];
		$params['KUNRG'] = self::$KUNNR_RG[$user_type];
		if (empty($items)){
			Log::insert([
				'url' => $_SERVER['REQUEST_URI'],
				'text' => "Армтек: не найдено товаров для отправки для $user_type"
			]);
			return 0;
		} 
		$itemsForSending = array();
		foreach($items as $i){
			$itemsForSending[strtoupper($i['brend']) . ":" . strtoupper($i['article']) . ":" .strtoupper($i['store'])] = [
				'order_id' => $i['order_id'],
				'item_id' => $i['item_id'],
				'store_id' => $i['store_id'],
				'user_id' => $i['user_id'],
				'price' => $i['price'],
				'PIN' => $i['article'],
				'BRAND' => $i['brend'],
				'KWMENG' => $i['quan'],
				'KEYZAK' => $i['store']
			];
		}
		$params['ITEMS'] = $itemsForSending;
		$params['format'] = 'json';
		$request_params = [
			'url' => 'order/createOrder',
			'params' => $params
		];
		$response = self::getClientArmtek()->post($request_params);
		$json_responce_data = $response->json();
		return [
			'itemsForSending' => $itemsForSending,
			'responseData' => $json_responce_data
		];
	}
	private static function parseOrderResponse($input){
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
				if ($value->RESULT[0]->ERROR){
					Log::insert([
						'text' => $value->RESULT[0]->ERROR,
						'additional' => "osi: {$items[$itemKey['order_id']]}-{$items[$itemKey['store_id']]}-{$items[$itemKey['item_id']]}"
					]);
					continue;
				}
				if ($value->RESULT->REMAIN) Log::insert([
					'text' => 'Армтек: нехватка остатка для заказа',
					'additional' => "osi: {$items[$itemKey]['order_id']}-{$items[$itemKey]['store_id']}-{$items[$itemKey]['item_id']}"
				]);
				OrderValue::changeStatus(11, [
					'order_id' => $items[$itemKey]['order_id'],
					'store_id' => $items[$itemKey]['store_id'],
					'item_id' => $items[$itemKey]['item_id'],
					'price' => $items[$itemKey]['price'],
					'quan' => $value->RESULT[0]->KWMENG,
					'user_id' => $items[$itemKey]['user_id']
				]);
				parent::updateProviderBasket(
					[
						'order_id' => $items[$itemKey]['order_id'],
						'store_id' => $items[$itemKey]['store_id'],
						'item_id' => $items[$itemKey]['item_id'],
					],
					[
						'response' => 'OK',
						'successful' => 1
					]
				);
			}
		}
	}
	/**
	 * prepares data for sending and sends them to order
	 * @return void
	 */
	public static function sendOrder(){
		$private = [];
		$entity = [];
		$output = [];
		$providerBasket = parent::getProviderBasket(self::$provider_id);
		if (!$providerBasket->num_rows) return false;
		foreach($providerBasket as $pb){
			switch($pb['user_type']){
				case 'private': $private[] = $pb; break;
				case 'entity': $entity[] = $pb; break;
			}
		}
		$resultPrivate = self::executeSendOrder($private, 'private');
		$resParseOrderResponsePrivate = self::parseOrderResponse($resultPrivate);

		$resultEntity = self::executeSendOrder($entity, 'entity');
		$resParseOrderResponseEntity = self::parseOrderResponse($resultEntity);
	}
	public static function isInBasket($ov){
		return parent::getInstanceDataBase()->getCount('provider_basket', parent::getWhere($ov));
	}
	public static function removeFromBasket($ov){
		return parent::getInstanceDataBase()->delete('provider_basket', parent::getWhere($ov));
	}
}
