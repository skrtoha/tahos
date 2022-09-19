<?php
namespace core;

use mysqli_result;

class User{
	public static function noOverdue($user_id){
		$query = Fund::getQueryListFunds(
			"f.user_id = $user_id AND f.overdue > 0",
			'date_payment > CURRENT_DATE'
		);
		$res_funds = $GLOBALS['db']->query($query, '');
		if ($res_funds->num_rows) return false;
		return true;
	}
	public static function checkOverdue($user_id, $creditedAmount){
		$query = Fund::getQueryListFunds("f.user_id = $user_id AND f.overdue > 0", '', 'f.created');
		$res = $GLOBALS['db']->query($query, '');
		if (!$res->num_rows) return;
		
		$remain = $creditedAmount;
		foreach($res as $fund){
			$remain = $remain - $fund['overdue'];
			if ($remain > 0) $GLOBALS['db']->update('funds', ['overdue' => 0], "`id` = {$fund['id']}");
			else{
				$GLOBALS['db']->update('funds', ['overdue' => abs($remain)], "`id` = {$fund['id']}");
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
	public static function get(array $params = []){
		$db = $GLOBALS['db'];
		$where = '';
        $having = '';
        $limit = '';
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
					case 'user_id': $where .= "u.id = {$value} AND "; break;
					case 'withWithdraw': $where .= "u.bill < 0 AND "; break;
                    case 'full_name': $having .= "full_name LIKE '%{$value}%' AND "; break;
                    case 'limit': $limit = "LIMIT $value"; break;
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
			$limit
		";
		return $db->query($q_user, '');
	}

	/**
	 * updates reserved funds for user
	 * @param  [integer] $user_id user_id
	 * @param  [inter] $price value for increase|reduse reserved_funds
	 * @return true if successfully updated
	 */
	public static function updateReservedFunds($user_id, $price, $act = 'plus'){
		$sign = $act == 'plus' ? '+' : '-';
		return self::update(
			$user_id,
			['reserved_funds' => "`reserved_funds` $sign {$price}"]
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
		$db = $GLOBALS['db'];
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
		return $GLOBALS['db']->update('users', $fields, "`id`=$user_id");
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
		return $GLOBALS['db']->query("
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
				CONCAT_WS (' ', ot.title, u.organization_name),
				CONCAT_WS (' ', u.name_1, u.name_2, u.name_3)
			)
		";
	}
    
    public static function updateSettings($settings, $user){
        $update = $settings['data'];
        $db = $GLOBALS['db'];
        
        if (!$update['is_subscribe']) $update['is_subscribe'] = 0;
        if (!$update['get_news']) $update['get_news'] = 0;
        if (!$update['show_all_analogies']) $update['show_all_analogies'] = 0;
        if (!$update['get_notifications']) $update['get_notifications'] = 0;
        if (!$update['get_sms_provider_refuse']) $update['get_sms_provider_refuse'] = 0;
    
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
            $settings['default_address'] ?? []
        );
        
        return $db->update('users', $update, "`id` = {$user['id']}");
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
}
