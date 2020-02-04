<?php
namespace core;
class Item{
	/**
	 * updates item
	 * @param  array $fields field => value
	 * @param  array $where  id => 10
	 * @return boolean true if updated sucessfully
	 */
	public static function update($fields, $where){
		$item = $GLOBALS['db']->select_one('items', "isBlocked", "`id`={$where['id']}");
		debug($_SERVER);
		var_dump(strpos($_SERVER['REQUEST_URI'], '/admin/?view=item&id='));
		if ($item['isBlocked'] && strpos($_SERVER['REQUEST_URI'], '/admin/?view=item&id=') === false) return false;
		if (!$where) return false;
		if ($where['id']) return false;
		$conditions = '';
		foreach($where as $key => $value) $conditions .= "`{$key}` = '{$value}' AND ";
		$conditions = substr($value, 0, -5);
		return $GLOBALS['db']->update('items', $fields, $conditions);
	}
}
