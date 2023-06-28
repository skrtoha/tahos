<?php
namespace core;

/* @global $db \core\Database */

class UserIPS{

	private const MaxNonAuthorizatedConnections = 100;
	private const MaxPeriodHours = 24;

    private static $noRenderView = ['authorization', 'exit'];

	public static $isBlockedUser = false;

	public static function getHoursBetweenTwoDays($start, $end){
		$first = new \DateTime($start);
		$last = new \DateTime($end);
		$diff = $last->diff($first);
		return $diff->h + ($diff->days * 24);
	}

    public static function isAllowedIP($ip): bool
    {
        /** @var Database $db */
        $db = $GLOBALS['db'];

        if ($db->getCount('allowed_ip', "`ip` = '$ip'")) return true;
        return false;
    }

	public static function registerIP($params){
        global $view;
        if (in_array($params['view'], self::$noRenderView)) return false;

        if (self::isAllowedIP($params['ip'])) return true;

        $IPInfo = self::getIPInfo($params['ip']);
		if (!$params['user_id']){
			$params['user_id'] = 'DEFAULT';
			$maxConnections = self::MaxNonAuthorizatedConnections;
		} 
		else $maxConnections = $IPInfo['max_connections'];

        $hours = self::getHoursBetweenTwoDays($IPInfo['first'], $IPInfo['last']);
        if (
            $hours < self::MaxPeriodHours &&
            (int) $IPInfo['count_connections'] > $maxConnections
        ){
            self::$isBlockedUser = true;
            self::insert($params);
        }
        else{
            self::$isBlockedUser = false;
        }

        if ($hours > self::MaxPeriodHours){
            $result = self::resetFirstDate($params['ip']);
            if ($view == 'exceeded_connections' && !self::$isBlockedUser){
                header('Location: /');
                die();
            }
            return $result;
        }

		if ($params['view'] == 'exceeded_connections' && self::$isBlockedUser){
            self::setMessage();
            return false;
        }

		if (!$IPInfo) return self::insert($params);
		if (!$IPInfo['last']) return self::insert($params);

		if (self::$isBlockedUser){
            self::handleRefuseAccess();
            return false;
        }

		return self::insert($params);
	}

    private static function setMessage(){
        if ($_SESSION['user']) return;
        message("Превышено количество запросов. Авторизуйтесь для продолжения", false);
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
        global $db;
        $result = $db->query("
            SELECT
                ui.*,
                u.max_connections
            FROM
                #user_ips ui
            LEFT JOIN
                #users u ON u.id = ui.user_id
            WHERE
                ui.ip = '$ip'
        ", '')->fetch_assoc();
		return $result;
	}

	private static function handleRefuseAccess(){
        self::setMessage();
		header("Location: /exceeded_connections");
        die();
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
