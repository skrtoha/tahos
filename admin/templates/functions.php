<?function message($text, $type = true){
	if (!$type) $type_message = "error";
	else $type_message = 'ok';
	// echo "$text $type_message";
	setcookie('message', $text, time() + 3600, '/');
	setcookie('message_type', $type_message, time() + 3600, '/');
}
function get_from_uri($from = ''){
	if (!$from) return '/'.str_replace(['&', '/', '?'], ['|', '\\', '^'], $_SERVER['REQUEST_URI']);
	else return '/'.str_replace(['|', '\\', '^'], ['&', '/', '?'],  $from);
}
function article_clear($article){
	return preg_replace('/[^\w_а-яА-Я]+/u', '', $article);
}
function console($str){
	echo "<script>console.log('$str')</script>";
}
function accStr($length = 8){
  $chars = 'abcdefhiknoprstxyzABCDEFGHKNOPQRSTXYZ0123456789';
  $numChars = strlen($chars);
  $string = '';
  for ($i = 0; $i < $length; $i++) {
    $string .= substr($chars, mt_rand(1, $numChars) - 1, 1);
  }
  return $string;
}
function getCountLeftMenu($table, $where = 1){
	global $db;
	$count = $db->getCount($table, $where);
	if ($count){?>
		<span><?=$count?></span>
	<?}
}
function debug($obj, $name = ''){?>
	<div style="clear: both"></div>
	<?if ($name){?>
		<p><b><?=$name?>:</b></p>
	<?}?>
	<pre><?print_r($obj)?></pre>
<?}
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
function show_array($array){
	foreach ($array as $key => $value) {
		echo "<b>$key: </b>";
		print_r($value);
		echo "<br>";
	}
}
function get_cipher(){
	global $db;
	for ($i = 0; $i < 4; $i++) $chiper[] = rand(65, 90);
	foreach ($chiper as $value) $str .= chr($value);
	while ($db->getCount('providers', "`cipher`=$str")) {
		$str = '';
		for ($i = 0; $i < 4; $i++) $chiper[] = rand(65, 90);
		foreach ($chiper as $value) $str .= chr($value);
	}
	return $str;
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
function get_tax($summ){
	global $db;
	$tax = $db->getFieldOnID('settings', 1, 'tax');
	return $summ * $tax / 100;
}
function get_sending_status($sendings_values){
	$bl_expecting = false;
	foreach ($sendings_values as $key => $value){
		if ($value['status'] != 10) continue;
		return 'Ожидает';
	}
	return 'Завершен';
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
function getStrFilters($strFilters){
	global $db, $filters;
	$filters = $db->select('filters', 'id,title', "`id` IN ($strFilters)", '', '', '', true);
	// debug($filters);
	if (count($filters)){
		foreach($filters as $id => $filter) {
			$filter_values = $db->select('filters_values', 'id,title', "`filter_id`=$id");
			if (count($filter_values)){
				$array = array();
				foreach($filter_values as $filter_value) $array[$filter_value['id']] = $filter_value['title'];
				$filters_values_table[$id] = $array;
			}
		}
		return [
			'filters_values_table' => $filters_values_table,
			'filters' => $filters
		]; 
	}
	else return false;
}
function set_image($file, $id){
	global $db;
	$array = [];

	/*$name = $file['name'];
	if (!$name) {
		$array['error'] = '';
		return $array;
	}*/

	$dir_big = core\Config::$imgPath . "/items/big/$id";
	$dir_small = core\Config::$imgPath . "/items/small/$id";
	require_once("{$_SERVER['DOCUMENT_ROOT']}/vendor/class.upload.php");
	if (!file_exists($dir_big)) mkdir($dir_big);
	if (!file_exists($dir_small)) mkdir($dir_small);
	$handle = new upload($file);
	$handle_big = new upload($file);
	// debug($handle_big);
	if (!$handle->file_is_image){
		$array['error'] = 'Запрещенный вид файла!';
		return $array;
	}
	$need_ratio = [
		'x' => 200,
		'y' => 250
	];
	if ($handle->uploaded){
		$handle->file_new_name_body = time();
		$handle_big->file_new_name_body = $handle->file_new_name_body;
		$handle->image_resize = true;
		$src_x = $handle->image_src_x;
		$src_y = $handle->image_src_y;
		if (($need_ratio['x'] / $need_ratio['y']) >= ($src_x / $src_y)){
			$handle->image_x = $need_ratio['x'];
			$handle->image_y = floor($need_ratio['x'] * $src_y / $src_x);
			$t = floor($handle->image_y / 2 - $need_ratio['y'] / 2);
			$handle->image_crop = "$t 0";
		}
		else{
			$handle->image_y = $need_ratio['y'];
			$handle->image_x = floor($need_ratio['y'] * $src_x / $src_y);
			$t = floor($handle->image_x / 2 - $need_ratio['x'] / 2);
			$handle->image_crop = "0 $t";
		}
		$handle->process($dir_small);
		$handle_big->process($dir_big);
		if ($handle->processed) $array['name'] = $handle->file_dst_name;
		$handle->clean();
		$handle_big->clean();
		$array['error'] = '';
		return $array;
	}
	else{
		$array['error'] = 'Произошла ошибка';
		return $array;
	}
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
	";
	$filters = $db->select_unique($query, false);
	if (count($filters)){
		foreach ($filters as $value){
			$fv = $value['filter_id'];
			$f = & $t_filters[$fv];
			$f['title'] = $value['title'];
			$f['slider'] = $value['slider'];
			$f['filters_values'][] = $value['value_id'];
		} 
	}
	return $t_filters;
}
function set_ratings(){
	global $db;
	$min_max = $db->select_unique('
		SELECT MIN(rating) as min, MAX(rating) as max FROM #items;
	', false);
	$rate_min = $min_max[0]['min'];
	$rate_max = $min_max[0]['max'];
	$r = ($rate_max - $rate_min) / 10;
	$ratings[1] = $rate_min;
	for ($i = 2; $i <=10; $i++) $ratings[$i] = $ratings[$i - 1] + $r;
	core\Setting::update('items', 'ratings', json_encode($ratings));
}
?>