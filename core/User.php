<?php
namespace core;

use core\Messengers\Telegram;
use mysqli_result;

class User{
    const BILL_CASH = 1;
    const BILL_CASHLESS = 2;

    const BILL_MODE_CASH = 1;
    const BILL_MODE_CASHLESS = 2;
    const BILL_MODE_CASH_AND_CASHLESS = 3;

    private static $userGet;

	public static function noOverdue($user_id){
		$query = Fund::getQueryListFunds(
			"f.user_id = $user_id AND f.overdue > 0",
			'date_payment > CURRENT_DATE'
		);
		$res_funds = Database::getInstance()->query($query, '');
		if ($res_funds->num_rows) return false;
		return true;
	}
	public static function checkOverdue($user_id, $creditedAmount){
		$query = Fund::getQueryListFunds("f.user_id = $user_id AND f.overdue > 0", '', 'f.created');
		$res = Database::getInstance()->query($query, '');
		if (!$res->num_rows) return;
		
		$remain = $creditedAmount;
		foreach($res as $fund){
			$remain = $remain - $fund['overdue'];
			if ($remain > 0) Database::getInstance()->update('funds', ['overdue' => 0], "`id` = {$fund['id']}");
			else{
				Database::getInstance()->update('funds', ['overdue' => abs($remain)], "`id` = {$fund['id']}");
				break;
			}
		}
	}
	/**
	 * size bonus, percents
	 * @var integer
	 */
	public static $bonus_size = 1;

    /**
     * gets common information about user
     * @param array $params
     * @return array [type] if no $user_id and $_SESSION['user'] returns default values, if no user_id then by $_SESSION['user']
     * @return mysqli_result
     */
	public static function get(array $params = [])
    {
        $paramsString = json_encode($params);
        if (isset(self::$userGet[$paramsString])) return self::$userGet[$paramsString];

		$where = '';
        $having = '';
        $limit = '';
        $order = '';
        $dir = 'ASC';
        if (isset($params['order_by'])) $dir = $params['dir'];
		if (!empty($params)){
			if (isset($params['user_id']) && !$params['user_id']) return [
				'markup' => 0,
				'designation' => '<i class="fa fa-rub" aria-hidden="true"></i>',
				'currency_id' => 1,
				'rate' => 1,
				'show_all_analogies' => 0
			];
			foreach($params as $key => $value){
				switch($key){
					case 'user_id':
                        if (is_array($value)){
                            $where .= "u.id IN (".implode(',', $value).") AND ";
                        }
                        else{
                            $where .= "u.id = {$value} AND ";
                        }

                        break;
					case 'withWithdraw': $where .= "
                        CASE
                            WHEN u.bill_mode = 1 THEN u.bill_cash < 0
                            WHEN u.bill_mode = 2 THEN u.bill_cashless < 0
                            WHEN u.bill_mode = 3 THEN u.bill_cash < 0 or u.bill_cashless < 0
                        END AND ";
                        break;
                    case 'full_name': $having .= "full_name LIKE '%{$value}%' AND "; break;
                    case 'limit': $limit = "LIMIT $value"; break;

                    case 'order':
                    case 'dir':
                        $order = "ORDER BY {$params['order']} $dir";
                        break;
                    default:
                        $where .= "u.$key = '$value' AND ";
				}
			}
			if ($where){
				$where = substr($where, 0, -5);
				$where = "WHERE $where";
			}
            if ($having){
                $having = substr($having, 0, -5);
                $having = "HAVING $having";
            }
        }
		$q_user = "
			SELECT 
				u.*,
				CASE
				    WHEN u.bill_mode = ".User::BILL_MODE_CASH_AND_CASHLESS." THEN @bill_total := u.bill_cash + u.bill_cashless
				    WHEN u.bill_mode = ".User::BILL_MODE_CASH." THEN @bill_total := u.bill_cash
				    WHEN u.bill_mode = ".User::BILL_MODE_CASHLESS." THEN @bill_total := u.bill_cashless
                END,
				CASE
                    WHEN u.currency_id = 1 THEN @bill_total := ROUND(@bill_total / c.rate, 0)
                    WHEN u.currency_id = 6 THEN @bill_total := ROUND(@bill_total / c.rate * 10)
                    ELSE @bill_total := ROUND(@bill_total / c.rate, 2)
                END as bill_total,
                CASE
				    WHEN u.bill_mode = ".User::BILL_MODE_CASH_AND_CASHLESS." THEN @bill_total - u.reserved_cash - u.reserved_cashless
				    WHEN u.bill_mode = ".User::BILL_MODE_CASH." THEN @bill_total - u.reserved_cash
				    WHEN u.bill_mode = ".User::BILL_MODE_CASHLESS." THEN @bill_total - u.reserved_cashless
                END AS bill_available,
                CASE
				    WHEN u.bill_mode = ".User::BILL_MODE_CASH_AND_CASHLESS." THEN u.reserved_cash + u.reserved_cashless
				    WHEN u.bill_mode = ".User::BILL_MODE_CASH." THEN u.reserved_cash
				    WHEN u.bill_mode = ".User::BILL_MODE_CASHLESS." THEN u.reserved_cashless
                END AS reserved_total,
				c.designation, 
				c.rate, 
				i.title AS issue_title,
				i.desc AS issue_desc,
				i.adres AS issue_adres,
				i.telephone AS issue_telephone,
				i.email AS issue_email,
				i.twitter AS issue_twitter,
				i.vk AS issue_vk,
				i.facebook AS issue_facebook,
				i.google AS issue_google,
				i.ok AS issue_ok,
				i.coords AS issue_coords,
				" . User::getUserFullNameForQuery() . " AS full_name
			FROM #users u
			LEFT JOIN 
				#currencies c ON c.id=u.currency_id
			LEFT JOIN 
				#issues i ON i.id=u.issue_id
			LEFT JOIN 
				#organizations_types ot ON ot.id=u.organization_type
			$where
			$having
			$order
			$limit
		";
        self::$userGet[$paramsString] = Database::getInstance()->query($q_user, '');
		return self::$userGet[$paramsString];
	}

