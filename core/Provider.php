<?php
namespace core;
class Provider{
	public static function get(){
		$res = $GLOBALS['db']->select('providers', 'id,title', '', 'title');
		return $res;
	}
	public static function getIsDisabled($provider_id){
		if (self::isAdminArea()) return 0;
		$db = isset($GLOBALS['db']) ? $GLOBALS['db'] : new DataBase();
		return $db->getFieldOnID('providers', $provider_id, 'is_disabled');
	}
	protected static function isAdminArea(){
		if (preg_match('/^\/admin/', $_SERVER['REQUEST_URI'])) return true;
		else return false;
	}
}