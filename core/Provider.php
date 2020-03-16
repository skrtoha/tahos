<?php
namespace core;
class Provider{
	public static function get(){
		$res = $GLOBALS['db']->select('providers', 'id,title', '', 'title');
		return $res;
	}
	public static function getIsDisabledApiSearch($provider_id){
		static $disabledArrayApiSearch;
		if (!$disabledArrayApiSearch){
			$db = isset($GLOBALS['db']) ? $GLOBALS['db'] : new DataBase();
			$output = array();
			$res = $db->query("
				SELECT
					id, is_disabled_api_search
				FROM
					#providers
				WHERE
					is_disabled_api_search = 1
			", '');
			if (!$res->num_rows) return false;
			foreach($res as $value) $output[] = $value['id'];
			$disabledArrayApiSearch = $output;
		}	
		if (in_array($provider_id, $disabledArrayApiSearch)) return true;
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
	public static function getIsDisabledApiOrder($provider_id){
		static $disabledArrayApiOrder;
		if (!$disabledArrayApiOrder){
			$db = isset($GLOBALS['db']) ? $GLOBALS['db'] : new DataBase();
			$output = array();
			$res = $db->query("
				SELECT
					id, is_disabled_api_order
				FROM
					#providers
				WHERE
					is_disabled_api_order = 1
			", '');
			if (!$res->num_rows) return false;
			foreach($res as $value) $output[] = $value['id'];
			$disabledArrayApiOrder = $output;
		}	
		if (in_array($provider_id, $disabledArrayApiOrder)) return true;
		else return false;
	}
	public static function showErrorDisabledApiOrder(){
		die("API заказов для этого поставщика отключено. <a href=\"{$_SERVER['HTTP_REFERER']}\">Назад</a>");
	}
}
