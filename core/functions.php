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
	echo "<p>$left</p>";
	$right = substr($str, 1, strlen($str) - 1);
	echo "<p>$right</p>";
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
function get_status_filters($is_filters, $value){
	if ($is_filters){
		if ($value == 1) return "checked";
		else return "";
	}
	else return "checked";
}
function get_date($time){
	$time_array = explode('.', $time);
	$day = $time_array[0];
	$mounth = $time_array[1];
	$year = $time_array[2];
	// return date("d.m.Y H:i", mktime(0,0,0, $mounth, $day, $year));
	return mktime(0,0,0, $mounth, $day, $year);
}
function begin_day($time){
	$date = getdate($time);
	return mktime(0,0,0, $date['mon'], $date['mday'], $date['year']);
}
function end_day($time){
	$time_array = explode('.', $time);
	$day = $time_array[0];
	$mounth = $time_array[1];
	$year = $time_array[2];
	return mktime(23, 59, 59, $mounth, $day, $year);
}
function get_items_search($text){
	global $db;
	if (!$text) return false;
	switch ($_COOKIE['search_type']) {
		case 'articles':
		case '':
			$array = $db->select('items', 'id', "`article`='$text' AND `type`=1");
			break;
		case 'subtitutes':
			$items = $db->select('items', 'id', "`article`='$text' AND `type`=1");
			$where = "";
			foreach ($items as $item) $where .= "`item_type`=".$item['id']." OR ";
			$where = substr($where, 0, -4);
			$where = "($where) AND `type`=2";
			$array = $db->select('items', 'id', $where);
			break;
		case 'analogies':
			$items = $db->select('items', 'id', "`article`='$text' AND `type`=1");
			$where = "";
			foreach ($items as $item) $where .= "`item_type`=".$item['id']." OR ";
			$where = substr($where, 0, -4);
			$where = "($where) AND `type`=3";
			$array = $db->select('items', 'id', $where);
			break;
	}
	if (count($array)) return $array;
	else return false;
}
function get_items($kol_elem = 0, $cat_id = 0){
	global $db;
	$settings = $db->select('settings', "*");
	$per_page = $settings[0]['per_page'];
	$sort_type = $_COOKIE['sort_type'] ? $_COOKIE['sort_type'] : "title";
	$sort_direct = $_COOKIE['sort_direct'];
	$category_id = $cat_id ? $cat_id : $_GET['category_id'];
	$cookie_filters = get_cookie_flilters($category_id);
	// echo count($cookie_filters);
	// print_r($cookie_filters);
	if ($cookie_filters){
		$str = "";
		$where = "`category_id`=$category_id";
		if ($cookie_filters['brend']) $where .= " AND `brend_id`=".$cookie_filters['brend'];
		// echo "$where";
		$count_values = 0;
		foreach ($cookie_filters as $key => $value) {
			if ($key != "brend"){
	$str .= "`value_id`=$value OR ";
	$count_values++;
			} 
		}
		$str = substr($str, 0, -3);
		if ($count_values){
			$temp = $db->select('items_values', "item_id", "$str ORDER BY `item_id`");
			$count = count($temp);
			// echo "$count";
			// if ($count == 1) 
			if ($count == $count_values) return false;
			// echo "$count";
			for ($i = 0; $i < $count - $count_values; $i++) {
	$bool = true;
	for ($j = $i; $j < $i + $count_values; $j++){
		if ($temp[$i]['item_id'] != $temp[$j]['item_id']) $bool = false;
	}
	if ($bool) $items[] = $temp[$i]['item_id'];
			}
			if (count($items)){
	$str = "";
	foreach ($items as $value)  $str .= "`id`=$value OR ";
	$str = substr($str, 0, -3);
	$where .= " AND ($str)";
			} 
			else return false;
			foreach ($_POST as $key => $value) {
	# code...
			}
			$where .=  " ORDER BY `$sort_type` $sort_direct";
		}
	}
	else $where = "`category_id`=$category_id ORDER BY `$sort_type` $sort_direct";
	$array = $db->select('items', "*", "$where", '', '', "$kol_elem,$per_page");
	return count($array) ? $array : false;
}
function array_id($array){
	foreach ($array as $key => $value){
		foreach ($value as $k => $v)if ($k != "id") $new[$value['id']][$k] = $v;
	}
	return $new;
}
function print_array($array){
	foreach ($array as $key => $value) {
		echo "$key : ";
		print_r($value);
		echo "<br>";
	}
}
function p_arr($array, $table = true){
	if ($table){
		foreach ($array as $key => $value){
			foreach ($value as $k => $val) $names[] = $k;
			break;
		}?>
		<table style="width: auto">
			<tr>
				<?foreach ($names as $value){?>
					<td style="padding: 10px 10px;vertical-align: middle;text-align: center;border-bottom:1px solid grey"><?=$value?></td>
				<?}?>
			</tr>
			<?foreach ($array as $key => $value) {?>
				<tr>
					<?foreach ($value as $val){?>
						<td style="padding: 10px 10px;vertical-align: middle;text-align: center;border-bottom:1px solid grey"><?=$val?></td>
					<?}?>
				</tr>
			<?}?>
		</table>
	<?}
}
function get_values_item($array){
	global $db;
	$where = "";
	foreach ($array as $value) $where .= "`id`=".$value['value_id']." OR ";
	$where = substr($where, 0, -4);
	$filters_values = $db->select('filters_values', "*", "$where GROUP BY `filter_id`");
	if (!count($filters_values)) return false;
	$where = "";
	foreach ($filters_values as $value) $where .= "`id`=".$value['filter_id']." OR ";
	$where = substr($where, 0, -4);
	$temp = $db->select('filters', "title,id", $where, 'position');
	if (!count($temp)) return false;
	foreach($temp as $key => $value) $filters[$value['id']] = $value['title'];
	foreach($filters_values as $value)$new[$filters[$value['filter_id']]] = $value['title'];
	return $new;
}
function get_sort(){
	$sort_type = $_COOKIE['sort_type'] ? $_COOKIE['sort_type'] : 'title';
	$sort_direct = $_COOKIE['sort_direct'];
	switch($sort_type){
		case 'title':?>
			<a sort_type="title" sort_direct="<?=$sort_direct?>" class="active <?=$sort_direct?>" href="#">Наименование</a>
			<a sort_type="price" class="" href="#">Цена</a>
			<a sort_type="rating" class="" href="#">Рейтинг</a>
			<?break;
		case 'price':?>
			<a sort_type="title" href="#">Наименование</a>
			<a sort_type="price" sort_direct="<?=$sort_direct?>" class="active <?=$sort_direct?>" href="#">Цена</a>
			<a sort_type="rating" class="" href="#">Рейтинг</a>
			<?break;
		case 'rating':?>
			<a sort_type="title" href="#">Наименование</a>
			<a sort_type="price" class="" href="#">Цена</a>
			<a sort_type="rating" sort_direct="<?=$sort_direct?>" class="active <?=$sort_direct?>" class="" href="#">Рейтинг</a>
			<?break;
	}
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
//перевод в валюту пользователя при отображении результатов
function price_article($provider_item, $show_currency = true){
	global $currencies, $user, $providers;
	//если пользователь не авторизован, то просто возращаем цену в рублях
	if (!$user) return $provider_item['price'];
	$rubls = $provider_item['price'];
	$user_rate = $currencies[$user['currency_id']]['rate'];
	// echo $provider_item['price']." $provider_currency $provider_percent $provider_rate $rubls $user_rate";
	// return;
	if ($user['currency_id'] != 1) $rubls = round($rubls / $user_rate, 2);
	if ($show_currency) return $rubls.$currencies[$user['currency_id']]['designation'];
	else return $rubls;
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
//для получение значения цены пользователя с учетом наценки
function get_user_price($price, $user){
	global $db;
	//если пользователь не авторизован, то возвращаем цену в рублях
	$price += $price * $user['markup'] / 100;
	$rate = $user['rate'];
	switch ($user['currency_id']){
		case '1':
			$value = ceil($price/$rate);
			break;
		default:
			$value = round($price/$rate, 2);
	}
	return '<span class="price_format">'.$value.'</span>';
}
//для перевода в рубли из формы фильтра при поиске артикля
function get_rubls_form($price){
	global $currencies, $user;
	if (!$price) return false;
	if (!$user) return $price;
	return round($price * $currencies[$user['currency_id']]['rate']);
}
//для отображения если нету поставщиков
function no_providers(){
	global $article, $item, $brend;?>
	<tr class="button-row active">
		<td style="padding: 20px 0 0 0;text-align:left" class="button_padding">
			<b class="brend_info" brend_id="<?=$item['brend_id']?>"><?=$brend?></b>
			<a href="<?=getHrefArticle($article)?>" class="articul"><?=$article?></a></td>
		<td class="name-col" style="padding: 20px 0 0 0;text-align:left">
		<?$i = $item;
		if ($i['applicability'] or $i['characteristics'] or $i['foto'] or $i['full_desc']){?>
			<a href="#"><i item_id="<?=$item['id']?>" class="fa fa-camera product-popup-link" aria-hidden="true"></i></a>
		<?}?>
		<?=$item['title_full']?>
		</td>
		<td colspan="5" style="padding-top: 20px">Поставщиков не найдено</td>
	</tr>
<?}
function getHrefArticle($article){
	return "/search/article/$article";
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
function set_cache($array = array()){
	$file = 'cache/category.chc';
	if (empty($array)) return file_put_contents($file, '');
	$str = file_get_contents($file);
	$str .= json_encode($array).'\n';
	return file_put_contents($file, $str);
}
function get_rating($rate, $ratings){
	if ($rate <= $ratings[1]) return 0;
	if ($rate >= $ratings[10]) return 10;
	for ($i = 1; $i <= 9; $i++){
		if ($rate > $ratings[$i] && $rate <= $ratings[$i + 1]) return $i;
	}
	return 10;
}
function getHtmlRating($rating){
	$str = '';
	$div = $rating / 2;
	// echo $rating;
	for ($i = 1; $i <= 5; $i++){
		if ($i < $div) $fa = 'fa-star';
		else{
			if ($div == $i) $fa = 'fa-star';
			elseif ($div + 0.5 == $i) $fa = 'fa-star-half-o';
			else $fa = 'fa-star-o';
		}
		$str .= '<i class="fa '.$fa.'" aria-hidden="true"></i>';
	}
	return $str;
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
function console($str){
	echo "<script>console.log('$str')</script>";
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
function cat_get_user(){
	global $db;
	if ($_SESSION['user']){
		$q_user = "
			SELECT 
				u.*,
				c.designation, 
				c.rate, 
				u.delivery_type,
				u.bonus_count,
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
				c.id as currency_id
			FROM #users u
			LEFT JOIN #currencies c ON c.id=u.currency_id
			LEFT JOIN #issues i ON i.id=u.issue_id
			WHERE u.id={$_SESSION['user']}
		";
		$user = $db->select_unique($q_user, '');
		$user = $user[0];
	} 
	else{
		$user['markup'] = 0;
		$user['designation'] = '<i class="fa fa-rub" aria-hidden="true"></i>';
		$user['currency_id'] = 1;
		$user['rate'] = 1;
		$user['show_all_analogies'] = 0;
	}
	return $user;
}
function category_items_without_filters($sub_id, $sort = ['type' => 'title_full', 'desc' => '']){
	$time_start = microtime();
	global $db, $settings, $res, $user;
	$perPage = $settings['cat_perPage'];
	$countChunk = $settings['cat_countChunk'];
	if ($_SESSION['user']) $userDiscount = " - p.price * {$user['discount']} / 100";
	$limit = $perPage * $countChunk;
	$q_items = "
		SELECT
			i.id, 
			b.title as brend, 
			IF(i.article_cat != '', i.article_cat, i.article) as article, 
			i.title_full, 
			i.foto, 
			i.rating, 
			p.price $userDiscount AS price, 
			p.delivery
		FROM
			tahos_items i
		JOIN tahos_prices p ON p.item_id=i.id
		LEFT JOIN tahos_brends b ON b.id=i.brend_id
		LEFT JOIN tahos_categories_items ci ON ci.item_id=i.id
		WHERE ci.category_id=$sub_id
		ORDER BY 
	";
	switch($sort['type']){
		case 'title_full': $q_items .= 'i.title_full'; break;
		case 'price': $q_items .= 'p.price'; break;
		case 'rating': $q_items .= 'i.rating';break;
	}
	if ($sort['desc']) $q_items .= ' DESC';
	$q_items .= " LIMIT {$_SESSION['start']},$limit";
	$_SESSION['items_chunks'] = cat_get_chunks_items($q_items);
	$items = $_SESSION['items_chunks'][0];
	$items_values = cat_get_items_values($items);
	$user = cat_get_user();
	$ratings = json_decode($settings['ratings'], true);
	if (empty($items)) return false;
	foreach ($items as $key => $item){
		$items[$key]['price'] = get_user_price($item['price'], $user).$user['designation'];
		$items[$key]['filters_values'] = $items_values[$item['id']];
		$items[$key]['rating'] = get_rating($item['rating'], $ratings);
	} 
	unset($items_values);
	$time_end = microtime();
	$res['time'] = "Выполнено за ".($time_end - $time_start). ' секунд.';
	return $items;
}
function category_items_with_filters($sub_id, $sort = ['type' => 'title_full', 'desc' => '']){
	$time_start = microtime();
	global $db, $settings, $res;
	$perPage = $settings['cat_perPage'];
	$countChunk = $settings['cat_countChunk'];
	$limit = $perPage * $countChunk;
	$q_items = "
		SELECT
			i.id, 
			i.brend_id,
			b.title as brend, 
			IF(i.article_cat != '', i.article_cat, i.article) as article, 
			i.title, 
			i.title_full, 
			i.foto, 
			i.rating, 
			p.price, 
			p.delivery,
					COUNT(i.id) as total
		FROM
			#items i
		JOIN #prices p ON p.item_id=i.id
		JOIN #brends b ON b.id=i.brend_id
		JOIN #items_values iv ON iv.item_id=i.id
		JOIN #filters_values fv ON fv.id=iv.value_id
		WHERE 
	";
	$c_post = 0;//счетчик примененных фильтров
	foreach($_POST as $key => $value){
		if (!is_numeric($key)) continue;
		if (!$value) continue;
		if (strpos($value, ',')){
			$v = explode(',', $value);
			$q_items .= "(fv.title>={$v[0]} AND fv.title<={$v[1]} AND fv.filter_id=$key) OR ";
		}
		else $q_items .= "iv.value_id=$value OR ";
		$c_post++;
	}
	$q_items = substr($q_items, 0, -4);
	$q_items .= " GROUP BY i.id HAVING total>=$c_post";
	$q_items .= " ORDER BY ";
	switch($sort['type']){
		case 'title_full': $q_items .= 'i.title_full'; break;
		case 'price': $q_items .= 'p.price'; break;
		case 'rating': $q_items .= 'i.rating';break;
	}
	if ($sort['desc']) $q_items .= ' DESC';
	$q_items .= " LIMIT {$_SESSION['start']},$limit";
	$items_chunks = cat_get_chunks_items($q_items);
	if (!$items_chunks) return false;
	$_SESSION['items_chunks'] = $items_chunks;
	$items = $_SESSION['items_chunks'][0];
	$items_values = cat_get_items_values($items);
	$user = cat_get_user();
	$ratings = json_decode($settings['ratings'], true);
	foreach ($items as $key => $item){
		$items[$key]['price'] = get_user_price($item['price'], $user).$user['designation'];
		$items[$key]['filters_values'] = $items_values[$item['id']];
		$items[$key]['rating'] = get_rating($item['rating'], $ratings);
	} 
	unset($items_values);
	$time_end = microtime();
	$res['time'] = "Выполнено за ".($time_end - $time_start). ' секунд.';
	return $items;
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
		$userDiscount = " - si.price * c.rate * {$user['discount']} / 100";
	} 
	if (!$user['show_all_analogies'] && $search_type == 'analogies') $hide_analogies = true;
	else $hide_analogies = false;
	$q_item = "
		SELECT
			diff.item_diff as item_id,
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
			ps.cipher,
			ps.provider_id,
			ps.id as store_id,
			IF (
				i.article_cat != '', 
				i.article_cat, 
				IF (
					i.article !='',
					i.article,
					i.barcode
				)
			) as article,
			IF (i.title_full!='', i.title_full, i.title) as title_full,
			IF (si.in_stock = 0, ps.under_order, ps.delivery) as delivery,
			ps.prevail,
			ps.noReturn,
			CEIL(si.price * c.rate + si.price * c.rate * ps.percent / 100 $userDiscount) as price,
			$ba_quan
			IF (
				i.applicability !='' || i.characteristics !=''  || i.full_desc !='' || i.foto != '',
				1,
				0
			) as is_desc
		FROM #$search_type diff
		RIGHT JOIN #store_items si ON si.item_id=diff.item_diff
		LEFT JOIN #provider_stores ps ON ps.id=si.store_id
		LEFT JOIN #currencies c ON c.id=ps.currency_id
		LEFT JOIN #items i ON diff.item_diff=i.id
		LEFT JOIN #brends b ON b.id=i.brend_id
		$join_basket
		WHERE diff.item_id=$item_id AND diff.hidden=0
	";
	if ($hide_analogies) $q_item .= ' AND si.item_id IS NOT NULL';
	if (!empty($filters)){
		if ($filters['in_stock']) $q_item .= ' AND si.in_stock>0';
		$q_item .= "
			HAVING
				price BETWEEN {$filters['price_from']} AND {$filters['price_to']} AND
				delivery BETWEEN {$filters['time_from']} AND {$filters['time_to']} 
		";
	}
	else $q_item .= " HAVING price>0";
	if (!$hide_analogies) $q_item .= " OR price IS NULL";
	$q_item .= ' ORDER BY ps.prevail DESC, price, delivery';
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
							i.barcode
						)
				) as article,
				b.title as brend,
				b.id as brend_id,
				IF (
					i.applicability !='' || i.characteristics !=''  || i.full_desc !='',
					1,
					0
				) as is_desc,
				i.id as item_id
			FROM #$search_type diff
			LEFT JOIN #items i ON i.id=diff.item_diff
			LEFT JOIN #brends b ON b.id=i.brend_id
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
	$c = 0;
	while ($v = $res_item->fetch_assoc()){
		$p = & $store_items[$v['item_id']];
		if (!(int)$v['in_stock'] && $v['provider_id'] != 1) continue;
		$p['title_full'] = $v['title_full'];
		$p['article'] = $v['article'];
		$p['brend'] = $v['brend'];
		$p['brend_id'] = $v['brend_id'];
		$p['is_desc'] = $v['is_desc'];
		$p['item_id'] = $v['item_id'];
		$list['store_id'] = $v['store_id'];
		$list['in_stock'] = (int) $v['in_stock'] ? $v['in_stock'] : 'Под заказ';
		$list['cipher'] = $v['cipher'];
		$list['packaging'] = $v['packaging'];
		$list['packaging_text'] = $v['packaging_text'];
		$list['delivery'] = $v['delivery'];
		$list['price'] = $v['price'];
		$list['in_basket'] = $v['in_basket'];
		$list['prevail'] = $v['prevail'];
		$list['noReturn'] = $v['noReturn'] ? "class='noReturn' title='Возврат поставщику невозможен!'" : '';
		if ($v['prevail']){
			$p['prevails'][$v['store_id']] = $list;
			$prices[] = $v['price'];
			$deliveries[] = $v['delivery'];
			continue;
		}
		else $p['list'][$v['store_id']] = $list;
		$p['deliveries'][] = $v['delivery'];
		$prices[] = $v['price'];
		$deliveries[] = $v['delivery'];
	}
	// debug($store_items);
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
						i.barcode
					)
			) as article,
			br.title as brend,
			IF (i.title_full, i.title_full, i.title) as title
			FROM
				#basket b
			JOIN #items i ON i.id=b.item_id
			JOIN #brends br ON br.id=i.brend_id
			WHERE b.user_id={$_SESSION['user']}
	", false);
	if (empty($basket)) return false;
	foreach($basket as $key => $value){
		$b = & $basket[$key];
		unset($b['user_id'], $b['comment']);
		$b['href'] = getHrefArticle($b['article']);
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
					i.barcode
				)
			) AS article,
			b.title AS brend,
			i.brend_id,
			ps.cipher,
			IF (ps.noReturn, 'class=\"noReturn\" title=\"Возврат поставщику невозможен!\"', '') AS noReturn,
			ov.item_id,
			ov.price,
			ov.quan,
			ov.ordered,
			ov.arrived,
			ov.issued,
			ov.returned,
			ov.declined,
			os.id AS status_id,
			os.title AS status,
			os.class AS status_class,
			@delivery:=IF(si.in_stock = 0, ps.under_order, ps.delivery) AS delivery,
			IF (c.id IS NULL, 'disable', '') AS message,
			DATE_FORMAT(o.created, '%d.%m.%Y') as date_from,
			DATE_FORMAT(DATE_ADD(o.created, Interval @delivery DAY), '%d.%m.%Y') AS date_to,
			c.id AS correspond_id
		FROM 
			#orders_values ov
		LEFT JOIN #items i ON i.id=ov.item_id
		LEFT JOIN #provider_stores ps ON ps.id=ov.store_id
		LEFT JOIN #orders_statuses os ON ov.status_id=os.id
		LEFT JOIN #store_items si ON si.store_id=ov.store_id AND si.item_id=ov.item_id
		LEFT JOIN #brends b ON b.id=i.brend_id
		LEFT JOIN #orders o ON o.id=ov.order_id
		LEFT JOIN #corresponds c 
		ON 
			c.order_id=ov.order_id AND
			c.store_id=ov.store_id AND
			c.item_id=c.item_id
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
	if ($params['text']) $query .= " AND i.article='".article_clear($params['text'])."'";
	if (isset($params['status_id'])){
		$str = '';
		foreach($params['status_id'] as $key => $value) $str .= "$key,";
		$str = substr($str, 0, -1);
		$query .= " AND status_id IN ($str)";
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
?>