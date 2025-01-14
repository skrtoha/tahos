<?php
namespace core;
use core\Provider\Rossko;
use core\Provider\Tahos;

abstract class Provider{
	private static $ignoreProvidersForMarkups = [18, 14];
	private static $counterDaysDelivery;

    public const ACTIVE_ONLY_PRIVATE = 'only_private';
    public const ACTIVE_ONLY_ENTITY = 'only_entity';
    public const ACTIVE_BOTH = 'both';

	public static $todayIssue;
    public static $statusAPI = [];

	protected abstract static function getItemsToOrder(int $provider_id);

    public static function getProviderAddressList($provider_id){
        $output = "";
        switch ($provider_id){
            case 15: $output = Rossko::getAddressList();
        }
        return $output;
    }

	public static function getProviderTitle($provider_id){
		$providers = self::get();
		return $providers[$provider_id]['title'];
	}
	public static function getProviderAPITitle($provider_id){
		$providers = self::get();
		return $providers[$provider_id]['api_title'];
	}
	private static function getProviderIDByAPITitle($api_title){
		$providers = self::get();
		foreach($providers as $provider){
			if ($provider['api_title'] == $api_title) return $provider['id'];
		}
		return false;
	}

	/**
	 * @param  array provider_id | api_title, typeOrganization
	 * @return [type]
	 */
	public static function getApiParams($inputData){
		static $params;

		if (!$inputData['api_title']) $api_title = self::getProviderAPITitle($inputData['provider_id']);
		else $api_title = $inputData['api_title'];

		if (!isset($inputData['provider_id'])) $provider_id = self::getProviderIDByAPITitle($api_title);
		else $provider_id = $inputData['provider_id'];

		$typeOrganization = $inputData['typeOrganization'];

        if (isset($params[$provider_id]->$typeOrganization)) return $params[$provider_id]->$typeOrganization;

		if (!$provider_id) return false;

		$params[$provider_id] = json_decode(\core\Setting::get('api_settings', $provider_id));

        if (!$params[$provider_id]->entity->isActive && $params[$provider_id]->private->isActive){
            $params[$provider_id]->entity = $params[$provider_id]->private;
            self::$statusAPI[$provider_id] = self::ACTIVE_ONLY_PRIVATE;
        }
        elseif (!$params[$provider_id]->private->isActive && $params[$provider_id]->entity->isActive){
            $params[$provider_id]->private = $params[$provider_id]->entity;
            self::$statusAPI[$provider_id] = self::ACTIVE_ONLY_ENTITY;
        }
        else{
            self::$statusAPI[$provider_id] = self::ACTIVE_BOTH;
        }

		return $params[$provider_id]->$typeOrganization;
	}
	public static function get(){
		static $providers;
		if (!$providers) {
			$result = self::getInstanceDataBase()->select('providers', '*', '', 'title', true);
			foreach($result as $value) $providers[$value['id']] = $value;
		}
		return $providers;
	}
	protected static function isActive($provider_id){
		$providers = self::get();
		return $providers[$provider_id]['is_active'];
	}
	public static function getEmailPrices(){
		static $emailPrices;
		if ($emailPrices) return $emailPrices;
		$res = Database::getInstance()->query("SELECT store_id FROM #email_prices", '');
		if (!$res->num_rows) return false;
		foreach($res as $value) $emailPrices[] = $value['store_id'];
		return $emailPrices;
	}
	public static function getProviderBasket($provider_id, $flag = NULL): \mysqli_result
	{
        if (is_array($provider_id)) $where = "p.id IN (".implode(',', $provider_id).")";
        else $where = "p.id IN ($provider_id)";
        $where .= " AND pb.response IS NULL";
		return self::getInstanceDataBase()->query("
			SELECT
				pb.order_id,
				pb.store_id,
				pb.item_id,
				ps.title AS store,
				ps.cipher,
				ps.provider_id,
				ov.quan,
				ov.price,
				i.article,
				IF(i.title_full != '', i.title_full, i.title) AS title_full,
				IF(pbr.title IS NOT NULL, pbr.title, b.title) AS brend,
				u.id AS user_id,
				p.title AS provider,
				p.api_title,
				CASE
				    WHEN o.bill_type = ".User::BILL_CASH." THEN 'private' 
				    WHEN o.bill_type = ".User::BILL_CASHLESS." THEN 'entity' 
				END as typeOrganization,
				o.pay_type
			FROM
				#provider_basket pb
			LEFT JOIN
				#items i ON pb.item_id = i.id
			LEFT JOIN
				#brends b ON b.id = i.brend_id
			LEFT JOIN 
				#orders_values ov ON ov.order_id = pb.order_id AND ov.store_id = pb.store_id AND ov.item_id = pb.item_id
			LEFT JOIN
				#provider_stores ps ON ps.id = pb.store_id
			LEFT JOIN
				#provider_brends pbr ON pbr.brend_id = i.brend_id AND pbr.provider_id = ps.provider_id
			LEFT JOIN
				#providers p ON p.id = ps.provider_id
			LEFT JOIN
				#orders o ON o.id = pb.order_id
			LEFT JOIN
				#users u ON u.id = o.user_id
			WHERE
				$where
		", $flag);
	}
    /**
     * @param $params
     * @return array|bool|\mysqli_result|string|string[]
     */
	public static function updatePriceUpdated($params){
		if (isset($params['store_id']))
            return self::getInstanceDataBase()->query(
			    "UPDATE #provider_stores
                    SET `price_updated` = CURRENT_TIMESTAMP
                    WHERE id = {$params['store_id']}", ''
            );
		if (isset($params['provider_id'])) return
			self::getInstanceDataBase()->query("UPDATE #provider_stores SET `price_updated` = CURRENT_TIMESTAMP WHERE provider_id = {$params['provider_id']}", '');
	    return false;
	}
	public static function getDiliveryDate($workSchedule, $calendar, $deliveryDays){
		$dateTimeOut = new \DateTime();
		$dateTimeNow = new \DateTime();
		self::$counterDaysDelivery = 0;
		self::$todayIssue = true;

		if (!$workSchedule && !$calendar){
			$dateTimeOut->add(new \DateInterval('P' . abs($deliveryDays) . 'D'));
			self::$todayIssue = false;
			return $dateTimeOut->format('d.m');
		}

		self::$counterDaysDelivery = 0;
		$date = $dateTimeNow->format('d.m.Y');
		$dayWeek = $dateTimeNow->format('l');
		if (isset($calendar[$date])){
			if ($calendar[$date]['isWorkDay']){
				$dateTimeInterval = \DateTime::createFromFormat(
					'd.m.Y G:i',
					$date . ' ' . $calendar[$date]['hours'] . ':' . $calendar[$date]['minutes']
				);
				if ($dateTimeNow >= $dateTimeInterval){
					self::$counterDaysDelivery = - 1;
					self::$todayIssue = false;
				}
			}
			else{
				self::$counterDaysDelivery = - 1;
				self::$todayIssue = false;
			}
		}
		elseif (isset($workSchedule[$dayWeek])){
			if ($workSchedule[$dayWeek]['isWorkDay']){
				$dateTimeInterval = \DateTime::createFromFormat(
					'd.m.Y G:i',
					$date . ' ' . $workSchedule[$dayWeek]['hours'] . ':' . $workSchedule[$dayWeek]['minutes']
				);
				if ($dateTimeNow >= $dateTimeInterval){
					self::$counterDaysDelivery = - 1;
					self::$todayIssue = false;
				}
			}
			else{
				self::$counterDaysDelivery = - 1;
				self::$todayIssue = false;
			}
		}
		while(self::$counterDaysDelivery < $deliveryDays){
			self::$counterDaysDelivery++;
			$dateTimeOut->add(new \DateInterval('P1D'));

			// debug($workSchedule[$dayWeek], "$i, ".$dateTimeOut->format('d.m.Y'));

			$date = $dateTimeOut->format('d.m.Y');
			$dayWeek = $dateTimeOut->format('l');

			if (isset($calendar[$date])){
				if (!$calendar[$date]['isWorkDay']) self::$counterDaysDelivery--;
				self::$todayIssue = false;
			}
			elseif (isset($workSchedule[$dayWeek])){
				if (!$workSchedule[$dayWeek]['isWorkDay']) self::$counterDaysDelivery--;
				self::$todayIssue = false;
			}
		}
		return $dateTimeOut->format('d.m');
	}
	public static function addToProviderBasket($ov){
        $db = Database::getInstance();
        $db->startTransaction();
		Database::getInstance()->insert('provider_basket', [
			'order_id' => $ov['order_id'],
			'store_id' => $ov['store_id'],
			'item_id' => $ov['item_id'],
		]);
		OrderValue::changeStatus(7, $ov, true);
        $db->commit();
		return true;
	}
	public static function getIsEnabledApiSearch($provider_id){
		static $enabledArrayApiSearch;
		if (!$enabledArrayApiSearch){
			$output = array();
			$res = self::getInstanceDataBase()->query("
				SELECT
					id, is_enabled_api_search
				FROM
					#providers
				WHERE
					is_enabled_api_search = 1
			", '');
			if (!$res->num_rows) return false;
			foreach($res as $value) $output[] = $value['id'];
			$enabledArrayApiSearch = $output;
		}
		if (in_array($provider_id, $enabledArrayApiSearch)) return true;
		else return false;
	}
	public static function updateProviderBasket(array $params, array $fields){
		return self::getInstanceDataBase()->update('provider_basket', $fields, self::getWhere($params));
	}
	/**
	 * gets list with is_disabled_api_order =1
	 * @return array array with is_disabled_api_order
	 */
	public static function getIsEnabledApiOrder($provider_id){
		static $enabledArrayApiOrder;
		if (!$enabledArrayApiOrder){
			$output = array();
			$res = self::getInstanceDataBase()->query("
				SELECT
					id, is_enabled_api_order
				FROM
					#providers
				WHERE
					is_enabled_api_order = 1
			", '');
			if (!$res->num_rows) return false;
			foreach($res as $value) $output[] = $value['id'];
			$enabledArrayApiOrder = $output;
		}
		if (in_array($provider_id, $enabledArrayApiOrder)) return true;
		else return false;
	}
	public static function getInstanceDataBase(){
		if (isset($GLOBALS['db'])) return $GLOBALS['db'];
		else return new Database();
	}
	public static function getWhere($array){
		return "`order_id`={$array['order_id']} AND `store_id`={$array['store_id']} AND `item_id`={$array['item_id']}";
	}
	public static function getComparableString($str){
		if (!$str) return false;
		$str = preg_replace('/[^\wа-яA-Z]/i', '', $str);
		return mb_strtolower($str);
	}
	/**
	 * gets response from remote server by url
	 * @param  [string] $url remote url server
	 * @param  [array] $data if is null then method is get
	 * @return [string] response from server
	 */
	public static function getUrlData($url, $data = array(), $header = null){
		$context = array();
		$url = str_replace(' ', '%20', $url);
		if ($header) $array['http']['header'] = $header;
		if (empty($data)){
			$array['ssl']['verify_peer'] = false;
			$array['http']['method'] = 'GET';
		}
		else{
			$array['http']['method'] = 'POST';
			if (is_array($data)) $array['http']['content'] = http_build_query($data);
			if (self::isJSON($data)) $array['http']['content'] = json_encode($data, JSON_UNESCAPED_UNICODE);
		}
		$context = stream_context_create($array);
		try{
			$res = file_get_contents($url, false, $context);
			$GLOBALS['response_header'] = $http_response_header;
			if (!$res) return self::getCurlUrlData($url, $data, $header);
		} catch(\Exception $e){}
		return $res;
	}
	public static function getCurlUrlData($url, $data = [], $header = null){
		$curl = \curl_init();
        $url = str_replace(' ', '+', $url);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, empty($data) ? 'GET' : 'POST');
		if (isset($data['username']) && isset($data['password'])){
			curl_setopt($curl, CURLOPT_USERPWD, $data['username'] . ':' . $data['password']);
			unset($data['username'], $data['password']);
		}
		if (!empty($data)){
			curl_setopt($curl, CURLOPT_POST, true);
			if (is_array($data)) curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
			else curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

		if ($header){
            $arrayHeader = [];
            foreach ($header as $key => $value) $arrayHeader[] = "$key: $value";
			curl_setopt($curl, CURLOPT_HTTPHEADER, $arrayHeader);
		}

		$fileOut = fopen(Config::$tmpFolderPath . '/curl_out.txt', "w");
		curl_setopt ($curl, CURLOPT_VERBOSE, 1);
		curl_setopt ($curl, CURLOPT_STDERR, $fileOut);
		
		$result = curl_exec($curl);
		curl_close($curl);
		return $result;
	}
	public static function isDuplicate($str){
		if (preg_match('/Duplicate/', $str)) return true;
		else return false;
	}
	static public function getRandomString($str_length = 4){
		$str_characters = array (0,1,2,3,4,5,6,7,8,9,'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
		$characters_length = count($str_characters) - 1;
		$string = '';
		for($i = $str_length; $i > 0; $i--) $string .= $str_characters[mt_rand(0, $characters_length)];
		return $string;
	}
	static public function getStringLog(array $ov): \mysqli_result
	{
		return self::getInstanceDataBase()->query("
			SELECT
				DATE_FORMAT(created, '%d.%m.%Y %H:%i:%s') AS 'date',
				`text`
			FROM
				#logs
			WHERE
				`additional` = 'osi: {$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}'
		", '');
	}
	public static function getStoreInfo(int $store_id, $params = ['flag' => '']): array
	{
		static $storeInfo;
		if (isset($storeInfo[$store_id])) return $storeInfo[$store_id];
		$res = self::getInstanceDataBase()->query("
			SELECT
				ps.*,
				DATE_FORMAT(ps.price_updated, '%d.%m.%Y') AS price_updated, 
				p.title AS provider,
				c.rate,
				c.title AS currency,
				p.cron_hours,
				p.cron_minutes
			FROM
				#provider_stores ps
			LEFT JOIN
				#currencies c ON c.id = ps.currency_id
			LEFT JOIN
				#providers p ON p.id = ps.provider_id
			WHERE
				ps.id = $store_id
		", $params['flag']);
		if (!$res->num_rows) return false;
		$storeInfo[$store_id]  = $res->fetch_assoc();
		return $storeInfo[$store_id];
	}
	/**
	 * increases price taking into account markup of provider and discount of user
	 * @param  double $price
	 * @param  int $store_id
	 * @param  int $user_id
	 * @return int
	 */
	public static function getPriceWithMarkups($price, int $store_id, int $user_id)
	{
		$storeInfo = self::getStoreInfo($store_id, '');
		$res_user = User::get(['user_id' => $user_id]);
        foreach($res_user as $value) $userInfo = $value;

		$price = $price + $price * $storeInfo['percent'] / 100;
		$price -= $price * $userInfo['discount'] / 100;
		return ceil($price);
	}
	/**
	 * gets price from provider by article and brend
	 * @param  array  $params provider_id, store_id, article, brend, user_id
	 * @return array price, in_stock
	 */
	public static function getPrice(array $params){
		if (!$params['provider_id']) return [];
		if (!$params['store_id']) return [];

		$provider = self::getInstanceProvider($params['provider_id']);
		if (!$provider || !self::getIsEnabledApiOrder($params['provider_id'])){
			return self::getStoreItem($params['store_id'], $params['item_id']);
		}
		$price = $provider::getPrice($params);
		if (!$price) return [
			'price' => 0,
			'available' => -1
		];
		if (!$price['price']) return [];
		if (!in_array($params['provider_id'], self::$ignoreProvidersForMarkups)){
			$price['price'] = self::getPriceWithMarkups($price['price'], $params['store_id'], $params['user_id']);
		}
        $price['price'] = floor($price['price']);
		return $price;
	}
	private static function getInstanceProvider($provider_id){
		if (!$provider_id) throw new Exception\InvalidProviderIDException;
		$api_title = self::getProviderAPITitle($provider_id);
		if (!$api_title) return false;
		$api_title = 'core\Provider\\'.$api_title;
		return new $api_title;
	}
	static public function isInBasket($ov){
		$provider = self::getInstanceProvider($ov['provider_id']);
		if (!$provider) return false;
		return $provider::isInBasket($ov);
	}
	static public function removeFromBasket($ov){
		$api_title = 'core\Provider\\'.self::getProviderAPITitle($ov['provider_id']);
		$provider = new $api_title;
		if ($provider::removeFromBasket($ov)){
			OrderValue::changeStatus(5, $ov);
			return true;
		}
		else return false;
	}
	/**
	 * get type of user by order_id
	 * @param  int $order_id order_id
	 * @return string private|entity
	 */
	public static function getUserTypeByOrderID(int $order_id): string
	{
		$res_user = self::getInstanceDataBase()->query("
			SELECT
				u.id,
				u.user_type
			FROM
				#orders o
			LEFT JOIN 
				#users u ON u.id = o.user_id
			WHERE
				o.id = $order_id
		", '');
		$user = $res_user->fetch_assoc();
		return $user['user_type'];
	}
	public static function getStoreItem(int $store_id, int $item_id)
	{
		$storeItem = self::getInstanceDataBase()->select_one('store_items', 'price,in_stock', "`item_id` = $item_id AND `store_id` = $store_id");
		return [
			'price' => $storeItem['price'],
			'available' => $storeItem['in_stock']
		];
	}
	protected static function getProviderBrend($provider_id, $brend): string
	{
        $cacheId = "$provider_id-$brend";
        $result = Cache::get($cacheId);
        if ($result) {
            return $result;
        }
		$res = self::getInstanceDataBase()->query("
			SELECT
				pb.title
			FROM
				#provider_brends pb
			LEFT JOIN
				#brends b ON b.id = pb.brend_id
			WHERE
				b.title = '$brend' AND pb.provider_id = $provider_id
		", '');
		if (!$res->num_rows) return $brend;
		$array = $res->fetch_assoc();
        Cache::set($cacheId, $array['title']);
		return $array['title'];
	}

	public static function clearStoresItems(array $params = []){
        $where = '';
	    if (isset($params['provider_id']) && $params['provider_id']){
            $where .= "ps.provider_id = {$params['provider_id']} AND ";
        }

        if (!empty($params)){
			foreach($params as $key => $value){
				switch($key){
                    case '!main_stores':
                        $selfStores = Tahos::getSelfStores();
                        $where .= "ps.id NOT IN (".implode(',', array_column($selfStores, 'id')).") AND ";
                        break;
				}
			}
		}

		if ($where){
            $where = substr($where, 0, -5);
            $where = "WHERE $where";
        }

		self::getInstanceDataBase()->query("
			DELETE si FROM #store_items si
			LEFT JOIN #provider_stores ps ON ps.id = si.store_id
			$where
		", '');

        self::getInstanceDataBase()->query("
		    UPDATE
                #provider_stores ps
            set ps.price_updated = current_timestamp()
            $where
		");
	}

	public static function getCommonItemsToOrders(){
		$res_providers = self::getInstanceDataBase()->query("
			SELECT id, title, api_title FROM #providers WHERE api_title IS NOT NULL
		", '');
		$items = array();
		foreach($res_providers as $p){
			switch($p['id']){
				case 13://'М Партс'
				case 2://'Армтек':
				case 6://'Авторусь':
				case 15://'Rossko':
				case 17://'Forum-Avto':
                case 18://'Autoeuro':*/
                case 30://'ShateM':
                    $output = Provider\Abcp::getItemsToOrder($p['id']);
                    break;
				default:
					eval("\$output = core\\Provider\\".$p['api_title']."::getItemsToOrder(".$p['id'].");");
			}
			if (empty($output)) continue;
			foreach($output as $value) $items[$value['provider']][] = $value;
		}
		return $items;
	}

	public static function getCountItemsToOrders($items){
		$count = 0;
		foreach($items as $providerTitle => $item) $count += count($item);
		return $count;
	}

	public static function prepareSettingsAPI($provider_id){
		$provider = self::getInstanceProvider($provider_id);
		$fieldsForSettings = $provider::$fieldsForSettings;
		$output = [
			'private' => [],
			'entity' => []
		];
		foreach($fieldsForSettings as $field){
			$output['private'][$field] = '';
			$output['entity'][$field] = '';
		}
		return $output;
	}

	public static function isJSON($string){
		return is_string($string) && is_array(json_decode($string, true)) ? true : false;
	}

    public static function getCacheData($cacheId) {
        if (Cache::useArticleCache()) {
            $result = Cache::get($cacheId);
            if ($result) {
                return true;
            }
            else {
                return false;
            }
        }
        return false;
    }

    public static function setCacheData($cacheId) {
        if (Cache::useArticleCache()) {
            Cache::set($cacheId, 1, Cache::getDuration());
        }
    }
}