	/**
	 * updates reserved funds for user
	 * @param  [integer] $user_id user_id
	 * @param  [inter] $price value for increase|reduce reserved_funds
	 * @return bool
     */
	public static function updateReservedFunds($user_id, $price, $act, $pay_type){
		if (!$pay_type){
            die('Не указан способ оплаты');
        }

        $sign = $act == 'plus' ? '+' : '-';

        $columnPayType = '';
        if ($pay_type == 'Наличный' || $pay_type == 'Онлайн') $columnPayType = 'reserved_cash';
        if ($pay_type == 'Безналичный') $columnPayType = 'reserved_cashless';
		return self::update(
			$user_id,
			[$columnPayType => "`$columnPayType` $sign {$price}"]
		);
	}

	/**
	 * adds bonus count to user
	 * @param [integer] $user_id user_id
	 * @param [integer] $item_id item_id
	 * @param [integer] $sum summ from where calculating bonus count
	 * @return [boolean] true is updated successfully, false - if no user bonus program
	 */
	public static function setBonusProgram($user_id, $titles, $sum){
        /** @var mysqli_result $res_user */
		$res_user = self::get(['user_id' => $user_id]);
        $user = $res_user->fetch_assoc();

		if (!$user['bonus_program']) return false;
		$current_bonus_count = floor($sum * self::$bonus_size / 100);
		Fund::insert(3, [
			'sum' => $current_bonus_count,
			'remainder' => $user['bonus_count'] + $current_bonus_count,
			'user_id' => $user_id,
			'comment' => 'Начисление бонусов за покупку ' . implode(', ', $titles)
		]);
		return true;
	}

	/**
	 * updates user by user_id
	 * @param  [integer] $user_id user_id
	 * @param  [array] $fields  array(key => value)
	 * @return [boolean] true if updated successfully 
	 */
	public static function update($user_id, $fields){
        unset($fields['addressee']);
        unset($fields['default_address']);
        unset($fields['address_id']);
		return Database::getInstance()->update('users', $fields, "`id`=$user_id");
	}

	public static function getHtmlUserPrice($price, $designation){
		ob_start();?>
		<input type="hidden" value="<?=$price?>">
		<span class="price_format"><?=$price?></span>
		<?=$designation?>
		<?
		return ob_get_clean();
	}

