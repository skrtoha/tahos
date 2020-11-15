<?php
namespace core;
class GoodsArrival{
	public static function get($params = array(), $flag = '')/*: \mysqli_result*/
	{
		$query = self::getQuery($params);
		return $GLOBALS['db']->query($query, $flag);
	}

	private static function getQuery($params): string
	{
		$limit = '';
		$where = '';
		$having = '';
		$groupBy = '';
		if (isset($params['limit']) && $params['limit']) $limit = "LIMIT {$params['limit']}";
		if (isset($params['where']) && $params['where']) $where = "WHERE {$params['where']}";
		if (isset($params['groupBy']) && $params['groupBy']) $groupBy = "GROUP BY {$params['groupBy']}";
		return "
			SELECT
				ga.id,
				ga.provider_id,
				p.title AS provider,
				ga.store_id,
				CONCAT(ps.cipher, ' ', ps.title) AS store,
				gai.item_id,
				b.title AS brend,
				i.article,
				i.title_full,
				gai.price,
				gai.in_stock,
				gai.packaging,
				DATE_FORMAT(ga.created, '%d.%m.%Y %H:%i:%s') AS created
			FROM
				#goods_arrival ga
			LEFT JOIN
				#providers p ON p.id = ga.provider_id
			LEFT JOIN
				#provider_stores ps ON ps.id = ga.store_id
			LEFT JOIN
				#goods_arrival_items gai ON gai.arrival_id = ga.id
			LEFT JOIN
				#items i ON i.id = gai.item_id
			LEFT JOIN
				#brends b ON b.id = i.brend_id
			$where
			$groupBy
			$having
			$limit
		";
	}

	public static function insertItem($array){
		$GLOBALS['db']->insert('goods_arrival_items', [
			'arrival_id' => $array['arrival_id'],
			'item_id' => $array['item_id'],
			'in_stock' => $array['in_stock'],
			'price' => $array['price'],
			'packaging' => $array['packaging']
		]/*, ['print' => true]*/);
		$GLOBALS['db']->insert('store_items', [
			'store_id' => $array['store_id'],
			'item_id' => $array['item_id'],
			'price' => $array['price'],
			'in_stock' => $array['in_stock'],
			'packaging' => $array['packaging']
		]/*, ['print' => true]*/);
	}
}
