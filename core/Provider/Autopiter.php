<?php
namespace core\Provider;
use core\Provider;
use core\Log;
use core\OrderValue;
use core\Exceptions\Autopiter as EAutopiter;

class Autopiter extends Provider{
	public static $fieldsForSettings = [
		'isActive',
		'UserID',
		'Password',
		'provider_id'
	];

	private static function getClient($typeOrganization = 'entity'){
		try{
			$client = new \SoapClient(self::getParams($typeOrganization)->url);
			$authorization = $client->IsAuthorization()->IsAuthorizationResult;
		}
		catch(\SoapFault $e){
			
		}
        if (!($authorization)){
            $client->Authorization(array(
				"UserID" => self::getParams($typeOrganization)->UserID,
                "Password" => self::getParams($typeOrganization)->Password,
                "Save" => false)
            );
        }
		return $client;
	}

	public static function getParams($typeOrganization = 'entity'){
		$params = parent::getApiParams([
			'api_title' => 'Autopiter',
			'typeOrganization' => $typeOrganization
		]);
		$params->url = "http://service.autopiter.ru/v2/price?WSDL";
		return $params;
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
	private static function getBasket($typeOrganization){
		$client = self::getClient($typeOrganization);
		try{
			$basket = $client->GetBasket();
		}
		catch(\SoapFault $e){
		    echo $e->getMessage();
			return false;
		}
		$br = & $basket->GetBasketResult->ItemCartModel;
		if (!$br) return false;
		return $br;
	}
	private static function setOutputItemsToOrder(& $output, $basket){
        if (!$basket) return;
        if (is_array($basket)){
            foreach($basket as $cartModel){
                if (!$cartModel->Comment) continue;
                $output[$cartModel->Comment] = self::parseBasketForItemToOrder($cartModel);
			}
		}
		else{
            if ($basket->Comment){
                $output[$basket->Comment] = self::parseBasketForItemToOrder($basket);
            }
        }
	}
	public static function getItemsToOrder($provider_id){
		$output = [];
		$basket = self::getBasket('entity');
        self::setOutputItemsToOrder($output, $basket);
        $basket = self::getBasket('private');
        self::setOutputItemsToOrder($output, $basket);
        return $output;
	}
	private static function parseBasketForItemToOrder($model){
		if (!$model) return false;
		$osi = explode('-', $model->Comment);
		$resOrderValue = OrderValue::get([
			'order_id' => $osi[0],
			'store_id' => $osi[1],
			'item_id' => $osi[2]
		]);
		$orderValue = $resOrderValue->fetch_assoc();
		return [
			'provider' => 'Autopiter',
			'provider_id' => $orderValue['provider_id'],
			'store' => $orderValue['cipher'],
			'store_id' => $orderValue['store_id'],
			'order_id' => $orderValue['order_id'],
			'item_id' => $orderValue['item_id'],
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
		$providerBrend = Provider::getProviderBrend(self::getParams()->provider_id, $brend);
		$output = $providerBrend ? $providerBrend : $brend;
		$brends[$brend] = $output;
		return $output;
	}
	private static function getArticleIdByBrendAndArticle($brend, $article){
		$client = self::getClient();
		try{
			$result = $client->FindCatalog(["Number" => $article]);
		}
		catch(\SoapFault $e){
			return false;
		}
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
		if (!parent::getIsEnabledApiSearch(self::getParams()->provider_id)) return false;
		if (!parent::isActive(self::getParams()->provider_id)) return false;

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
			$i = 0;
			foreach($PriceIdResult->GetPriceIdResult->PriceSearchModel as $model){
				if ($i > 4) break;
				self::parseSearchModel($model);
				$i++;
			}
		}
		else self::parseSearchModel($PriceIdResult->GetPriceIdResult->PriceSearchModel);
	}
	private static function getStoreID($model){
		$providerStore = Provider::getInstanceDataBase()->select_one('provider_stores', 'id', "`title` = '{$model->SellerId}' AND `provider_id` = " . self::getParams()->provider_id);
		if ($providerStore) return $providerStore['id'];
		$res = Provider::getInstanceDataBase()->insert('provider_stores', [
			'title' => $model->SellerId, 
			'provider_id' => self::getParams()->provider_id,
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
		try{
			$basket = self::getClient()->GetBasket();
		}
		catch(\SoapFault $e){
			return false;
		}
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
		debug($model);
		$item = [
			'DetailUid' => $model->DetailUid,
			'Comment' => Autoeuro::getStringBasketComment($params),
			'SalePrice' => $model->SalePrice,
			'Quantity' => $params['quan']
		];
		$resInsertToBasket = self::getClient($params['typeOrganization'])->InsertToBasket(['Items' => [
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
		$clients = [
			'entity' => self::getClient(),
			'private' => self::getClient('private')
		];
		foreach($clients as $client){
			$res = $client->MakeOrderFromBasket();
			$itemCart = & $res->Items->ResponseCodeItemCart;
			if (!$itemCart) return false;
			if (is_array($itemCart)){
				foreach($itemCart as $ic) self::parseSendOrderItemCart($ic);
			}
			else self::parseSendOrderItemCart($itemCart);
		}
	}
	private static function parseSendOrderItemCart($model){
		$osi = explode('-', $model->Item->Comment);
//		if ($model->Code->ResponseCode == 0){
			$resOrderValue = $orderValue = OrderValue::get([
				'order_id' => $osi[0],
				'store_id' => $osi[1],
				'item_id' => $osi[2]
			]);
			OrderValue::changeStatus(11, $resOrderValue->fetch_assoc());
//		}
		/*else{
			Log::insert([
				'text' => 'Ошибка отправка заказа',
				'additional' => "osi: " . $model->Item->Comment
			]);
		}*/
	}
	public static function getCoincidences($search){
		if (!parent::getIsEnabledApiSearch(self::getParams()->provider_id)) return false;
		if (!parent::isActive(self::getParams()->provider_id)) return false;
		$client = self::getClient();
        try{
            $result = $client->FindCatalog(["Number" => $search]);
            $items = $result->FindCatalogResult->SearchCatalogModel;
        }
		catch(\SoapFault $e){
			return false;
		}
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
