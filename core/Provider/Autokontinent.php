<?php
namespace core\Provider;

use core\Provider;
use core\Brend;
use core\Item;
use core\OrderValue;
use core\Exceptions\Autokontinent as EAuto;

class Autokontinent extends Provider{
	public static $fieldsForSettings = [
		'isActive',
		'username',
		'password',
		'provider_id'
	];

	private static $mainStores = [
		'Череповец' => 7,
		'СПб Юг' => 1,
        'Вологда' => 9
	];

	public static function getParams($typeOrganization = 'entity'){
		$params = parent::getApiParams([
			'api_title' => 'Autokontinent',
			'typeOrganization' => $typeOrganization
		]);
		$params->title = "Автоконтинент";
		$params->url = 'http://api.autokontinent.ru/v1/';
		return $params;
	}

	private static function getAuthData($typeOrganization = 'entity'){
		return [
			'username' => self::getParams($typeOrganization)->username, 
			'password' => self::getParams($typeOrganization)->password
		];
	}

	public static function getItemsToOrder(int $provider_id){
		if (!parent::getIsEnabledApiOrder($provider_id)) return false;
		$output = [];
		$basketList = self::getBasket('private');
		self::parseBasketList($output, $basketList);
		$basketList = self::getBasket('entity');
		self::parseBasketList($output, $basketList);
		return $output;
	}
	private static function parseBasketList(& $output, $basketList){
		foreach($basketList as $basket){
			if (!$basket->comment) continue;
			$osi = explode('-', $basket->comment);
			$resOrderValue = OrderValue::get([
				'order_id' => $osi[0],
				'store_id' => $osi[1],
				'item_id' => $osi[2]
			]);
			$orderValue = $resOrderValue->fetch_assoc();
			$output[$basket->basket_id] = [
				'provider_id' => $orderValue['provider_id'],
				'provider' => 'Autokontinent',
				'store' => $orderValue['cipher'],
				'order_id' => $orderValue['order_id'],
				'store_id' => $orderValue['store_id'],
				'item_id' => $orderValue['item_id'],
				'brend' => $orderValue['brend'],
				'article' => $orderValue['article'],
				'title_full' => $orderValue['title_full'],
				'price' => $orderValue['price'],
				'count' => $orderValue['quan']
			];
		}
		return $output;
	}
	public static function getPrice(array $params){
		$part_id = self::getPartIdByBrandAndArticle($params['brend'], $params['article']);
		$itemsList = self::getItemsByPartID($part_id);
		$warehouse_name = parent::getInstanceDataBase()->getFieldOnID('provider_stores', $params['store_id'], 'title');
		foreach($itemsList as $item){
			if ($item->part_id == $part_id && $item->warehouse_name == $warehouse_name) return [
				'price' => $item->price,
				'available' => $item->quantity
			];
		}
	}

