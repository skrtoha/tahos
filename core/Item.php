<?php
namespace core;
class Item{
	/**
	 * updates item
	 * @param  array $fields field => value
	 * @param  array $where  id or article and brend_id is required
	 * @return boolean true if updated sucessfully
	 */
	public static function update($fields, $where){
		if (!$where) return false;
		$conditions = '';
		if (strpos($_SERVER['REQUEST_URI'], '/admin/?view=item&id=') !== false) return self::processUpdate($fields, $where);
		if (isset($where['id'])) $conditions = "`id`={$where['id']}";
		elseif (isset($where['article']) && isset($where['brend_id'])) $conditions = "`article`='{$where['article']}' AND `brend_id`='{$where['brend_id']}'";
		$item = $GLOBALS['db']->select('items', "is_blocked", $conditions);
		if ($item['is_blocked'] || empty($item)) return false;
		return self::processUpdate($fields, $where);
	}
	private function processUpdate($fields, $where){
		$conditions = '';
		foreach($where as $key => $value) $conditions .= "`{$key}` = '{$value}' AND ";
		$conditions = substr($conditions, 0, -5);
		return $GLOBALS['db']->update('items', $fields, $conditions);
	}
	public static function clearAnalogies($item_id){
		return $GLOBALS['db']->delete('analogies', "`item_id` = $item_id OR `item_diff` = $item_id");
	}
}
