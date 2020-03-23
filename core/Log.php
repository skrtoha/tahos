<?php
namespace core;
class Log{
	public static function insert($params, $flag = null){
		return $GLOBALS['db']->insert(
			'logs',
			$params,
			$flag
		);
	}
	/**
	 * insert into log, if there is an Exception
	 * @param  Exception $e 
	 * @param  array $params 'text' - requeired, 'query' - optional
	 * @return boolean true if inserted successfully
	 */
	public static function insertThroughException($e, $params = []){
		return self::insert([
			'url' => $_SERVER['REQUEST_URI'],
			'query' => isset($params['query']) ? $params['query'] : '',
			'trace' => $e->getTraceAsString(),
			'text' => $e->getMessage()
		]);
	}
}