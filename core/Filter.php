<?
namespace core;
class Filter{
	public static function getFilterValuesByCategoryID($category_id, $params = []){
		$where = "(f.category_id = {$category_id} AND iv.value_id IS NOT NULL) AND f.isActive = 1 AND ";
		if (!empty($params)){
			$query = Item::getQueryItemsByCategoryID($category_id, $params);
			$res_items = $GLOBALS['db']->query($query, '');
			if ($res_items->num_rows){
				$item_ids = '';
				foreach($res_items as $value) $item_ids .= "{$value['item_id']},";
				$item_ids = substr($item_ids, 0, -1);
				$where .= "iv.item_id IN ($item_ids) AND ";
			} 
		}

		$where = substr($where, 0, -5);
		$query = "
			SELECT
				f.pos,
				fv.id AS fv_id,
				fv.title AS filter_value,
				fv.filter_id,
				f.title AS filter,
				f.slider,
				CAST(fv.title AS UNSIGNED) as sort_fv
			FROM
				#filters f
			LEFT JOIN
				#filters_values fv ON fv.filter_id = f.id
			LEFT JOIN
				#items_values iv ON iv.value_id = fv.id
			WHERE 
				$where
			GROUP BY
				fv.id
			ORDER BY
				f.pos, sort_fv, fv.title
		";
		$res = $GLOBALS['db']->query($query, '');
		if (!$res->num_rows) return false;
		$output = [];
		foreach($res as $value){
			$o = & $output[$value['filter']];
			if (!isset($o)){
				$o['min'] = 10000000000;
				$o['max'] = 0;
			}
			$o['id'] = $value['filter_id'];
			$o['title'] = $value['filter'];
			$o['slider'] = $value['slider'];
			if ($o['slider']){
				if ($value['filter_value'] > $o['max']) $o['max'] = $value['filter_value'];
				if ($value['filter_value'] < $o['min']) $o['min'] = $value['filter_value'];
			}
			$o['filter_values'][$value['fv_id']]['id'] = $value['fv_id'];
			$o['filter_values'][$value['fv_id']]['title'] = $value['filter_value'];
		}

		//закоментировано в связи с тем, что необходимости в сбросе ключей отпала
		/*//дальнейшая обработка необходима, чтобы сделать ключи массивов по возрастанию
		foreach($output as $title => $value){
			$output[$title]['filter_values'] = array_values($output[$title]['filter_values']);
		}*/
		return $output;
	}
	public static function isSelectedFilterValue(string $fv_id, array $params_fv){
		foreach($params_fv as $filter_id => $fv_ids){
			if (in_array($fv_id, $fv_ids)) return true;
		}
		return false;
	}
}