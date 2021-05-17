<?php  
function article_clear($article){
	return preg_replace('/[^\w_а-яА-Я]+/u', '', $article);
}
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
}
function uppercase_first_letter($str){
	$left = substr($str, 0, 1);
	$right = substr($str, 1, strlen($str) - 1);
	return mb_strtoupper($left).$right;
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
function shot_str($str, $len){
	$len -= 3;
	if (mb_strlen($str, "UTF-8") < $len) return $str;
	$text_cut = mb_substr($str, 0, $len, "UTF-8");
	$text_explode = explode(" ", $text_cut);
	unset($text_explode[count($text_explode) - 1]);
	return implode(" ", $text_explode).'...';
}
function toStringKey($array, $key){
	$str = '';
	foreach ($array as $k => $v) $str .= $v[$key].',';
	return substr($str, 0, -1);
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
	// echo "$text $type_message";
	setcookie('message', $text, 0, '/');
	setcookie('message_type', $type_message, 0, '/');
}
function get_cookie_flilters($cat_id = 0){
	if ($_COOKIE['filters']){
		$temp = json_decode($_COOKIE['filters'], true);
		$temp_2 = $temp['filters'];
		$category_id = $_GET['category_id'] ? $_GET['category_id'] : $cat_id;
		foreach ($temp_2 as $value) {
			if ($value['category_id'] == $category_id){
	$temp = $value['values'];
	break;
			} 
		}
		foreach ($temp as $value) $cookie_filters[$value['name']] = $value['value'];
		return $cookie_filters;
	}
	else return false;
}
function get_bill(){
	global $db;
	$user = $db->select('users', 'currency_id,bill', '`id`='.$_SESSION['user']); $user = $user[0];
	if (!$user['bill']) return 'нет средств';
	$currency = $db->select('currencies', 'rate,designation', '`id`='.$user['currency_id']); $currency = $currency[0];
	switch ($user['currency_id']){
		case 1:
			$result = round($user['bill']/$currency['rate']);
			return "<span class='price_format'>$result</span>".$currency['designation'];
			break;
		case 6:
			$result = round($user['bill']/$currency['rate']*10);
			return "<span class='price_format'>$result</span>".$currency['designation'];
			break;
		default:
			$result = round($user['bill']/$currency['rate'], 2);
			return "<span class='price_format_2'>$result</span>".$currency['designation'];
	}
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
function begin_date(){
	return date('d.m.Y', time() - 60 * 60 * 24 * 30);
}
function end_date(){
	return date('d.m.Y', time());
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
	$not = ['id', 'user_id', 'created', 'sub_delivery'];
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
			default:
				$str .= "<span value='$value' key='$key'>$value</span>, ";
		}
	}
	return substr($str, 0, -2);
}
function get_rating($rate, $ratings){
	if ($rate <= $ratings[1]) return 0;
	if ($rate >= $ratings[10]) return 10;
	for ($i = 1; $i <= 9; $i++){
		if ($rate > $ratings[$i] && $rate <= $ratings[$i + 1]) return $i;
	}
	return 10;
}

