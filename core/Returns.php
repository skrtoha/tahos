<?php
namespace core;
class Returns{
	public static function get($params = []): \mysqli_result
	{
		$where = '';
		if (!empty($params)){
			foreach($params as $key => $value){
				if (!$value) continue;
				switch($key){
					case 'user_id': $where .= "o.user_id = {$value} AND "; break;
					case 'order_id':
					case 'store_id':
					case 'item_id':
					case 'status_id':
						$where .= "r.{$key} = '{$value}' AND ";
						break;
					case 'dateFrom':
						$where .= "r.created >='" . $params['dateFrom']->format('Y-m-d H:i:s') . "' AND ";
						break;
					case 'dateTo':
						$where .= "r.created <='" . $params['dateTo']->format('Y-m-d H:i:s') . "' AND ";
						break;
					case 'article':
						$article = Item::articleClear($params['article']);
						if (!$article) break;
						$where .= "i.article = '$article' AND ";
						break;
				}
			}
		}
		if ($where){
			$where = substr($where, 0, -5);
			$where = "WHERE $where";
		} 
		$query = "
			SELECT
				r.id AS return_id,
				o.user_id,
				r.order_id,
				r.item_id,
				r.quan,
				r.comment,
				i.brend_id,
				b.title AS brend,
				i.article,
				i.title_full,
				r.store_id,
				r.is_new,
				ps.cipher,
				ov.price,
				IF (
					r.return_price IS NULL,
					CEIL(ov.price - ov.price * p.return_percent / 100),
					r.return_price
				) AS return_price,
				r.status_id,
				rs.title AS status,
				rr.title AS reason,
				" . User::getUserFullNameForQuery() . " AS fio,
				o.bill_type,
				DATE_FORMAT(r.created, '%d.%m.%Y') AS created
			FROM
				#returns r
			LEFT JOIN 
				#orders_values ov 
				ON 
					r.order_id = ov.order_id AND
					r.store_id = ov.store_id AND
					r.item_id = ov.item_id
			LEFT JOIN
				#return_statuses rs ON r.status_id = rs.id
			LEFT JOIN
				#orders o ON o.id = r.order_id
			LEFT JOIN
				#users u ON o.user_id = u.id
			LEFT JOIN 
				#organizations_types ot ON ot.id=u.organization_type
			LEFT JOIN
				#return_reasons rr ON rr.id = r.reason_id
			LEFT JOIN
				#items i ON i.id = r.item_id
			LEFT JOIN
				#brends b ON b.id = i.brend_id
			LEFT JOIN
				#provider_stores ps ON ps.id = r.store_id
			LEFT JOIN
				#providers p ON p.id = ps.provider_id
			$where
			ORDER BY
				r.created DESC
		";
		return $GLOBALS['db']->query($query, '');
	}
	public static function getReasons(): array
	{
		return $GLOBALS['db']->select('return_reasons', '*');
	}
	public static function getStatuses(): array
	{
		return $GLOBALS['db']->select('return_statuses', '*'); 
	}
	public static function createReturnRequest($items){
		$emailPrices = Provider::getEmailPrices();
		foreach($items as $value){
			if (in_array($value['store_id'], $emailPrices)) $status_id = 2;
			else $status_id = 1;
			$GLOBALS['db']->query("
				INSERT INTO #returns (`order_id`,`store_id`,`item_id`,`reason_id`,`quan`,`status_id`) 
				VALUES ({$value['order_id']}, {$value['store_id']}, {$value['item_id']}, {$value['reason_id']}, {$value['quan']}, $status_id) 
				ON DUPLICATE KEY UPDATE `status_id` = $status_id,`created` = CURRENT_TIMESTAMP, `updated` = NULL
			", '');
		}
	}

	public static function processReturn($params, $return){
		Database::getInstance()->update(
			'returns',
			[
				'return_price' => $params['return_price'],
				'quan' => $params['quan'],
				'status_id' => $params['status_id'],
				'comment' => $params['comment']
			],
			Provider::getWhere([
				'order_id' => $return['order_id'],
				'store_id' => $return['store_id'],
				'item_id' => $return['item_id']
			])
		);
		if ($params['status_id'] == 3){
			OrderValue::changeStatus(2, [
				'order_id' => $return['order_id'],
				'store_id' => $return['store_id'],
				'item_id' => $return['item_id'],
				'price' => $params['return_price'],
				'quan' => $params['quan'],
				'user_id' => $return['user_id'],
                'bill_type' => $return['bill_type']
			], true);
            User::checkDebt(
                $return['user_id'],
                $params['return_price'] * $params['quan'],
                $return['bill_type'],
                Fund::$last_id
            );
		}
        return true;
	}
}
