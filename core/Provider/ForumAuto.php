<?php
namespace core\Provider;

use core\Cache;
use core\Exceptions\ForumAuto\ErrorBrendID;
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

    /**
     * @throws ErrorBrendID
     */
    public static function getBrendID($brand_name){
        $cacheId = "Forum-Auto-brend-$brand_name";
        $result = Cache::get($cacheId);
        if ($result) {
            return $result;
        }

		$res_brend = Brend::get([
			'title' => $brand_name, 
			'provider_id' => self::getParams()->provider_id
		], [], '');
		if (!$res_brend->num_rows){
			Cache::set($cacheId, Null);
			throw new EForumAuto\ErrorBrendID("Бренд $brand_name отсутствует в базе");
		} 
		$brend = $res_brend->fetch_assoc();
		if ($brend['parent_id']) {
            $brend_id = $brend['parent_id'];
        }
		else {
            $brend_id = $brend['id'];
        }
        Cache::set($cacheId, $brend_id);
		return $brend_id;
	}
	private static function getItemID(object $part){
		$cacheId = "Forum-Auto-{$part->brand}:{$part->art}";
        $result = Cache::get($cacheId);
        if ($result) {
            return $result;
        }

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
			]);
			if ($res === true){
				$item_id = parent::getInstanceDataBase()->last_id();
				parent::getInstanceDataBase()->insert('item_articles', ['item_id' => $item_id, 'item_diff' => $item_id]);
			}
			else {
				$item = Item::getByBrendIDAndArticle($brend_id, $part->art);
				$item_id = $item['id'];
			}
			if (!$item_id) throw new EForumAuto\ErrorItemID("Ошибка получения item_id");
            Cache::set($cacheId, $item_id);
		}
		catch(EForumAuto\ErrorItemID $e){
			$e->process($brend_id, $part);
		}
		return $item_id;
	}

	private static function getStoreID($whse){
        $cacheId = "Forum-Auto-$whse";
        $result = Cache::get($cacheId);
        if ($result) {
            return $result;
        }
        $store = parent::getInstanceDataBase()->select_one(
            'provider_stores',
            'id,cipher',
            "`provider_id` = ".self::getParams()->provider_id." AND `title` = '$whse'"
        );
        Cache::set($cacheId, $store['id']);
        return $store['id'];
	}
	private static function getItemsByBrendAndArticle($brend, $article, $typeOrganization = 'private'){
		try{
			$queryString = self::getStringQuery(
                'listGoods',
                ['art' => $article, 'br' => $brend, 'cross' => 0],
                $typeOrganization
            );
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
		if(!parent::getIsEnabledApiSearch(self::getParams()->provider_id)) {
            return false;
        }
		if (!parent::isActive(self::getParams()->provider_id)) {
            return false;
        }

		$response = self::getItemsByBrendAndArticle($brend, $article);

		foreach($response as $item){
            $store_id = self::getStoreID($item->whse);
            $item_id = self::getItemID($item);
            parent::getInstanceDataBase()->insert('store_items', [
                'store_id' => $store_id,
                'item_id' => $item_id,
                'price' => $item->price,
                'in_stock' => $item->num,
                'packaging' => $item->kr
            ], [
                'duplicate' => [
                    'price' => $item->price,
                    'in_stock' => $item->num
            ]]);
        }
        return true;
	}
	public static function getPrice($params){
		try{
			$itemsList = self::getItemsByBrendAndArticle($params['brend'], $params['article']);
			foreach($itemsList as $item){
				if ($item->whse != $params['providerStore']) continue;
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
        $ordered = 0;
		foreach($providerBasket as $pb){
			$itemsList = self::getItemsByBrendAndArticle($pb['brend'], $pb['article'], $pb['typeOrganization']);
			$requiredItem = NULL;
			try{
				foreach($itemsList as $item){
					if ($item->whse != $pb['store']) continue;
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
            $ordered += $pb['quan'];
		}
        return $ordered;
	}
}
