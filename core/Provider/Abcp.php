<?
namespace core\Provider;
use core\Provider;
use core\Brend;
use core\Log;
use core\OrderValue;
use core\Item;
use core\Setting;

if ($_SERVER['DOCUMENT_ROOT']) $path = $_SERVER['DOCUMENT_ROOT'].'/';
else $path = '';
require_once $path.'vendor/autoload.php';

class Abcp extends Provider{
	public static $fieldsForSettings = [
		"isActive",	// is required	
		'title',
		'cronOrder',
		'url',
		'userlogin',
		'userpsw',
		'provider_id',
		'paymentMethod',
		'shipmentMethod',
		'shipmentAddress',
		'getAnalogies'
	];
	private $providerStores = [];
	public function __construct($item_id = NULL, $db = NULL){
		// if (!$_SESSION['user']) return false;
		if ($db) $this->db = $db;
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
	}
	public static function getPrice(array $params)
	{
		$p = self::getParam($params['provider_id']);
		$storeTitle = parent::getInstanceDataBase()->getField('provider_stores', 'title', 'id', $params['store_id']);
		$distributorId = str_replace("{$p['title']}-", '', $storeTitle);
		$res = parent::getUrlData(
			"{$p['url']}/search/articles/?userlogin={$p['userlogin']}&userpsw=".md5($p['userpsw'])."&useOnlineStocks=1&number={$params['article']}&brand={$params['brend']}"
		);
		$result = json_decode($res, true);
		foreach($result as $value){
			if ($value['distributorId'] != $distributorId) continue;
			if (Provider::getComparableString($value['number']) != Provider::getComparableString($params['article'])){
				continue;
			}
			if (Provider::getComparableString($value['brand']) != Provider::getComparableString($params['brend'])){
				continue;
			}
			return [
				'price' => $value['price'],
				'available' => $value['availability']
			];
		}
		return false;
	}
	public static function isInBasket($ov){
		return Armtek::isInBasket($ov);
	}
	static public function removeFromBasket($ov){
		return Armtek::removeFromBasket($ov);
	}
	public static function getItemsToOrder(int $provider_id){
		$basketProvider = parent::getProviderBasket($provider_id, '');
		if (!$basketProvider->num_rows) return false;
		$output = array();
		foreach($basketProvider as $bp){
			$param = self::getParam($bp['provider_id']);
			$provider = $param['cronOrder'] ? $param['cronOrder'] : $bp['api_title'];
			$output[] = [
				'provider_id' => $bp['provider_id'],
				'provider' => $provider,
				'store_id' => $bp['store_id'],
				'store' => $bp['cipher'],
				'order_id' => $bp['order_id'],
				'brend' => $bp['brend'],
				'item_id' => $bp['item_id'],
				'article' => $bp['article'],
				'title_full' => $bp['title_full'],
				'price' => $bp['price'],
				'count' => $bp['quan']
			];
		} 
		return $output;
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
	public static function getParam(int $provider_id, $typeOrganization = 'entity'){
		$param = parent::getApiParams([
			'provider_id' => $provider_id,
			'typeOrganization' => $typeOrganization
		]);
		return (array) $param;
	}
	private function insertProviderStore($provider_id, $item){
		if (!$item['distributorId']) return false;
		$p = & self::getParam($provider_id);
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
				'cipher' => strtoupper(self::getRandomString(4)),
				'percent' => 10,
				'currency_id' => 1,
				'provider_id' => $provider_id,
				'delivery' => ceil($item['deliveryPeriod'] / 24),
				'delivery_max' => ceil($item['deliveryPeriod'] / 24),
				'under_order' => ceil ($item['deliveryPeriod'] / 24),
				'noReturn' => $item['noReturn']
			]/*,
			['print' => false, 'deincrement_duplicate' => 1]*/
		);
		if ($res === true){
			$store_id = $this->db->last_id();
			$p['providerStores'][$store_id] = $item['distributorId'];
			return $store_id;
		} 
	}
	private function getItems($provider_id){
		$brends = Brend::get(['id' => $this->item['brend_id'], 'provider_id' => $provider_id], [], '');
		$brend = $brends->fetch_assoc();
		$p = self::getParam($provider_id);
		$res = parent::getUrlData(
			"{$p['url']}/search/articles/?userlogin={$p['userlogin']}&userpsw=".md5($p['userpsw'])."&useOnlineStocks=1&number={$this->item['article']}&brand={$brend['title']}"
		);
		return json_decode($res, true);
	}
	public function getSearch($search){
		$coincidences = array();
		$params = [
			6 => self::getParam(6),
			13 => self::getParam(13)
		];
		foreach($params as $provider_id => $value){
			if (!parent::getIsEnabledApiSearch($provider_id)) continue;
			if (!parent::isActive($provider_id)) continue;
			$param = (array) $params[$provider_id];
			$url = "{$param['url']}/search/brands?".http_build_query([
				'userlogin' => $param['userlogin'],
				'userpsw' => md5($param['userpsw']),
				'number' => $search
			]);
			$response = parent::getUrlData($url);
			if ($response == 'maintenance' || !$response) return Log::insert([
				'text' => "Не срабатывает api ".$value['title']." - ответ сервера: $response"
			]);
			$items = json_decode($response, true);
			if (empty($items)) continue;
			if (isset($items['errorMessage'])) continue;
			foreach($items as $value){
				if (!self::getComparableString($value['description'])) continue;
				$coincidences[$value['brand']] = $value['description'];
			} 
		}
		return $coincidences;
	}
	public function render($provider_id){
		if(!parent::getIsEnabledApiSearch($provider_id)) return false;
		if (!parent::isActive($provider_id)) return false;
		//пока не понятно для чего эта строка
		// if (!empty($providerStores) && !in_array($provider_id, $providerStores)) continue;

		$items = $this->getItems($provider_id);
		if (!$items) return false;

		$count = count($items);
		$count = $count <= 5 ? $count : 5; 
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
			if (self::getComparableString($this->item['article']) != self::getComparableString($item['numberFix'])){
				$this->insertAnalogies($provider_id, $item_id, $item);
			}
			$store_id = $this->insertProviderStore($provider_id, $item);
			if (!$store_id) continue;
			$this->insertStoreItems($store_id, $item_id, $item);
		}
	}
	public function getBrandId($provider_id, $brand){
		// $brend = $this->db->select_one('brends', 'id,parent_id', "`title`='$brand'");
		$brendsList = Brend::get([
			'title' => $brand,
			'provider_id' => $provider_id
		], [], '');
		if (!$brendsList->num_rows) {
			$param = self::getParam($provider_id);
			$this->db->insert(
				'log_diff',
				[
					'type' => 'brends',
					'from' => $param['title'],
					'text' => "Бренд $brand отсутствует в базе",
					'param1' => $brand,
					'param2' => $brand
				]
			);
			return false;
		}
		return Brend::getBrendIdFromList($brendsList);
	}
	public function insertItem($provider_id, $array, & $insertedItems = NULL){
		$param = self::getParam($provider_id);
		$array['source'] = $param['title'];
		$res = $this->db->insert('items', $array, ['print_query' => false]);
		$last_query = $this->db->last_query;
		$last_res = $res;
		if ($res === true){
			$last_id = $this->db->last_id();
			$res2 = $this->db->insert('item_articles', ['item_id' => $last_id, 'item_diff' => $last_id]);
			if ($insertedItems !== NULL){
				$this->db->insert('rendered_voshod', ['item_id' => $last_id], ['print_query' => false]);
				$insertedItems++;
			} 
			return $last_id;
		} 
		if (self::isDuplicate($res)){
			$item = $this->db->select('items', 'id', "`article`='{$array['article']}' AND `brend_id`={$array['brend_id']}");
			$item_id = $item[0]['id'];
			return $item_id;
		} 
		return false;
	}
	public function insertAnalogies($provider_id, $item_id, $item){
		$param = self::getParam($provider_id);
		$res1 = $this->db->insert('item_analogies', ['item_id' => $this->item_id, 'item_diff' => $item_id], ['print_query' => false]);
		$last_query1 = $this->db->last_query;
		$res2 = $this->db->insert('item_analogies', ['item_id' => $item_id, 'item_diff' => $this->item_id], ['print_query' => false]);
		$last_query2 = $this->db->last_query;
		if ($res1 === true && $res2 === true) return $this->db->insert(
			'log_diff',
			[
				'type' => 'analogies',
				'from' => $param['title'],
				'text' => "
					к {$this->item['brand']} - {$this->item['article']} - {$this->item['title_full']} добавлено 
					{$item['brand']} - {$item['numberFix']} - {$item['description']}
				",
				'param1' => $this->item_id,
				'param2' => $item_id,
			]
		);
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
					'price' => ceil($item['price']),
					'in_stock' => $item['availability']
				]
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
	public static function getItemInfoByArticleAndBrend($ov){
		// debug($ov);
		$param = self::getParam($ov['provider_id']);
		$article = self::getComparableString($ov['article']);
		$distributorId = str_replace($param['title'].'-', '', $ov['store']);
		$url =  "{$param['url']}/search/articles/?userlogin={$param['userlogin']}&userpsw=".md5($param['userpsw'])."&useOnlineStocks=1&number={$article}&brand={$ov['brend']}";
		$response = self::getUrlData($url);
		if (!$response) return false;
		$items = json_decode($response, true);
		if (empty($items)) return false;
		foreach($items as $value){
			if (
				self::getComparableString($value['numberFix']) == self::getComparableString($article) 
				&& $value['distributorId'] == $distributorId
			) {
				return[
					'brand' => $value['brand'],
					'number' => $value['number'],
					'supplierCode' => $value['supplierCode'],
					'itemKey' => $value['itemKey']
				];
			}
		}
		return false;
	}
	/**
	 * returns payment methods that was added to $params and is not nothere used
	 * @param  integer $provider_id
	 * @return array 
	 */
	public static function getPaymentMethods($provider_id){
		$param = self::getParam($provider_id);
		$url =  "{$param['url']}/basket/paymentMethods/?userlogin={$param['userlogin']}&userpsw=".md5($param['userpsw']);
		$response = self::getUrlData($url);
		return json_decode($response, true);
	}
	public static function sendOrder($provider_id){
		$providerBasket = parent::getProviderBasket($provider_id, '');
		$param = self::getParam($provider_id);
		$items = [];
		if (!$providerBasket->num_rows) return false;
		foreach($providerBasket as $value){
			if (!parent::getIsEnabledApiOrder($provider_id)){
				Log::insert([
					'text' => "API заказов для {$param['title']} отключено",
					'additional' => "osi: {$value['order_id']}-{$value['store_id']}-{$value['item_id']}"
				]);
				continue;
			}
			$items["{$value['order_id']}-{$value['store_id']}-{$value['item_id']}"] = $value;
		}
		$responseAddToBasket = self::addToBasket($items, $value);
		debug($responseAddToBasket);
		self::parseResponseAddToBasket($responseAddToBasket, $items);
		self::sendBasketToOrder($provider_id, $value['typeOrganization']);
	}
	private static function addToBasket($items, $ov){
		$param = self::getParam($ov['provider_id'], $ov['typeOrganization']);
		$positions = [];
		foreach($items as $item){
			$item['provider_id'] = $ov['provider_id'];
			$itemInfo = self::getItemInfoByArticleAndBrend($item);
			if (!$itemInfo){
				Log::insert([
					'text' => 'Ошибка получения itemInfo',
					'additional' => "osi: {$item['order_id']}-{$item['store_id']}-{$item['item_id']}"
				]);
				continue;
			}
			$positions[] = [
				'brand' => $itemInfo['brand'],
				'number' => $itemInfo['number'],
				'supplierCode' => $itemInfo['supplierCode'],
				'itemKey' => $itemInfo['itemKey'],
				'quantity' => $item['quan'],
				'comment' => "{$item['order_id']}-{$item['store_id']}-{$item['item_id']}"
			];
		}
		$response = parent::getUrlData(
			"{$param['url']}/basket/add",
			[
				'userlogin' => $param['userlogin'],
				'userpsw' => md5($param['userpsw']),
				'positions' => $positions
			]
		);
		return json_decode($response, true);
	}
	private static function parseResponseAddToBasket($response, $items){
		if ($response['error']){
			foreach($items as $osi => $item){
					Log::insert([
						'text' => $response['error'],
						'additional' => "osi: $osi"
					]);
				}
			return false;
		}
		if (empty($response['positions'])) return false;
		foreach($response['positions'] as $position){
			if ($position['status'] == 0){
				Log::insert([
					'text' => $position['errorMessage'],
					'additional' => $position['comment']
				]);
				continue;
			}
			OrderValue::changeStatus(11, $items[$position['comment']]);
		}
	}
	private static function getShipmentDate($provider_id){
		if ($provider_id == 13) return '';
		$param = self::getParam($provider_id);
		$url =  "{$param['url']}/basket/shipmentDates/?userlogin={$param['userlogin']}&userpsw=".md5($param['userpsw']);
		$response = parent::getUrlData($url);
		$res = json_decode($response, true);
		return $res[1]['date'];
	}
	public static function sendBasketToOrder(int $provider_id, string $user_type): void
	{
		$param = self::getParam($provider_id, $user_type);
		$shipmentDate = self::getShipmentDate($provider_id);
		$res = self::getUrlData(
			"{$param['url']}/basket/order",
			[
				'userlogin' => $param['userlogin'],
				'userpsw' => md5($param['userpsw']),
				'paymentMethod' => $param['paymentMethod'],
				'shipmentAddress' => $param['shipmentAddress'],
				'shipmentOffice' => isset($param['shipmentOffice']) ? $param['shipmentOffice'] : '',
				'shipmentMethod' => isset($param['shipmentMethod']) ? $param['shipmentMethod'] : '',
				'shipmentDate' => $shipmentDate
			]
		);
		$responseData = json_decode($res, true);
		if ($responseData['status'] != 1) return;
		foreach($responseData['orders'] as $number => $value){
			foreach($value['positions'] as $position){
				$osi = explode('-', $position['comment']);
				Provider::updateProviderBasket(
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
}
?>
