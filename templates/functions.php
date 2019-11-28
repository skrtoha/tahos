<?function message($text, $type = true){
	if (!$type) $type_message = "error";
	else $type_message = 'ok';
	// echo "$text $type_message";
	setcookie('message', $text, time() + 3600, '/');
	setcookie('message_type', $type_message, time() + 3600, '/');
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
function get_summ($order_values){
	$summ = 0;
	foreach($order_values as $order_value){
		$summ += $order_value['price'] * $order_value['quan'];
	}
	return $summ;
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
function get_status($order_values){
	$canceled = true;
	foreach($order_values as $order_value){
		if ($order_value['status'] == 7 or $order_value['status'] == 3) return 'В работе';
	}
	foreach($order_values as $order_value){
		if ($order_value['status'] != 8){
			$canceled = false;
			break;
		}
	}
	if ($canceled) return 'Отменен';
	foreach ($order_values as $order_value){
		if ($order_value == 5) return 'Ожидает';
	}
	$done = true;
	$done_statuses = array(1, 2, 3, 4, 6, 8, 9);
	foreach($order_values as $order_value){
		if (!in_array($order_value['status'], $done_statuses)){
			$done = false;
			break;
		}
	}
	if ($done) return 'Завершен';
	return 'Ожидает';
}
function get_order_statuses($status){
	global $db;
	switch ($status) {
		case 1: $in = array(2); break;
		case 2: $in = array(9); break;
		case 3: $in = array(1); break;
		case 4: $in = array(2); break;
		case 5: $in = array(7); break;
		case 6: $in = array(); break;
		case 7: $in = array(8, 6, 3); break;
		case 8: $in = array(); break;
		case 10: $in = array(4, 3); break;
	}
	return $db->select('orders_statuses', '*', '`id` IN ('.implode(',', $in).')');
}
function get_count_orders_providers($provider_id){
	global $db;
	$count = $db->getCount('orders_values', "`provider_id`=$provider_id AND `status`=7");
	if ($count) return "Заказы ($count)";
	else return false;
}
function unique_multidim_array($array, $key) { 
	$temp_array = array(); 
	$i = 0; 
	$key_array = array(); 
	foreach($array as $val) { 
		if (!in_array($val[$key], $key_array)) { 
			$key_array[$i] = $val[$key]; 
			$temp_array[$i] = $val; 
		} 
		$i++; 
	} 
	return $temp_array; 
} 
function get_query($type){
	global $i, $cycle, $depth, $items, $brends, $errors, $inserted;
	if ($type == 'where') $str = "";
	elseif ($type == 'insert'){
		$str = "INSERT INTO `tahos_items` (`title_full`,`full_desc`,`characteristics`,`applicability`,`brend_id`,`article`,`article_cat`,`weight`,`amount_package`,`barcode`) VALUES ";
	} 
	for ($i = $cycle * $depth; $i < $cycle * $depth + $depth; $i++){
		$item = $items[$i];
		$title_full = $item[4];
		$full_desc = $item[7];
		$characteristics = $item[8];
		$applicability = $item[9];
		$brend_id = $brends[$item[0]];
		$article = $article;
		$article_cat = $item[2];
		$weight = $item[6];
		$amount_package = $item[5];
		$barcode = $item[3];
		if (!$item[0]){
			$errors[] = $i + 2;
			continue;
		} 
		if ($item[1] and !$item[2] and !$item[3] and !$item[4]){
			$errors[] = $i + 2;
			continue;
		} 
		 //если в наличии каталожный номер
		if ($item[2]){
		 	$article = str_replace(array('-',' ', '.', ',', '/', '(', ')'), '', $item[2]);
		 	if ($type == 'where') $str .= "(`article`='$article' AND `brend_id`=".$brends[$item[0]].") OR ";
		 	elseif ($type == 'insert'){
			 	$str .= "('$title_full', '$full_desc', '$characteristics', '$applicability', '$brend_id', '$article', '$article_cat', '$weight', '$amount_package', '$barcode'), ";
			 	$inserted++;
		 	} 
		}
		 //если в наличии артикул
		elseif ($item[1]){
			$article = str_replace(array('-',' ', '.', ',', '/', '(', ')'), '', $item[1]);
			if ($type == 'where') $str .= "(`article`='$article' AND `brend_id`=".$brends[$item[0]].") OR ";
			elseif ($type == 'insert'){
				$str .= "('$title_full', '$full_desc', '$characteristics', '$applicability', '$brend_id', '$article', '$article_cat', '$weight', '$amount_package', '$barcode'), ";
				$inserted++;
			} 
		}
		else{
			$barcode = $item[3];
			//если нет и штрих-кода то пропускаем текущую итерацию
		 	if (!$item[3]){
		 		$errors[] = $i + 2;
			 	continue;
		 	} 
		 	if ($type == 'where') $str .= "(`barcode`='$barcode' AND `brend_id`=".$brends[$item[0]].") OR ";
		 	elseif ($type == 'insert'){
			 	$str .= "('$title_full', '$full_desc', '$characteristics', '$applicability', '$brend_id', '$article', '$article_cat', '$weight', '$amount_package', '$barcode'), ";
			 	$inserted++;
		 	} 
		}
	}
	// echo "$i : $str<br>";
	if ($type == 'where') return substr($str, 0, -4);
	elseif ($type = 'insert') return substr($str, 0, -2);
}
function get_query_remainder($type){
	global $i, $remainder, $items, $brends, $errors, $inserted;
	if ($type == 'where') $str = "";
	elseif ($type == 'insert'){
		$str = "INSERT INTO `tahos_items` (`title_full`,`full_desc`,`characteristics`,`applicability`,`brend_id`,`article`,`article_cat`,`weight`,`amount_package`,`barcode`) VALUES ";
	} 
	for ($j = $i + 1; $j < $i + 1 + $remainder; $j++){
		$item = $items[$j];
		$title_full = $item[4];
		$full_desc = $item[7];
		$characteristics = $item[8];
		$applicability = $item[9];
		$brend_id = $brends[$item[0]];
		$article = $article;
		$article_cat = $item[2];
		$weight = $item[6];
		$amount_package = $item[5];
		$barcode = $item[3];
		if (!$item[0]){
			$errors[] = $i + 1;
			continue;
		} 
		if ($item[1] and !$item[2] and !$item[3] and !$item[4]){
			$errors[] = $i + 1;
			continue;
		} 
		 //если в наличии каталожный номер
		if ($item[2]){
		 	$article = str_replace(array('-',' ', '.', ',', '/', '(', ')'), '', $item[2]);
		 	if ($type == 'where') $str .= "(`article`='$article' AND `brend_id`=".$brends[$item[0]].") OR ";
		 	elseif ($type == 'insert'){
			 	$str .= "('$title_full', '$full_desc', '$characteristics', '$applicability', '$brend_id', '$article', '$article_cat', '$weight', '$amount_package', '$barcode'), ";
			 	$inserted++;
		 	} 
		}
		 //если в наличии артикул
		elseif ($item[1]){
			$article = str_replace(array('-',' ', '.', ',', '/', '(', ')'), '', $item[1]);
			if ($type == 'where') $str .= "(`article`='$article' AND `brend_id`=".$brends[$item[0]].") OR ";
			elseif ($type == 'insert'){
				$str .= "('$title_full', '$full_desc', '$characteristics', '$applicability', '$brend_id', '$article', '$article_cat', '$weight', '$amount_package', '$barcode'), ";
				$inserted++;
			} 
		}
		else{
			$barcode = $item[3];
			//если нет и штрих-кода то пропускаем текущую итерацию
		 	if (!$item[3]){
		 		$errors[] = $i + 1;
			 	continue;
		 	} 
		 	if ($type == 'where') $str .= "(`barcode`='$barcode' AND `brend_id`=".$brends[$item[0]].") OR ";
		 	elseif ($type == 'insert'){
			 	$str .= "('$title_full', '$full_desc', '$characteristics', '$applicability', '$brend_id', '$article', '$article_cat', '$weight', '$amount_package', '$barcode'), ";
			 	$inserted++;
		 	} 
		}
	}
	if ($type == 'where') return substr($str, 0, -4);
	elseif ($type = 'insert') return substr($str, 0, -2);
}
function check_alone(){
	global $cycle, $depth, $items, $brends, $errors, $inserted, $db, $i;
	for ($i = $cycle * $depth; $i < $cycle * $depth + $depth; $i++){
		$item = $items[$i];
		$brend_id = $brends[$item[0]];
		if (!$item[0] or !$item[4]){
			$errors[] = $i+2;
			continue;
		} 
		if (!$item[1] and !$item[2] and !$item[3]){
			$errors[] = $i+2;
			continue;
		} 
		// continue;
		 //если в наличии каталожный номер
		if ($item[2]){
		 	if ($item[1]) $article = $item[1];
		 	//если нету, то берем из каталожного номера
		 	else $article = str_replace(array('-',' ', '.', ',', '/', '(', ')'), '', $item[2]);
		 	$array = array('title_full' => $item[4],
										'full_desc' => ($item[7]),
										'characteristics' => ($item[8]),
										'applicability' => ($item[9]),
										'brend_id' => $brend_id,
										'article' => $article,
										'article_cat' => $item[2],
										'weight' => $item[6],
										'amount_package' => $item[5],
										'barcode' => $item[3]);
		 	$arr_c_id = $db->select('items', 'id', "`article`='$article' AND `brend_id`=$brend_id");
		 	$c_id = count($arr_c_id) ? $arr_c_id[0]['id'] : '';
		 }
		//если в наличии артикул
		elseif ($item[1]){
		 	$article = str_replace(array('-',' ', '.', ',', '/', '(', ')'), '', $item[1]);
		 	$array = array('title_full' => $item[4],
										'full_desc' => ($item[7]),
										'characteristics' => ($item[8]),
										'applicability' => ($item[9]),
										'brend_id' => $brend_id,
										'article' => $article,
										'article_cat' => $item[2],
										'weight' => $item[6],
										'amount_package' => $item[5],
										'barcode' => $item[3]);
		 	$arr_c_id = $db->select('items', 'id', "`article`='$article' AND `brend_id`=$brend_id");
		 	$c_id = count($arr_c_id) ? $arr_c_id[0]['id'] : '';
		}
		else{
		 	//если нет и штрих-кода то пропускаем текущую итерацию
		 	if (!$item[3]){
		 		$errors[] = $i + 1;
			 	continue;
		 	} 
		 	$barcode = $item[3];
	 		$array = array('title_full' => $item[4],
									'full_desc' => ($item[7]),
									'characteristics' => ($item[8]),
									'applicability' => ($item[9]),
									'brend_id' => $brend_id,
									'article' => $item[1],
									'article_cat' => $item[2],
									'weight' => $item[6],
									'amount_package' => $item[5],
									'barcode' => $barcode);
 			$arr_c_id = $db->select('items', 'id', "`barcode`='$barcode' AND `brend_id`=$brend_id");
		 	$c_id = count($arr_c_id) ? $arr_c_id[0]['id'] : '';
		 }
		foreach ($array as $key => $value) $array[$key] = trim($value);
		if ($c_id){
		 	// if ($_POST['treatment'] == 1) continue;
		 	if ($db->update('items', $array, "`id`=$c_id")) $updated++;
		 	else $errors[] = $i + 1;
	 	} 
	 	else{
	 		// if ($_POST['treatment'] == 2) continue;
	 		$array['source'] = 'Загрузка номенклатуры из файла';
		 	if ($db->insert('items', $array)) $inserted++;
		 	else $errors[] = $i + 1;
	 	} 
	}
}
function check_alone_remainder(){
	global $i, $remainder, $items, $brends, $errors, $inserted, $db;
	for ($j = $i + 1; $j < $i + 1 + $remainder; $j++){
		$item = $items[$j];
		$brend_id = $brends[$item[0]];
		// if ($i > 20) break;
		if (!$item[0]){
			$errors[] = $i + 1;
			continue;
		} 
		if ($item[1] and !$item[2] and !$item[3] and !$item[4]){
			$errors[] = $i + 1;
			continue;
		} 
		 //если в наличии каталожный номер
		 if ($item[2]){
		 	if ($item[1]) $article = $item[1];
		 	//если нету, то берем из каталожного номера
		 	else $article = str_replace(array('-',' ', '.', ',', '/', '(', ')'), '', $item[2]);
		 	$array = array('title_full' => $item[4],
										'full_desc' => ($item[7]),
										'characteristics' => ($item[8]),
										'applicability' => ($item[9]),
										'brend_id' => $brend_id,
										'article' => $article,
										'article_cat' => $item[2],
										'weight' => $item[6],
										'amount_package' => $item[5],
										'barcode' => $item[3]);
		 	$arr_c_id = $db->select('items', 'id', "`article`='$article' AND `brend_id`=$brend_id");
		 	$c_id = count($arr_c_id) ? $arr_c_id[0]['id'] : '';
		 }
		 //если в наличии артикул
		elseif ($item[1]){
		 	$article = str_replace(array('-',' ', '.', ',', '/', '(', ')'), '', $item[1]);
		 	$array = array('title_full' => $item[4],
										'full_desc' => ($item[7]),
										'characteristics' => ($item[8]),
										'applicability' => ($item[9]),
										'brend_id' => $brend_id,
										'article' => $article,
										'article_cat' => $item[2],
										'weight' => $item[6],
										'amount_package' => $item[5],
										'barcode' => $item[3]);
		 	$arr_c_id = $db->select('items', 'id', "`article`='$article' AND `brend_id`=$brend_id");
		 	$c_id = count($arr_c_id) ? $arr_c_id[0]['id'] : '';
		 }
		 else{
		 	//если нет и штрих-кода то пропускаем текущую итерацию
		 	if (!$item[3]){
		 		$errors[] = $i + 1;
			 	continue;
		 	} 
		 	$barcode = $item[3];
	 		$array = array('title_full' => $item[4],
									'full_desc' => ($item[7]),
									'characteristics' => ($item[8]),
									'applicability' => ($item[9]),
									'brend_id' => $brend_id,
									'article' => $item[1],
									'article_cat' => $item[2],
									'weight' => $item[6],
									'amount_package' => $item[5],
									'barcode' => $barcode);
 			$arr_c_id = $db->select('items', 'id', "`barcode`='$barcode' AND `brend_id`=$brend_id");
		 	$c_id = count($arr_c_id) ? $arr_c_id[0]['id'] : '';
		 }
		 foreach ($array as $key => $value) $array[$key] = trim($value);
		 if ($c_id){
		 	if ($_POST['treatment'] == 1) continue;
		 	if ($db->update('items', $array, "`id`=$c_id")) $updated++;
		 	else $errors[] = $i + 1;
	 	} 
	 	else{
	 		if ($_POST['treatment'] == 2) continue;
	 		$array['source'] = 'Загрузка номенклатуры из файла';
		 	if ($db->insert('items', $array)) $inserted++;
		 	else $errors[] = $i + 1;
	 	} 
	}
}
function get_sending_status($sendings_values){
	$bl_expecting = false;
	foreach ($sendings_values as $key => $value){
		if ($value['status'] != 10) continue;
		$bl_expecting = true;
		break;
	}
	if ($bl_expecting) return 'Ожидает';
	else  return 'Завершен';
}
function getCountNews($table, $where = 1){
	global $db;
	$count = $db->getCount($table, "`is_new`=1 AND $where");
	if ($count){?>
		<span><?=$count?></span>
	<?}
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
function translite($var){
	$var = mb_strtolower($var, 'UTF-8');
	$var = replace_winword_chars($var);
	// $var = str_replace(" ","-",$var);
	// $var = str_replace("__","-",$var);
	$alpha = array("а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d","е"=>"e","ё"=>"jo","ж"=>"zh","з"=>"z","и"=>"i","й"=>"i","к"=>"k","л"=>"l","м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r","с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"kh","ц"=>"c","ч"=>"ch","ш"=>"sh","щ"=>"shh","э"=>"je","ю"=>"yu","я"=>"ja","ы"=>"i","ъ"=>"","ь"=>"","/"=>"","\\"=>"");
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
	$name = $file['name'];
	if (!$name) {
		$array['error'] = '';
		return $array;
	}
	$dir_big = "../images/items/big/$id";
	$dir_small = "../images/items/small/$id";
	require_once('../class/class.upload.php');
	if (!file_exists($dir_big)) mkdir($dir_big);
	if (!file_exists($dir_small)) mkdir($dir_small);
	$handle = new upload($file);
	$handle_big = new upload($file);
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
?>