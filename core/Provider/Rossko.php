<?php
namespace core\Provider;
use core\Provider;
use core\OrderValue;
use core\Log;
use core\Item;
class Rossko extends Provider{
	public static $fieldsForSettings = [
		'KEY1',
		'KEY2',
		'provider_id'
	];
	private $db, $result;
	private static $delivery_id = '000000001';
	private static $connect = array(
		'wsdl' => 'http://api.rossko.ru/service/v2.1',
		'options' => array(
			'connection_timeout' => 1,
			'trace' => true
		)
	);
	public static function getParams($typeOrganization = 'entity'){
		return Provider::getApiParams([
			'api_title' => 'Rossko',
			'typeOrganization' => $typeOrganization
		]);
	}

	/**
	 * wrote for admin/scripts/parse_images.php
	 * @param  [type] $brend   [description]
	 * @param  [type] $article [description]
	 * @return [type]          [description]
	 */
	public function getPartTitleByBrendAndArticle($brend, $article){
		return false;
		$result = $this->getResult("$article $brend");
		if (!isset($result->SearchResult->PartsList)) return false;
		return $result->SearchResult->PartsList->Part->name;
	}

	/**
	 * [getItemsToOrder description]
	 * @param  int $provider_id provider_id
	 * @return array user_id, order_id, store_id, store, item_id, price, article, brend, provider_id, provider, count
	 */
	public static function getItemsToOrder(int $provider_id){
		return Abcp::getItemsToOrder($provider_id);
	}
	public function __construct($db = NULL){
		ini_set('soap.wsdl_cache_enabled',0);
		ini_set('soap.wsdl_cache_ttl',0);
		if ($db) $this->db = $db;
	}
	public function isRossko($store_id){
		$array = $this->db->select_one('provider_stores', 'id,provider_id', "`id`=$store_id");
		if ($array['provider_id'] == $this->provider_id) return true;
		else return false;
	}
	public function getBrandId($brand){
		$brend = $this->db->select_one('brends', 'id,parent_id', "`title`='$brand'");
		if (empty($brend)) {
			$this->db->insert(
				'log_diff',
				[
					'type' => 'brends',
					'from' => 'rossko',
					'text' => "Бренд $brand отсутствует в базе",
					'param1' => $brand,
					'param2' => $brand
				]
			);
			return false;
		}
		if ($brend['parent_id']) return $brend['parent_id'];
		else return $brend['id'];
	}
	private function addItem($item, $printQuery = false){
		$brend_id = $this->getBrandId($item->brand);
		if (!$brend_id) return false;
		$article = Item::articleClear($item->partnumber);
		$name = $item->name ? $item->name : 'Деталь';
		$res = $this->db->insert('items', [
			'brend_id' => $brend_id,
			'article_cat' => $item->partnumber,
			'article' => $article,
			'title' => $name,
			'title_full' => $name,
			'source' => 'Росско'
		], ['print_query' => $printQuery, 'deincrement_duplicate' => true]);
		if ($res === true){
			$item_id = $this->db->last_id();
			$this->db->insert('articles', ['item_id' => $item_id, 'item_diff' => $item_id], ['print_query' => false]);
			return $item_id;
		} 
		else{
			$array = $this->db->select_one('items', 'id', "`article`='$article' AND `brend_id`=$brend_id");
			return $array['id'];
		}
	}
	private function addProviderStore($stock){
		$res = $this->db->insert(
			'provider_stores', 
			[
				'title' => $stock->id,
				'provider_id' => self::getParams()->provider_id,
				'cipher' => strtoupper(parent::getRandomString(4)),
				'currency_id' => 1,
				'delivery' => $stock->delivery,
				'percent' => 10
			], 
			[
				'print_query' => false, 
				'deincrement_duplicate' => true,
			]
		);
		if (parent::isDuplicate($res)){
			$where = "`title`='{$stock->id}' AND `provider_id` = " . self::getParams()->provider_id;
			$this->db->update('provider_stores', ['delivery' => $stock->delivery], $where);
			$array = $this->db->select_one('provider_stores', 'id', $where);
			return $array['id'];
		}
		return $this->db->last_id();
	}
	private function addStoreItem($store_id, $item_id, $stock){
		$res = $this->db->insert(
			'store_items',
			[
				'store_id' => $store_id,
				'item_id' => $item_id,
				'price' => $stock->price,
				'in_stock' => $stock->count,
				'packaging' => $stock->multiplicity
			],
			[
				'duplicate' => [
					'price' => $stock->price,
					'in_stock' => $stock->count
				], 
				'print_query' => false
			]
		);
	}
	private function addAnalogy($item_id, $item_diff){
		$res = $this->db->insert('analogies', ['item_id' => $item_id, 'item_diff' => $item_diff], ['print_query' => false]);
		$res = $this->db->insert('analogies', ['item_id' => $item_diff, 'item_diff' => $item_id], ['print_query' => false]);
	}
	private function renderStock($item_id, $stock){
		$store_id = $this->addProviderStore($stock);
		if (!$store_id) return false;
		$this->addStoreItem($store_id, $item_id, $stock);
	}
	private function renderCrossPart($item_id, $part){
		$item_diff = $this->addItem($part, false);
		if (!$item_diff) return false;
		$this->addAnalogy($item_id, $item_diff);
		if (isset($part->stocks)){
			$this->db->query(Abcp::getQueryDeleteByProviderId($item_diff, $this->provider_id), ''); 
			if (is_array($part->stocks->stock)){
				foreach($part->stocks->stock as $stock) $this->renderStock($item_diff, $stock);
			}
			else $this->renderStock($item_diff, $part->stocks->stock);
		}
	}
	private function renderPart($value){
		$item_id = $this->addItem($value);
		if (isset($value->crosses)){
			$this->db->query(Abcp::getQueryDeleteByProviderId($item_id, $this->provider_id), ''); 
			if (is_array($value->crosses->Part)){
				foreach($value->crosses->Part as $v){
					$this->renderCrossPart($item_id, $v);
				} 
			}
			else $this->renderCrossPart($item_id, $value->crosses->Part);
		}
		if (isset($value->stocks)){
			if (is_array($value->stocks->stock)){
				foreach($value->stocks->stock as $v){
					$this->renderStock($item_id, $v);
				} 
			}
			else $this->renderStock($item_id, $value->stocks->stock);
		}
	}
	private static function getSoap($method){
		try{
			$soap = new \SoapClient(self::$connect['wsdl']."/$method", self::$connect['options']);
			if (!$soap) throw new \Exception("Не удается подключиться к Росско.");
		}catch(\Exception $e){
			return false;
		}
		return $soap;
	}
	private function getDeliveryID(){
		$query = self::getSoap('GetCheckoutDetails');
		$result = $query->GetCheckoutDetails([
			'KEY1' => self::getParams()->KEY1,
			'KEY2' => self::getParams()->KEY2,
		]);
		debug($result);
	}
	public function getResult($search){
		$query = self::getSoap('GetSearch');
		if (!$query) return false;
		$param = [
			'KEY1' => self::getParams()->KEY1,
			'KEY2' => self::getParams()->KEY2,
		];
		$param['text'] = $search;
		$param['delivery_id'] = self::$delivery_id;
		$result = $query->GetSearch($param);
		return $result;
	}
	public static function getCheckoutDetails($typeOrganization = 'entity'){
		$soap  = self::getSoap('GetCheckoutDetails');
		if (!$soap) return false;
		try{
			$query = $soap->GetCheckoutDetails([
				'KEY1' => self::getParams($typeOrganization)->KEY1,
				'KEY2' => self::getParams($typeOrganization)->KEY2,
			]);
		}catch(\SoapFault $e){
			Log::insertThroughException($e);
			return false;
		}
		return $query;
	}
	private static function getPartsForSending(){
		$providerBasket = parent::getProviderBasket(self::getParams()->provider_id, '');
		if (!$providerBasket->num_rows) return false;
		$items = array();
		while ($item = $providerBasket->fetch_assoc()){
			if ($store_id && $item['store_id'] != $store_id) continue;
			$items[] = [
				'partnumber' => $item['article'],
				'brand' => $item['brend'],
				'stock' => $item['store'],
				'count' => $item['quan'],
				'comment' => "{$item['order_id']}-{$item['store_id']}-{$item['item_id']}",
				'price' => $item['price'],
				'user_id' => $item['user_id'],
				'user_type' => $item['typeOrganization']
			];
		}
		return $items;
	}
	public static function sendOrder($store_id = NULL){
		if ($store_id) $stock = parent::getInstanceDataBase()->getFieldOnID('provider_stores', $store_id, 'title');
		$partsList = self::getPartsForSending();
		$items = [];
		if (!$partsList){
			if (!empty($ov)) $additional = "osi: {$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}";
			else $additional = NULL;
			Log::insert([
				'text' => 'Росско: нет товаров для отправки',
				'additional' => $additional
			]);
			return false;
		}
		$privateParts = [];
		$entityParts = [];
		foreach($partsList as $part){
			if (isset($stock) && $part['stock'] != $stock) continue;

		static $checkoutDetails;
		if (empty($parts)) return false;
		if (!$checkoutDetails){
			$checkoutDetails = self::getCheckoutDetails('private');
			if (!$checkoutDetails) die("Ошибка получения checkoutDetails. Подробности в логе.");
		} 
		$payment_id = $typeOrganization == 'private' ? 2 : 1;
		
		$soap  = self::getSoap('GetCheckout');
		if (!$soap){
			foreach($parts as $part) Log::insert([
				'text' => 'Ошибка подключения к Росско',
				'additional' => "osi: {$part['comment']}"
			]);
			return false;
		}

		$param = array(
			'KEY1' => self::getParams('private')->KEY1,
			'KEY2' => self::getParams('private')->KEY2,
			'delivery' => array(
				'delivery_id' => '000000001',
				'city' => $checkoutDetails->CheckoutDetailsResult->DeliveryAddress->address->city,
				'street' => $checkoutDetails->CheckoutDetailsResult->DeliveryAddress->address->street,
				'house' => $checkoutDetails->CheckoutDetailsResult->DeliveryAddress->address->house,
				'office' => $checkoutDetails->CheckoutDetailsResult->DeliveryAddress->address->office
			),
			'payment' => array(
				'payment_id' => $payment_id,
				'company_name' => $checkoutDetails->CheckoutDetailsResult->CompanyList->company->name,
				'company_requisite' => $checkoutDetails->CheckoutDetailsResult->CompanyList->company->requisite
			),
			'contact' => array(
				'name' => 'ИП Баранов Валерий Геннадьевич',
				'phone' => '+7(951)737-33-66',
			),
			'delivery_parts' => true,
			'PARTS' => $parts
		);
		try{
			$result = $soap->GetCheckout($param);
			debug($result);
		} catch(\SoapFault $e){
			return $e;
		}
		return $result;
	}
	private static function parseSendOrderResponse($result, array $parts){
		if (empty($parts)) return false;
		if ($result->CheckoutResult->message && !$result->CheckoutResult->success){
			foreach($parts as $part) Log::insert([
				'text' => $result->CheckoutResult->message,
				'additional' => "osi: {$part['comment']}"
			]);
			return false;
		}
		if (isset($result->CheckoutResult->ItemsList)){
			$itemsList = & $result->CheckoutResult->ItemsList->Item;
			if (is_array($itemsList)){
				foreach($itemsList as $value) self::parseItemList($value, $parts);
			}
			else self::parseItemList($itemsList, $parts);
		}
		if (isset($result->CheckoutResult->ItemsErrorList)){
			$errorList = & $result->CheckoutResult->ItemsErrorList->ItemError;
			if (is_array($errorList)){
				foreach($errorList as $value) self::parseItemErrorList($value, $parts);
			}
			else self::parseItemErrorList($errorList, $parts);
		}
	}
	private static function parseItemList($Item, $parts){
		foreach($parts as $value){
			if (
				parent::getComparableString($Item->partnumber) == parent::getComparableString($value['partnumber']) &&
				parent::getComparableString($Item->brand) == parent::getComparableString($value['brand'])
			){
				$osi = explode('-', $value['comment']);
				OrderValue::changeStatus(11, [
					'order_id' => $osi[0],
					'store_id' => $osi[1],
					'item_id' => $osi[2],
					'price' => $value['price'],
					'quan' => $value['count'],
					'user_id' => $value['user_id']
				]);
				parent::updateProviderBasket(
					[
						'order_id' => $osi[0],
						'store_id' => $osi[1],
						'item_id' => $osi[2]
					],
					['response' => 'OK']
				);
			}
		}
	}
	private static function parseItemErrorList($Item, $parts){
		foreach($parts as $value){
			if (
				parent::getComparableString($Item->partnumber) == parent::getComparableString($value['partnumber']) &&
				parent::getComparableString($Item->brand) == parent::getComparableString($value['brand'])
			){
				$osi = explode('-', $value['comment']);
				Log::insert([
					'text' => $Item->message,
					'additional' => "osi: {$value['comment']}"
				]);
			}
		}
	}
	public function getSearch($search){
		if (!parent::getIsEnabledApiSearch(self::getParams()->provider_id)) return false;
		if (!parent::isActive(self::getParams()->provider_id)) return false;
		$result = $this->getResult($search);
		if (!$result) return false;
		if (!$result->SearchResult->success) return false;
		$coincidences = array();
		$Part = & $result->SearchResult->PartsList->Part;
		if (is_array($Part)){
			foreach($Part as $value) {
				if (!self::getComparableString($value->name)) continue;
				$coincidences[$value->brand] = $value->name;
			}
		}
		else{
			if (self::getComparableString($Part->name)) $coincidences[$Part->brand] = $Part->name;
		} 
		return $coincidences;
	}
	public static function getPrice(array $params){
		$query = self::getSoap('GetSearch');
		if (!$query) return false;
		$storeInfo = parent::getStoreInfo($params['store_id']);
		
		$param = [
			'KEY1' => self::getParams()->KEY1,
			'KEY2' => self::getParams()->KEY2,
		];
		$param['text'] = "{$params['brend']} {$params['article']}";
		$param['delivery_id'] = self::$delivery_id;

		$result = $query->GetSearch($param);
		// debug($storeInfo);
		if (!$result) return false;
		if (!$result->SearchResult->success) return false;
		$Part = $result->SearchResult->PartsList->Part;
		if (is_array($Part)){
			foreach($Part as $value){
				if (!isset($value->stocks)) continue;
				if (is_array($value->stocks->stock)){
					foreach($value->stocks->stock as $v){
						if ($v->id == $storeInfo['title']) return [
							'price' => $v->price,
							'available' => $v->count
						];
					} 
				}
				else{
					if ($value->stocks->stock->id == $storeInfo['title']) return [
						'price' => $value->stocks->stock->price,
						'available' => $value->stocks->stock->count
					];
				} 
			}
		}
		else{
			if (!isset($Part->stocks)) return false;
			if (is_array($Part->stocks->stock)){
				foreach($Part->stocks->stock as $v){
					if ($v->id == $storeInfo['title']) return [
						'price' => $v->price,
						'available' => $v->count
					];
				} 
			}
			else{
				if ($Part->stocks->stock->id == $storeInfo['title']) return [
					'price' => $Part->stocks->stock->price,
					'available' => $Part->stocks->stock->count
				];
			} 
		}
		return false;
	}
	public function execute($search){
		if (!parent::getIsEnabledApiSearch(self::getParams()->provider_id)) return false;
		if (!parent::isActive(self::getParams()->provider_id)) return false;
		$result = $this->getResult($search);
		if (!$result) return false;
		if (!$result->SearchResult->success) return false;
		if (is_array($result->SearchResult->PartsList->Part)){
			foreach($result->SearchResult->PartsList->Part as $value) $this->renderPart($value);
		}
		else $this->renderPart($result->SearchResult->PartsList->Part);
	}
	public static function isInBasket($ov){
		return Armtek::isInBasket($ov);
	}
	static public function removeFromBasket($ov){
		return Armtek::removeFromBasket($ov);
	}
}
