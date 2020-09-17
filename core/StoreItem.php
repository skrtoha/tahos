<?php
namespace core;
class StoreItem{
	public static function getQueryStoreItem(): string
	{
		return "
			SELECT
				si.item_id,
				si.store_id,
				si.price,
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
				#providers p ON p.id = ps.provider_id
			LEFT JOIN
				#items i ON i.id = si.item_id
			LEFT JOIN
				#brends b ON b.id = i.brend_id
			LEFT JOIN
				#required_remains rr ON rr.item_id = si.item_id
		";
	}
}
