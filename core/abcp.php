<?
namespace core;
require_once ($_SERVER['DOCUMENT_ROOT'].'/admin/vendor/autoload.php');

class Abcp{
	public static $params = [
		6 => [
			'title' => 'Восход',
			'url' => 'http://autorus.public.api.abcp.ru',
			'userlogin' => 'info@tahos.ru',
			'userpsw' => 'vk640431',
			'provider_id' => 6,
			'paymentMethod' => 1062,
			'shipmentAddress' => 659625,
			'getAnalogies' => true,
			'providerStores' => array()
		],
		13 => [
			'title' => 'МПартс',
			'url' => 'http://v01.ru/api/devinsight',
			'userlogin' => 'info@tahos.ru',
			'userpsw' => '1031786',
			'provider_id' => 13,
			'shipmentMethod' => 1,
			'paymentMethod' => 6,
			'shipmentOffice' => 13146,
			'shipmentAddress' => 'a3f75dfd-5b5b-11e9-8f43-0050568f12a5',
			'getAnalogies' => false,
			'providerStores' => array()
		]
	];
	private $providerStores = [];
	public function __construct($item_id = NULL, $db){
		// if (!$_SESSION['user']) return false;
		$this->db = $db;
		if ($item_id){
			$this->item_id = $item_id;
			$this->item = $this->db->select_unique("
				SELECT
					i.article,
					i.brend_id,
					i.title_full,
					b.title AS brand
				FROM
					#items i
				LEFT JOIN
					#brends b ON b.id=i.brend_id
				WHERE 
					i.id=$item_id
			", '');
			$this->item = $this->item[0];
		}
		foreach(self::$params as $provider_id => $param){
			self::$params[$provider_id]['log'] = new \Katzgrau\KLogger\Logger($_SERVER['DOCUMENT_ROOT'].'/admin/logs', \Psr\Log\LogLevel::WARNING, array(
				'filename' => "{$param['title']}.txt",
				'dateFormat' => 'G:i:s'
			));
			$this->setLog($provider_id, 'debug', 'Исходный item: ', $this->item);
		}
	}
	public static function getQueryDeleteByProviderId($item_id, $provider_id){
		return "
			DELETE si FROM
				#store_items si
			LEFT JOIN
				#provider_stores ps ON ps.id=si.store_id
			WHERE 
				si.item_id = $item_id AND ps.provider_id = $provider_id
		"; 
	}
	private function insertProviderStore($provider_id, $item){
		if (!$item['distributorId']) return false;
		$p = & self::$params[$provider_id];
		$store_id = array_search($item['distributorId'], $p['providerStores']);
		if ($store_id) return $store_id;
		$array = $this->db->select_one('provider_stores', 'id', "`title`='{$p['title']}-{$item['distributorId']}' AND provider_id=$provider_id");
		if (!empty($array)){
			$p['providerStores'][$array['id']] = $item['distributorId'];
			return $array['id'];
		}
		$res = $this->db->insert(
			'provider_stores',
			[
				'title' => $p['title'].'-'.$item['distributorId'],
				'cipher' => strtoupper(static::getRandomString(4)),
				'percent' => 10,
				'currency_id' => 1,
				'provider_id' => $provider_id,
				'delivery' => ceil($item['deliveryPeriod'] / 24),
				'delivery_max' => ceil($item['deliveryPeriod'] / 24),
				'under_order' => ceil ($item['deliveryPeriod'] / 24),
				'prevail' => 0,
				'noReturn' => $item['noReturn']
			],
			['print_query' => false, 'deincrement_duplicate' => 1]
		);
		if ($res === true){
			$store_id = $this->db->last_id();
			$p['providerStores'][$store_id] = $item['distributorId'];
			return $store_id;
		} 
	}
	private function getItems($provider_id){
		$brends = $this->db->select('brends', 'id,title', "`id`={$this->item['brend_id']} OR `parent_id`={$this->item['brend_id']}");
		if (empty($brends)) return false;
		foreach($brends as $value) $search[] = [
			'number' => $this->item['article'],
			'brand' => $value['title']
		];
		$res = static::getPostData(
			self::$params[$provider_id]['url'].'/search/batch',
			[
				'userlogin' => self::$params[$provider_id]['userlogin'],
				'userpsw' => md5(self::$params[$provider_id]['userpsw']),
				'search' => $search
			]
		);
		$res = json_decode($res, true);
		if (!self::$params[$provider_id]['getAnalogies']) return $res;
		$p = self::$params[$provider_id];
		$res = file_get_contents("{$p['url']}/search/articles/?userlogin={$p['userlogin']}&userpsw=".md5($p['userpsw'])."&useOnlineStocks=1&number={$res[0]['number']}&brand={$res[0]['brand']}");
		return json_decode($res, true);
	}
	public function getSearch($search){
		$coincidences = array();
		foreach(self::$params as $store_id => $param){
			$c = array();
			$url = "{$param['url']}/search/brands?userlogin={$param['userlogin']}&userpsw=".md5($param['userpsw'])."&number=$search";
			$response = @file_get_contents($url);
			$items = json_decode($response, true);
			// debug($items);
			if (empty($items)) continue;
			foreach($items as $value){
				if (!Armtek::getComparableString($value['description'])) continue;
				$coincidences[$value['brand']] = $value['description'];
			} 
		}
		return $coincidences;
	}
	public function render($providerStores = array()){
		foreach(self::$params as $provider_id => $param){
			if (!empty($providerStores) && !in_array($provider_id, $providerStores)) continue;

			$items = $this->getItems($provider_id);
			if (!$items) continue;
			// debug($this->item);
			// debug($items); exit();

			$count = count($items);
			// $count = $count <= 5 ? $count : 5; 
			for($i = 0; $i < $count; $i++){
				$item = $items[$i];
				$brend_id = $this->getBrandId($provider_id, $item['brand']);
				if (!$brend_id) continue;
				$item_id = $this->insertItem($provider_id, [
					'brend_id' => $brend_id,
					'article' => $item['numberFix'],
					'article_cat' => $item['number'],
					'title' => $item['description'] ? $item['description'] : 'Деталь',
					'title_full' => $item['description'] ? $item['description'] : 'Деталь',
					'weight' => $item['weight'] ? $item['weight'] * 1000 : null
				]);
				if (!$item_id) return false;
				if (Armtek::getComparableString($this->item['article']) != Armtek::getComparableString($item['numberFix'])){
					$this->insertAnalogies($provider_id, $item_id, $item);
				}
				$store_id = $this->insertProviderStore($provider_id, $item);
				if (!$store_id) continue;
				$this->insertStoreItems($store_id, $item_id, $item);
			}
		}
	}
	private function setLog($store_id, $logLevel, $text, $array = []){
		if ($logLevel == 'debug' && !empty($array)) return self::$params[$store_id]['log']->debug("$text", $array);
		return self::$params[$store_id]['log']->$logLevel("$text");
	}
	public function getBrandId($provider_id, $brand){
		$brend = $this->db->select_one('brends', 'id,parent_id', "`title`='$brand'");
		if (empty($brend)) {
			$this->setLog($provider_id, 'warning', "Бренд $brand отсутствует в базе");
			$this->db->insert(
				'log_diff',
				[
					'type' => 'brends',
					'from' => self::$params[$provider_id]['title'],
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
	public static function isDuplicate($str){
		if (preg_match('/Duplicate/', $str)) return true;
		else return false;
	}
	public function insertItem($provider_id, $array, & $insertedItems = NULL){
		$array['source'] = self::$params[$provider_id]['title'];
		$res = $this->db->insert('items', $array, ['print_query' => false]);
		$last_query = $this->db->last_query;
		$last_res = $res;
		if ($res === true){
			$last_id = $this->db->last_id();
			$this->setLog($provider_id, 'info', "Items success: item_id=$last_id");
			$res2 = $this->db->insert('articles', ['item_id' => $last_id, 'item_diff' => $last_id]);
			if ($res2 !== true) $this->setLog($provider_id, 'error', "articles $res2 | {$this->db->last_query}");
			if ($insertedItems !== NULL){
				$this->db->insert('rendered_voshod', ['item_id' => $last_id], ['print_query' => false]);
				$insertedItems++;
			} 
			return $last_id;
		} 
		if (static::isDuplicate($res)){
			$item = $this->db->select('items', 'id', "`article`='{$array['article']}' AND `brend_id`={$array['brend_id']}");
			$item_id = $item[0]['id'];
			$this->setLog($provider_id, 'info', "Duplicate item {$array['article']} с id=$item_id");
			return $item_id;
		} 
		$this->setLog($provider_id, 'error', "items $last_res | $last_query");
		return false;
	}
	public function insertAnalogies($provider_id, $item_id, $item){
		$res1 = $this->db->insert('analogies', ['item_id' => $this->item_id, 'item_diff' => $item_id], ['print_query' => false]);
		$last_query1 = $this->db->last_query;
		$res2 = $this->db->insert('analogies', ['item_id' => $item_id, 'item_diff' => $this->item_id], ['print_query' => false]);
		$last_query2 = $this->db->last_query;
		if ($res1 === true && $res2 === true) return $this->db->insert(
			'log_diff',
			[
				'type' => 'analogies',
				'from' => self::$params[$provider_id]['title'],
				'text' => "
					к {$this->item['brand']} - {$this->item['article']} - {$this->item['title_full']} добавлено 
					{$item['brand']} - {$item['numberFix']} - {$item['description']}
				",
				'param1' => $this->item_id,
				'param2' => $item_id,
			]
		);
		if (static::isDuplicate($res1)) $this->setLog($provider_id, 'info', "duplicate analogies item_id=$this->item_id, item_diff=$item_id");
		else $this->setLog($provider_id, 'error', "$last_query1 | $res1");
		if (static::isDuplicate($res2)) $this->setLog($provider_id, 'info', "duplicate analogies item_id=$item_id, item_diff=$this->item_id");
		else $this->setLog($provider_id, 'error', "$last_query2 | $res2");
	}
	static public function getRandomString($str_length = 4){
		$str_characters = array (0,1,2,3,4,5,6,7,8,9,'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
		$characters_length = count($str_characters) - 1;
		$string = '';
		for($i = $str_length; $i > 0; $i--) $string .= $str_characters[mt_rand(0, $characters_length)];
		return $string;
	}
	private function insertStoreItems($store_id, $item_id, $item){
		$res = $this->db->insert('store_items', 
			[
				'store_id' => $store_id,
				'item_id' => $item_id,
				'price' => ceil($item['price']),
				'packaging' => $item['packing'] ? $item['packing'] : 1,
				'in_stock' => $item['availability']
			],
			[
				'duplicate' => [
					'price' => ceil($item['price'])
				],
				'print_query' => false
			]
		);
	}
	private function getBrands(){
		$queryString = $this->getQueryString('brands', ['number' => $this->number]);
		$response = file_get_contents($queryString);
		$response = json_decode($response, true);
		// debug($response); exit();
		foreach($response as $value) $brands[] = [
			'number' => $value['number'],
			'brand' => $value['brand']
		];
		return $brands;
	}
	public static function getPostData($url, $data){
		// return debug($data, $url);
		// if (!self::isSiteAvailable($url)) return false;
		$context = stream_context_create([
			'http' => [
				'method' => 'POST',
				'content' => http_build_query($data)
			]
		]);
		return file_get_contents($url, null, $context);
	}
	public static function isSiteAvailable($url) {
		// Проверка правильности URL
		if(!filter_var($url, FILTER_VALIDATE_URL)){
				return false;
		}
		// Инициализация cURL
		$curlInit = curl_init($url);
		// Установка параметров запроса
		curl_setopt($curlInit,CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($curlInit,CURLOPT_HEADER, true);
		curl_setopt($curlInit,CURLOPT_NOBODY, true);
		curl_setopt($curlInit,CURLOPT_RETURNTRANSFER, true);
		// Получение ответа
		$response = curl_exec($curlInit);
		// закрываем CURL
		curl_close($curlInit);
		return preg_match('/200 OK/', $response) ? true : false;
	}
}
?>