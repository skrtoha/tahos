<?php
namespace core;
class UserIPS{

	private const MaxAuthorizatedConnections = 1000;
	private const MaxNonAuthorizatedConnections = 200;
	private const MaxPeriodHours = 24;

	public static function getHoursBetweenTwoDays($start, $end){
		$first = new \DateTime($start);
		$last = new \DateTime($end);
		$diff = $last->diff($first);
		return $diff->h + ($diff->days * 24);
	}

	public static function registerIP($params){
		if (!$params['user_id']){
			$params['user_id'] = 'DEFAULT';
			$maxConnections = self::MaxNonAuthorizatedConnections;
		} 
		else $maxConnections = self::MaxAuthorizatedConnections;
		$apiInfo = self::getApiInfo($params['ip']);
		if (!$apiInfo) return self::insert($params);
		if (!$apiInfo['last']) return self::insert($params);
		$hours = self::getHoursBetweenTwoDays($apiInfo['first'], $apiInfo['last']);
		if ($hours > self::MaxPeriodHours) return self::resetFirstDate($params['ip']);
		if ($apiInfo['count_connections'] > $maxConnections) return self::handleRefuseAccess();
		return self::insert($params);
	}

	private static function resetFirstDate($ip){
		return $GLOBALS['db']->query("
			UPDATE
				#user_ips
			SET
				`first` = CURRENT_TIMESTAMP,
				`last` = NULL,
				`count_connections` = 1
			WHERE
				`ip` = '$ip'
		", '');
	}

	public static function getApiInfo($ip){
		return $GLOBALS['db']->select_one('user_ips', '*', "`ip` = '$ip'");
	}

	private static function handleRefuseAccess(){
		die("Превышено количество запросов");
	}

	private static function insert($params){
		return $GLOBALS['db']->query("
			INSERT INTO
				#user_ips (ip, user_id)
			VALUES
				('{$params['ip']}', {$params['user_id']})
			ON DUPLICATE KEY UPDATE
				`count_connections` = `count_connections` + 1,
				`user_id` = {$params['user_id']}
		", '');
	}
}
