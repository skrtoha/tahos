<?php
namespace core;

use core\Messengers\Telegram;
use core\Provider\Absel;
use core\Provider\Autoeuro;
use core\Provider\Berg;
use core\Provider\Emex;
use core\Sms\SmsAero;
use PHPMailer\PHPMailer\Exception;


class OrderValue{
	public static $countOrdered = 0;
	public static function setFunds($params){
        $update = [];
        $bill = 0;
        $remainder = 0;
        $user = [];

        /** @var \mysqli_result $res_user */
		$res_user = User::get(['user_id' => $params['user_id']]);
        foreach($res_user as $value) $user = $value;

        if ($params['bill_type'] == User::BILL_CASH){
            $remainder = $user['bill_cash'] - $params['totalSumm'];
            $bill = $user['bill_cash'];
            $update['bill_cash'] = "`bill_cash` - " . $params['totalSumm'];
        }
        if ($params['bill_type'] == User::BILL_CASHLESS){
            $remainder = $user['bill_cashless'] - $params['totalSumm'];
            $bill = $user['bill_cashless'];
            $update['bill_cashless'] = "`bill_cashless` - " . $params['totalSumm'];
        }

		//вычисление задолженности
		$overdue = 0;
		if ($bill <= $params['totalSumm']){
			if ($bill > 0) $overdue = $params['totalSumm'] - $bill;
			else $overdue = $params['totalSumm'];
		}

        $paid = 0;
        if ($bill - $params['totalSumm'] >= 0) $paid = $params['totalSumm'];
        elseif ($bill > 0 && $remainder < 0) $paid = $bill;

		Fund::insert(2, [
            'sum' => $params['totalSumm'],
            'remainder' => $remainder,
            'user_id' => $params['user_id'],
            'issue_id' => $params['issue_id'],
            'paid' => $paid,
            'overdue' => $overdue,
            'comment' => 'Реализация товаров',
            'bill_type' => $params['bill_type']
        ]);

        User::update($params['user_id'], $update);
	}
    
    /**
     * @param $params array order_id, user_id
     */
    private static function allItemsArrived($params){
        return true;
    }

