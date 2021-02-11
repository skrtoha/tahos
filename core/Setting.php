<?php
namespace core;
class Setting{
	private static $tableName = 'settings_test';
	public static function get($param1, $param2 = ''){
		static $defaultSettingsOfView;
		if ($param2){
			$view = $param1;
			$name = $param2;
			$resArray = $GLOBALS['db']->select_one(self::$tableName, ['name', 'value'], "`view` = '$view' AND `name` = '$name'");
			return $resArray['value'];
		}
		else{
			$view = $_GET['view'];
			$name = $param1;
			if (!$defaultSettingsOfView){
				$resArray = $GLOBALS['db']->select(self::$tableName, ['name', 'value'], "`view` = '$view'");
				foreach($resArray as $value) $defaultSettingsOfView[$value['name']] = $value['value'];
			} 
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
		return $GLOBALS['db']->query("
			INSERT INTO #". self::$tableName. " (`view`, `name`, `value`) VALUES (
				'$view', '$name', '$value'
			) ON DUPLICATE KEY UPDATE `value` = '$value'
		", '');
		// return $GLOBALS['db']->update_query(self::$tableName, ['value' => $value], "`view` = '$view' AND `name` = '$name'");
	}
}
