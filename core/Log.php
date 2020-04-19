<?php
namespace core;
class Log{
	/**
	 * inserts a record to Log
	 * @param  array $params 
	 *         	text - required,
	 *         	url, query, trace - optional
	 * @param  bool|null $flag   [description]
	 * @return mixed true - if successfully inserted, string - error
	 */
	public static function insert(array $params, array $flag = null): bool
	{
		if (!isset($params['url'])) $params['url'] = $_SERVER['REQUEST_URI'];
		return $GLOBALS['db']->insert(
			'logs',
			$params,
			$flag
		);
	}
	/**
	 * insert into log, if there is an Exception
	 * @param  Exception $e 
	 * @param  string $additional additional information about log
	 * @return boolean true if inserted successfully
	 */
	public static function insertThroughException($e, array $params = []){
		return self::insert([
			'url' => isset($params['url']) ? $params['url'] : $_SERVER['REQUEST_URI'],
			'query' => isset($params['query']) ? $params['query'] : '',
			'trace' => $e->getTraceAsString(),
			'additional' => isset($params['additional']) ? $params['additional'] : '',
			'text' => $e->getMessage()
		]);
	}
}