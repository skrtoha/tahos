<?php
namespace core;
class Managers{
	public static $pages = [
		'Номенклатура' => [
			'Добавление',
			'Удаление',
			'Изменение'
		],
		'Обновление цен',
		'Доставки',
		'Заказы',
		'Финансовые операции',
		'Категории товаров' => [
			'Добавление',
			'Удаление',
			'Изменение'
		],
		'Подкатегории' => [
			'Добавление',
			'Удаление',
			'Изменение'
		],
		'Бренды товаров' => [
			'Добавление',
			'Удаление',
			'Изменение'
		],
		'Сообщения',
		'Валюта',
		'Прайсы',
		'Поставщики' => [
			'Добавление',
			'Удаление',
			'Изменение'
		],
		'Точки выдачи' => [
			'Добавление',
			'Удаление',
			'Изменение'
		],
		'Соединения',
		'Пользователи' => [
			'Добавление',
			'Удаление',
			'Изменение'
		],
		'Менеджеры' => [
			'Добавление',
			'Удаление',
			'Изменение'
		],
		'Оригинальные каталоги'  => [
			'Добавление',
			'Удаление',
			'Изменение'
		],
		'Выдачи товара',
		'Тексты',
		'Файлы',
		'Отчеты'
	];
	private static $pagesViews = [
		'Номенклатура' => ['items'],
		'Обновление цен' => ['min_prices'],
		'Доставки' => ['sendings'],
		'Заказы' => ['orders'],
		'Финансовые операции' => ['funds'],
		'Категории товаров' => ['categories'],
		'Подкатегории' => ['category'],
		'Бренды товаров' => ['brends'],
		'Сообщения' => ['messages', 'correspond'],
		'Валюта' => ['currencies', 'get_currencies'],
		'Прайсы' => ['prices'],
		'Поставщики' => ['providers'],
		'Точки выдачи' => ['issues'],
		'Соединения' => ['connections'],
		'Пользователи' => ['users'],
		'Менеджеры' => ['managers'],
		'Оригинальные каталоги' => ['original-catalogs'],
		'Выдачи товара' => ['order_issues'],
		'Тексты' => ['texts'],
		'Файлы' => ['files'],
		'Отчеты' => ['reports']
	];
	private static $defaultPermissions = [
		'authorization',
		'managers',
		'index',
		'cron'
	];
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
	public static function getChecked($p){
		var_dump($p);
		if (is_array($p)){
			foreach($p as $value){
				if ($value) return 'checked';
			}
		}
		if ($p) return 'checked';
		return '';
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
		if (in_array($view, self::$defaultPermissions)) return false;
		foreach(self::$permissions as $key => $value){
			if (in_array($view, self::$pagesViews[$key])) return false;
		}
		return true;
	}
	public static function handlerAccessNotAllowed($manager = []){
		die("Доступ заперещен");
	}
}
