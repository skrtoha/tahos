<?php
namespace core;
use ArmtekRestClient\Http\Exception\ArmtekException as ArmtekException; 
use ArmtekRestClient\Http\Config\Config as ArmtekRestClientConfig;
use ArmtekRestClient\Http\ArmtekRestClient as ArmtekRestClient;

//добавлен в связи с тем, что не работало в тестах для Росско
if ($_SERVER['DOCUMENT_ROOT']) $path = $_SERVER['DOCUMENT_ROOT'].'/';
else $path = '';

require_once $path.'vendor/Armtek/autoloader.php';
require_once $path.'vendor/autoload.php';


class Armtek extends Provider{
	public $provider_id = 2;
	private $config = [
		'user_login' => 'TAHOS@TAHOS.RU',
		'user_password' => 'tahos12345'
	];
	public $keyzak = array();
	private $mainItemId;
	private $params = [
		'VKORG' => '5000',
		'KUNNR_RG' => '43182432',
		'format' => 'json'
	];
	public function __construct($db){
		$this->db = $db;
		$armtek_client_config = new ArmtekRestClientConfig($this->config);
		$this->armtek_client = new ArmtekRestClient($armtek_client_config);
		$this->log = new \Katzgrau\KLogger\Logger(
			$_SERVER['DOCUMENT_ROOT'].'/admin/logs', 
			\Psr\Log\LogLevel::INFO, 
			array(
				'filename' => 'armtek_order',
				'extension' => 'txt'
			)
		);
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
		$array = $this->db->select_one('provider_stores', 'id,delivery', "`provider_id`={$this->provider_id} AND `title`='{$object->KEYZAK}'");
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
				'provider_id' => $this->provider_id,
				'title' => $object->KEYZAK,
				'cipher' => strtoupper(Abcp::getRandomString(4)),
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
				$this->log->error("$res: {$this->db->last_query}");
				return false;
			}
		}
	}
	public function getBrendId($brand, $from = 'armtek'){
		$brend = $this->db->select_one('brends', 'id,parent_id', "`title`='$brand'");
		// $brend = Brend::get([
		// 	'title' => $brand, 
		// 	'provider_id' => $this->provider_id
		// ], ['provider_id']); exit();
		if (empty($brend)) {
			$this->db->insert(
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
		if ($brend['parent_id']) return $brend['parent_id'];
		else return $brend['id'];
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
			else{
				$this->log->error("$res: {$this->db->last_query}");
				return false;
			}
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
		if (!parent::getIsEnabledApiSearch($this->provider_id)) return false;
		$this->params['PIN'] = $article;
		$this->params['BRAND']	= $brand;
		$this->params['QUERY_TYPE']	= 1;
		// debug($this->params);
		$request_params = [
			'url' => 'search/search',
			'params' => $this->params
		];
		$response = $this->armtek_client->post($request_params);
		$data = $response->json();
		$this->render($data->RESP);
	}
	public function getSearch($search){
		if (!parent::getIsEnabledApiSearch($this->provider_id)) return false;
		$this->params['PIN'] = $search;
		$request_params = [
			'url' => 'search/search',
			'params' => $this->params
		];
		$response = $this->armtek_client->post($request_params);
		$data = $response->json();
		// debug($data); exit;
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
		// debug($RESP, 'resp');
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
		// debug($keyzak); exit();
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
					'article' => article_clear($value['PIN']),
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
				$array = $this->db->select_one('items', 'id', "`brend_id`=$brend_id AND `article`='".article_clear($value['PIN'])."'");
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
		if (array_key_exists($keyzak, $this->keyzak)) return $this->keyzak[$keyzak];
		$array = $this->db->select_one('provider_stores', 'id', "`title`='$keyzak` AND `provider_id`={$this->provider_id}");
		if (empty($array)) return false;
		$this->keyzak[$keyzak] = $array['id'];
		return $array['id'];
	}
	public function isKeyzak($store_id){
		if ($temp = array_search($store_id, $this->keyzak)) return $temp;
		$array = $this->db->select_one('provider_stores', 'id,title,provider_id', "`id`=$store_id");
		if (empty($array)) return false;
		if ($array['provider_id'] == $this->provider_id){
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
	private function getKeyzakByStoreId($store_id){
		if ($temp = array_search($store_id, $this->keyzak)) return $temp;
		$array = $this->db->select_one('provider_stores', 'id,title,provider_id', "`id`=$store_id");
		if (empty($array)) return false;
		$this->keyzak[$array['title']] = $array['id'];
		return $array['title'];
	}
	public static function getWhere($array){
		return "`order_id`={$array['order_id']} AND `store_id`={$array['store_id']} AND `item_id`={$array['item_id']}";
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
	public function getItems($type = 'armtek'){
		$items = array();
		$res_items = $this->db->query("
			SELECT
				ov.user_id,
				ov.order_id,
				ov.store_id,
				ps.title AS store,
				ov.item_id,
				ov.price,
				i.article,
				IF(pb.provider_id IS NOT NULL, pb.title, b.title) AS brend,
				pb.provider_id,
				ov.quan AS count
			FROM
				#other_orders ao
			LEFT JOIN
				#orders_values ov ON ov.order_id = ao.order_id AND ov.store_id = ao.store_id AND ov.item_id = ao.item_id
			LEFT JOIN
				#provider_stores ps ON ps.id=ov.store_id
			LEFT JOIN
				#items i ON i.id=ov.item_id
			LEFT JOIN
				#brends b ON b.id=i.brend_id
			LEFT JOIN
				#provider_brends pb ON pb.brend_id = b.id AND pb.provider_id = ps.provider_id
			WHERE ao.type = '$type' && ao.response IS NULL
		", '');
		return $res_items;
	}
	public static function clearString($str){
		$str = mb_strtolower($str);
		return preg_replace('/[^\wА-Яа-я]+/', '', $str);
	}
	public function sendOrder($settings = array()){
		$params['VKORG'] = $this->params['VKORG'];
		$params['KUNRG'] = $this->params['KUNNR_RG'];
		$res_items = $this->getItems('armtek');
		if (!$res_items->num_rows){
			echo "<br>Товаров для отправки не найдено";
			return false;
		} 
		$items = array();
		foreach($res_items as $item){
			$items[] = [
				'user_id' => $item['user_id'],
				'order_id' => $item['order_id'],
				'item_id' => $item['item_id'],
				'store_id' => $item['store_id'],
				'price' => $item['price'],
				'PIN' => $item['article'],
				'BRAND' => $item['brend'],
				'KWMENG' => $item['count'],
				'KEYZAK' => $this->getKeyzakByStoreId($item['store_id'])
			];
		}
		$params['ITEMS'] = $items;
		$params['format'] = 'json';
		$request_params = [
			'url' => 'order/createOrder',
			'params' => $params
		];
		// debug($request_params, 'request_params'); 
		$response = $this->armtek_client->post($request_params);
		$json_responce_data = $response->json();

		// debug($json_responce_data, 'json_responce_data');
		// exit();

		foreach($json_responce_data->RESP->ITEMS as $value){
			foreach($params['ITEMS'] as $item){
				if (
					$item['PIN'] == $value->PIN && 
					strcasecmp(self::clearString($value->BRAND), self::clearString($item['BRAND'])) === 0 && 
					$item['KEYZAK'] == $value->KEYZAK
				){
					$arrayQuery = [
						'order_id' => $item['order_id'],
						'store_id' => $item['store_id'],
						'item_id' => $item['item_id']
					];
					if ($value->ERROR_MESSAGE){
						$this->db->update('other_orders', ['response' => $value->ERROR_MESSAGE], self::getWhere($arrayQuery)); 
						echo ("<br>Ошибка в заказе №{$item['order_id']}: {$value->BRAND} - {$value->PIN} - {$value->ERROR_MESSAGE}");
						break 2;
					}
					if (isset($value->RESULT[0]->ERROR) && $value->RESULT[0]->ERROR){
						echo("<br>Ошибка в заказе №{$item['order_id']}: {$value['BRAND']} - {$value['PIN']} - {$value->RESULT[0]->ERROR}");
						$this->db->update('other_orders', ['response' => $value->ERROR_MESSAGE], self::getWhere($arrayQuery)); 
						break 2;
					}
					if ($value->REMAIN){
						echo("<br>В заказе №{$item['order_id']}: {$value->BRAND} - {$value->PIN} на хватило остатка {$value->REMAIN}");
						$this->db->update('other_orders', ['response' => "Не хватило остатка $value->REMAIN"], self::getWhere($arrayQuery)); 
					} 
					$orderValue = new OrderValue();
					$item['quan'] = $value->RESULT[0]->KWMENG;
					$orderValue->changeStatus(11, $item);
					$this->db->update('other_orders', ['response' => 'OK'], self::getWhere($arrayQuery)); 
				}
			}
		}
		// debug($json_responce_data->RESP->ITEMS, 'json_responce_data');
	}
	public static function getComparableString($str){
		if (!$str) return false;
		$str = preg_replace('/[^\wа-яA-Z]/i', '', $str);
		return mb_strtolower($str);
	}
}
