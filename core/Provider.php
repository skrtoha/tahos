<?php
namespace core;
class Provider{
	public static function get(){
		$res = $GLOBALS['db']->select('providers', 'id,title', '', 'title');
		return $res;
	}
}