	/**
	 * sets search user
	 * @param  array $item ['text', 'title', 'user_id'] 
	 * @return int
	 		1 - added a new record,
	 		2 - updated last record
	 */
	public static function saveUserSearch($array)
	{
		return Database::getInstance()->query("
			INSERT INTO #search_items (`user_id`, `item_id`) VALUES
			({$array['user_id']}, '{$array['item_id']}') 
			ON DUPLICATE KEY UPDATE 
				`date` = CURRENT_TIMESTAMP
		", '');
	}

	/**
	 * необходимо добавить 
	 * LEFT JOIN #organizations_types ot ON ot.id=u.organization_type
	 */
	public static function getUserFullNameForQuery(){
		return "
			IF(
				u.organization_name <> '',
				CONCAT_WS (' ', u.organization_name, ot.title),
				CONCAT_WS (' ', u.name_1, u.name_2, u.name_3)
			)
		";
	}
    
    public static function updateSettings($settings, $user){
        $update = $settings['data'];

        if (!$update['is_subscribe']) $update['is_subscribe'] = 0;
        if (!$update['get_news']) $update['get_news'] = 0;
        if (!$update['show_all_analogies']) $update['show_all_analogies'] = 0;
        if (!$update['get_notifications']) $update['get_notifications'] = 0;
        if (!$update['get_sms_provider_refuse']) $update['get_sms_provider_refuse'] = 0;
        if ($update['phone']) $update['phone'] = preg_replace('/[^\d|+]+/i', '', $update['phone']);
    
        if ($update['password']){
            if ($settings['data']['password'] != $_POST['password']['repeat_new_password']){
                return 'Пароли не совпадают';
            }
            if (md5($_POST['password']['old_password']) != $user['password']){
                return 'Неверный старый пароль';
            }
            $update['password'] = md5($update['password']);
        }
        else unset($update['password']);
        
        self::setAddress(
            $user['id'],
            $settings['addressee'] ?? [],
            $settings['default_address'] ?? [],
            $settings['address_id'] ?? []
        );
        
        return Database::getInstance()->update('users', $update, "`id` = {$user['id']}");
    }
    
    public static function setAddress($user_id, $addressee, $default_address, $address_id = []){
        self::clearUserAddress($user_id, $addressee, $default_address, $address_id);
        foreach($addressee as $key => $value){
            UserAddress::edit([
                'user_id' => $user_id,
                'json' => $value,
                'is_default' => $default_address[$key]
            ]);
        }
    }
    
    public static function clearUserAddress($user_id, & $addressee, & $default_address, & $address_id){
        global $db;
        if (!$user_id) return;
        $userAddressesList = $db->select('user_addresses', '*', "`user_id` = $user_id");
        if (empty($userAddressesList)) return;
        foreach($userAddressesList as $address){
            $result = $db->delete('user_addresses', "`id` = {$address['id']}");
            if ($result === true) continue;
            $key = array_search($address['id'], $address_id);
            unset($addressee[$key]);
            unset($default_address[$key]);
        }
    }

    public static function getPayType($type_organization){
        $payType = ['Наличный', 'Онлайн'];
        if ($type_organization == 'entity') $payType[] = 'Безналичный';
        sort($payType);
        return $payType;
    }

    private static function getQueryGroupDebt($date, $user_id): string
    {
        return "
            SELECT
                SUM(f.sum) - SUM(f.paid) AS sum 
            FROM
                #funds f
            WHERE
                f.created <= '$date' AND
                f.user_id = $user_id AND
                f.paid < f.sum AND
                f.issue_id IS NOT NULL
            GROUP BY
                f.user_id
        ";
    }

    public static function getDebt(array $user): array
    {
        /** @global $db Database */
        global $db;
        static $output;
        $userString = json_encode([
            'id' => $user['id'],
            'defermentOfPayment' => $user['defermentOfPayment'],
        ]);
        if (isset($output[$userString])) return $output[$userString];

        $designation = '<i class="fa fa-rub" aria-hidden="true"></i>';

        if (!$user['defermentOfPayment']) return [];

        $currentDate = new \DateTime();
        $currentDate->sub(new \DateInterval("P{$user['defermentOfPayment']}D"));
        $query = self::getQueryGroupDebt($currentDate->format('Y-m-d H:i:s'), $user['id']);
        $result = $db->query($query);

        if (!$result->num_rows){
            $currentDate = new \DateTime();
            $defermentOfPayment = $user['defermentOfPayment'] - 1;
            $currentDate->sub(new \DateInterval("P{$defermentOfPayment}D"));
            $query = self::getQueryGroupDebt($currentDate->format('Y-m-d H:i:s'), $user['id']);
            $result = $db->query($query);
            if (!$result->num_rows) return [];
            $result = $result->fetch_assoc();
            $output[$userString] = [
                'message' => "До завтра необходимо внести {$result['sum']} $designation, иначе заказы будут заблокированы",
                'blocked' => false
            ];
            return $output[$userString];
        }

        $result = $result->fetch_assoc();
        $output[$userString] = [
            'message' => "Заказы заблокированы. Задолженность составляет {$result['sum']} $designation",
            'blocked' => true
        ];
        return $output[$userString];
    }

    /**
     * @param integer $bill_mode
     * @return array|string[]
     */
    public static function getListByBillMode($bill_mode): array
    {
        $payTypeList = [];
        if (in_array($bill_mode, [User::BILL_MODE_CASH, User::BILL_MODE_CASH_AND_CASHLESS])){
            $payTypeList = ['Наличный'];
        }
        if (in_array($bill_mode, [User::BILL_MODE_CASHLESS, User::BILL_MODE_CASH_AND_CASHLESS])){
            $payTypeList = ['Безналичный'];
        }
        if ($bill_mode == User::BILL_MODE_CASH_AND_CASHLESS){
            $payTypeList = [
                'Наличный',
                'Безналичный'
            ];
        }
        return $payTypeList;
    }

    private static function getQueryDebt($where = '', $orderBy = ''): string
    {
        $defaultWhere = 'f.issue_id IS NOT NULL AND ';
        if ($where) $defaultWhere .= "$where AND ";
        $defaultWhere = substr($defaultWhere, 0, -5);
        $query = "
            SELECT
                f.id,
                f.issue_id,
                f.sum,
                f.paid,
                DATE_FORMAT(f.created, '%d.%m.%Y %H:%i:%s') AS created 
            FROM
                #funds f
            WHERE $defaultWhere
        ";
        if ($orderBy) $query .= " ORDER BY $orderBy";
        return $query;
    }

    /**
     * @param $user_id - id пользователя
     * @param $amount - проверяемая сумма
     * @param $bill_type 1 или 2
     * @return void
     */
    public static function checkDebt($user_id, $amount, $bill_type, $replenishment_id){
        $query = self::getQueryDebt(
            "f.paid < f.sum AND f.user_id = $user_id AND f.issue_id IS NOT NULL AND `bill_type` = $bill_type",
            'f.created'
        );
        $result = Database::getInstance()->query($query);

        if (!$result->num_rows) return;

        $remain = $amount;
        foreach($result as $row){
            $difference = $row['sum'] - $row['paid'];
            if ($remain < $difference){
                Database::getInstance()->update(
                    'funds',
                    ['paid' => $row['paid'] + $remain],
                    "id = {$row['id']}"
                );
                Fund::setFundDistribution($row['id'], $replenishment_id, $remain);
                break;
            }
            $remain = $remain - $difference;
            $sum = $row['paid'] + $difference;
            Database::getInstance()->update(
                'funds',
                ['paid' => $sum],
                "id = {$row['id']}"
            );
            Fund::setFundDistribution($row['id'], $replenishment_id, $sum);
        }
    }

    public static function replenishBill($params){
        Database::getInstance()->startTransaction();

        $res_user = User::get(['user_id' => $params['user_id']]);
        foreach ($res_user as $value) $user = $value;

        if (empty($user)) return false;

        if($params['bill_type'] == User::BILL_CASH){
            $params['remainder'] = $user['bill_cash'] + $params['sum'];
            $arrayUser = ['bill_cash' => $params['remainder']];
        }
        else{
            $params['remainder'] = $user['bill_cashless'] + $params['sum'];
            $arrayUser = ['bill_cashless' => $params['remainder']];
        }

        if (isset($params['document_date'])){
            $dateTime = (\DateTime::createFromFormat('d.m.Y H:i:s', $params['document_date']));
            $params['document_date'] = $dateTime->format('Y-m-d H:i:s');
        }

        Fund::insert(1, $params);
        Database::getInstance()->update('users', $arrayUser, '`id`='.$params['user_id']);
        User::checkOverdue($params['user_id'], $params['sum']);
        User::checkDebt($params['user_id'], $params['sum'], $params['bill_type'], Fund::$last_id);

        if (isset($params['document_title'])){
            $data = [
                'fund_id' => Fund::$last_id,
                'sum' => $params['sum']
            ];
            Synchronization::set1CDocument($params['document_title'], $data);
        }

        Database::getInstance()->commit();
        return true;
    }

    public static function setSparePartsRequest($params){

        $params['ip'] = $_SERVER['REMOTE_ADDR'];
        unset($params['smart-token']);

        $result = Database::getInstance()->insert('spare_parts_request', $params);

        if ($result !== true) return false;

        $mailer = new Mailer(Mailer::TYPE_INFO);
        $body = "Пришел запрос с сайта на подбор запчастей:<br>";
        $body .= "<b>vin:</b> {$params['vin']}<br>";
        $body .= "<b>автомобить:</b> {$params['car']}<br>";
        $body .= "<b>год выпуска</b>: {$params['issue_year']}<br>";
        $body .= "<b>описание</b>: {$params['description']}<br>";
        $body .= "<b>телефон</b>: {$params['phone']}<br>";
        $body .= "<b>имя</b>: {$params['name']}<br>";
        $mailer->send([
            'emails' => 'info@tahos.ru',
            'body' => $body,
            'subject' => 'Запрос на подбор запчастей'
        ]);

        return $result;
    }

    public static function setUserArrangement1C($params){
        Database::getInstance()->insert(
            'user_1c_arrangements',
            $params,
            ['duplicate' => [
                'uid' => $params['uid'],
                'title' => $params['title']
            ]]
        );
    }

    /**
     * Проверяет на наличие заказов по виду счета
     * @param integer $user_id
     * @param int $bill_type
     * @return bool true, если есть неоплаченные заказы
     *              false - если нет
     */
    public static function checkOrdersBillModeExists(int $user_id, int $bill_type): bool
    {
        /** @global Database $db */
        $db = $GLOBALS['db'];

        $count = $db->getCount('funds', "`user_id` = $user_id AND `paid` < `sum` AND `bill_type` = $bill_type");
        if ($count) return false;
        return true;
    }

    public static function checkAuthKey($auth_key){
        /** @var mysqli_result $res_user */
        $res_user = User::get(['auth_key' => $auth_key]);
        if (!$res_user->num_rows){
            die('Неверный токен');
        }
        foreach($res_user as $value){
            $user = $value;
        }
        return $user['id'];
    }

    public static function isAuthorized(){
        return isset($_SESSION['user']) && $_SESSION['user'];
    }

    public static function isRegistedTelegram($user_id){
        static $output = [];
        if (isset($output[$user_id])) return $output[$user_id];
        $count = Database::getInstance()->getCount('user_telegram', "`user_id` = $user_id");
        $output[$user_id] = $count > 0;
        return $output[$user_id];
    }

    public static function returnMoney($user_id, $amount, $comment = 'Возврат средств', $bill_type = User::BILL_CASH, $document_date = null){
        $user = [];
        $res_user = User::get(['user_id' => $user_id]);
        foreach ($res_user as $value) $user = $value;

        $remainder = 0;
        if ($bill_type == User::BILL_CASH) {
            $remainder = $user['bill_cash'] + $amount;
        }
        if ($bill_type == User::BILL_CASHLESS) {
            $remainder = $user['bill_cashless'] + $amount;
        }

        if ($document_date){
            $dateTime = (\DateTime::createFromFormat('d.m.Y H:i:s', $document_date));
            $document_date = $dateTime->format('Y-m-d H:i:s');
        }

        Database::getInstance()->startTransaction();
        try{
            Fund::insert(2, [
                'sum' => $amount,
                'remainder' => $remainder,
                'user_id' => $user_id,
                'comment' => $comment,
                'document_date' => $document_date,
                'bill_type' => $bill_type
            ]);
        }
        catch (\Throwable $ex){
            return;
        }
        User::checkDebt($user_id, $amount, $bill_type, Fund::$last_id);

        $field = $bill_type == User::BILL_CASH ? 'bill_cash' : 'bill_cashless';
        User::update($user_id, [$field => $remainder]);

        Database::getInstance()->commit();
    }

    public static function changeReplenishBill($params){
        if ($params['previous_sum'] > $params['sum']){
            $result = self::changeReplenishBillDecrease($params);
        }
        if ($params['previous_sum'] < $params['sum']){
            $result = self::changeReplenishBillIncrease($params);
        }
        if ($result){
            $data = [
                'fund_id' => $params['fund_id'],
                'sum' => $params['sum']
            ];
            Synchronization::set1CDocument($params['document_title'], $data);
        }
        return false;
    }

    private static function changeReplenishBillDecrease($params): bool
    {
        $result = User::get(['user_id' => $params['user_id']]);
        $user = [];
        foreach($result as $value){
            $user = $value;
        }
        $remain = $params['previous_sum'] - $params['sum'];
        if ($params['bill_type'] == User::BILL_CASH){
            $bill = $user['bill_cash'];
            $field = 'bill_cash';
        }
        else{
            $bill = $user['bill_cashless'];
            $field = 'bill_cashless';
        }

        $db = Database::getInstance();
        $db->startTransaction();

        $remainder = $bill - $remain;
        User::update($params['user_id'], [
            $field => $remainder
        ]);
        if ($remainder >= 0){
            $db->update(
                'funds',
                ['sum' => $params['sum'], 'remainder' => $remainder],
                "`id` = {$params['fund_id']}"
            );
            $db->commit();
            return true;
        }

        $remain = abs($remainder);

        $query = self::getQueryDebt(
            "f.paid > 0 AND f.user_id = {$params['user_id']} AND f.issue_id IS NOT NULL AND `bill_type` = {$params['bill_type']}",
            'f.created DESC'
        );
        $result = $db->query($query);

        if (!$result->num_rows){
            return true;
        };

        self::parseUnsetFund($result, $remain, $params['fund_id']);

        $db->update(
            'funds',
            ['sum' => $params['sum'], 'remainder' => $remainder],
            "`id` = {$params['fund_id']}"
        );
        $db->commit();
        return true;
    }

    private static function parseUnsetFund(mysqli_result $result, $remain, $fund_id){
        $db = Database::getInstance();
        foreach($result as $row){
            if ($remain == 0){
                break;
            }
            $whereFunds = "`id` = {$row['id']}";
            $whereDistribution = "`debit_id` = {$row['id']} AND `replenishment_id` = $fund_id";
            if ($row['paid'] <= $remain){
                $db->update('funds', ['paid' => 0], $whereFunds);
                $remain -= $row['paid'];
                $db->delete('fund_distribution', $whereDistribution);
                continue;
            }

            $sum = $row['paid'] - $remain;
            $db->update('funds', ['paid' => $sum], $whereFunds);
            $db->update('fund_distribution', ['sum' => $sum], $whereDistribution);
            $remain = 0;
        }
    }

    private static function changeReplenishBillIncrease($params): bool
    {
        Database::getInstance()->startTransaction();

        $sum = $params['sum'] - $params['previous_sum'];
        $res_user = User::get(['user_id' => $params['user_id']]);
        foreach ($res_user as $value){
            $user = $value;
        }

        if (empty($user)){
            return false;
        }

        $remainder = $user['bill_cash'] + $sum;
        if($params['bill_type'] == User::BILL_CASH){
            $arrayUser = ['bill_cash' => $remainder];
        }
        else{
            $arrayUser = ['bill_cashless' => $remainder];
        }

        User::update($params['user_id'], $arrayUser);
        User::checkOverdue($params['user_id'], $sum);
        User::checkDebt($params['user_id'], $sum, $params['bill_type'], $params['fund_id']);

        Database::getInstance()->update(
            'funds',
            ['sum' => $params['sum'], 'remainder' => $remainder],
            "`id` = {$params['fund_id']}"
        );

        Database::getInstance()->commit();

        return true;
    }

    public static function unsetReplenishBill($params){
        $db = Database::getInstance();
        $db->startTransaction();

        $result = User::get(['user_id' => $params['user_id']]);
        $user = [];
        foreach($result as $value){
            $user = $value;
        }
        if ($params['bill_type'] == User::BILL_CASH){
            $bill = $user['bill_cash'];
            $field = 'bill_cash';
        }
        else{
            $bill = $user['bill_cashless'];
            $field = 'bill_cashless';
        }
        $remainder = $bill - $params['sum'];

        User::update($params['user_id'], [
            $field => $remainder
        ]);

        if ($remainder >= 0){
            return true;
        }

        $query = self::getQueryDebt(
            "f.paid > 0 AND f.user_id = {$params['user_id']} AND f.issue_id IS NOT NULL AND `bill_type` = {$params['bill_type']}",
            'f.created DESC'
        );
        $result = $db->query($query);

        if (!$result->num_rows){
            return true;
        };

        $remain = abs($remainder);
        self::parseUnsetFund($result, $remain, $params['fund_id']);

        $db->delete('funds', "`id` = {$params['fund_id']}");
        $db->delete('1c_documents', "`title` = '{$params['document_title']}'");

        $db->commit();
    }

    public static function getUserArrangement1C($user_id, $arrangement){
        return Database::getInstance()->select_one(
            'user_1c_arrangements',
            '*',
            "`user_id` = {$user_id} AND `uid` = '$arrangement'"
        );
    }
}