	private static function getItemsByArticle($article, $typeOrganization = 'entity'){
		$response = Provider::getCurlUrlData(
			self::getParams($typeOrganization)->url . 'search/part.json?part_code=' . $article, 
			self::getAuthData($typeOrganization)
		);
		$items = json_decode($response);
		if (empty($items)) return false;
		return $items;
	}
	public static function getCoincidences($search){
		if (!parent::getIsEnabledApiSearch(self::getParams()->provider_id)) return false;
		if (!parent::isActive(self::getParams()->provider_id)) return false;
		$coincidences = [];
		$items = self::getItemsByArticle($search);
		foreach($items as $item){
			$coincidences[$item->brand_name] = $item->part_descr;
		}
		return $coincidences;
	}
	public static function getBrendID($brand_name){
		static $brends;
		$brend_id = NULL;
		if (isset($brends[$brand_name]) && $brends[$brand_name]) return $brends[$brand_name];
		$res_brend = Brend::get([
			'title' => $brand_name, 
			'provider_id' => self::getParams()->provider_id
		], [], '');
		if (!$res_brend->num_rows){
			$brends[$brand_name] = NULL;
			throw new EAuto\ErrorBrendID("Бренд $brend отсутствует в базе");
		} 
		$brend = $res_brend->fetch_assoc();
		if ($brend['parent_id']) $brend_id = $brend['parent_id'];
		else $brend_id = $brend['id'];
		$brends[$brand_name] = $brend_id;
		return $brend_id;
	}
	private static function getPartIdByBrendAndArrayOfItems($brand, $items){
		foreach($items as $item){
			if (Provider::getComparableString($item->brand_name) == Provider::getComparableString($brand)) return $item->part_id;
		}
		throw new EAuto\ErrorPartID('Не найден part_id');
	}
	private static function getItemsByPartID($part_id){
		$response = Provider::getCurlUrlData(
			self::getParams()->url . 'search/price.json?part_id=' . $part_id, 
			self::getAuthData()
		);
		return json_decode($response);
	}
	private static function getItemID(object $part){
		static $items;
		$item_id = NULL;

		$key = "{$part->brand_name}:{$part->part_code}";
		if (isset($items[$key]) && $items[$key]) return $items[$key];

		try{
			$brend_id = self::getBrendID($part->brand_name);
		}
		catch(EAuto\ErrorBrendID $e){
			$e->process($part->brand_name);
			return false;
		}

		try{
			$res = Item::insert([
				'brend_id' => $brend_id,
				'article' => Item::articleClear($part->part_code),
				'article_cat' => $part->part_code,
				'title' => $part->part_name ? $part->part_name : $part->part_comment,
				'title_full' => $part->part_comment,
				'source' => self::getParams()->title
			]);
			if ($res === true) $item_id = Item::$lastInsertedItemID;
			else {
				$item = Item::getByBrendIDAndArticle($brend_id, $part->part_code);
				$item_id = $item['id'];
			}
			if (!$item_id) throw new EAuto\ErrorItemID("Ошибка получения item_id");
		}
		catch(EAuto\ErrorItemID $e){
			$e->process($brend_id, $part);
		}

		$items[$key] = $item_id;
		return $item_id;
	}
	private static function addAnalogy($mainItemID, $item_id){
		static $processedAnalogies = [];
		if (isset($processedAnalogies["$mainItemID:$item_id"])) return;
		parent::getInstanceDataBase()->insert('item_analogies', ['item_id' => $mainItemID, 'item_diff' => $item_id]);
		parent::getInstanceDataBase()->insert('item_analogies', ['item_id' => $item_id, 'item_diff' => $mainItemID]);
	}
	private static function getDaysDelivery($dt_delivery){
		$dateDelivery = \DateTime::createFromFormat('Y-m-d H:i:s', $dt_delivery);
		$currentDate = new \DateTime();
		$interval = $currentDate->diff($dateDelivery); 
		return $interval->format('%a') + 1;
	}
	private static function getStoreID(object $part){
		static $stores;
		$store_id = NULL;
		if (isset($stores[$part->warehouse_id]) && $stores[$part->warehouse_id]) return $stores[$part->warehouse_id];
		try{
			$res = parent::getInstanceDataBase()->insert('provider_stores', [
				'title' => $part->warehouse_name,
				'cipher' =>  strtoupper(Provider::getRandomString(4)),
				'currency_id' => 1,
				'provider_id' => self::getParams()->provider_id,
				'percent' => 10,
				'delivery' => self::getDaysDelivery($part->dt_delivery)
			]);
			if ($res === true){
				$store_id = parent::getInstanceDataBase()->last_id();
				$stores[$part->warehouse_id] = $store_id;
				return $store_id;
			}
			else{
				$store = parent::getInstanceDataBase()->select_one('provider_stores', '*', "`title` = '{$part->warehouse_name}' AND `provider_id` = " . self::getParams()->provider_id);
				$store_id = $store['id'];
			}
			if (!$store_id) throw new EAuto\ErrorStoreID('Ошибка получения store_id');
		}
		catch(EAuto\ErrorStoreID $e){
			$e->process($part, parent::getInstanceDataBase()->last_query);
		}
		$stores[$part->warehouse_id] = $store_id;
		return $store_id;
	}
	private static function getPartIdByBrandAndArticle($brand, $article, $params = []){
		$items = self::getItemsByArticle($article);
		if (!$items) return false;
        
        $brand = Provider::getProviderBrend(self::getParams($params['typeOrganization'])->provider_id, $brand);

		try{
			$part_id = self::getPartIdByBrendAndArrayOfItems($brand, $items);
		}
		catch(EAuto\ErrorPartID $e){
			if (!empty($params)) $e->process($params);
			return false;
		}
		return $part_id;
	}
	public static function setArticle(string $brand, string $article){
		if(!parent::getIsEnabledApiSearch(self::getParams()->provider_id)) return false;
		if (!parent::isActive(self::getParams()->provider_id)) return false;
		
		$part_id = self::getPartIdByBrandAndArticle($brand, $article);
		if (!$part_id) return false;

		$partsList = self::getItemsByPartID($part_id);

		foreach($partsList as $part){
			if (!$part->price) continue;
			$item_id = self::getItemID($part);
			if (!$item_id) continue;
			if ($part->part_id == $part_id) $mainItemID = $item_id;
			if ($mainItemID != $item_id){
				self::addAnalogy($mainItemID, $item_id);
			}
			$store_id = self::getStoreID($part);
			if (!$store_id) continue;
			// self::clearStoreItems($item_id);
			parent::getInstanceDataBase()->insert('store_items', [
				'store_id' => $store_id,
				'item_id' => $item_id,
				'price' => $part->price,
				'in_stock' => $part->quantity,
				'packaging' => $part->package
			]);
		}
	}
	public static function removeFromBasket($ov){
		$basketList = self::getBasket();
		foreach($basketList as $basket){
			if ("{$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}" == $basket->comment){
				$basket_id = $basket->basket_id;
				$version = $basket->version;
				break;
			}
		}

		Provider::getCurlUrlData(
			self::getParams()->url . "basket/del.json?basket_id=$basket_id&version=$version", 
			self::getAuthData()
		);

		OrderValue::changeStatus(5, $ov);
	}
	private static function getBasket($typeOrganization = 'entity'){
		$json = Provider::getCurlUrlData(
			self::getParams($typeOrganization)->url."basket/get.json", 
			self::getAuthData($typeOrganization)
		);
		return json_decode($json);
	}
	public static function isInBasket($ov){
		$basketList = self::getBasket();
		if (empty($basketList)) return false;
		foreach($basketList as $basket){
			$osi = explode('-', $basket->comment);
			if ("{$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}" == $basket->comment) return true;
		}
		return false;
	}
	public static function addToBasket($ov){
		$part_id = self::getPartIdByBrandAndArticle($ov['brend'], $ov['article'], $ov);
		if (preg_match('/Склад/', $ov['providerStore'])) $warehouse_id = preg_replace('/[\D]+/', '', $ov['providerStore']);
		else $warehouse_id = self::$mainStores[$ov['providerStore']];
		if (!$part_id) return false;
		try{
			$json = Provider::getCurlUrlData(
				self::getParams($ov['typeOrganization'])->url . "basket/add.json?part_id=$part_id&warehouse_id=$warehouse_id&quantity={$ov['quan']}&comment={$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}", 
				self::getAuthData($ov['typeOrganization'])
			);
			$response = json_decode($json);
			if ($response->status != 'OK') throw new EAuto\ErrorAddingToBasket("Ответ Автоконтинент: $response->error_message");
		}
		catch(EAuto\ErrorAddingToBasket $e){
			$e->process($ov);
			return false;
		}
		OrderValue::changeStatus(7, $ov);
		return true;
	}
	public static function sendOrder(){
		$basket = self::getBasket('private');
		self::executeSendOrder('private', $basket);

		$basket = self::getBasket('entity');
		self::executeSendOrder('entity', $basket);
	}
	private static function executeSendOrder($typeOrganization, $basket){
		if (empty($basket)) return 0;
		
		try{
			$json = Provider::getCurlUrlData(
				self::getParams($typeOrganization)->url . '/basket/order.json', 
				self::getAuthData($typeOrganization)
			);
			$response = json_decode($json);
			if ($response->status != 'OK') throw new EAuto\ErrorSendOrder('Ошибка отправления заказа');
		}
		catch(EAuto\ErrorSendOrder $e){
			$e->process($basket);
			return false;
		}

		foreach($basket as $b){
			$osi = explode('-', $b->comment);
			$resOrderValue = $orderValue = OrderValue::get([
				'order_id' => $osi[0],
				'store_id' => $osi[1],
				'item_id' => $osi[2]
			]);
			OrderValue::changeStatus(11, $resOrderValue->fetch_assoc());
		}

	}
}
