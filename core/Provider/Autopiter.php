<?php
namespace core\Provider;
use core\Provider;
use core\Log;
use core\OrderValue;
use core\Exceptions\Autopiter as EAutopiter;

class Autopiter extends Provider{
	public static $provider_id = 27;
	public static $store_id = 46472;

	private static function getClient(){
		$client = new \SoapClient("http://service.autopiter.ru/v2/price?WSDL");
		if (!($client->IsAuthorization()->IsAuthorizationResult)){
			$client->Authorization(array("UserID"=>"737995", "Password"=>"047135", "Save"=> "true"));
		}
		return $client;
	}

	public static function getPrice($params){
		debug($params);
	}
	public static function getItemsToOrder($provider_id): array
	{
		return [];
	}
	private static function getBrend($brend){
		static $brends;
		$output = '';
		if (isset($brends[$brend])) return $brends[$brend];
		$providerBrend = Provider::getProviderBrend(self::$provider_id, $brend);
		$output = $providerBrend ? $providerBrend : $brend;
		$brends[$brend] = $output;
		return $output;
	}
	private static function getArticleIdByBrendAndArticle($brend, $article){
		$client = self::getClient();
		$result = $client->FindCatalog(["Number" => $article]);
		$items = $result->FindCatalogResult->SearchCatalogModel;
		$brend = self::getBrend($brend);
		if (is_array($items)){
			foreach($items as $item){
				if (
					Provider::getComparableString($item->CatalogName) == Provider::getComparableString($brend) &&
					Provider::getComparableString($item->Number) == Provider::getComparableString($article)
				) return $item->ArticleId;
			}
		}
		else{
			if (
					Provider::getComparableString($items->CatalogName) == Provider::getComparableString($brend) &&
					Provider::getComparableString($items->Number) == Provider::getComparableString($article)
				) return $items->ArticleId;
		}
		throw new EAutopiter\ErrorArticleId(json_encode([
			'search' => "$brend $article",
			'items' => $items,
		]));
	}
	public static function setArticle($brend, $article){
		if (!parent::getIsEnabledApiSearch(self::$provider_id)) return false;
		try{
			$articleId = self::getArticleIdByBrendAndArticle($brend, $article);
		}
		catch(EAutopiter\ErrorArticleId $e){
			$e->process();
			return false;
		}
		$PriceIdResult = self::getClient()->GetPriceId([
			'ArticleId' => $articleId/*,
			'SearchCross' => 1,*/
		]);
		$items = [];
		$sellers = [];
		$storesTypes = [];
		if (is_array($PriceIdResult->GetPriceIdResult->PriceSearchModel)){
			foreach($PriceIdResult->GetPriceIdResult->PriceSearchModel as $model){
				self::parseSearchModel($model);
			}
		}
		else self::parseSearchModel($PriceIdResult->GetPriceIdResult->PriceSearchModel);
	}
	private static function getStoreID($model){
		$providerStore = Provider::getInstanceDataBase()->select_one('provider_stores', 'id', "`title` = '{$model->SellerId}' AND `provider_id` = ".self::$provider_id);
		if ($providerStore) return $providerStore['id'];
		$res = Provider::getInstanceDataBase()->insert('provider_stores', [
			'title' => $model->SellerId, 
			'provider_id' => self::$provider_id,
			'percent' => '10',
			'cipher' => strtoupper(parent::getRandomString(4)),
			'currency_id' => 1,
			'delivery' => $model->NumberOfDaysSupply,
			'delivery_max' => $model->NumberOfDaysSupply,
			'under_order' => $model->NumberOfDaysSupply,
			'daysForReturn' => 14
		]/*, ['print' => true]*/);
		return Provider::getInstanceDataBase()->last_id();
	}
	private static function parseSearchModel($model){
		$store_id = self::getStoreID($model);
		$res = parent::getInstanceDataBase()->insert('store_items', [
			'store_id' => $store_id,
			'item_id' => $_GET['item_id'],
			'price' => $model->SalePrice,
			'in_stock' => $model->NumberOfAvailable,
			'packaging' => $model->MinNumberOfSales
		]/*, ['print' => true]*/);
	}
	public static function isInBasket($params){}
	public static function removeFromBasket($ov){}
	public static function puIntBusket($params){}
	public static function getBasket(){}
	public static function sendOrder(){}
	public static function getCoincidences($search){
		if (!parent::getIsEnabledApiSearch(self::$provider_id)) return false;
		$client = self::getClient();
		$result = $client->FindCatalog(["Number" => $search]);
		$items = $result->FindCatalogResult->SearchCatalogModel;
		if (!$items) return false;
		if (is_array($items)){
			foreach($items as $item){
				$output[$item->CatalogName] = $item->Name;
			}
		}
		else $output[$items->CatalogName] = $items->Name;
		return $output;
	}
}
