<?php
namespace core;
class Rossko extends Provider{
	private $db, $result;
	private $connect = array(
		'wsdl' => 'http://api.rossko.ru/service/GetSearch',
		'options' => array(
			'connection_timeout' => 5,
			'trace' => true
		)
	);
	private $countDaysOfChecking = 0;
	private $param = array(
		'KEY1' => 'd3a3b2e361276178e60d8da2f9d553b4',
		'KEY2' => 'd2697480a48aee9f6238818072235929',
	);
	public $provider_id = 15;
	private $stopOnError = false;
	public $isNeedsToCheck;
	public function __construct($db, $text = NULL){
		ini_set('soap.wsdl_cache_enabled',0);
		ini_set('soap.wsdl_cache_ttl',0);
		$this->db = $db;
		if ($text){
			$this->text = $text;
			$rossko_query_query = $this->db->query("
				SELECT
					rq.query,
					rq.created,
					IF(NOW() >= DATE_ADD(rq.created, Interval {$this->countDaysOfChecking} DAY), 1, 0) AS isNeedsToCheck
				FROM
					#rossko_queries rq
				WHERE
					rq.query='$this->text'
			", '');
			if (!$rossko_query->num_rows) $this->isNeedsToCheck = true;
			else{
				$rossko_query = $rossko_query_query->fetch_assoc();
				$this->isNeedsToCheck = (bool) $rossko_query['isNeedsToCheck'];
			}
		}
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
		if ($this->stopOnError && !$brend_id) die("Ошибка получение brend_id $item->brand");
		if (!$brend_id) return false;
		$article = article_clear($item->partnumber);
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
				'provider_id' => $this->provider_id,
				'cipher' => strtoupper(Abcp::getRandomString(4)),
				'currency_id' => 1,
				'delivery' => $stock->delivery,
				'percent' => 10
			], 
			[
				'print_query' => false, 
				'deincrement_duplicate' => true,
			]
		);
		if (Abcp::isDuplicate($res)){
			$where = "`title`='{$stock->id}' AND `provider_id`={$this->provider_id}";
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
	private function getResult($text = NULL){
		if ($text) $this->param['TEXT'] = $text;
		else $this->param['TEXT'] = $this->text;
		$query  = new \SoapClient($this->connect['wsdl'], $this->connect['options']);
		$result = $query->GetSearch($this->param);
		return $result;
	}
	public function getCheckoutDetails(){
		$this->connect['wsdl'] = 'http://api.rossko.ru/service/GetCheckoutDetails';
		$query  = new \SoapClient($this->connect['wsdl'], $this->connect['options']);
		return $query->GetCheckoutDetails($this->param);
	}
	private function getParts($store_id = NULL){
		$armtek = new Armtek($this->db);
		$res_items = Amtek::getItems('rossko');
		if (!$res_items->num_rows) return false;
		// $query  = new \SoapClient('http://api.rossko.ru/service/GetSearch', $this->connect['options']);
		$items = array();
		while ($item = $res_items->fetch_assoc()){
			if ($store_id && $item['store_id'] != $store_id) continue;
			$items[] = [
				// 'partnumber' => is_array($part) ? $part[0]->partnumber : $part->partnumber,
				// 'brand' => is_array($part) ? $part[0]->brand : $part->brand,
				'partnumber' => $item['article'],
				'brand' => $item['brend'],
				'stock' => $item['store'],
				'count' => $item['count'],
				'osi' => "{$item['order_id']}-{$item['store_id']}-{$item['item_id']}",
				'price' => $item['price'],
				'user_id' => $item['user_id']
			];
		}
		return $items;
	}
	public function sendOrder($store_id = NULL){
		$checkoutDetails = $this->getCheckoutDetails();
		$parts = $this->getParts($store_id);
		if (!$parts){
			echo "<br>Нет товаров для отправки.";
			return false;
		}
		$this->connect['wsdl'] = 'http://api.rossko.ru/service/GetCheckout';
		$param = array(
			'KEY1' => $this->param['KEY1'],
			'KEY2' => $this->param['KEY2'],
			'delivery' => array(
				'delivery_id' => '000000001'
			),
			'payment' => array(
				'payment_id' => '1',
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
		echo json_encode($param);
		debug($param, 'param');

		$query  = new \SoapClient($this->connect['wsdl'], $this->connect['options']);
		$result = $query->GetCheckout($param);

		echo json_encode($result);
		debug($result, 'result');

		$this->armtek = new Armtek($this->db);
		if (isset($result->CheckoutResult->ItemsList)){
			if (is_array($result->CheckoutResult->ItemsList->Item)){
				foreach($result->CheckoutResult->ItemsList->Item as $value) $this->parseItemList($value, $param['PARTS']);
			}
			else $this->parseItemList($result->CheckoutResult->ItemsList->Item, $param['PARTS']);
		}
		if (isset($result->CheckoutResult->ItemsErrorList)){
			if (is_array($result->CheckoutResult->ItemsErrorList->ItemError)){
				foreach($result->CheckoutResult->ItemsErrorList->ItemError as $value) $this->parseItemErrorList($value, $param['PARTS']);
			}
			else $this->parseItemErrorList($result->CheckoutResult->ItemsErrorList->ItemError, $param['PARTS']);
		}
	}
	private function parseItemList($itemResponse, $itemsParts){
		// debug($itemResponse, 'itemResponse');
		// debug($itemsParts, 'itemsParts');
		foreach($itemsParts as $value){
			if (
				Armtek::getComparableString($itemResponse->partnumber) == Armtek::getComparableString($value['partnumber']) &&
				Armtek::getComparableString($itemResponse->brand) == Armtek::getComparableString($value['brand'])
			){
				$array = explode('-', $value['osi']);
				$orderValue = new OrderValue();
				$orderValue->changeStatus(11, [
					'order_id' => $array[0],
					'store_id' => $array[1],
					'item_id' => $array[2],
					'price' => $value['price'],
					'quan' => $value['count'],
					'user_id' => $value['user_id']
				]);
				$this->db->update(
					'other_orders',
					['response' => 'OK'],
					$this->armtek->getWhere([
						'order_id' => $array[0],
						'store_id' => $array[1],
						'item_id' => $array[2]
					])
				);
			}
		}
	}
	private function parseItemErrorList($itemResponse, $itemsParts){
		foreach($itemsParts as $value){
			if (
				Armtek::getComparableString($itemResponse->partnumber) == Armtek::getComparableString($value['partnumber']) &&
				Armtek::getComparableString($itemResponse->brand) == Armtek::getComparableString($itemResponse->brand == $value['brand'])
			){
				$array = explode('-', $value['osi']);
				$this->db->update(
					'other_orders',
					['response' => $itemResponse->message],
					$this->armtek->getWhere([
						'order_id' => $array[0],
						'store_id' => $array[1],
						'item_id' => $array[2]
					])
				);
			}
		}
	}
	public function getSearch($search){
		if (!parent::getIsEnabledApiSearch($this->provider_id)) return false;
		$result = $this->getResult($search);
		// debug($result); exit();
		if (!$result) return false;
		if (!$result->SearchResult->success) return false;
		$coincidences = array();
		$Part = & $result->SearchResult->PartsList->Part;
		if (is_array($Part)){
			foreach($Part as $value) {
				if (!Armtek::getComparableString($value->name)) continue;
				$coincidences[$value->brand] = $value->name;
			}
		}
		else{
			if (Armtek::getComparableString($Part->name)) $coincidences[$Part->brand] = $Part->name;
		} 
		return $coincidences;
	}
	public function execute(){
		if (!parent::getIsEnabledApiOrder($this->provider_id)) return false;
		if (!$this->text) return false;
		$result = $this->getResult();
		if (!$result) return false;
		// debug($result); exit();
		if (!$result->SearchResult->success) return false;
		if (is_array($result->SearchResult->PartsList->Part)){
			foreach($result->SearchResult->PartsList->Part as $value) $this->renderPart($value);
		}
		else $this->renderPart($result->SearchResult->PartsList->Part);
	}
}
