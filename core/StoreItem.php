<?php
namespace core;
class StoreItem{
	public static function getQueryStoreItem(): string
	{
		return "
			SELECT
				si.item_id,
				si.store_id,
				si.price AS priceWithoutMarkup,
				CEIL(si.price * c.rate + si.price * c.rate * ps.percent / 100) AS price,
				si.packaging,
				si.in_stock,
				ps.cipher,
				p.title AS provider,
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
	public static function getStoreItemsByStoreID(array $store_ids): \mysqli_result
	{
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
				si.store_id IN (" . implode(',', $store_ids) . ")
			GROUP BY
				si.item_id
		", '');
	}
}
