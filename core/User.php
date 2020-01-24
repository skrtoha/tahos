<?php
namespace core;
class User{
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
	public static function get($user_id = null){
		$db = $GLOBALS['db'];
		if (!$user_id && !$_SESSION['user']) return [
			'markup' => 0,
			'designation' => '<i class="fa fa-rub" aria-hidden="true"></i>',
			'currency_id' => 1,
			'rate' => 1,
			'show_all_analogies' => 0
		];
		if (!$user_id) $user_id = $_SESSION['user'];
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
				i.coords AS issue_coords
			FROM #users u
			LEFT JOIN #currencies c ON c.id=u.currency_id
			LEFT JOIN #issues i ON i.id=u.issue_id
			WHERE u.id=$user_id
		";
		$user = $db->select_unique($q_user, 'result');
		return $user[0];
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
			['reserved_funds' => "reserved_funds $sign {$price}"]
		);
	}

	/**
	 * adds bonus count to user
	 * @param [integer] $user_id user_id
	 * @param [integer] $item_id item_id
	 * @param [integer] $sum summ from where calculating bonus count
	 * @return [boolean] true is updated successfully, false - if no user bonus program
	 */
	public static function setBonusProgram($user_id, $item_id, $sum){
		$db = $GLOBALS['db'];
		$user = self::get($user_id);
		if (!$user['bonus_program']) return false;
		$current_bonus_count = floor($sum * self::$bonus_size / 100);
		$title = orderValue::getTitleComment($item_id);
		Fund::insert(3, [
			'sum' => $current_bonus_count,
			'remainder' => $user['bonus_count'] + $current_bonus_count,
			'user_id' => $user_id,
			'comment' => addslashes('Начисление бонусов за заказ "'.$title.'"')
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
}
