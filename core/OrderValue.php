<?php
namespace core;
class OrderValue{

	public static function setFunds($params){
		$res_user = User::get(['user_id' => $params['user_id']]);
		$user = $res_user->fetch_assoc();

		$remainder = $user['bill'] - $params['totalSumm'];

		//вычисление задолженности
		$overdue = 0;
		if ($user['bill'] < $params['totalSumm']){
			if ($user['bill'] > 0) $overdue = $params['totalSumm'] - $user['bill'];
			else $overdue = $params['totalSumm'];
		} 
		else $overdue = 0;

		Fund::insert(2, [
			'sum' => $params['totalSumm'],
			'remainder' => $remainder,
			'user_id' => $params['user_id'],
			'overdue' => $overdue,
			'comment' => addslashes('Списание средств за покупку ' . implode(', ', $params['titles']))
		]);

		User::update(
			$params['user_id'],
			[
				'reserved_funds' => "`reserved_funds` - " .$params['totalSumm'],
				'bill' => "`bill` - " . $params['totalSumm']
			]
		);
	}

	/**
	 * changes status
	 * @param  [integer] $status_id 1, 2, 3, 6, 10, 11
	 * @param  [array] $params 
	 *         order_id, store_id, item_id - required for all statuses
	 *         status = 1 - issued
	 *         status = 2|3|8|10|11  - price, quan, user_id
	 * @return [boolean] true if changed successfully
	 */
	public static function changeStatus($status_id, $params){
		$values = ['status_id' => $status_id];
		switch ($status_id){
			//выдано
			case 1:
				$quan = $params['issued'];
				$values['issued'] = "`issued` + $quan";
				self::update($values, $params);				
				break;
			//возврат
			case 2:
				$ov = $GLOBALS['db']->select_one('orders_values', '*', Provider::getWhere($params));
				if ($ov['returned'] + $params['quan'] < $ov['issued']) $values['status_id'] = 1;
				$values['returned'] = "`returned` + {$params['quan']}";
				self::update($values, $params);
				self::changeInStockStoreItem($params['quan'], $params, 'plus');
				$res_user = User::get(['user_id' => $params['user_id']]);
				$user = $res_user->fetch_assoc();
				$title = self::getTitleComment($params['item_id']);
				Fund::insert(1, [
					'sum' => $params['quan'] * $params['price'],
					'remainder' => $user['bill'] + $params['quan'] * $params['price'],
					'user_id' => $params['user_id'],
					'comment' => addslashes('Возврат средств за "'.$title.'"')
				]);
				User::update(
					$params['user_id'],
					['bill' => "`bill` + ".$params['quan'] * $params['price']]
				);
				break;
			//пришло
			case 3:
				$values['arrived'] = $params['quan'];
				self::update($values, $params);
				break;
			//отменен
			case 10:
				self::update($values, $params);
				User::updateReservedFunds($params['user_id'], $params['quan'] * $params['price'], 'minus');
				self::changeInStockStoreItem($params['quan'], $params, 'plus');
				break;
			//заказано
			case 11:
				$values['ordered'] = "`ordered` + {$params['quan']}";
				self::update($values, $params);
				User::updateReservedFunds($params['user_id'], $params['quan'] * $params['price']);
				self::changeInStockStoreItem($params['quan'], $params);
				break;
			//отменен клиентом
			case 12:
			//отменен поставщиком
			case 8:
				$ov = $GLOBALS['db']->select_one('orders_values', '*', Provider::getWhere($params));
				
				//если предыдущий статус был заказано
				if ($ov['status_id'] == 11){
					User::updateReservedFunds($params['user_id'], $params['price'], 'minus');
				}

				//если отменено поставщиком
				if ($status_id == 8){
					$GLOBALS['db']->delete('store_items', "`store_id` = {$params['store_id']} AND `item_id` = {$params['item_id']}");
				}

				self::update($values, $params);
				break;
			//отменен
			case 5:
				$values ['ordered'] = 0;
				$values['arrived'] = 0;
				$values['issued'] = 0;
				$values['declined'] = 0;
				$values['returned'] = 0;
				self::update($values, $params);
				break;
			case 6:
				$GLOBALS['db']->delete('store_items', "`store_id` = {$params['store_id']} AND `item_id` = {$params['item_id']}");
				self::update($values, $params);
				break;
			default:
				self::update($values, $params);
		}
	}

