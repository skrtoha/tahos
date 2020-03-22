<?php
namespace core;
class Log{
	public static function insert($params, $flag){
		return $GLOBALS['db']->insert(
			'logs',
			$params,
			$flag
		);
	}
}