<?php
namespace core;

class Mikado{
	private $db;
	public $ClientID = 33773;
	public $Password = 'tahos2bz';
	private $armtek;
	private $brends;
	// StockID => store_id
	public $stocks = [
		1 => 14,
		10 => 13,
		35 => 12
	];
	public function __construct($db){
		$this->db = $db;	
		$this->armtek = new Armtek($this->db);
	}
	public function getCoincidences($text){
		$xml = Abcp::getPostData(
			'http://mikado-parts.ru/ws1/service.asmx/Code_Search',
			[
				'Search_Code' => $text,
				'ClientID' => $this->ClientID,
				'Password' => $this->Password,
				'FromStockOnly' => 'FromStockOnly'  			
			]
		);
		$result = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
		$result = json_decode(json_encode($result));
		// debug($result, 'result'); exit();
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
		$xml = Abcp::getPostData(
			'http://www.mikado-parts.ru/ws1/service.asmx/Code_Search',
			[
				'Search_Code' => $article,
				'ClientID' => $this->ClientID,
				'Password' => $this->Password,
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
		// echo "запрос для $b1 и $b2<br>";
		if (Armtek::getComparableString($b1) == Armtek::getComparableString($b2)) return true;
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
				$this->brends[$value['title']]['id'] = $value['id'];
				$this->brends[$value['title']]['parent_id'] = $value['parent_id'];
			}
		} 
		if ($this->brends[$b1]['id'] == $this->brends[$b2]['parent_id'] || $this->brends[$b2]['id'] == $this->brends[$b1]['parent_id']){
			// echo "$b1 и $b2 равны<br>";
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
		// debug($row, $item_id);
		if (!$item_id) return false;
		$this->db->query(Abcp::getQueryDeleteByProviderId($item_id, 8), ''); 
		if ($row->CodeType == 'Analog' || $row->CodeType == 'AnalogOEM'){
			$this->db->insert('analogies', ['item_id' => $_GET['item_id'], 'item_diff' => $item_id], ['print_query' => false]);
			$this->db->insert('analogies', ['item_id' => $item_id, 'item_diff' => $_GET['item_id']], ['print_query' => false]);
		}
		// var_dump(empty($row->onStocks));
		if (!empty($row->OnStocks)){
			if (is_array($row->OnStocks->StockLine)){
				foreach($row->OnStocks->StockLine as $stock) $this->parseStockLine($stock, $item_id, $row);
			}
			else $this->parseStockLine($row->OnStocks->StockLine, $item_id, $row);
		}
	}
	private function parseStockLine($stock, $item_id, $row){
		$store_id = $this->stocks[$stock->StokID];
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
				'print_query' => false,
				'duplicate' => [
					'in_stock' => preg_replace('/\D+/', '', $stock->StockQTY),
					'price' => ceil($row->PriceRUR)
				]
			]
		);
	}
	public function isStoreMikado($store_id){
		if (array_search($store_id, $this->stocks)) return true;
		return false;
	}
	private function getCodeInfo($ZakazCode){
		$xml = Abcp::getPostData(
			'http://www.mikado-parts.ru/ws1/service.asmx/Code_Info',
			[
				'ZakazCode' => $ZakazCode,
				'ClientID' => $this->ClientID,
				'Password' => $this->Password,
			]
		);
		$result = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
		$result = json_decode(json_encode($result));
		// debug($result);
	}
	public function Basket_Add($ov){
		// debug($ov);
		$ZakazCode = $this->db->getField('mikado_zakazcode', 'ZakazCode', 'item_id', $ov['item_id']);
		if (!$ZakazCode){
			$ZakazCode = $this->setArticle($ov['brend'], $ov['article'], true);
			$this->db->insert('mikado_zakazcode', ['item_id' => $ov['item_id'], 'ZakazCode' => $ZakazCode], ['print_query' => false]); 
			// exit();
		} 
		// debug($ov, $ZakazCode);
		$xml = Abcp::getPostData(
			'http://www.mikado-parts.ru/ws1/basket.asmx/Basket_Add',
			[
				'ZakazCode' => $ZakazCode,
				'QTY' => $ov['quan'],
				'DeliveryType' => 0,
				'Notes' => '',
				'ClientID' => $this->ClientID,
				'Password' => $this->Password,
				'ExpressID' => 0,
				'StockID' => array_search($ov['store_id'], $this->stocks)
			]
		);
		$result = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
		$result = json_decode(json_encode($result));
		// debug($result); exit();
		$this->db->insert(
			'mikado_basket',
			[
				'order_id' => $ov['order_id'],
				'store_id' => $ov['store_id'],
				'item_id' => $ov['item_id'],
				'ItemID' => $result->ID,
				'OrderedQTY' => $result->OrderedQTY,
				'Message' => $result->Message
			]
		);
		if ($result->Message == 'OK'){
			$this->db->update(
				'orders_values',
				['status_id' => 11, 'ordered' => $result->OrderedQTY],
				Armtek::getWhere($ov)
			);
		}
	}
	public function isOrdered($ov){
		$array = $this->db->select_one('mikado_basket', '*', Armtek::getWhere($ov));
		if (empty($array)) return false;
		return $array['Message'];
	}
	public function deleteFromOrder($ov){
		$array = $this->db->select_one('mikado_basket', '*', Armtek::getWhere($ov));
		$xml = Abcp::getPostData(
			'http://www.mikado-parts.ru/ws1/basket.asmx/Basket_Delete',
			[
				'ItemID' => $array['ItemID'],
				'ClientID' => $this->ClientID,
				'Password' => $this->Password,
			]
		);
		$result = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
		$result = json_decode(json_encode($result));
		$this->db->delete('mikado_basket', Armtek::getWhere($ov));
		$this->db->update(
			'orders_values',
			['status_id' => 5, 'ordered' => 0],
			Armtek::getWhere($ov)
		);
	}
}
