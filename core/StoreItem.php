<?php
namespace core;
class StoreItem{
	public static function getQueryStoreItem($discount = 0): string
	{
        if ($discount) $userDiscount = "@price * {$discount} / 100";
        else $userDiscount = 0;
        
		return "
			SELECT
				si.item_id,
				si.store_id,
				(si.price * c.rate) AS priceWithoutMarkup,
				@price := si.price * c.rate + si.price * c.rate * ps.percent / 100,
                CEIL(@price - $userDiscount) AS price,
				si.packaging,
				si.in_stock,
				ps.cipher,
				p.title AS provider,
				ps.provider_id,
				b.title AS brend,
				i.id AS item_id,
				i.article,
				LEFT(i.title_full, 20) AS title_full,
				rr.requiredRemain
			FROM
				#store_items si
			LEFT JOIN
				#provider_stores ps ON ps.id = si.store_id
			LEFT JOIN
				#currencies c ON c.id = ps.currency_id
			LEFT JOIN
				#providers p ON p.id = ps.provider_id
			LEFT JOIN
				#items i ON i.id = si.item_id
			LEFT JOIN
				#brends b ON b.id = i.brend_id
			LEFT JOIN
				#required_remains rr ON rr.item_id = si.item_id
		";
	}
	public static function getStoreItemsByStoreID(array $store_ids, $notNulPrice = false): \mysqli_result
	{
        $where = "si.store_id IN (" . implode(',', $store_ids) . ")";
        if ($notNulPrice) $where .= " AND si.stock_id > 0";
		return $GLOBALS['db']->query("
			SELECT
				si.item_id,
				b.title AS brend,
				i.article,
				i.title_full,
				si.in_stock,
				MIN(CEIL(si.price * c.rate + si.price * c.rate * ps.percent / 100)) as price,
				si.packaging
			FROM
				#store_items si
			LEFT JOIN
				#items i ON i.id = si.item_id
			LEFT JOIN
				#brends b ON b.id = i.brend_id
			LEFT JOIN
				#provider_stores ps ON ps.id = si.store_id
			LEFT JOIN 
				#currencies c ON c.id=ps.currency_id
			WHERE
				$where
			GROUP BY
				si.item_id
		", '');
	}
}