function get_filters($category_id){
	global $db;
	$query = "
		SELECT 
			f.id as filter_id,
			f.title,
			fv.id as value_id,
			fv.title as filter_value,
			f.slider
		FROM 
			#filters as f
		LEFT JOIN 
			#filters_values fv
		ON
			fv.filter_id=f.id
		WHERE category_id=$category_id
		ORDER BY f.pos, fv.title
	";
	$filters = $db->select_unique($query, '');
	if (!empty($filters)){
		foreach ($filters as $value){
			$fv = $value['filter_id'];
			$f = & $t_filters[$fv];
			$f['title'] = $value['title'];
			$f['slider'] = $value['slider'];
			$f['filters_values'][$value['value_id']] = $value['filter_value'];
		} 
	}
	return $t_filters;
}
function cat_get_chunks_items($query){
	global $db, $settings, $res;
	$res_items = $db->query($query, '');
	if (!$res_items->num_rows) return false;
	$res['num_rows'] = $res_items->num_rows;
	$ch = 0;
	$bl = true;
	while($bl){
		for ($i = 0; $i < $settings['cat_perPage']; $i++){
			$value = $res_items->fetch_assoc();
			if ($value) $chunks[$ch][] = $value;
			else {
				$bl = false;
				break;
			}
		} 
		if ($bl) $ch++;
	}
	return $chunks;
}
function cat_get_items_values($items){
	global $db, $res;
	$ids = '';
	if (empty($items)) return false;
	foreach ($items as $value) $ids .= "{$value['id']},";
	$ids = substr($ids, 0, -1);
	$q_items_values = "
		SELECT 
			iv.item_id, fv.title, fv.id, fv.filter_id
		FROM #items_values iv
		JOIN #filters_values fv
		ON fv.id=iv.value_id
		WHERE iv.item_id IN ($ids)
	";
	$res_items_values = $db->query($q_items_values, false);
	if (!$res_items_values->num_rows) return false;
	while($row = $res_items_values->fetch_assoc()){
		$items_values[$row['item_id']][$row['filter_id']] = $row['title'];
	} 
	return $items_values;
}
function getQueryArticleStoreItems($item_id, $search_type, $filters = []){
	global $db, $user;
	if ($_SESSION['user']){
		$join_basket = "
			LEFT JOIN 
					#basket ba 
			ON 
				si.store_id=ba.store_id AND 
				si.item_id=ba.item_id AND 
				ba.user_id={$_SESSION['user']}
		";
		$ba_quan = " ba.quan as in_basket, ";
		$userDiscount = "@price * {$user['discount']} / 100";
	} 
	else $userDiscount = 0;
	if (!$user['show_all_analogies'] && $search_type == 'analogies') $hide_analogies = true;
	else $hide_analogies = false;

	if ($search_type == 'analogies'){
		$selectAnalogies = 'diff.status, ';
		$whereAnalogies = 'AND diff.status IN (0, 1)';
	}

	$q_item = "
		SELECT
			diff.item_diff as item_id,
			$selectAnalogies
			si.in_stock,
			IF(
				si.packaging != 1,
				CONCAT(
					'&nbsp;(<span>уп.&nbsp;',
					si.packaging,
					'&nbsp;шт.</span>)'
				),
				''
			) as packaging_text,
			si.packaging,
			b.title as brend,
			i.brend_id as brend_id,
			i.photo,
			ps.cipher,
			ps.provider_id,
			ps.id as store_id,
			IF (ps.workSchedule IS NOT NULL, ps.workSchedule, p.workSchedule) AS workSchedule,
			IF (
				i.article_cat != '', 
				i.article_cat, 
				IF (
					i.article !='',
					i.article,
					ib.barcode
				)
			) as article,
			IF (i.title_full!='', i.title_full, i.title) as title_full,
			@delivery := CASE
				WHEN aok.order_term IS NOT NULL THEN aok.order_term
				ELSE
					IF (si.in_stock = 0, ps.under_order, ps.delivery) 
			END AS delivery,
			IF(ps.calendar IS NOT NULL, ps.calendar, p.calendar) AS  calendar,
			IF(ps.workSchedule IS NOT NULL, ps.workSchedule, p.workSchedule) AS  workSchedule,
			ps.noReturn,
			ps.percent,
			@price := si.price * c.rate + si.price * c.rate * ps.percent / 100,
			CEIL(@price - $userDiscount) AS price,
			$ba_quan
			IF (
				i.applicability !='' || i.characteristics !=''  || i.full_desc !='' || i.photo != '',
				1,
				0
			) as is_desc
		FROM #$search_type diff
		RIGHT JOIN #store_items si ON si.item_id=diff.item_diff
		LEFT JOIN #provider_stores ps ON ps.id=si.store_id AND ps.block = 0
		LEFT JOIN #providers p ON p.id = ps.provider_id
		LEFT JOIN #currencies c ON c.id=ps.currency_id
		LEFT JOIN #items i ON diff.item_diff=i.id
		LEFT JOIN #brends b ON b.id=i.brend_id
		LEFT JOIN #item_barcodes ib ON ib.item_id = i.id
		LEFT JOIN #autoeuro_order_keys aok ON aok.item_id = si.item_id AND aok.store_id = si.store_id
		$join_basket
		WHERE diff.item_id=$item_id $whereAnalogies
	";
	if ($hide_analogies) $q_item .= ' AND si.item_id IS NOT NULL';
	if (!empty($filters)){
		if ($filters['in_stock']) $q_item .= ' AND si.in_stock>0';
		if (isset($filters['is_main'])) $q_item .= " AND ps.is_main = {$filters['is_main']}";
		if (isset($filters['price_from']) && isset($filters['time_from'])){
			$q_item .= "
				HAVING
					price BETWEEN {$filters['price_from']} AND {$filters['price_to']} AND
					delivery BETWEEN {$filters['time_from']} AND {$filters['time_to']} 
			";

		}
	}
	else $q_item .= " HAVING price>0";

	//строка закоментирована, т.к. затягивался поиск
	/*if (!$hide_analogies){
		if (empty($filters)) $q_item .= " OR price IS NULL";
		else $q_item .= " OR si.price IS NULL";
	} */

	$q_item .= ' ORDER BY si.price, delivery';
	return $q_item;
}
function article_store_items($item_id, $filters = [], $search_type = 'articles'){
	global $db, $user;
	$q_item = getQueryArticleStoreItems($item_id, $search_type, $filters);
	$res_item = $db->query($q_item, '');
	if (!$res_item->num_rows){
		$q_item = "
			SELECT 
				IF (i.title_full<>'', i.title_full, i.title) as title_full,
				IF (
						i.article_cat != '', 
						i.article_cat, 
						IF (
							i.article !='',
							i.article,
							ib.barcode
						)
				) as article,
				b.title as brend,
				b.id as brend_id,
				IF (
					i.applicability !='' || i.characteristics !=''  || i.full_desc !='',
					1,
					0
				) as is_desc,
				i.photo,
				i.id as item_id
			FROM #$search_type diff
			LEFT JOIN #items i ON i.id=diff.item_diff
			LEFT JOIN #brends b ON b.id=i.brend_id
			LEFT JOIN #item_barcodes ib ON ib.item_id = i.id
			WHERE
				diff.item_id=$item_id
		";
		$items = $db->select_unique($q_item, '');
		foreach($items as $key => $item){
			$array['store_items'][$item['item_id']] = $item;
			$array['store_items'][$item['item_id']]['list'] = array();
		}
		$array['prices'] = array();
		$array['deliveries'] = array();
		$array['query'] = $db->query($q_item, 'query');
		return $array;
	} 

	$items = core\Provider\Tahos::parseResItem($res_item);

	$c = 0;
	foreach ($items as $v){
		$p = & $store_items[$v['item_id']];
		$p['title_full'] = $v['title_full'];
		$p['article'] = $v['article'];
		$p['brend'] = $v['brend'];
		$p['brend_id'] = $v['brend_id'];
		$p['is_desc'] = $v['is_desc'];
		$p['photo'] = $v['photo'];
		$p['item_id'] = $v['item_id'];
		$p['status'] = $v['status'];
		$list['delivery_date'] = core\Provider::getDiliveryDate(
			json_decode($v['workSchedule'], true), 
			json_decode($v['calendar'], true),
			$v['delivery']
		);

		// debug(core\Provider::$counterDaysDelivery);
		if (core\Provider::$todayIssue) $v['prevail'] = 1;
		else $v['prevail'] = 0;

		$list['store_id'] = $v['store_id'];
		$list['in_stock'] = (int) $v['in_stock'] ? $v['in_stock'] : 'Под заказ';
		$list['cipher'] = $v['cipher'];
		$list['packaging'] = $v['packaging'];
		$list['packaging_text'] = $v['packaging_text'];

		if (!(int)$v['in_stock'] && $v['provider_id'] != 1) continue;
		
		$list['delivery'] = $v['delivery'];
		$list['price'] = $v['price'];
		$list['in_basket'] = $v['in_basket'];
		$list['noReturn'] = $v['noReturn'] ? "class='noReturn' title='Возврат поставщику невозможен!'" : '';
		if ($v['prevail']){
			$p['prevails'][$v['store_id']] = $list;
			$prices[] = $v['price'];
			$deliveries[] = $v['delivery'];
			continue;
		}
		else{
            if (isset($filters['in_stock']) && $filters['in_stock']) continue;
            $p['list'][$v['store_id']] = $list;
        }
		$p['deliveries'][] = $v['delivery'];
		$prices[] = $v['price'];
		$deliveries[] = $v['delivery'];
	}
	
	foreach($store_items as $key => $value){
		$p = & $store_items[$key];
		if (!empty($p['list'])) $p['list'] = array_merge($p['list']);
		if (!empty($p['prevails'])){
			usort($p['prevails'], function($a, $b){
				if ($a['delivery'] <  $b['delivery']) return false;
			});
		} 
		if (!empty($p['list'])) $p['min_price'] = $p['list'][0];
		if (isset($p['deliveries']) && count($p['deliveries']) == 2) $p['min_delivery'] = $p['list'][1];
		else{
			if (isset($p['deliveries']) && count($p['deliveries']) > 2){
				$min_delivery = min($p['deliveries']);
				unset($p['deliveries']);
				foreach ($p['list'] as $k => $v){
					if ($v['delivery'] == $min_delivery) $p['min_delivery'] = $v;
				}
			}
		}
	}

	return [
		'store_items' => $store_items,
		'prices' => $prices ? $prices : array(),
		'deliveries' => $deliveries ? $deliveries : array(),
		'query' => $db->query($q_item, 'query'),
		'hide_analogies' => $hide_analogies,
		'user' => $user
	];
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
			) as article,
			br.title as brend,
			IF (i.title_full != '', i.title_full, i.title) as title
			FROM
				#basket b
			JOIN #items i ON i.id=b.item_id
			JOIN #brends br ON br.id=i.brend_id
			LEFT JOIN #item_barcodes ib ON ib.item_id = i.id
			WHERE b.user_id={$_SESSION['user']}
	", false);
	if (empty($basket)) return false;
	foreach($basket as $key => $value){
		$b = & $basket[$key];
		unset($b['user_id'], $b['comment']);
		$b['href'] = core\Item::getHrefArticle($b['article']);
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
			ov.user_id = {$_SESSION['user']} 
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
			o.is_new
		FROM
			#orders o
		LEFT JOIN #orders_values ov ON ov.order_id=o.id
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
?>
