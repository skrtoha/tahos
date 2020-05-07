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
	public static function get($brend_id, $article){
		return $GLOBALS['db']->select_one('items', '*', "`brend_id` = $brend_id AND `article` = '$article'");
	}
	public static function getInstanceDataBase(){
		return $GLOBALS['db'];
	}
	private function processUpdate($fields, $where){
		$conditions = '';
		foreach($where as $key => $value) $conditions .= "`{$key}` = '{$value}' AND ";
		$conditions = substr($conditions, 0, -5);
		try{
			$res = $GLOBALS['db']->update('items', $fields, $conditions);
			if ($res !== true) throw new \Exception($res);
		} catch(\Exception $c){
			Log::insertThroughException($c, [
				'query' => $GLOBALS['db']->last_query,
				'text' => $res
			]);
			return $res;
		}
		return true;
	}
	public static function clearAnalogies($item_id){
		return $GLOBALS['db']->delete('analogies', "`item_id` = $item_id OR `item_diff` = $item_id");
	}
	/**
	 * blocks items where there are photos, prices and exists barcode
	 * @return true is succesfully processed
	 */
	public static function blockItem(){
		return $GLOBALS['db']->query("
			UPDATE
				#items
			SET
				is_blocked = 1
			WHERE
				foto != '' OR
				barcode != '' OR
				id IN (
					SELECT item_id FROM #store_items GROUP BY item_id
				)
		", '');
	}
	public static function getHrefArticle($article){
		return "/search/article/$article";
	}
	public function getFilters($item_id){
		$res_filters = self::getInstanceDataBase()->query("
			SELECT
				c.title AS category,
				ci.category_id,
				f.id AS filter_id,
				f.title AS filter,
				fv.id AS fv_id,
				fv.title AS fv,
				IF(iv.value_id IS NOT NULL, 'selected', '') AS selected,
				iv.value_id,
				CAST(fv.title AS UNSIGNED) as sort_fv
			FROM
				#categories_items ci
			LEFT JOIN
				#filters f ON f.category_id = ci.category_id
			LEFT JOIN
				#filters_values fv ON fv.filter_id = f.id
			LEFT JOIN
				#categories c ON c.id = ci.category_id
			LEFT JOIN
				#items_values iv ON iv.item_id = {$_GET['id']} AND iv.value_id = fv.id
			WHERE
				ci.item_id = {$item_id}
			ORDER BY
				f.pos, sort_fv, fv.title
		", '');
		if(!$res_filters->num_rows) return false;
		$output = [];
		foreach($res_filters as $v){
			$c = & $output[$v['category']];
			$c['id'] = $v['category_id'];
			$c['title'] = $v['category'];
			$f = & $c['filters'][$v['filter_id']];
			$f['id'] = $v['filter_id'];
			$f['title'] = $v['filter'];
			$f['filter_values'][$v['fv_id']]['id'] = $v['fv_id'];
			$f['filter_values'][$v['fv_id']]['title'] = $v['fv'];
			$f['filter_values'][$v['fv_id']]['selected'] = $v['selected'];
		}
		return $output;
	}
}
