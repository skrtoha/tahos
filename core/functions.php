<?php
function replace_winword_chars($val){
			$_r=array(
						"–"=>"-",
						"—"=>"-",
						"‘"=>"\"",
						"’"=>"\"",
						"“"=>"\"",
						"”"=>"\"",
						"„"=>"\"",
						"‹"=>"\"",
						"›"=>"\"",
						"«"=>"\"",
						"»"=>"\"",
						"…"=>"...",
			"“"=>"\"",
			"”"=>"\"",
			);
	$val = strtr($val,$_r);
	return $val;
	debug();
}
function translite($var){
	$var = mb_strtolower($var, 'UTF-8');
	$var = replace_winword_chars($var);
	// $var = str_replace(" ","-",$var);
	// $var = str_replace("__","-",$var);
	$alpha = array("а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d","е"=>"e","ё"=>"jo","ж"=>"zh","з"=>"z","и"=>"i","й"=>"i","к"=>"k","л"=>"l","м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r","с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"kh","ц"=>"c","ч"=>"ch","ш"=>"sh","щ"=>"shh","э"=>"je","ю"=>"yu","я"=>"ja","ы"=>"i","ъ"=>"","ь"=>"","/"=>"","\\"=>"", ' ' => '-');
	$var = strtr($var,$alpha);
	return $var;
}
function debug($obj, $name = ''){?>
	<div style="clear: both"></div>
	<?if ($name){?>
		<p style="font-weight: 800"><?=$name?>:</p>
	<?}?>
	<pre><?print_r($obj)?></pre>
<?}
function message($text, $type = true){
	if (!$type) $type_message = "error";
	else $type_message = 'ok';
	setcookie('message', $text, 0, '/');
	setcookie('message_type', $type_message, 0, '/');
}
function get_bill(){
	global $db;

    $res_user = \core\User::get(['user_id' => $_SESSION['user']]);
    foreach($res_user as $value) $user = $value;

	if (!$user['bill_total']) return 'нет средств';

    return "<span class='price_format_2'>{$user['bill_total']}</span>".$user['designation'];
}
function get_price($provider_item){
	global $db;
	$provider = $db->select('providers', 'id,currency_id,percent', '`id`='.$provider_item['provider_id']);
	$provider_currency = $provider[0]['currency_id'];
	$provider_percent = $provider[0]['percent'];
	$provider_rate = $db->getFieldOnID('currencies', $provider_currency, 'rate');
	$rubls = $provider_item['price'] * $provider_rate;
	return round($provider_percent/100*$rubls + $rubls);
}
function getStrTemplate($template){
	global $db, $deliveries;
	$str = '';
	$not = ['id', 'user_id', 'created', 'sub_delivery', 'json'];
	if ($template['sub_delivery']) $template['delivery_way'] = $template['sub_delivery'];
	foreach ($template as $key => $value){
		if (in_array($key, $not)) continue;
		if (!$value && $key != 'pasport') continue;
		// if ($key == 'sub_delivery' && $value)
		switch ($key){
			case 'delivery_way':
				$str .= "<span value='$value' key='$key'>{$deliveries[$value]['title']}</span>, ";
				break;
			case 'insure':
			case 'pasport':
				$str .= "<span value='$value' key='$key'></span>";
				break;
            case 'address_id':
                 $value = core\UserAddress::getString(
                     $template['address_id'],
                     json_decode($template['json'], true)
                 );
                 $str .= "<span value='{$template['address_id']}' key='address_id'>$value</span>";
                break;
			default:
				$str .= "<span value='$value' key='$key'>$value</span>, ";
		}
	}
	return substr($str, 0, -2);
}

function get_basket(){
	global $db;
	$basket = $db->select_unique("
		SELECT 
			b.*,
			IF (
				i.article_cat != '', 
				i.article_cat, 
				IF (
					i.article !='',
					i.article,
					ib.barcode
				)
			) as article_cat,
            i.article,
            b.item_id,
			br.title as brend,
			IF (i.title_full != '', i.title_full, i.title) as title
			FROM
				#basket b
			JOIN #items i ON i.id=b.item_id
			JOIN #brends br ON br.id=i.brend_id
			LEFT JOIN #item_barcodes ib ON ib.item_id = i.id
			WHERE b.user_id={$_SESSION['user']}
	", false);
	if (empty($basket)) return [];
	foreach($basket as $key => $value){
		$b = & $basket[$key];
		unset($b['user_id'], $b['comment']);
		$b['href'] = "/article/{$value['item_id']}-{$value['article']}/noUseAPI";
	} 
	return $basket;
}
function get_orders($params, $flag = ''){
	global $db;
	$query = "
		SELECT 
			ov.order_id,
			ov.store_id,
			ov.item_id,
			ov.comment,
			IF (i.title_full, i.title_full, i.title) AS title,
			IF (
				i.article_cat != '', 
				i.article_cat, 
				IF (
					i.article !='',
					i.article,
					ib.barcode
				)
			) AS article,
			b.title AS brend,
			i.brend_id,
			ps.cipher,
			IF (ps.noReturn, 'class=\"noReturn\" title=\"Возврат поставщику невозможен!\"', '') AS noReturn,
			ov.item_id,
			ov.price,
			CEIL(ov.price - ov.price * p.return_percent / 100) AS return_price,
			ps.provider_id,
			ov.quan,
			ov.ordered,
			ov.arrived,
			ov.issued,
			ov.returned,
			ov.declined,
			ov.status_id,
			os.title AS status,
			os.class AS status_class,
			@delivery := ps.delivery AS delivery,
			si.packaging,
			IF (c.id IS NULL, 'disable', '') AS message,
			DATE_FORMAT(o.created, '%d.%m.%Y') AS date_from,
			DATE_FORMAT(DATE_ADD(o.created, Interval @delivery DAY), '%d.%m.%Y') AS date_to,
			c.id AS correspond_id,
			TO_DAYS(CURDATE()) - TO_DAYS(ov.updated) AS days_from_purchase,
			@end_date := DATE_ADD(ov.updated, Interval ps.daysForReturn DAY) AS end_date,
			IF(
				@end_date > CURDATE() AND 
				ov.status_id = 1 AND 
				ps.noReturn != 1 AND 
				(r.status_id = 5 OR r.status_id IS NULL),
				1,
				0
			) AS is_return_available,
			r.status_id AS return_status_id,
			rs.title AS ordered_return
		FROM 
			#orders_values ov
		LEFT JOIN #items i ON i.id=ov.item_id
		LEFT JOIN #provider_stores ps ON ps.id=ov.store_id
		LEFT JOIN #providers p ON p.id = ps.provider_id
		LEFT JOIN #orders_statuses os ON ov.status_id=os.id
		LEFT JOIN #store_items si ON si.store_id=ov.store_id AND si.item_id=ov.item_id
		LEFT JOIN #returns r 
			ON 
				r.order_id = ov.order_id AND
				r.store_id = ov.store_id AND
				r.item_id = ov.item_id
		LEFT JOIN #return_statuses rs ON rs.id = r.status_id
		LEFT JOIN #brends b ON b.id=i.brend_id
		LEFT JOIN #item_barcodes ib ON ib.item_id = i.id
		LEFT JOIN #orders o ON o.id=ov.order_id
		LEFT JOIN #corresponds c 
			ON 
				c.order_id = ov.order_id AND
				c.store_id = ov.store_id AND
				c.item_id = c.item_id
		WHERE 
			o.is_draft = 0 AND
			o.user_id = {$_SESSION['user']} 
	";
	if ($params['period'] == 'custom'){
		$begin = DateTime::createFromFormat('d.m.Y', $params['begin']);
		$begin = $begin->format('Y-m-d');
		$end = DateTime::createFromFormat('d.m.Y H:i:s', $params['end'].' 23:59:59');
		$end = $end->format('Y-m-d H:i:s');
		$query .= " AND o.created BETWEEN '$begin' AND '$end'";
	};
	if ($params['text']) $query .= " AND i.article='".core\Item::articleClear($params['text'])."'";
	if (isset($params['status_id'])){
		$str = '';
		foreach($params['status_id'] as $key => $value) $str .= "$key,";
		$str = substr($str, 0, -1);
		$query .= " AND ov.status_id IN ($str)";
	}
	$query .= "
		ORDER BY o.created DESC
	";
	// print_r($statuses);
	return $db->query($query, $flag);
}
function get_order_group($params, $flag = ''){
	global $db;
	$query = "
		SELECT
			o.id,
			DATE_FORMAT(o.created, '%d.%m.%Y %H:%i') AS date,
			GROUP_CONCAT(ov.price) AS price,
			GROUP_CONCAT(ov.quan) AS quan,
			GROUP_CONCAT(ov.ordered) AS ordered,
			GROUP_CONCAT(ov.arrived) AS arrived,
			GROUP_CONCAT(ov.issued) AS issued,
			GROUP_CONCAT(ov.returned) AS returned,
			getOrderStatus(GROUP_CONCAT(ov.status_id)) AS status,
			o.user_id,
			o.is_draft,
			o.is_new,
            min(
                if (topi.payed = 1, '', topi.invoice_id)
            ) as invoice
		FROM
			#orders o
		LEFT JOIN #orders_values ov ON ov.order_id = o.id
		LEFT JOIN tahos_order_paykeeper_invoice topi on o.id = topi.order_id
		WHERE
			o.user_id={$_SESSION['user']} AND
			o.is_draft = 0
	";
	if ($params['period'] == 'custom'){
		$begin = DateTime::createFromFormat('d.m.Y', $params['begin']);
		$begin = $begin->format('Y-m-d');
		$end = DateTime::createFromFormat('d.m.Y H:i:s', $params['end'].' 23:59:59');
		$end = $end->format('Y-m-d H:i:s');
		$query .= " AND o.created BETWEEN '$begin' AND '$end'";
	};
	$query .= " GROUP BY ov.order_id";
	if (isset($params['status'])){
		$str = '';
		foreach($params['status'] as $key => $value) $str .= "'$key',";
		$str = substr($str, 0, -1);
		$query .= " HAVING status IN ($str)";
	}
	$query .= ' ORDER BY o.created DESC';
	return $db->query($query, $flag);
}
/**
 * cheking is there even one in basket
 * @param  [type]  $store_items_list array of store_items
 * @return boolean true if exists
 */
function isInBasketExists($store_items_list){
	foreach($store_items_list as $store_item){
		foreach($store_item['store_item']['list'] as $value){
			if ($value['in_basket']) return true;
		}
		foreach($store_item['store_item']['prevails'] as $value){
			if ($value['in_basket']) return true;
		}
	}
	return false;
}
function getInBasket($basket){
	$output = array();
	foreach($basket as $b) $output[$b['store_id'].':'.$b['item_id']] = $b['quan'];
	return $output;
}
function trimStr($string, $length = 200){
    if (mb_strlen($string) < $length) return $string;
    $string = strip_tags($string);
    $string = mb_substr($string, 0, $length);
    $string = rtrim($string, "!,.-");
    $string = mb_substr($string, 0, strrpos($string, ' '));
    return $string."...";
}
function payment_funds($type, $user, $difference = false){
	global $db;
	if (!$user[$type]) return '0';
	if ($difference) $user[$type] = $user['bill'] - $user['reserved_funds'];
	$currency = $db->select('currencies', 'rate,designation', '`id`='.$user['currency_id']); $currency = $currency[0];
	switch ($user['currency_id']){
		case 1:
			$result = round($user[$type]/$currency['rate']);
			return "<span class='price_format'>$result</span>";
			break;
		default:
			$result = round($user[$type]/$currency['rate'], 2);
			return "<span class='price_format_2'>$result</span>";
	}
}
?>
