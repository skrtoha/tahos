<?use core\Abcp;
function items_submit(){
	global $db;
	$profiling = $db->isProfiling;
	$db->isProfiling = false;
	require_once ('vendor/autoload.php');
	$catalog_name = 'prices_'.date('d.m.Y_H-i-s').'.txt';
	// debug($_POST); debug($_FILES); exit();
	$log = new Katzgrau\KLogger\Logger('logs', Psr\Log\LogLevel::WARNING, array(
		'filename' => $catalog_name,
		'dateFormat' => 'G:i:s'
	));
	$handle = fopen($_FILES['items']['tmp_name'], 'r');
	$i = 0;
	$inserted = 0;
	if ($_POST['parse'] == 'full'){
		$armtek = new core\Armtek($db);
		$rossko = new core\Rossko($db);
		if ($armtek->isKeyzak($_POST['store_id']) || $_POST['store_id'] == 4 || $_POST['store_id'] == 3){
			$query = "
				DELETE si FROM
					#store_items si
				LEFT JOIN
					#provider_stores ps ON ps.id = si.store_id
				WHERE
					ps.provider_id = $armtek->provider_id
			";
			if ($_POST['store_id'] == 3) $query .= " AND ps.id != 4";
			if ($_POST['store_id'] == 4) $query .= " AND ps.id != 3";
			if ($_POST['store_id'] != 3 && $_POST['store_id'] != 4) $query .= " AND ps.id NOT IN (3,4)";
			$db->query($query, '');
		}
		elseif($rossko->isRossko($_POST['store_id'])){
			$query = "
				DELETE si FROM
					#store_items si
				LEFT JOIN
					#provider_stores ps ON ps.id = si.store_id
				WHERE
					ps.provider_id = $rossko->provider_id
			";
			if ($_POST['store_id'] == 24 || $_POST['store_id'] == 25) $query .= " AND ps.id != {$_POST['store_id']}";
			else $query .= " AND ps.id NOT IN (24, 25)";
			$db->query($query, '');
		}
		else $db->query("
			DELETE FROM 
				#store_items 
			WHERE `store_id`={$_POST['store_id']} 
		", '');
		while ($data = fgetcsv($handle, 1000, ",")) {
			$row = explode(';', str_replace('"', '', $data[0]));
			// debug($row); continue;
			$i++;
			if (!$row[0] || !$row[1]){
				$log->error("В строке $i произошла ошибка.");
				continue;
			}
			$brend = $db->select_one('brends', ['id', 'title', 'parent_id'], "`title`='{$row[0]}'");
			if (empty($brend)){
				$log->warning("В строке $i бренд {$row[0]} не найден.");
				continue;
			}
			$brend_id = $brend['parent_id'] ? $brend['parent_id'] : $brend['id'];
			$article = article_clear($row[1]);
			$item = $db->select_one('items', 'id', "`brend_id`=$brend_id AND `article`='$article'");
			if (empty($item)){
				$log->warning("В строке $i товар с брендом {$row[0]} и артикулом {$row[1]} не найден.");
				continue;
			}
			$res = $db->insert(
				'store_items',
				[
					'store_id' => $_POST['store_id'],
					'item_id' => $item['id'],
					'price' => str_replace(' ', '', $row[4]),
					'in_stock' => $row[3],
					'packaging' => $row[5] ? $row[5] : 1
				]
			);
			if (Abcp::isDuplicate($res)){
				$log->warning("В строке $i дублирующие данные: $res | {$db->last_query}");
				continue;
			} 
			elseif ($res !== true) {
				$log->error("В строке $i ошибка вставки: $res | {$db->last_query}");
				continue;
			}
			$inserted++;
		}
	}
	if ($_POST['parse'] == 'particulary'){
		$inserted = 0;
		$updated = 0;
		while ($data = fgetcsv($handle, 1000, ",")) {
			$row = explode(';', str_replace('"', '', $data[0]));
			$i++;
			if (!$row[0] || !$row[1]){
				$log->error("В строке $i произошла ошибка.");
				continue;
			}
			$brend = $db->select_one('brends', ['id', 'title', 'parent_id'], "`title`='{$row[0]}'");
			$brend_id = $brend['parent_id'] ? $brend['parent_id'] : $brend['id'];
			$article = article_clear($row[1]);
			$item = $db->select_one('items', 'id', "`brend_id`=$brend_id AND `article`='{$article}'");
			if (empty($item)){
				$log->warning("В строке $i товар с брендом <b>{$row[0]}</b> и артикулом <b>{$row[1]}</b> не найден.");
				continue;
			}
			$res = $db->update(
				'store_items',
				[
					'price' => $row[4],
					'in_stock' => $row[3],
					'packaging' => $row[5] ? $row[5] : 1
				],
				"`store_id`={$_POST['store_id']} AND `item_id`={$item['id']}"
			);
			if ($db->rows_affected()){
				$updated++;
				continue;
			}
			if (!$db->rows_matches()){
				$res = $db->insert(
					'store_items',
					[
						'store_id' => $_POST['store_id'],
						'item_id' => $item['id'],
						'price' => $row[4],
						'in_stock' => $row[3],
						'packaging' => $row[5] ? $row[5] : 1
					]
				);	
				if ($res !== true){
					$log->error("В строке $i ошибка вставки в базу данных: $res | {$db->last_query} ");
					continue;
				} 
				$inserted++;
			}
		}
		echo "Обновлено <b>$updated</b> строк<br>";
		$log->alert("Обновлено $updated строк");
	}
	echo "Вставлено <b>$inserted</b> строк<br>";
	$log->alert("Вставлено $inserted строк");
	echo "<a target='_blanc' href='/admin/logs/$catalog_name'>Просмотреть лог</a><br>";
	if ($_POST['store_id'] == 22) $db->query("
		DELETE FROM
			#store_items
		WHERE
			`store_id` IN (
				SELECT `id` FROM #provider_stores WHERE `provider_id` = 13
			) AND
			`store_id` != 22
	");
	if ($profiling) $db->isProfiling = true;
	exit();
}
function provider_save(){
	global $db;
	$saveble = true;
	// debug($_POST);
	$id = $_GET['id'];
	foreach($_POST as $key => $value){
		switch($key){
			case 'telephone': $array['telephone'] = str_ireplace(array('(', ')', ' ', '-'), '', $value); break;
			case 'telephone_extra': $array['telephone_extra'] = str_ireplace(array('(', ')', ' ', '-') , '', $value); break;
			default: if ($key != 'is_legal') $array[$key] = $value;
		}
	} 
	if ($_POST['is_legal']){
		$array['fact_index'] = $array['legal_index'];
		$array['fact_region'] = $array['legal_region'];
		$array['fact_adres'] = $array['legal_adres'];
	}
	if (!$_GET['id']) $res = $db->insert('providers', $array);
	else $res = $db->update('providers', $array, "`id`={$_GET['id']}");
	if ($res !== true) message($res, false);
	else{
		message('Успешно сохранено!');
		if (!$_GET['id']) header("Location: /admin/?view=providers&id=".$db->last_id());
	}
}
/**
 * Парсит массив с данными
 * @param  [type] $row  Массив из файла]
 * @param  [type] $fields Номера полей 
 * @param  [type] $price Класс
 * @param  [type] $stringNumber Номер строки файла
 * @return Никакие переменные не возвращаются
 */
function parse_row($row, $fields, core\Price $price, $stringNumber){
	$fieldBrend = $fields['brend'] - 1;
	$fieldPrice = $fields['price'] - 1;
	$fieldTitle = $fields['title'] - 1;
	$fieldArticle = $fields['article'] - 1;
	$filedArticle_cat = $fields['article_cat'] - 1;
	$fieldInStock = $fields['inStock'] - 1;
	$fieldPackaging = $fields['packaging'] - 1;


	if (!$row[$filedArticle_cat] || !$row[$fieldBrend]){
		$price->log->error("В строке $stringNumber произошла ошибка.");
		continue;
	}
	$brend_id = $price->getBrendId($row[$fieldBrend]);
	if (!$brend_id) continue;
	$item_id = $price->getItemId([
		'brend_id' => $brend_id,
		'brend' => $row[$fieldBrend],
		'article' => article_clear($row[$filedArticle_cat]),
		'title' => $row[$fieldTitle],
		'row' => $stringNumber
	]);
	if (!$item_id) continue;
	$price->insertStoreItem([
		'store_id' => $_GET['store_id'],
		'item_id' => $item_id,
		'price' => $row[$fieldPrice],
		'in_stock' => $row[$fieldInStock],
		'packaging' => $row[$fieldPackaging],
		'row' => $stringNumber
	]);
}
?>