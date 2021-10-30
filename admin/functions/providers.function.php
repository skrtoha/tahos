<?php
use core\Provider;
use Katzgrau\KLogger\Logger;

function items_submit(){
	global $db;
	$profiling = $db->isProfiling;
	$db->isProfiling = false;
	require_once ($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');
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
		$armtek = new core\Provider\Armtek($db);
		$rossko = new core\Provider\Rossko($db);
		if ($armtek->isKeyzak($_POST['store_id']) || $_POST['store_id'] == 4 || $_POST['store_id'] == 3){
			$query = "
				DELETE si FROM
					#store_items si
				LEFT JOIN
					#provider_stores ps ON ps.id = si.store_id
				WHERE
					ps.provider_id = ". core\Provider\Armtek::getConfig('entity', true)->provider_id ."
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
			$article = core\Item::articleClear($row[1]);
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
			if (Provider::isDuplicate($res)){
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
			$article = core\Item::articleClear($row[1]);
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
	$db->query("UPDATE #provider_stores SET `price_updated` = CURRENT_TIMESTAMP WHERE `id`={$_POST['store_id']}", '');
	exit();
}
function provider_save(){
	global $db;
	$saveble = true;
	// debug($_POST); exit();
	$id = $_GET['id'];
	foreach($_POST as $key => $value){
		switch($key){
			case 'telephone': $array['telephone'] = str_ireplace(array('(', ')', ' ', '-'), '', $value); break;
			case 'telephone_extra': $array['telephone_extra'] = str_ireplace(array('(', ')', ' ', '-') , '', $value); break;
			default: if ($key != 'is_legal') $array[$key] = $value;
		}
	} 
	if (!isset($_POST['is_enabled_api_search'])) $array['is_enabled_api_search'] = 0;
	if (!isset($_POST['is_enabled_api_order'])) $array['is_enabled_api_order'] = 0;
	if (!isset($_POST['is_active'])) $array['is_active'] = 0;
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
 * @param  [type] $row  Массив из файла
 * @param  [type] $fields Номера полей 
 * @param  [type] $price Класс
 * @param  [type] $stringNumber Номер строки файла
 * @return void
 */
function parse_row($row, $fields, core\Price $price, $stringNumber){
	$fieldBrend = $fields['brend'] - 1;
	$fieldPrice = $fields['price'] - 1;
	$fieldTitle = $fields['title'] - 1;
	$filedArticle_cat = $fields['article_cat'] - 1;
	$fieldInStock = $fields['inStock'] - 1;
	$fieldPackaging = $fields['packaging'] - 1;

	if (!$row[$filedArticle_cat] || !$row[$fieldBrend]){
		$price->setLog('error', "В строке $stringNumber произошла ошибка.");
		return;
	}
	
	$brend_id = $price->getBrendId($row[$fieldBrend]);
	if (!$brend_id) return;
	
	$item_id = $price->getItemId([
		'brend_id' => $brend_id,
		'brend' => $row[$fieldBrend],
		'article' => $row[$filedArticle_cat],
		'title' => $row[$fieldTitle],
		'row' => $stringNumber
	]);
	if (!$item_id) return;
	
	$price->insertStoreItem([
		'store_id' => $price->store_id,
		'item_id' => $item_id,
		'price' => $row[$fieldPrice],
		'in_stock' => $row[$fieldInStock],
		'packaging' => $row[$fieldPackaging],
		'row' => $stringNumber
	]);
}
function endSuccessfullyProccessing($isLogging, Logger $logger, $store_id){
	global $db, $price, $stringNumber;
	$db->query("UPDATE #provider_stores SET `price_updated` = CURRENT_TIMESTAMP WHERE `id`={$store_id}", '');

    $price->setLog('alert', "Обработано $stringNumber строк");
    $price->setLog('alert', "Добавлено в прайс: $price->insertedStoreItems записей");
    $price->setLog('alert', "Вставлено: $price->insertedBrends брендов");
    $price->setLog('alert', "Вставлено: $price->insertedItems номенклатуры");
    
    $logger->alert("Обработано $stringNumber строк");
    $logger->alert("Добавлено в прайс: {$price->insertedStoreItems} записей");
    $logger->alert("Вставлено: $price->insertedBrends брендов");
    $logger->alert("Вставлено: $price->insertedItems номенклатуры");
    
    if ($isLogging){
        $logger->info("Полный лог: $price->nameFileLog");
    }
}
function parseWithPhpOffice($workingFile, $debuggingMode, Logger $logger){
	global $emailPrice, $price, $stringNumber;
    $xls = \PhpOffice\PhpSpreadsheet\IOFactory::load($workingFile);
    $xls->setActiveSheetIndex(0);
    $sheet = $xls->getActiveSheet();
	$rowIterator = $sheet->getRowIterator();
	foreach ($rowIterator as $iterator) {
		$row = array();
		$cellIterator = $iterator->getCellIterator();
		foreach($cellIterator as $cell){
			$row[] = $cell->getCalculatedValue();
		} 
		$stringNumber++;

		if ($debuggingMode){
			$logger->debug('', $row);
			if ($stringNumber > 100){
                $logger->alert("Обработка прошла");
                die();
            }
		}

		parse_row($row, $emailPrice['fields'], $price, $stringNumber);
	}
	$logger->alert("Обрабока с помощью PhpOffice закончена");
}
?>
