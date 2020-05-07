<?php
namespace core;
class Managers{
	public static $permissions;

	private static function getInstanceDatabase(){
		return $GLOBALS['db'];
	}
	public static function getGroups($params = array()){
		$where = '';
		if (isset($params['id']) && $params['id']) $where .= "mg.id = {$params['id']} AND ";
		if ($where){
			$where = substr($where, 0, -5);
			$where = "WHERE $where";
		} 
		$res = self::getInstanceDatabase()->query("
			SELECT
				mg.*
			FROM
				#manager_groups mg
			$where
			ORDER BY
				mg.title
		", '');
		return $res;
	}
	/**
	 * [get description]
	 * @param  array  $params id
	 * @return mysqli_result
	 */
	public static function get($params = array()): \mysqli_result
	{
		$where = '';
		if (isset($params['id']) && $params['id']) $where .= "m.id = {$params['id']} AND ";
		if ($where){
			$where = substr($where, 0, -5);
			$where = "WHERE $where";
		} 
		$res = self::getInstanceDatabase()->query("
			SELECT
				m.*,
				mg.title AS group_title
			FROM
				#managers m
			LEFT JOIN
				#manager_groups mg ON mg.id = m.group_id
			$where
			ORDER BY
				mg.title, m.first_name, m.last_name
		", '');
		return $res;
	}
	public static function addGroup($fields){
		$res = self::getInstanceDatabase()->insert('manager_groups', [
			'title' => $fields['title'],
			'permissions' => json_encode($fields['permissions']),
		]/*, ['print' => true]*/);
		if ($res === true) return self::getInstanceDatabase()->last_id();
		else return $res;
	}
	public static function add($fields){
		$res = self::getInstanceDatabase()->insert('managers', [
			'login' => $fields['login'],
			'first_name' => $fields['first_name'],
			'last_name' => $fields['last_name'],
			'password' => md5($fields['password']),
			'is_blocked' => $fields['is_blocked'],
			'group_id' => $fields['group_id']
		]/*, ['print' => true]*/);
		if ($res === true) return self::getInstanceDatabase()->last_id();
		else return $res;
	}
	public static function update($id, $fields){
		$array = [
			'login' => $fields['login'],
			'first_name' => $fields['first_name'],
			'last_name' => $fields['last_name'],
			'is_blocked' => $fields['is_blocked'],
			'group_id' => $fields['group_id']
		];
		if (isset($fields['password']) && $fields['password']) $array['password'] = md5($fields['password']);
		return self::getInstanceDatabase()->update('managers', $array, "`id` = $id");
	}
	public static function updateGroup($id, $fields){
		$array = [
			'title' => $fields['title'],
			'permissions' => json_encode($fields['permissions']),
		];
		return self::getInstanceDatabase()->update('manager_groups', $array, "`id` = $id");
	}
	public static function getPermissions($group_id){
		return self::getInstanceDatabase()->getFieldOnID('manager_groups', $group_id, 'permissions');
	}
	public function isActionForbidden(string $view, string $action)
	{
		if (isset(self::$permissions[$view][$action])) return false;
		else return true;
	}
	public static function isAccessForbidden(string  $view = null)
	{
		if (in_array($view, Config::$defaultPermissions)) return false;
		foreach(self::$permissions as $key => $value){
			if (in_array($view, Config::$pagesViews[$key])) return false;
		}
		return true;
	}
	public static function handlerAccessNotAllowed($manager = []){
		die("Доступ заперещен");
	}
	public static function isActiveMenuGroup($subgroup, $view){
		foreach($subgroup as $key => $value){
			if ($value == $view) return true;
		}
		return false;
	}
}
