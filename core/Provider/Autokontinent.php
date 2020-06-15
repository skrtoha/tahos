<?php
namespace core\Provider;

use core\Provider;
use core\Brend;
use core\Item;
use core\OrderValue;
use core\Exceptions\Autokontinent as EAuto;

class Autokontinent extends Provider{
	public static $params = [
		'title' => 'Автоконтинент',
		'url' => 'http://api.autokontinent.ru/v1/',
		'provider_id' => 20
	];

	private static $mainStores = [
		'Череповец' => 7,
		'Петербург' => 1
	];

	//в скрипте данные авторизации вставляются напрямую, т.к. при использовании self::$auth не срабатывает
	private static $auth = ['username' => '010345', 'password' => '​7373366'];

	public static function getItemsToOrder(int $provider_id){}
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

	private static function getItemsByArticle($article){
		$response = Provider::getCurlUrlData(
			self::$params['url'].'search/part.json?part_code=' . $article, 
			['username' => '010345', 'password' => '7373366']
		);
		$items = json_decode($response);
		if (empty($items)) return false;
		return $items;
	}
	public static function getCoincidences($search){
		$coincidences = [];
		$items = self::getItemsByArticle($search);
		foreach($items as $item){
			$coincidences[$item->brand_name] = $item->part_descr;
		}
		return $coincidences;
	}
	private static function getBrendID($brand_name){
		static $brends;
		$brend_id = NULL;
		if (isset($brends[$brand_name]) && $brends[$brand_name]) return $brends[$brand_name];
		$res_brend = Brend::get([
			'title' => $brand_name, 
			'provider_id' => self::$params['provider_id']
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
			self::$params['url'].'search/price.json?part_id=' . $part_id, 
			['username' => '010345', 'password' => '7373366']
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
			$res = parent::getInstanceDataBase()->insert('items', [
				'brend_id' => $brend_id,
				'article' => article_clear($part->part_code),
				'article_cat' => $part->part_code,
				'title' => $part->part_name ? $part->part_name : $part->part_comment,
				'title_full' => $part->part_comment,
				'source' => self::$params['title']
			]);
			if ($res === true){
				$item_id = parent::getInstanceDataBase()->last_id();
			}
			else {
				$item = Item::get($brend_id, $part->part_code);
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
		parent::getInstanceDataBase()->insert('analogies', ['item_id' => $mainItemID, 'item_diff' => $item_id]);
		parent::getInstanceDataBase()->insert('analogies', ['item_id' => $item_id, 'item_diff' => $mainItemID]);
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
				'provider_id' => self::$params['provider_id'],
				'percent' => 10,
				'delivery' => self::getDaysDelivery($part->dt_delivery)
			]);
			if ($res === true){
				$store_id = parent::getInstanceDataBase()->last_id();
				$stores[$part->warehouse_id] = $store_id;
				return $store_id;
			}
			else{
				$store = parent::getInstanceDataBase()->select_one('provider_stores', '*', "`title` = '{$part->warehouse_name}' AND `provider_id` = " . self::$params['provider_id']);
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
	private static function clearStoreItems($item_id){
		parent::getInstanceDataBase()->query("
			DELETE si FROM #store_items si
			LEFT JOIN #provider_stores ps ON ps.id = si.store_id
			WHERE si.item_id = $item_id AND ps.provider_id = " . self::$params['provider_id'] 
		, '');
	}
	private static function getPartIdByBrandAndArticle($brand, $article, $params = []){
		$items = self::getItemsByArticle($article);
		if (!$items) return false;

		$providerBrend = Provider::getProviderBrend(self::$params['provider_id'], $brand);
		$brand =  $providerBrend ? $providerBrend : $brand;

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
			self::$params['url']."basket/del.json?basket_id=$basket_id&version=$version", 
			['username' => '010345', 'password' => '7373366']
		);

		OrderValue::changeStatus(5, $ov);
	}
	private static function getBasket(){
		$json = Provider::getCurlUrlData(
			self::$params['url']."basket/get.json", 
			['username' => '010345', 'password' => '7373366']
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
				self::$params['url']."basket/add.json?part_id=$part_id&warehouse_id=$warehouse_id&quantity={$ov['quan']}&comment={$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}", 
				['username' => '010345', 'password' => '7373366']
			);
			$response = json_decode($json);
			if ($response->status != 'OK') throw new EAuto\ErrorAddingToBasket('Ошибка добавления товара в корзину');
		}
		catch(EAuto\ErrorAddingToBasket $e){
			$e->process($ov);
			return false;
		}
		OrderValue::changeStatus(7, $ov);
		return true;
	}
	public static function sendOrder(){
		$basket = self::getBasket();
		if (empty($basket)) return false;
		try{
			$json = Provider::getCurlUrlData(
				self::$params['url'].'/basket/order.json', 
				['username' => '010345', 'password' => '7373366']
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
			OrderValue::changeStatus(11, [
				'order_id' => $osi[0],
				'store_id' => $osi[1],
				'item_id' => $osi[2],
				'price' => $b->price,
				'quan' => $b->quantity,
				'user_id' => parent::getInstanceDataBase()->getField('orders', 'user_id', 'id', $osi[0])
			]);
		}
	}
}