	/**
	 * gets title for inserting into fund comment
	 * @param  [interger] $item_id item_id
	 * @return [string] html-string for inserting into comment
	 */
	public static function getTitleComment($item_id){
		$item = self::getItem($item_id);
		return '<b style="font-weight: 700">'.$item['brend'].'</b> <a href="/search/article/'.$item['article'].'" class="articul">'.$item['article'].'</a> '.$item['title_full'];
	}

	/**
	 * gets item by it's id
	 * @param  [integer] $item_id item_id
	 * @return [array] brend_id, article, title_full, brend
	 */
	public static function getItem($item_id){
		$item = $GLOBALS['db']->query("
			SELECT
				i.brend_id,
				i.article,
				i.article_cat,
				i.title_full,
				b.title as brend
			FROM
				#items i
			LEFT JOIN
				#brends b ON b.id=i.brend_id
			WHERE
				i.id = $item_id
		", '');
		return $item->fetch_assoc();
	}	

	/**
	 * updates order values
	 * @param  [array] $values for update
	 * @param  [type] $params for condition (order_id, store_id, item_id)
	 * @return [boolean] true if updated successfully else error of update
	 */
	public static function update($values, $params){
		return $GLOBALS['db']->update('orders_values', $values, Provider::getWhere([
			'order_id' => $params['order_id'],
			'store_id' => $params['store_id'],
			'item_id' => $params['item_id']
		]));
	}

	/**
	 * declines store_items
	 * @param  [integer] $quan value for decline
	 * @param  [array] $condition (store_id, item_id)
	 * @param  string $act plus|minus (+|-) minus as default
	 * @return none
	 */
	private static function changeInStockStoreItem($quan, $condition, $act = 'minus'){
		$sign = $act == 'minus' ? '-' : '+';
		$GLOBALS['db']->update(
			'store_items',
			['in_stock' => "`in_stock` $sign $quan"],
			"`store_id`= {$condition['store_id']} AND `item_id` = {$condition['item_id']}"
		);
	}

	/**
	 * gets order value by brend and article
	 * @param  [array] $params article, brend - is required, provider_id, status_id - optional
	 * @return [array]  row from orders_values
	 */
	public static function getByBrendAndArticle($params){
		$article = Item::articleClear($params['article']);
		$where = '';
		if (isset($params['provider_id'])) {
			$where .= " AND ps.provider_id = {$params['provider_id']}";
		}
		if (isset($params['status_id'])) {
			$where .= " AND ov.status_id = {$params['status_id']}";
		}
		$query = "
			SELECT
				ov.*
			FROM
				#orders_values ov
			LEFT JOIN
				#items i ON i.id = ov.item_id
			LEFT JOIN
				#brends b ON b.id = i.brend_id
			LEFT JOIN 
				#provider_stores ps ON ps.id = ov.store_id
			WHERE
				i.article = '$article' AND b.title LIKE '%{$params['brend']}%'
				$where
		";
		$res = $GLOBALS['db']->query($query, '');
		return $res->fetch_assoc();
	}

	/**
	 * gets common information of order value
	 * @param  array  $fields user_id|status_id|order_id|store_id|item_id
	 * @return object mysqli object
	 */
	public static function get($fields = array()){
		$where = '';
		if (isset($fields['user_id'])) $where .= "o.user_id = {$fields['user_id']} AND ";
		if (isset($fields['status_id'])) $where .= "ov.status_id = {$fields['status_id']} AND ";
		if (isset($fields['order_id'])) $where .= "ov.order_id = {$fields['order_id']} AND ";
		if (isset($fields['store_id'])) $where .= "ov.store_id = {$fields['store_id']} AND ";
		if (isset($fields['item_id'])) $where .= "ov.item_id = {$fields['item_id']} AND ";
		if ($where){
			$where = substr($where, 0, -4);
			$where = "WHERE $where";
		}
		$query = "
			SELECT
				ps.cipher,
				ps.provider_id,
				b.title AS brend,
				i.article,
				i.id AS item_id,
				IF (i.title_full<>'', i.title_full, i.title) AS title_full,
				ov.user_id,
				ov.issued,
				ov.price,
				ov.ordered,
				ov.arrived,
				ov.issued,
				ov.declined,
				ov.returned,
				ov.quan,
				ov.comment,
				ov.store_id,
				o.id AS order_id,
				si.packaging,
				DATE_FORMAT(o.created, '%d.%m.%Y %H:%i') AS created,
				os.title AS status,
				os.class AS class
			FROM
				#orders_values ov
			LEFT JOIN #orders o ON o.id=ov.order_id
			LEFT JOIN #provider_stores ps ON ps.id=ov.store_id
			LEFT JOIN #items i ON i.id=ov.item_id
			LEFT JOIN #store_items si ON si.item_id = ov.item_id AND si.store_id = ov.store_id
			LEFT JOIN #brends b ON i.brend_id=b.id
			LEFT JOIN #orders_statuses os ON os.id=ov.status_id
			$where
			ORDER BY o.created DESC
		";
		return $GLOBALS['db']->query($query, '');
	}
	public static function getStatuses(): \mysqli_result
	{
		return $GLOBALS['db']->query("SELECT * FROM #orders_statuses ORDER BY title");
	}

	public static function setStatusInWork($ov, $automaticOrder){
		if (!in_array($ov['status_id'], [5])) return;
		if (!Provider::getIsEnabledApiOrder($ov['provider_id']) && $ov['api_title']){
			try{
				throw new Exception("API заказов " . Provider::getProviderTitle($ov['provider_id']) . " отключено");
			} catch(Exception $e){
				Log::insertThroughException($e, ['additional' => "osi: {$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}"]);
				return;
			}
		} 
		
		switch($ov['provider_id']){
			case 8: //Микадо
				$mikado = new Provider\Mikado($db);
				$mikado->Basket_Add($ov);
				break;
			case 2: //Армтек
				Provider::addToProviderBasket($ov);
				if ($automaticOrder) Provider\Armtek::sendOrder();
				break;
			case 6: //Восход
				Provider::addToProviderBasket($ov);
				if ($automaticOrder) Provider\Abcp::sendOrder(6);
				break;
			case 13: //МПартс
				Provider::addToProviderBasket($ov);
				if ($automaticOrder) Provider\Abcp::sendOrder(13);
				break;
			case 15: //Росско
				Provider::addToProviderBasket($ov);
				if ($ov['store_id'] == 24 || $automaticOrder) Provider\Rossko::sendOrder($ov['store_id']);
				break;
			case 17://ForumAuto
				Provider::addToProviderBasket($ov);
				Provider\ForumAuto::sendOrder();
				break;
			case 18: //Autoeuro
				Provider\Autoeuro::putBusket($ov);
				if ($automaticOrder) Provider\Autoeuro::sendOrder();
				break;
			case 19://Favorit
				Provider\FavoriteParts::addToBasket($ov);
				if ($automaticOrder) Provider\FavoriteParts::toOrder();
				break;
			case 20://Autokontinent
				Provider\Autokontinent::addToBasket($ov);
				if ($automaticOrder) Provider\Autokontinent::sendOrder();
				break;
			case Provider\Autopiter::getParams()->provider_id:
				Provider\Autopiter::addToBasket($ov); 
				if ($automaticOrder) Provider\Autopiter::sendOrder();
				break;
		case Provider\Tahos::$provider_id:
				OrderValue::changeStatus(11, $ov);
				break;
			default:
				OrderValue::changeStatus(7, $ov);
		}
	}
}