    /**
     * changes status
     * @param $status_id
     * @param $params
     * @return void [boolean] true if changed successfully
     * @throws Exception
     */
	public static function changeStatus($status_id, $params, $noCommit = false){
        if (!$noCommit){
            Database::getInstance()->startTransaction();
        }

        $postActions = [];
		$values = ['status_id' => $status_id];
		if (isset($params['synchronized']) && $params['synchronized']){
            $values['synchronized'] = 1;
        }
        switch ($status_id){
			//выдано
			case 1:
				$quan = $params['issued'];
				$values['issued'] = "`issued` + $quan";
				self::update($values, $params);
				break;
			//возврат
			case 2:
				$ov = OrderValue::get($params)->fetch_assoc();
				if ($ov['returned'] + $params['quan'] < $ov['issued']) $values['status_id'] = 1;
				$values['returned'] = "`returned` + {$params['quan']}";
				self::update($values, $params);
				self::changeInStockStoreItem($params['quan'], $params, 'plus');

                /** @var \mysqli_result $res_user */
				$res_user = User::get(['user_id' => $params['user_id']]);
                foreach ($res_user as $value) $user = $value;

                $title = self::getTitleComment($params['item_id']);

                $bill = 0;
                $billColumn = '';
                if ($ov['bill_type'] == 1){
                    $bill = $user['bill_cash'];
                    $billColumn = 'bill_cash';
                }
                if ($ov['bill_type'] == 2){
                    $bill = $user['bill_cashless'];
                    $billColumn = 'bill_cashless';
                }

                if ($bill === false) die('Ошибка получения остатка счета или данных запроса');

                $remainder = $bill + $params['quan'] * $params['price'];
				Fund::insert(1, [
					'sum' => $params['quan'] * $params['price'],
					'remainder' => $remainder,
					'user_id' => $params['user_id'],
					'comment' => addslashes('Возврат средств за "'.$title.'"'),
                    'bill_type' => $params['bill_type']
				]);

				User::update(
					$params['user_id'],
					[$billColumn => $remainder]
				);
				break;
			//пришло
			case 3:
				$values['arrived'] = $params['quan'];
				self::update($values, $params);
                $postActions[] = [
                    'name' => 'sendStatusArrived',
                    'params' => $params
                ];
				break;
			//отменен
			case 10:
				self::update($values, $params);
				User::updateReservedFunds($params['user_id'], $params['quan'] * $params['price'], 'minus', $params['pay_type']);
				self::changeInStockStoreItem($params['quan'], $params, 'plus');
				break;
			//заказано
			case 11:
				$values['ordered'] = "`ordered` + {$params['quan']}";
				self::update($values, $params);
                User::updateReservedFunds($params['user_id'], $params['quan'] * $params['price'], 'plus', $params['pay_type']);
				self::changeInStockStoreItem($params['quan'], $params);
				break;
			//отменен клиентом
			case 12:
			//отменен поставщиком
			case 8:
                $ov = OrderValue::get([
                    'order_id' => $params['order_id'],
                    'store_id' => $params['store_id'],
                    'item_id' => $params['item_id']
                ])->fetch_assoc();
				
				//если предыдущий статус был заказано
				if (in_array($ov['status_id'], [11, 3])){
					User::updateReservedFunds($params['user_id'], $params['price'], 'minus', $params['pay_type']);
				}

				//если отменено поставщиком
				if ($status_id == 8){
					Database::getInstance()->delete('store_items', "`store_id` = {$params['store_id']} AND `item_id` = {$params['item_id']}");
                    $userInfo = User::getById($ov['user_id']);

                    if ($userInfo['get_sms_provider_refuse'] && $userInfo['phone']){
                        $smsAero = new SmsAero();
                        $query = Item::getQueryItemInfo();
                        $query .= " WHERE i.id = {$ov['item_id']}";
                        $itemInfo = Database::getInstance()->query($query)->fetch_assoc();
                        $itemInfo['title_full'] = substr($itemInfo['title_full'], 0, 50);
                        $url = str_replace('www.', '', $_SERVER['HTTP_ORIGIN']);
                        $message = "Позиция {$itemInfo['brend']}-{$itemInfo['article']} отменена поставщиком. Заказ {$url}/order/{$ov['order_id']}";

                        $telegram = new Telegram();
                        $userTelegram = $telegram->getTelegramId($userInfo['id']);
                        if ($userTelegram){
                            Telegram::sendMessageProviderRefuse($userInfo['id'], $message);
                        }
                        else{
                            $smsAero->sendSms(
                                $userInfo['phone'],
                                $message
                            );
                        }
                    }
				}
                if ($status_id == 12){
                    Database::getInstance()->query("
                        UPDATE
                            #store_items
                        SET
                            `in_stock` = `in_stock` + {$ov['quan']}
                        WHERE
                            `store_id` = {$params['store_id']} AND `item_id` = {$params['item_id']}
                    ");
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
				Database::getInstance()->delete('store_items', "`store_id` = {$params['store_id']} AND `item_id` = {$params['item_id']}");
				self::update($values, $params);
				break;
			default:
				self::update($values, $params);
		}

        if (!$noCommit){
            $result = Database::getInstance()->commit();
            if($result){
                self::executePostActions($postActions);
            }
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
		$item = Database::getInstance()->query("
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
     * @return bool
     * @throws Exception
     */
	public static function update($values, $params){
		if ($values['status_id'] == 6){
		    $orderValuerResult = self::get([
                'order_id' => $params['order_id'],
                'store_id' => $params['store_id'],
                'item_id' => $params['item_id']
            ]);
		    $orderValue = $orderValuerResult->fetch_assoc();
            
            $mailer = new Mailer(Mailer::TYPE_INFO);
		    $mailer->send([
		        'emails' => $orderValue['email'],
                'subject' => 'Отказ поставщика',
                'body' => "Поставщик отказал в поставке товара {$orderValue['brend']} {$orderValue['article']} {$orderValue['title_full']}"
            ]);
        }
	    return Database::getInstance()->update('orders_values', $values, Provider::getWhere([
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
	 * @return true everything is ok
     * @return string if there is an error
     * @return false if there is no 'where' conditions
	 */
	private static function changeInStockStoreItem($quan, $condition, string $act = 'minus'){
        /** @var Database $db */
        $db = Database::getInstance();

		$sign = $act == 'minus' ? '-' : '+';
		return Database::getInstance()->update(
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
		$res = Database::getInstance()->query($query, '');
		return $res->fetch_assoc();
	}

    private static function getLeftJoin(): string
    {
        return "
            LEFT JOIN #order_issue_values oiv ON
                oiv.order_id = ov.order_id AND oiv.store_id = ov.store_id AND oiv.item_id = ov.item_id
            LEFT JOIN #order_issues oi ON oi.id = oiv.issue_id 
            LEFT JOIN #returns r ON 
                r.order_id = ov.order_id AND 
                r.store_id = ov.store_id AND 
                r.item_id = ov.item_id AND
                r.status_id = 3
			LEFT JOIN #provider_stores ps ON ps.id=ov.store_id
			LEFT JOIN #store_items si ON si.store_id=ov.store_id AND si.item_id=ov.item_id
			LEFT JOIN #providers p ON p.id=ps.provider_id
			LEFT JOIN #items i ON i.id=ov.item_id
			LEFT JOIN #brends b ON b.id=i.brend_id
			LEFT JOIN #item_barcodes ib ON ib.item_id = i.id
			LEFT JOIN #orders_statuses os ON os.id=ov.status_id
			LEFT JOIN #orders o ON ov.order_id=o.id
			LEFT JOIN
			    #user_1c_arrangements ua ON ua.user_id = o.user_id and ua.bill_type = o.bill_type
			LEFT JOIN #users u ON u.id=o.user_id
			LEFT JOIN #corresponds c 
			ON
				c.order_id=ov.order_id AND
				c.store_id=ov.store_id AND
				c.item_id=ov.item_id
			LEFT JOIN
				#mikado_zakazcode mzc ON mzc.item_id = ov.item_id 
			LEFT JOIN #organizations_types ot ON ot.id=u.organization_type
        ";
    }

    public static function getCount($params){
        /** @var Database $db */
        $db = Database::getInstance();

        $query = "
            SELECT 
                COUNT(*) as cnt
            FROM
				#orders_values ov
            ".self::getLeftJoin()."
            ".self::getWhere($params)."
        ";
        $result = $db->query($query)->fetch_assoc();
        return $result['cnt'];
    }

    /**
     * @param array $params
     * @return string
     */
    public static function getWhere(array $params): string
    {
        $where = '';
        foreach($params as $key => $value){
            switch($key){
                case 'osi':
                    $where .= "(";
                    if (!is_array($value)) {
                        $value = [$value];
                    }
                    foreach($value as $v){
                        $array = explode('-', $v);
                        $where .= "(ov.order_id = {$array[0]} AND ov.store_id = {$array[1]} AND ov.item_id = {$array[2]}) OR ";
                    }
                    $where = substr($where, 0, -4);
                    $where .= ") AND ";
                    break;
                case 'order_id':
                case 'item_id':
                case 'store_id':
                case 'status_id':
                case 'synchronized':
                    if (!is_array($value)) {
                        $value = [$value];
                    }
                    $where .= "ov.$key in (".implode(',', $value).") AND ";
                    break;
                case 'user_id':
                    $where .= "o.user_id = $value AND ";
                    break;
                case 'limit':
                    $limit = "LIMIT $value";
                    break;
                case 'created':
                    $where .= "o.created >= '$value' AND ";
                    break;
            }
        }
        if ($where){
            $where = substr($where, 0, -5);
            $where = "WHERE $where";
        }
        return $where;
    }

    /**
     * gets common information of order value
     * @param array $params
     * @param string $flag
     * @return \mysqli_result mysqli object
     */
	public static function get(array $params = array(), string $flag = ''){
		$limit = '';

        $where = '';
		if (!empty($params)) {
            $where = self::getWhere($params);
        }
        if ($params['limit']){
            $limit = "LIMIT {$params['limit']}";
        }

        $db = Database::getInstance();
		return $db->query("
			SELECT
				ps.cipher,
				i.brend_id,
				b.title AS brend,
				IF (i.title_full != '', i.title_full, i.title) AS title_full,
				i.article,
				i.article_cat,
				IF (si.packaging IS NOT NULL, si.packaging, 1) AS packaging,
				ov.order_id,
				ov.store_id,
				ov.item_id,
				ov.price,
				ov.quan,
				ov.ordered,
				ov.arrived,
				ov.issued,
				oiv.issue_id,
				DATE_FORMAT(oi.created, '%d.%m.%Y %H:%i:%s') AS issued_date, 
				ov.declined,
				ov.returned,
				IF(ov.withoutMarkup > 0, ov.withoutMarkup, ov.price + ov.price * 0.1) AS withoutMarkup,
				(ov.price * ov.quan) AS sum,
				ov.comment,
				DATE_FORMAT(o.created, '%d.%m.%Y %H:%i:%s') AS updated, 
				os.id AS status_id,
				os.title AS status,
				os.class AS status_class,
				o.user_id,
				DATE_FORMAT(o.created, '%d.%m.%Y %H:%i:%s') AS created,
				" . User::getUserFullNameForQuery() . " AS userName,
				u.bill_cash,
				u.bill_cashless,
				u.email,
				u.reserved_cash,
				u.reserved_cashless,
				CASE
				    WHEN o.bill_type = ".User::BILL_CASH." THEN 'private'
				    WHEN o.bill_type = ".User::BILL_CASHLESS." THEN 'entity'
				END AS typeOrganization,
				DATE_FORMAT(u.created, '%d.%m.%Y %H:%i:%s') AS userCreated, 
				ps.delivery,
				p.api_title,
				p.title AS provider,
				ps.title AS providerStore,
				ps.provider_id,
				mzc.ZakazCode,
				IF(r.item_id IS NOT NULL, 1, 0) return_ordered,
				IF (ps.noReturn, 'class=\"noReturn\" title=\"Возврат поставщику невозможен!\"', '') AS noReturn,
				c.id AS correspond_id,
				o.is_payed,
				o.pay_type,
				IF(ps.calendar IS NOT NULL, ps.calendar, p.calendar) AS  calendar,
				IF(ps.workSchedule IS NOT NULL, ps.workSchedule, p.workSchedule) AS  workSchedule,
				r.return_price,
				DATE_FORMAT(r.created, '%d.%m.%Y %H:%i:%s') AS return_data,
				(
					SELECT 
						COUNT(id)
					FROM 
						#messages
					WHERE correspond_id=c.id
				) as count,
				o.bill_type,
				ua.uid AS arrangement_uid
			FROM
				#orders_values ov
            ".self::getLeftJoin()."
			$where
			ORDER BY o.created DESC
			$limit
		", $flag);
	}
	public static function getStatuses(): array
	{
        $cacheId = 'order-statuses';
        $result = Cache::get($cacheId);
        if ($result) {
            return $result;
        }
        $output = Database::getInstance()->query("SELECT * FROM #orders_statuses ORDER BY title")->fetch_all(MYSQLI_ASSOC);
        Cache::set($cacheId, $output);
		return $output;
	}

    public static function getStatusIdByTitle(string $title): int
    {
        $statuses = self::getStatuses();
        foreach ($statuses as $status) {
            if ($status['title'] == $title) {
                return $status['id'];
            }
        }
        return false;
    }

	public static function setStatusInWork($ov, $automaticOrder){
		if (!in_array($ov['status_id'], [5])) return;
		if (!Provider::getIsEnabledApiOrder($ov['provider_id']) && $ov['api_title']){
			try{
				throw new \Exception("API заказов " . Provider::getProviderTitle($ov['provider_id']) . " отключено");
			} catch(\Exception $e){
				Log::insertThroughException($e, ['additional' => "osi: {$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}"]);
				return;
			}
		} 
		switch($ov['provider_id']){
			case 8: //Микадо
				$mikado = new Provider\Mikado();
				$mikado->Basket_Add($ov);
				break;
			case 2: //Армтек
				Provider::addToProviderBasket($ov);
				if ($automaticOrder) self::$countOrdered = Provider\Armtek::sendOrder();
				break;
			case 6: //Восход
				Provider::addToProviderBasket($ov);
				if ($automaticOrder) self::$countOrdered = Provider\Abcp::sendOrder(6);
				break;
			case 13: //МПартс
				Provider::addToProviderBasket($ov);
				if ($automaticOrder) self::$countOrdered = Provider\Abcp::sendOrder(13);
				break;
			case 15: //Росско
				Provider::addToProviderBasket($ov);
				if ($ov['store_id'] == 24 || $automaticOrder){
					self::$countOrdered = Provider\Rossko::sendOrder($ov['store_id']);
				} 
				break;
            case 16:
            case 26:
            case 27:
            case 30:
                Provider::addToProviderBasket($ov);
                if ($automaticOrder) self::$countOrdered = Berg::sendOrder();
                break;
			case 17://ForumAuto
				Provider::addToProviderBasket($ov);
				self::$countOrdered = Provider\ForumAuto::sendOrder();
				break;
			case 18: //Autoeuro
                Provider::addToProviderBasket($ov);
                if ($automaticOrder) self::$countOrdered = Autoeuro::sendOrder();
				break;
			case 19://Favorit
				Provider\FavoriteParts::addToBasket($ov);
				if ($automaticOrder) self::$countOrdered = Provider\FavoriteParts::toOrder();
				break;
			case 20://Autokontinent
				Provider\Autokontinent::addToBasket($ov);
				if ($automaticOrder) self::$countOrdered = Provider\Autokontinent::sendOrder();
				break;
            case 40: //ТД Столица
                self::$countOrdered = Absel::addToBasket($ov);
                break;
			case Provider\Autopiter::getParams()->provider_id:
				Provider\Autopiter::addToBasket($ov); 
				if ($automaticOrder) self::$countOrdered = Provider\Autopiter::sendOrder();
				break;
		    case Provider\Tahos::$provider_id:
				OrderValue::changeStatus(11, $ov);
				self::$countOrdered += 1; 
				break;
            case Emex::PROVIDER_ID:
                Emex::addToBasket($ov);
                if ($automaticOrder) self::$countOrdered = Emex::sendOrder();
                break;
			default:
				OrderValue::changeStatus(7, $ov);
		}
	}
    
    
    /**
     * @param mixed $order_id
     * @param bool $provider_id - если передан, тогда в выборку попадут данные из provider_addresses
     * @param string $flag
     * @return mixed
     */
    public static function getOrderInfo($order_id, bool $provider_id = true, string $flag = ''){
        $selectAddressId = 'o.address_id';
        if ($provider_id){
            $leftJoin = "
                LEFT JOIN
                    #provider_addresses pa
                ON
                    pa.address_site_id = o.address_id AND
                    pa.user_id = o.user_id AND
                    pa.provider_id = $provider_id
                ";
            $selectAddressId .= ", pa.address_provider_id";
        }
        else $leftJoin = '';

        if (!is_array($order_id)) $order_id = [$order_id];

        $query = "
            SELECT
                o.id,
                DATE_FORMAT(o.created, '%d.%m.%Y %H:%i:%s') AS date,
                GROUP_CONCAT(ov.status_id) AS statuses,
                GROUP_CONCAT(ov.price) AS prices,
                GROUP_CONCAT(ov.quan) AS quans,
                GROUP_CONCAT(ov.ordered) AS ordered,
                GROUP_CONCAT(ov.arrived) AS arrived,
                GROUP_CONCAT(ov.issued) AS issued,
                GROUP_CONCAT(ov.declined) AS declined,
                GROUP_CONCAT(ov.returned) AS returned,
                " . User::getUserFullNameForQuery() . " AS organization,
                CONCAT_WS(' ', u.name_1, u.name_2, u.name_3) AS fio,
                if (u.delivery_type = 'Самовывоз', u.issue_id, 1) as user_issue,
                min(ps.delivery) as min_delivery,
                max(ps.delivery) as max_delivery,
                o.user_id,
                u.bill_cash,
                u.bill_cashless,
                u.defermentOfPayment,
                u.bill_mode,
                o.pay_type,
                o.delivery,
                $selectAddressId,
                o.entire_order,
                o.date_issue,
                o.is_new,
                o.is_draft,
                o.is_payed,
                ua.json,
                isSuspended(GROUP_CONCAT(ov.status_id)) AS is_suspended
            FROM
                #orders o
            LEFT JOIN #orders_values ov ON ov.order_id=o.id
            LEFT JOIN #provider_stores ps on ps.id = ov.store_id
            LEFT JOIN #users u ON u.id=o.user_id
            LEFT JOIN #organizations_types ot ON ot.id=u.organization_type
            LEFT JOIN #user_addresses ua ON ua.id = o.address_id
            $leftJoin
            WHERE o.id IN (".implode(',', $order_id).")
        ";
        if ($flag) return Database::getInstance()->query($query, $flag);
        $orders = Database::getInstance()->select_unique($query, $flag);

        if (count($orders) == 1) return $orders[0];

        $output = [];
        foreach($orders as $o) $output[$o['id']] = $o;
        return $output;
    }

    private static function executePostActions($actions){
        foreach($actions as $act){
            switch($act['name']){
                case 'sendStatusArrived':
                    Telegram::sendMessageArrived($act['params']['order_id'], $act['params']['item_id']);
                    break;
            }
        }
    }
}
