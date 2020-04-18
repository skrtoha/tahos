<?php
namespace core;
class Basket{
	function get($user_id, $isToOrder = false){
		$db = $GLOBALS['db'];
		if ($isToOrder) $whereIsToOrder = "AND b.isToOrder = 1";
		$basket = $db->select_unique("
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
				) as article,
				br.title as brend,
				IF (i.title_full != '', i.title_full, i.title) AS title,
				IF (f.item_id IS NOT NULL, 1, 0) AS is_favorite,
				IF (si.in_stock = 0, ps.under_order, ps.delivery) as delivery,
				ps.cipher,
				si.packaging,
				si.in_stock,
				IF (ps.noReturn, 'class=\"noReturn\" title=\"Возврат поставщику невозможен!\"', '') AS noReturn,
				CEIL(si.price * c.rate + si.price * c.rate * ps.percent / 100) as new_price
			FROM
					#basket b
			LEFT JOIN #items i ON i.id=b.item_id
			LEFT JOIN #brends br ON br.id=i.brend_id
			LEFT JOIN #store_items si ON si.item_id=b.item_id AND si.store_id=b.store_id
			LEFT JOIN #provider_stores ps ON ps.id=si.store_id
			LEFT JOIN #currencies c ON c.id=ps.currency_id
			LEFT JOIN #favorites f ON f.item_id=b.item_id AND f.user_id=$user_id
			WHERE b.user_id=$user_id $whereIsToOrder
		", 'result');
		if (empty($basket)) return false;
		foreach($basket as $key => $value){
			$b = & $basket[$key];
			unset($b['user_id']);
			$b['href'] = Item::getHrefArticle($b['article']);
		} 
		return $basket;
	}
}
