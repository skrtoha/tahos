<?php
namespace core;
class Basket{
	function get($user_id, $isToOrder = false): \mysqli_result
	{
		if ($isToOrder) $whereIsToOrder = "AND b.isToOrder = 1";
		return $GLOBALS['db']->query("
			SELECT 
			b.*,
			IF (
				i.article_cat != '', 
				i.article_cat, 
				IF (
					i.article !='',
					i.article,
					i.barcode
				)
			) AS article,
			i.brend_id,
			br.title AS brend,
			IF(pb.title IS NOT NULL, pb.title, br.title) AS provider_brend,
			IF (i.title_full != '', i.title_full, i.title) AS title,
			IF (f.item_id IS NOT NULL, 1, 0) AS is_favorite,
			CASE
				WHEN aok.order_term IS NOT NULL THEN aok.order_term
				ELSE
					IF (si.in_stock = 0, ps.under_order, ps.delivery) 
			END AS delivery,
			ps.cipher,
			ps.provider_id,
			p.api_title,
			si.packaging,
			si.in_stock,
			IF (ps.noReturn, 'class=\"noReturn\" title=\"Возврат поставщику невозможен!\"', '') AS noReturn,
			CEIL(si.price * c.rate + si.price * c.rate * ps.percent / 100) AS new_price
		FROM
				#basket b
		LEFT JOIN #items i ON i.id=b.item_id
		LEFT JOIN #brends br ON br.id=i.brend_id
		LEFT JOIN #store_items si ON si.item_id=b.item_id AND si.store_id=b.store_id
		LEFT JOIN #provider_stores ps ON ps.id=si.store_id
		LEFT JOIN #providers p ON p.id = ps.provider_id
		LEFT JOIN #provider_brends pb ON pb.brend_id = i.brend_id AND pb.provider_id = ps.provider_id
		LEFT JOIN #currencies c ON c.id=ps.currency_id
		LEFT JOIN #favorites f ON f.item_id=b.item_id AND f.user_id=$user_id
		LEFT JOIN #autoeuro_order_keys aok ON aok.item_id = si.item_id AND aok.store_id = si.store_id
		WHERE b.user_id=$user_id $whereIsToOrder
		", '');
	}
}
