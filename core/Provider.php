<?php
namespace core;
abstract class Provider{
	protected abstract static function getItemsToOrder(int $provider_id);

	public static function getProviderTitle($provider_id){
		return self::getInstanceDataBase()->getField('providers', 'title', 'id', $provider_id);
	}
	public static function getProviderAPITitle($provider_id){
		return self::getInstanceDataBase()->getField('providers', 'api_title', 'id', $provider_id);
	}
	public static function get(){
		$res = self::getInstanceDataBase()->select('providers', 'id,title', '', 'title');
		return $res;
	}
	public static function getProviderBasket($provider_id, $flag = NULL): \mysqli_result
	{
		return self::getInstanceDataBase()->query("
			SELECT
				pb.order_id,
				pb.store_id,
				pb.item_id,
				ps.title AS store,
				ps.cipher,
				ov.quan,
				ov.price,
				i.article,
				IF(i.title_full != '', i.title_full, i.title) AS title_full,
				IF(pbr.title IS NOT NULL, pbr.title, b.title) AS brend,
				u.id AS user_id,
				p.title AS provider,
				u.user_type
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
				p.id = $provider_id AND pb.response IS NULL
		", $flag);
	}
	public function addToProviderBasket($ov){
		self::getInstanceDataBase()->insert('provider_basket', [
			'order_id' => $ov['order_id'],
			'store_id' => $ov['store_id'],
			'item_id' => $ov['item_id'],
		]);
		OrderValue::changeStatus(7, $ov);
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
	protected function updateProviderBasket(array $params, array $fields){
		return self::getInstanceDataBase()->update('provider_basket', $fields, self::getWhere($params));
	}
	public static function isAdminArea(){
		if (preg_match('/^\/admin/', $_SERVER['REQUEST_URI'])) return true;
		else return false;
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
		else return new DataBase();
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
			$array['http']['content'] = http_build_query($data);
		}
		$context = stream_context_create($array);
		try{
			$res = file_get_contents($url, false, $context);
			if ($res === false) return false;
		} catch(\Exception $e){}
		$GLOBALS['response_header'] = $http_response_header;
		return $res;
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
	static public function isInBasket($ov){
		$api_title = self::getProviderAPITitle($ov['provider_id']);
		if (!$api_title) return false;
		$api_title = 'core\Provider\\'.$api_title;
		$provider = new $api_title;
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
}
