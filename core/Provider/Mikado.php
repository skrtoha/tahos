<?php
namespace core\Provider;

use core\Provider;
use core\OrderValue;
use core\Log;
use Exception;

class Mikado extends Provider{
	private $db;
	private $armtek;
	private $brends;
	public static $fieldsForSettings = [
		"isActive",	// is required	
		"ClientID",
		"Password",
		"provider_id",
		"MIPI",
		"MIPE",
		"MIVO"
	];
	public static function getParams($typeOrganization = 'entity'){
		return Provider::getApiParams('Mikado', $typeOrganization);
	}
	public static function getPrice(array $params){
		$clientData = self::getClientData();
		$xml = self::getUrlData(
			'http://www.mikado-parts.ru/ws1/service.asmx/Code_Search',
			[
				'Search_Code' => $params['article'],
				'ClientID' => $clientData['ClientID'],
				'Password' => $clientData['Password'],
				'FromStockOnly' => 'FromStockOnly'  			
			]
		);
		if (!$xml) return false;
		$result = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
		$result = json_decode(json_encode($result));
		if (empty($result->List)) return false;

		$ZakazCode = parent::getInstanceDataBase()->getField('mikado_zakazcode', 'ZakazCode', 'item_id', $params['item_id']);
		$StokID = self::getStockId($params['store_id']);

		$r = & $result->List->Code_List_Row;
		if (is_array($r)){
			foreach($r as $row){
				if ($row->ZakazCode != $ZakazCode) continue;
				if (empty($row->OnStocks)) return [
					'price' => $row->PriceRUR,
					'available' => 0
				];
				if (is_array($row->OnStocks->StockLine)){
					foreach($row->OnStocks->StockLine as $stock){
						if ($stock->StokID == $StokID) return [
							'price' => $row->PriceRUR,
							'available' => $stock->StockQTY
						];
					}
				}
				else{
					if ($row->OnStocks->StockLine->StokID == $StokID) return [
						'price' => $row->PriceRUR,
						'available' => $row->OnStocks->StockLine->StockQTY
					];
				}
			} 
		}
		else{
			if ($r->ZakazCode != $ZakazCode) return false;
			if (empty($r->OnStocks)) return [
				'price' => $r->PriceRUR,
				'available' => 0
			];
			if (is_array($r->OnStocks->StockLine)){
				foreach($r->OnStocks->StockLine as $stock){
					if ($stock->StokID == $StokID) return [
						'price' => $r->PriceRUR,
						'available' => $stock->StockQTY
					];
				}
			}
			else{
				if ($r->OnStocks->StockLine->StokID == $StokID) return [
					'price' => $r->PriceRUR,
					'available' => $r->OnStocks->StockLine->StockQTY
				];
			}
		} 
	}
	public static function getItemsToOrder(int $provider_id = null, $order_id = null, $fullList = false){
		$clientData = self::getClientData('private');

		$output = [];
		$xml = self::getUrlData(
			'http://www.mikado-parts.ru/ws1/basket.asmx/Basket_List',
			$clientData
		);
		if (!$xml) return;
		$result = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
		$result = json_decode(json_encode($result));
		$basketItem = & $result->List->BasketItem;
		if (is_object($basketItem)){
			$output[] = self::parseBasketItem($basketItem);
			return;
		} 
		foreach($basketItem as $value){
			$output[] = self::parseBasketItem($value);
		}
		return $output;
	}
	/**
	 * parses BasketItem
	 * @param  object $BasketItem 
	 * @return array 
	 */
	private static function parseBasketItem($basketItem){
		$res = Provider::getInstanceDataBase()->query("
			SELECT
				mz.item_id,
				i.brend_id,
				b.title AS brend,
				i.article,
				i.title_full
			FROM
				#mikado_zakazcode mz
			LEFT JOIN
				#items i ON i.id = mz.item_id
			LEFT JOIN
				#brends b ON b.id = i.brend_id
			WHERE
				mz.ZakazCode = '{$basketItem->ZakazCode}'
		", '');
		$item = $res->fetch_assoc();
		$osi = explode('-', $basketItem->Notes);
		return [
			'provider' => 'Mikado',
			'provider_id' => self::getParams()->provider_id,
			'order_id' => $osi[0],
			'store_id' => $osi[1],
			'item_id' => $osi[2],
			'store' => self::getCipher($basketItem->Notes),
			'brend' => $item['brend'],
			'article' => $item['article'],
			'title_full' => $item['title_full'],
			'ZakazCode' => $basketItem->ZakazCode,
			'ID' => $basketItem->ID,
			'price' => $basketItem->Price,
			'count' => $basketItem->QTY
		];
	}
	public function getCipher($str){
		$array = explode('-', $str);
		return Provider::getInstanceDataBase()->getField('provider_stores', 'cipher', 'id', $array[1]);
	}
	public function __construct($db = NULL){
		if ($db) $this->db = $db;	
		$this->armtek = new Armtek($this->db);
	}
	public function getCoincidences($text){
		if (!parent::getIsEnabledApiSearch(self::getParams()->provider_id)) return false;
		if (!parent::isActive(self::getParams()->provider_id)) return false;
		$clientData = self::getClientData();
		$xml = self::getUrlData(
			'http://www.mikado-parts.ru/ws1/service.asmx/Code_Search',
			[
				'Search_Code' => $text,
				'ClientID' => $clientData['ClientID'],
				'Password' => $clientData['Password'],
				'FromStockOnly' => 'FromStockOnly'  			
			]
		);
		$result = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
		$result = json_decode(json_encode($result));
		if (empty($result->List)) return false;
		$brendTitle = array();
		if (is_array($result->List->Code_List_Row)){
			foreach($result->List->Code_List_Row as $value) {
				if (!$this->isRealBrend($value->Source->SourceProducer)) continue;
				$brendTitle[$value->Source->SourceProducer] = $value->Name;
			}
		}
		else{
			if ($this->isRealBrend($result->List->Code_List_Row->Source->SourceProducer)){
				$brendTitle[$result->List->Code_List_Row->Source->SourceProducer] = $result->List->Code_List_Row->Name;
			}
		} 
		// debug($brendTitle); exit();
		if (empty($brendTitle)) return false;
		else return $brendTitle;
		foreach($brendTitle as $key => $value){
			$brend_id = $this->armtek->getBrendId($key, 'mikado');
			if (!$brend_id) continue;
			$article = preg_replace('/[\W_]+/', '', $text);
			$res = $this->db->insert(
				'items',
				[
					'brend_id' => $brend_id,
					'article' => $article,
					'article_cat' => $text,
					'title_full' => $value,
					'title' => $value, 
				],
				['print_query' => true]
			);
			if ($res === true){
				$item_id = $this->db->last_id();
				$this->db->insert('articles', ['item_id' => $item_id, 'item_diff' => $item_id]);
			}
		}
	}
	private function isRealBrend($brend){
		if (preg_match('/АНАЛОГИ ПРОЧИЕ \(БРЭНД НЕИЗВЕСТЕН\)/i', $brend)) return false;
		return true;
	}
	public function setArticle($brend, $article, $getZakazCode = false){
		if (!parent::getIsEnabledApiSearch(self::getParams()->provider_id)) return false;
		if (!parent::isActive(self::getParams()->provider_id)) return false;
		$clientData = self::getClientData();
		$xml = self::getUrlData(
			'http://www.mikado-parts.ru/ws1/service.asmx/Code_Search',
			[
				'Search_Code' => $article,
				'ClientID' => $clientData['ClientID'],
				'Password' => $clientData['Password'],
				'FromStockOnly' => 'FromStockOnly'  			
			]
		);
		if (!$xml) return false;
		$result = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
		$result = json_decode(json_encode($result));
		if (empty($result->List)) return false;
		$r = & $result->List->Code_List_Row;
		if (is_array($r)){
			foreach($r as $row){
				if ($getZakazCode){
					if ($this->compareBrends($row->ProducerBrand, $brend)) return $row->ZakazCode;
				}
				if (!$this->compareBrends($row->Source->SourceProducer, $brend)) continue;
				$this->parseCodeListRow($row);
			} 
		}
		else{
			if ($getZakazCode && $this->compareBrends($r->ProducerBrand, $brend)) return $r->ZakazCode;
			if ($this->compareBrends($r->Source->SourceProducer, $brend)){
				$this->parseCodeListRow($r);
			}
		} 
	}
	private function compareBrends($b1, $b2){
		if (self::getComparableString($b1) == self::getComparableString($b2)) return true;
		$in = '';
		if (!isset($this->brends[$b1])) $in .= "'$b1',";
		if (!isset($this->brends[$b2])) $in .= "'$b2',";
		if ($in){
			$in = substr($in, 0, -1);
			$res = $this->db->query("
				SELECT
					id, title, parent_id
				FROM
					#brends
				WHERE
					`title` IN ('$b1', '$b2')
			", '');
			if (!$res->num_rows) return false;
			foreach($res as $value){
				$title = self::getComparableString($value['title']);
				$this->brends[$title]['id'] = $value['id'];
				$this->brends[$title]['parent_id'] = $value['parent_id'];
			}
		} 
		$b1 = self::getComparableString($b1);
		$b2 = self::getComparableString($b2);
		if ($this->brends[$b1]['id'] == $this->brends[$b2]['parent_id'] || $this->brends[$b2]['id'] == $this->brends[$b1]['parent_id']){
			return true;
		} 
	}
	private function parseCodeListRow($row){
		$object = new \StdClass();
		$object->BRAND = $row->ProducerBrand;
		$object->PIN = $row->ProducerCode;
		$object->NAME = $row->Name;
		$object->ZakazCode = $row->ZakazCode;
		$item_id = $this->armtek->getItemId($object, 'mikado');
		if (!$item_id) return false;
		$this->db->query(Abcp::getQueryDeleteByProviderId($item_id, 8), ''); 
		if ($row->CodeType == 'Analog' || $row->CodeType == 'AnalogOEM'){
			$this->db->insert('analogies', ['item_id' => $_GET['item_id'], 'item_diff' => $item_id], ['print_query' => false]);
			$this->db->insert('analogies', ['item_id' => $item_id, 'item_diff' => $_GET['item_id']], ['print_query' => false]);
		}
		if (!empty($row->OnStocks)){
			if (is_array($row->OnStocks->StockLine)){
				foreach($row->OnStocks->StockLine as $stock) $this->parseStockLine($stock, $item_id, $row);
			}
			else $this->parseStockLine($row->OnStocks->StockLine, $item_id, $row);
		}
	}
	private static function getStoreID($StockID){
		$stocks = self::getStocks();
		return $stocks[$StockID];
	}
	public static function getStocks(){
		static $stocks;
		if (!$stocks){
			$stocks = [
				1 => self::getParams()->MIPI, // MIPI
				10 => self::getParams()->MIPE, //MIPE
				35 => self::getParams()->MIVO //MIVO
			];
		}
		return $stocks;
	}
	private function parseStockLine($stock, $item_id, $row){
		$store_id = self::getStoreID($stock->StokID);
		// debug($stock, "store_id = $store_id"); return;
		if (!$store_id) return false;
		$this->db->insert(
			'store_items',
			[
				'store_id' => $store_id,
				'item_id' => $item_id,
				'price' => ceil($row->PriceRUR),
				'in_stock' => preg_replace('/\D+/', '', $stock->StockQTY)
			],
			[
				'duplicate' => [
					'in_stock' => preg_replace('/\D+/', '', $stock->StockQTY),
					'price' => ceil($row->PriceRUR)
				]
			]
		);
	}
	public function isStoreMikado($store_id){
		if (array_search($store_id, self::getStoreID)) return true;
		return false;
	}
	protected static function getStockId($store_id){
		foreach(self::getStocks() as $key => $value){
			if ($value == $store_id) return $key;
		}
		throw new \Exception("Не удалось получить StockID по store_id = $store_id");
		return false;
	}
	public function getDeliveryType($ZakazCode, $store_id){
		$clientData = self::getClientData('private');
		$xml = self::getUrlData(
			'http://www.mikado-parts.ru/ws1/service.asmx/Code_Info',
			[
				'ZakazCode' => $ZakazCode,
				'ClientID' => $clientData['ClientID'],
				'Password' => $clientData['Password'],
			]
		);
		$result = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
		$result = json_decode(json_encode($result));
		$StokID = $this->getStockId($store_id);
		if (is_object($result->Prices->Code_PriceInfo)){
			$deliveryType = $this->parseCodePriceInfo($result->Prices->Code_PriceInfo, $StokID);
			if ($deliveryType) return $deliveryType;
		} 
		foreach($result->Prices->Code_PriceInfo as $value){
			if (empty($value->OnStocks)) continue;
			$deliveryType = $this->parseCodePriceInfo($value, $StokID);
			if ($deliveryType) return $deliveryType;
		}
		return false;
	}
	private function parseCodePriceInfo(object $value, int $StokID){
		if (is_object($value->OnStocks->StockLine)){
			if ($value->OnStocks->StockLine->StokID == $StokID) return $value->DeliveryType;
		}
		else{
			foreach($value->OnStocks->StockLine as $v){
				if ($v->StokID == $StokID) return $value->DeliveryType;
			}
		}
		return false;
	}
	private static function getClientData($typeOrganization = 'entity'): array
	{
		return [
			'ClientID' => self::getParams($typeOrganization)->ClientID,
			'Password' => self::getParams($typeOrganization)->Password
		];
	}
	public function Basket_Add($ov){
		// debug($ov); exit();
		/**
		 * Можно обойтись и без записи в базу данных ZakazCode, но это крайне необходимо, когда нужно
		 * получить по ZakazCode item_id и brend_id
		 */
		$ZakazCode = $this->db->getField('mikado_zakazcode', 'ZakazCode', 'item_id', $ov['item_id']);
		if (!$ZakazCode){
			$ZakazCode = $this->setArticle($ov['brend'], $ov['article'], true);
			$this->db->insert('mikado_zakazcode', ['item_id' => $ov['item_id'], 'ZakazCode' => $ZakazCode], ['print_query' => false]); 
			// exit();
		} 
		if (preg_match('/^g/', $ZakazCode)){
			try{
				$DeliveryType = $this->getDeliveryType($ZakazCode, $ov['store_id']);
				if (!$DeliveryType) throw new Exception("Ошибка получения DeliveryType по $ZakazCode");
			} catch(Exception $e){
				Log::insertThroughException($e, ['additional' => "osi: {$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}"]);
				return fase;
			}
		} 
		else $DeliveryType = 0;
		$clientData = self::getClientData('private');
		$params = [
			'ZakazCode' => $ZakazCode,
			'QTY' => $ov['quan'],
			'DeliveryType' => $DeliveryType,
			'Notes' => "{$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}",
			'ClientID' => $clientData['ClientID'],
			'Password' => $clientData['Password'],
			'ExpressID' => 0,
			'StockID' => $this->getStockId($ov['store_id'])
		];
		try{
			$xml = self::getUrlData(
				'http://www.mikado-parts.ru/ws1/basket.asmx/Basket_Add',
				$params
			);
			if (!$xml) throw new Exception("Ошибка отправки заказа в Микадо");
		} catch(Exception $e){
			Log::insertThroughException($e, ['additional' => "osi: {$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}"]);
			return false;
		}
		$result = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
		$result = json_decode(json_encode($result));
		if ($result->Message == 'OK'){
			$ov['quan'] = $result->OrderedQTY;
			OrderValue::changeStatus(11, $ov);
		}
		else Log::insert([
			'url' => $_SERVER['REQUEST_URI'],
			'text' => $result->Message,
			'additional' => "osi: {$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}"
		]);
	}
	public function isOrdered($ov){
		$array = $this->db->select_one('mikado_basket', '*', self::getWhere($ov));
		if (empty($array)) return false;
		return $array['Message'];
	}
	public function deleteFromOrder($ov){
		$res = OrderValue::get($ov);
		$ov = $res->fetch_assoc();
		$array = $this->db->select_one('mikado_basket', '*', self::getWhere($ov));
		$xml = self::getUrlData(
			'http://www.mikado-parts.ru/ws1/basket.asmx/Basket_Delete',
			[
				'ItemID' => $array['ItemID'],
				'ClientID' => $this->ClientID,
				'Password' => $this->Password,
			]
		);
		$result = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
		$result = json_decode(json_encode($result));
		$this->db->delete('mikado_basket', self::getWhere($ov));

		OrderValue::update(['status_id' => 5, 'ordered' => 0], $ov);
		User::updateReservedFunds($ov['user_id'], $ov['price'], 'minus');
	}
	public static function isInBasket($ov){
		$items = self::getItemsToOrder(null, $ov['order_id']);
		foreach($items as $value){
			if ($value['ZakazCode'] == $ov['ZakazCode']) return true;
		}
		return false;
	}
	public static function removeFromBasket($ov){
		$items = self::getItemsToOrder(null, $ov['order_id']);
		$clientData = self::getClientData($ov['order_id']);
		$isParsed = false;
		foreach($items as $value){
			if ($value['ZakazCode'] == $ov['ZakazCode']){
				$xml = self::getUrlData(
					'http://www.mikado-parts.ru/ws1/basket.asmx/Basket_Delete',
					[
						'ClientID' => $clientData['ClientID'],
						'Password' => $clientData['Password'],
						'ItemID' => $value['ID']
					]
				);
				if (!$xml) return false;
				$result = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
				$result = json_decode(json_encode($result), true);
				debug($result);
				if ($result[0] != 'OK'){
					Log::insert([
						'url' => $_SERVER['REQUEST_URI'],
						'text' => 'Ошибка удаления из корзины Микадо: ' . $result[0],
						'additional' => "osi: {$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}"
					]);
					return false;
				}
				OrderValue::changeStatus(5, $ov);
				$isParsed = true;
			}
		}
		if ($isParsed) return true;
		return false;
	}
}
