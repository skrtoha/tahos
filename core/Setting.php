<?php
namespace core;
class Setting{
	private static $tableName = 'settings';
	public static function get($param1, $param2 = null, $type = null){
		static $defaultSettingsOfView;
		if ($param2){
			$view = $param1;
			$name = $param2;
			$resArray = Database::getInstance()->select_one(self::$tableName, ['name', 'value'], "`view` = '$view' AND `name` = '$name'");
			return $resArray['value'];
		}
		else{
			$view = $type == null ? $_GET['view'] : $param1;
			$name = $param1;
			if (!$defaultSettingsOfView){
				$resArray = Database::getInstance()->select(self::$tableName, ['name', 'value'], "`view` = '$view'");
				foreach($resArray as $value) $defaultSettingsOfView[$value['name']] = $value['value'];
			}
            if ($type == 'all') return $defaultSettingsOfView;
            return $defaultSettingsOfView[$name];
		}
	}
	public static function update($param1, $param2, $param3 = ''){
		if ($param3){
			$view = $param1;
			$name = $param2;
			$value = $param3;
		}
		else{
			$view = $_GET['view'];
			$name = $param1;
			$value = $param2;
		}
		return Database::getInstance()->query("
			INSERT INTO #". self::$tableName. " (`view`, `name`, `value`) VALUES (
				'$view', '$name', '$value'
			) ON DUPLICATE KEY UPDATE `value` = '$value'
		", '');
		// return Database::getInstance()->update_query(self::$tableName, ['value' => $value], "`view` = '$view' AND `name` = '$name'");
	}
}
