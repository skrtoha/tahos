<?php
namespace core;
class Provider{
	public static function get(){
		$res = $GLOBALS['db']->select('providers', 'id,title', '', 'title');
		return $res;
	}
	public static function getIsDisabled($provider_id){
		return $GLOBALS['db']->getFieldOnID('providers', $provider_id, 'is_disabled');
	}
}