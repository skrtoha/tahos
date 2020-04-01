<?
namespace core\Provider\Abcp;
use core\Provider\Abcp;
use core\OrderValue;
use core\Provider;

class OrderAbcp extends Abcp{
	public $param;
	private $basketContent;
	public function __construct($db, $provider_id){
		parent::__construct(NULL, $db);
		$this->param = parent::$params[$provider_id];
	}
	public function addToBasket($params){
		// debug($params); exit();
		$res = parent::getUrlData(
			"{$this->param['url']}/basket/add",
			[
				'userlogin' => $this->param['userlogin'],
				'userpsw' => md5($this->param['userpsw']),
				'positions' => [
					0 => [
						'brand' => $params['brand'],
						'number' => $params['number'],
						'supplierCode' => $params['supplierCode'],
						'itemKey' => $params['itemKey'],
						'quantity' => $params['quantity']
					]
				]
			]
		);
		$array = json_decode($res, true);
		if ($array['positions'][0]['errorMessage']) die ("Произошла ошибка: {$array['positions'][0]['errorMessage']} <a href='{$_SERVER['HTTP_REFERER']}'>Назад</a>");
		$status_id = $params['quantity'] ? 7 : 5;
		$orderValue = new OrderValue();
		$orderValue->changeStatus($status_id, $params);
	}
	public function getItemInfoByArticleAndBrend($array){
		$article = self::getComparableString($array['article']);
		$distributorId = str_replace($this->param['title'].'-', '', $array['providerStore']);
		$url =  "{$this->param['url']}/search/articles/?userlogin={$this->param['userlogin']}&userpsw=".md5($this->param['userpsw'])."&useOnlineStocks=1&number={$article}&brand={$array['brend']}";
		$response = self::getUrlData($url);
		$items = json_decode($response, true);
		// debug($items); exit();
		if (empty($items)) return false;
		foreach($items as $value){
			if (
				self::getComparableString($value['numberFix']) == self::getComparableString($article) 
				&& $value['distributorId'] == $distributorId
				// && self::getComparableString($value['brand']) == self::getComparableString($brend)
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
	public function getItemsToOrder(int $provider_id){
		$output = array();
		$basketContent = $this->getBasketContent();
		if (!$basketContent) return false;
		foreach($basketContent as $value) $output[] = [
			'provider' => $this->param['title'],
			'store' => $this->param['title'] . '-' . $value['supplierCode'],
			'brend' => $value['brand'],
			'article' => $value['numberFix'],
			'title_full' => $value['description'],
			'price' => $value['price'],
			'count' => $value['quantity']
		];
		return $output;
	}
	private function getBasketContent(){
		$url =  "{$this->param['url']}/basket/content/?userlogin={$this->param['userlogin']}&userpsw=".md5($this->param['userpsw']);
		try{
			$response = file_get_contents($url);
			if (!$response) throw new \Exception("Ошибка получение данных корзины {$this->param['title']}");
			$output = json_decode($response, true);
			if (isset($output['error']) && $output['error']) throw new \Exception("Ошибка {$this->param['title']}: {$output['error']}");
			
		} catch(\Exception $e){
			\core\Log::insertThroughException($e);
			return false;
		}
		return $output;
	}
	public static function getGetString($param){
		return "
			/admin/?view=orders
			&id={$param['order_id']}
			&item_id={$param['item_id']}
			&article={$param['article']}
			&brend={$param['brend']}
			&store_id={$param['store_id']}
			&quan={$param['quan']}
			&price={$param['price']}
			&user_id={$param['user_id']}
			&provider_id={$param['provider_id']}
			&providerStore={$param['providerStore']}
		";
	}
	public function isInBasket($brend, $article){
		if (!$this->basketContent) $this->basketContent = $this->getBasketContent();
		if (!$this->basketContent) return false;
		foreach($this->basketContent as $value){
			if (
				self::getComparableString($value['numberFix']) == self::getComparableString($article) 
				// && self::getComparableString($value['brand']) == self::getComparableString($brend)
			) return true;
		}
		return false;
	}
	private function getShipmentDate(){
		if ($this->param['provider_id'] == 13) return '';
		$url =  "{$this->param['url']}/basket/shipmentDates/?userlogin={$this->param['userlogin']}&userpsw=".md5($this->param['userpsw']);
		$response = file_get_contents($url);
		$res = json_decode($response, true);
		return $res[1]['date'];
	}
	public function basketOrder(){
		$shipmentDate = $this->getShipmentDate();
		$res = self::getUrlData(
			"{$this->param['url']}/basket/order",
			[
				'userlogin' => $this->param['userlogin'],
				'userpsw' => md5($this->param['userpsw']),
				'paymentMethod' => $this->param['paymentMethod'],
				'shipmentAddress' => $this->param['shipmentAddress'],
				'shipmentOffice' => isset($this->param['shipmentOffice']) ? $this->param['shipmentOffice'] : '',
				'shipmentMethod' => isset($this->param['shipmentMethod']) ? $this->param['shipmentMethod'] : '',
				'shipmentDate' => $shipmentDate
			]
		);
		// $res = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/core/test.rossko.txt');

		if (!$res) die("Ошибка отправления заказка");
		$array = json_decode($res, true);

		foreach($array['orders'] as $key => $order){
			$orderValue = new OrderValue();
			foreach($order['positions'] as $pos){
				$ov = OrderValue::getByBrendAndArticle([
					'brend' => $pos['brand'],
					'article' => $pos['numberFix'],
					'provider_id' => $this->param['provider_id'],
					'status_id' => 7
				]);
				$orderValue->changeStatus(11, $ov);
			}
		}
	}
}

