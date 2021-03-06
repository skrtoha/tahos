<?php
namespace core;
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
	 * @param  [integer] $user_id user id
	 * @return [type] if no $user_id and $_SESSION['user'] returns default values, if no user_id then by $_SESSION['user']
	 */
	public static function get($params = []){
		$db = $GLOBALS['db'];
		$where = '';
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
				}
			}
			if ($where){
				$where = substr($where, 0, -5);
				$where = "WHERE $where";
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
}
