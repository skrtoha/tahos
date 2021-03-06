<?php
namespace core\Provider;

use core\Provider;
use core\Brend;
use core\Item;
use core\OrderValue;
use core\Exceptions\ForumAuto as EForumAuto;

class ForumAuto extends Provider{
	public static $fieldsForSettings = [
		'isActive',
		'provider_id',
		'login',
		'pass',
		'storePrefix'
	];
	public static function getParams($typeOrganization = 'entity'){
		$params = parent::getApiParams([
			'api_title' => 'ForumAuto',
			'typeOrganization' => $typeOrganization
		]);
		$params->title = 'ФорумАвто';
		$params->url = "https://api.forum-auto.ru/v2/";
		return $params;
	}
	public static function getItemsToOrder(int $provider_id){}
	private static function getStringQuery($method, $params = [], $typeOrganization = 'entity'){
		$output = self::getParams($typeOrganization)->url;
		$output .= "$method";
		$output .= '?login=' . self::getParams($typeOrganization)->login . '&pass=' . self::getParams($typeOrganization)->pass;
		if (!empty($params)){
			foreach($params as $key => $value) $output .= "&$key=$value";
		}
		return $output;
	}
	public static function getCoincidences($search){
		if (!parent::getIsEnabledApiSearch(self::getParams()->provider_id)) return false;
		if (!parent::isActive(self::getParams()->provider_id)) return false;
		$output = [];
		$queryString = self::getStringQuery('listGoods', ['art' => $search]);
		$json = Provider::getUrlData($queryString);
		$itemsList = json_decode($json);
		if (empty($itemsList)) return false;
		foreach($itemsList as $item) $output[$item->brand] = $item->name;
		return $output;
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
			throw new EForumAuto\ErrorBrendID("Бренд $brand_name отсутствует в базе");
		} 
		$brend = $res_brend->fetch_assoc();
		if ($brend['parent_id']) $brend_id = $brend['parent_id'];
		else $brend_id = $brend['id'];
		$brends[$brand_name] = $brend_id;
		return $brend_id;
	}
	private static function getItemID(object $part){
		static $items;
		$item_id = NULL;

		$key = "{$part->brand}:{$part->art}";
		if (isset($items[$key]) && $items[$key]) return $items[$key];

		try{
			$brend_id = self::getBrendID($part->brand);
		}
		catch(EForumAuto\ErrorBrendID $e){
			$e->process($part->brand);
			return false;
		}
		try{
			$res = parent::getInstanceDataBase()->insert('items', [
				'brend_id' => $brend_id,
				'article' => Item::articleClear($part->art),
				'article_cat' => $part->art,
				'title' => $part->name,
				'title_full' => $part->name,
				'source' => self::getParams()->title
			]/*, ['print' => true]*/);
			if ($res === true){
				$item_id = parent::getInstanceDataBase()->last_id();
				parent::getInstanceDataBase()->insert('articles', ['item_id' => $item_id, 'item_diff' => $item_id]);
			}
			else {
				$item = Item::getByBrendIDAndArticle($brend_id, $part->art);
				$item_id = $item['id'];
			}
			if (!$item_id) throw new EForumAuto\ErrorItemID("Ошибка получения item_id");
		}
		catch(EForumAuto\ErrorItemID $e){
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
	private static function getStoreID($item){
		// debug($item);
		static $stores = [];
		if (empty($stores)){
			$storesList = parent::getInstanceDataBase()->select('provider_stores', 'id,cipher', '`provider_id` = ' . self::getParams()->provider_id);
			foreach($storesList as $store) $stores[$store['cipher']] = $store['id'];
		} 
		$key = self::getParams()->storePrefix . $item->d_deliv;
		if(!isset($stores[$key])) throw new EForumAuto\ErrorStoreID('Ошибка получения strore_id');
		return $stores[$key];
	}
	private static function getItemsByBrendAndArticle($brend, $article){
		try{
			$queryString = self::getStringQuery('listGoods', ['art' => $article, 'br' => $brend, 'cross' => 1]);
			$json = Provider::getUrlData($queryString);
			$response = json_decode($json);
			if (isset($response->errors)) throw new EForumAuto\ErrorFindArticle;
		}
		catch(EForumAuto\ErrorFindArticle $e){
			$e->process($response->errors);
			return false;
		}
		return $response;
	}
	public static function setArticle($mainItemID, $brend, $article){
		if(!parent::getIsEnabledApiSearch(self::getParams()->provider_id)) return false;
		if (!parent::isActive(self::getParams()->provider_id)) return false;
		
		$item_id = NULL;

		// Provider::clearStoresItemsByProviderID(self::getParams()->provider_id, ['item_id' => $mainItemID]);

		$response = self::getItemsByBrendAndArticle($brend, $article);

		//первоначальная обработка для отбора цены
		$itemsOrderedByPrice = [];
		foreach($response as $item) $itemsOrderedByPrice[$item->d_deliv][$item->art][] = $item;

		foreach($itemsOrderedByPrice as $delivery => $itemsList){
			foreach($itemsList as $item){
				try {
					$store_id = self::getStoreID($item[0]);
				} catch (EForumAuto\ErrorStoreID $e) {
					$e->process();
					return false;
				}
				$item_id = self::getItemID($item[0]);
				if ($mainItemID != $item_id) self::addAnalogy($mainItemID, $item_id);
				parent::getInstanceDataBase()->insert('store_items', [
					'store_id' => $store_id,
					'item_id' => $item_id,
					'price' => $item[0]->price,
					'in_stock' => $item[0]->num,
					'packaging' => 1
				], [
					'duplicate' => [
						'price' => $item[0]->price,
						'in_stock' => $item[0]->num
				]/*, 'print' => true*/]);
			}
		}
	}
	public static function getPrice($params){
		try{
			$itemsList = self::getItemsByBrendAndArticle($params['brend'], $params['article']);
			$cipher = parent::getInstanceDataBase()->getField('provider_stores', 'cipher', 'id', $params['store_id']);
			$delivery = str_replace(self::getParams()->storePrefix, '', $cipher);
			foreach($itemsList as $item){
				if ($item->d_deliv != $delivery) continue;
				if (Provider::getComparableString($item->brand) != Provider::getComparableString($params['brend'])) continue;
				if (Provider::getComparableString($item->art) != Provider::getComparableString($params['article'])) continue;
				return [
					'price' => $item->price,
					'available' => $item->num
				];
			}
			throw new EForumAuto\ErrorGettingPrice;
		}
		catch(EForumAuto\ErrorGettingPrice $e){
			$e->process();
		}
		return false;
	}
	public static function isInBasket($ov){
		return Armtek::isInBasket($ov);
	}
	static public function removeFromBasket($ov){
		return Armtek::removeFromBasket($ov);
	}
	public static function sendOrder(){
		$providerBasket = parent::getProviderBasket(self::getParams()->provider_id, '');
		if (!$providerBasket->num_rows) return false;
		foreach($providerBasket as $pb){
			$itemsList = self::getItemsByBrendAndArticle($pb['brend'], $pb['article']);
			$delivery = str_replace(self::getParams($pb['typeOrganization'])->storePrefix, '', $pb['cipher']);
			$requiredItem = NULL;
			try{
				foreach($itemsList as $item){
					if ($item->d_deliv != $delivery) continue;
					if (Provider::getComparableString($item->brand) != Provider::getComparableString($pb['brend'])) continue;
					if (Provider::getComparableString($item->art) != Provider::getComparableString($pb['article'])) continue;
					$requiredItem = $item;
					break;
				}
				if (!$requiredItem) throw new EForumAuto\ErrorGettingRequiredItem('Ошибка получения item для отправки в заказ');
			}
			catch(EForumAuto\ErrorGettingRequiredItem $e){
				$e->process($pb);
				continue;
			}
			var_dump($requiredItem);
			$queryString = self::getStringQuery('addGoodsToOrder', [
				'tid' => $requiredItem->gid,
				'num' => $pb['quan'],
				'eoid' => "{$pb['order_id']}{$pb['store_id']}{$pb['item_id']}"
			], $pb['typeOrganization']);
			try{
				$json = Provider::getUrlData($queryString);
				$response = json_decode($json);
				if (isset($response->errors)) throw new EForumAuto\ErrorSendingOrder('Ошибка отправки заказа');
			}
			catch(EForumAuto\ErrorSendingOrder $e){
				$e->process($response->errors, $pb);
				return false;
			}
			OrderValue::changeStatus(11, $pb);
			parent::updateProviderBasket(
				[
					'order_id' => $pb['order_id'],
					'store_id' => $pb['store_id'],
					'item_id' => $pb['item_id']
				],
				['response' => 'OK']
			);
		}
	}
}
