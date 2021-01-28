<?php
namespace core;
class UserIPS{

	private const MaxAuthorizatedConnections = 1000;
	private const MaxNonAuthorizatedConnections = 100;
	private const MaxPeriodHours = 24;

	public static $isBlockedUser;

	public static function getHoursBetweenTwoDays($start, $end){
		$first = new \DateTime($start);
		$last = new \DateTime($end);
		$diff = $last->diff($first);
		return $diff->h + ($diff->days * 24);
	}

	public static function registerIP($params){
		$IPInfo = self::getIPInfo($params['ip']);
		if (!$params['user_id']){
			$params['user_id'] = 'DEFAULT';
			$maxConnections = self::MaxNonAuthorizatedConnections;
		} 
		else $maxConnections = self::MaxAuthorizatedConnections;
		
		self::$isBlockedUser = $IPInfo['count_connections'] > $maxConnections;
		
		$hours = self::getHoursBetweenTwoDays($IPInfo['first'], $IPInfo['last']);
		if ($hours > self::MaxPeriodHours){
			self::$isBlockedUser = false;
			self::resetFirstDate($params['ip']);
		}

		if ($params['view'] == 'exceeded_connections' && self::$isBlockedUser) return false;

		if (!$IPInfo) return self::insert($params);
		if (!$IPInfo['last']) return self::insert($params);


		if (self::$isBlockedUser) return self::handleRefuseAccess();

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

	public static function getIPInfo($ip){
		return $GLOBALS['db']->select_one('user_ips', '*', "`ip` = '$ip'");
	}

	private static function handleRefuseAccess(){

		message("Превышено количество запросов. Авторизуйтесь для продолжения", false);
		header("Location: /exceeded_connections");
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
