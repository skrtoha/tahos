<?php
namespace core\Provider;
use core\Provider;
use core\Log;
use core\OrderValue;
use core\Exceptions\Autopiter as EAutopiter;

class Autopiter extends Provider{
	public static $provider_id = 24;

	private static function getClient(){
		$client = new \SoapClient("http://service.autopiter.ru/v2/price?WSDL");
		if (!($client->IsAuthorization()->IsAuthorizationResult)){
			$client->Authorization(array("UserID"=>"737995", "Password"=>"047135", "Save"=> "true"));
		}
		return $client;
	}

	public static function getPrice($params){
		try{
			$model = self::getModelByBrendArticleStoreID($params['brend'], $params['article'], $params['store_id']);
		}
		catch(EAutopiter\ErrorGetModel $e){
			return false;
		}
		return [
			'price' => $model->SalePrice,
			'available' => $model->NumberOfAvailable 
		];
	}
	private static function getModelByBrendArticleStoreID($brend, $article, $store_id){
		try{
			$articleId = self::getArticleIdByBrendAndArticle($brend, $article);
		}
		catch (EAutopiter\ErrorArticleId $e){
			$e->process();
			return false;
		}
		$PriceIdResult = self::getClient()->GetPriceId([
			'ArticleId' => $articleId
		]);
		$storeInfo = Provider::getStoreInfo($store_id);
		$SellerId = $storeInfo['title'];
		$SM = & $PriceIdResult->GetPriceIdResult->PriceSearchModel;
		if (is_array($SM)){
			foreach($SM as $model){
				if ($model->SellerId == $SellerId){
					return $model;
				} 
			}
		}
		else{
			if ($SM->SellerId == $SellerId){
				return $SM;
			}
		}
		throw new EAutopiter\ErrorGetModel("Ошибка получение model");
	}
	public static function getItemsToOrder($provider_id): array
	{
		$output = [];
		$basket = self::getClient()->GetBasket();
		$br = & $basket->GetBasketResult->ItemCartModel;
		if (!$br) return $output;
		if (is_array($br)){
			foreach($br as $cartModel){
				$output[] = self::parseBasketForItemToOrder($cartModel);
			}
		}
		else $output = self::parseBasketForItemToOrder($br);
		return $output;
	}
	private static function parseBasketForItemToOrder($model){
		$osi = explode('-', $model->Comment);
		$resOrderValue = OrderValue::get([
			'order_id' => $osi[0],
			'store_id' => $osi[1],
			'item_id' => $osi[2]
		]);
		$orderValue = $resOrderValue->fetch_assoc();
		return [
			'provider' => 'Autopiter',
			'store' => $orderValue['cipher'],
			'brend' => $orderValue['brend'],
			'article' => $orderValue['article'],
			'title_full' => $orderValue['title_full'],
			'price' => $orderValue['price'],
			'count' => $orderValue['quan']
		];
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
			'ArticleId' => $articleId
		]);
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
	public static function isInBasket($params){
		$basket = self::getClient()->GetBasket();
		if (empty($basket->GetBasketResult)) return false;
		$br = & $basket->GetBasketResult->ItemCartModel;
		if (is_array($br)){
			foreach($br as $cartModel){
				if ($cartModel->Comment == Autoeuro::getStringBasketComment($params)){
					return true;
				}
			}
		}
		else{
			if ($br->Comment == Autoeuro::getStringBasketComment($params)){
				return true;
			}
		}
		return false;
	}
	public static function removeFromBasket($ov){
		$basket = self::getClient()->GetBasket();
		$br = & $basket->GetBasketResult->ItemCartModel;
		if (is_array($br)){
			foreach($br as $cartModel){
				if ($cartModel->Comment == Autoeuro::getStringBasketComment($ov)){
					$DetailUid = $cartModel->DetailUid;
				}
			}
		}
		else{
			if ($br->Comment == Autoeuro::getStringBasketComment($ov)){
				$DetailUid = $br->DetailUid;
			}
		}
		self::getClient()->DeleteItemCart(['DetailUid' => $DetailUid]);
		return true;
	}
	public static function addToBasket($params){
		try{
			$model = self::getModelByBrendArticleStoreID($params['brend'], $params['article'], $params['store_id']);
		}
		catch(EAutopiter\ErrorGetModel $e){
			Log::insert([
				'text' => 'Ошибка получения model',
				'additional' => "osi: " . Autoeuro::getStringBasketComment($params)
			]);
			return false;
		}
		$item = [
			'DetailUid' => $model->DetailUid,
			'Comment' => Autoeuro::getStringBasketComment($params),
			'SalePrice' => $model->SalePrice,
			'Quantity' => $params['quan']
		];
		$resInsertToBasket = self::getClient()->InsertToBasket(['Items' => [
			0 => $item 
		]]);
		if ($resInsertToBasket->InsertToBasketResult->ResponseCodeItemCart->Code->ResponseCode != '0'){
			Log::insert([
				'text' => 'Ошибка добавления в корзину',
				'additional' => 'osi: ' . Autoeuro::getStringBasketComment($params),
				'query' => json_encode($resInsertToBasket)
			]);
			return false;
		}
		OrderValue::changeStatus(7, $params);
		return true;
	}
	public static function sendOrder(){
		$res = self::getClient()->MakeOrderFromBasket();
		$itemCart = & $res->Items->ResponseCodeItemCart;
		if (!$itemCart) return false;
		if (is_array($itemCart)){
			foreach($itemCart as $ic) self::parseSendOrderItemCart($ic);
		}
		else self::parseSendOrderItemCart($itemCart);
	}
	private static function parseSendOrderItemCart($model){
		$osi = explode('-', $model->Item->Comment);
		if ($model->Code->ResponseCode == 0){
			$resOrderValue = $orderValue = OrderValue::get([
				'order_id' => $osi[0],
				'store_id' => $osi[1],
				'item_id' => $osi[2]
			]);
			OrderValue::changeStatus(11, $resOrderValue->fetch_assoc());
		}
		else{
			Log::insert([
				'text' => 'Ошибка отправка заказа',
				'additional' => "osi: " . $model->Item->Comment
			]);
		}
	}
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
