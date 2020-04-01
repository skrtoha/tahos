<?php
namespace core;
abstract class Provider{
	protected abstract function getItemsToOrder(int $provider_id);
	public static function get(){
		$res = self::getInstanceDataBase()->select('providers', 'id,title', '', 'title');
		return $res;
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
	public static function showErrorDisabledApiOrder(){
		die("API заказов для этого поставщика отключено. <a href=\"{$_SERVER['HTTP_REFERER']}\">Назад</a>");
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
}
