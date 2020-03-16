<?
set_time_limit(0);
require_once ($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

// $log_name = 'items.txt';
// file_put_contents('logs/items.txt', '');

$log_name = 'items_'.date('d.m.Y_H-i-s').'.txt';
$log = new Katzgrau\KLogger\Logger('logs', Psr\Log\LogLevel::WARNING, array(
	'filename' => $log_name,
	'dateFormat' => 'G:i:s'
));
//Закоментировать для включения лога запросов
$isUseProfiling = false;

$profiling = $db->isProfiling;
if ($isUseProfiling) $db->isProfiling = true;

if ($_POST['items_submit']){
	$db->isProfiling = false;
	require_once ('../vendor/PHPExcel/IOFactory.php');
	$xls = PHPExcel_IOFactory::load($_FILES['items']['tmp_name']);
	$xls->setActiveSheetIndex(0);
	$sheet = $xls->getActiveSheet();
	$rowIterator = $sheet->getRowIterator();
	$r = 0;
	$errors = array();
	$updated = 0;
	$inserted = 0;
	foreach ($rowIterator as $row) {
		$cellIterator = $row->getCellIterator();
		$value = array();
		foreach($cellIterator as $cell){
			$value[] = $cell->getCalculatedValue();
		} 
		$r++;//счетчик строк в файле
		if (!$value[1] && !$value[2] && !$value[3]){
			$log->error("В стоке $r ошибочные данные.");
			continue;
		}
		$brend = $db->select_one('brends', ['id', 'parent_id'], "`title`='{$value[0]}' ");
		$brend_id = $brend['parent_id'] ? $brend['parent_id'] : $brend['id'];
		$log->info("Для {$value[0]} brend_id=$brend_id");
		if (!$brend_id){
			$log->error("В строке $r бренд {$value[0]} отсутствует");
			continue;
		}
		if (!$value[1] && !$value[2]) $article = trim($value[3]);
		elseif (!$value[1]) $article = article_clear($value[2]);
		else $article = trim($article[1]);
		if (!$article){
			$log->debug("для строки $r value=", $value);
			$log->info("для строки $r article=$article");
			$log->error("Ошибка получения артикула в $r");
			continue;
		}
		//заполнить, которых нет
		if ($_POST['treatment'] == 1){
			$res_insert = $db->insert(
				'items',
				[
					'brend_id' => $brend_id,
					'article' => $article,
					'article_cat' => $value[2],
					'barcode' => $value[3],
					'title_full' => $value[4],
					'title' => $value[4],
					'amount_package' => $value[5] ? $value[5] : 0,
					'weight' => $value[6] ? $value[6] : 0,
					'full_desc' => $value[7],
					'characteristics' => $value[8],
					'applicability' => $value[9]
				],
				['deincrement_duplicate' => 1]
			);
			if ($res_insert === true){
				$item_id = $db->last_id();
				$log->info("Артикул $article и {$value[0]} успешно добавлен с id={$item_id}");
				$res_artticles = $db->insert(
					'articles',
					[
						'item_id' => $item_id,
						'item_diff' => $item_id
					]
				);
				if ($res_artticles !== true){
					$log->error("В строке $r ошибка вставки в `tahos_articles`: $db->last_query | $res_artticles");
					continue;
				}
				$inserted++;
			}
			else{
				$log->info("В строке $r: $res_insert");
				$log->info($db->last_query);
			} 
		}
		else{
			$res_update = core\Item::update(
				[
					'article_cat' => $value[2],
					'barcode' => $value[3],
					'title_full' => $value[4],
					'title' => $value[4],
					'amount_package' => $value[5] ? $value[5] : 0,
					'weight' => $value[6] ? $value[6] : 0,
					'full_desc' => $value[7],
					'characteristics' => $value[8],
					'applicability' => $value[9]
				],
				['brend_id' => $brend_id, 'article' => $article]
			);
			// echo ($db->last_query);
			$rows_affected = $db->rows_affected();
			if ($res_update === true){
				if ($rows_affected){
					$log->notice("Бренд {$value[0]} c артикулом $article успешно обновлен");
					$updated++;
				}
			}
			else $log->error("$res_update: $db->last_query");
		}
	}
	$log->alert("Всего вставлено: $inserted");
	$log->alert("Всего обновлено: $updated");
}
function to_file($str){
	global $f, $name;
	if (!$f){
		$name = date('d.m.Y_H-i-s').'.log';
		$path = dirname(__DIR__).'\\logs\\'.$name;
		$f = fopen($path, 'w');
	}
	fwrite($f, $str.PHP_EOL);
}
if ($_POST['items_analogies']){
	require_once ('../vendor/PHPExcel/IOFactory.php');
	$xls = PHPExcel_IOFactory::load($_FILES['items']['tmp_name']);
	$xls->setActiveSheetIndex(0);
	$sheet = $xls->getActiveSheet();
	$rowIterator = $sheet->getRowIterator();
	$r = 0;
	$errors = array();
	$inserted = 0;
	$analogies = 0;
	foreach ($rowIterator as $row) {
		$cellIterator = $row->getCellIterator();
		$value = array();
		foreach($cellIterator as $cell) $value[] = $cell->getCalculatedValue();
		$r++;//счетчик строк в файле
		// debug($value, $r);
		if (
				!$value[0] || 											//основной бренд пуст
				(!$value[1] && !$value[2]) ||		//артикул и каталожный номер пусты
				!$value[4] ||											//бренд аналога пуст
				(!$value[5] && !$value[6])			//артикул и каталожный номер аналога пусты
			){
			to_file("В стоке $r ошибочные данные.");
			// echo "<p>Проверка не пройдена!</p>";
			continue;
		}
		$brend = $db->select_one('brends', ['id', 'parent_id'], "`title`='{$value[0]}' ");
		if (empty($brend)){
			to_file("В строке $r бренд {$value[0]} отсутствует.");
			continue;
		}
		$brend_main_id = $brend['parent_id'] ? $brend['parent_id'] : $brend['id'];
		$brend = $db->select_one('brends', ['id', 'parent_id'], "`title`='{$value[4]}' ");
		if (empty($brend)){
			to_file("В строке $r бренд {$value[4]} отсутствует.");
			continue;
		}
		$brend_other_id = $brend['parent_id'] ? $brend['parent_id'] : $brend['id'];
		if (!$value[1]){
			$article_main = article_clear($value[2]);
			$where_main = "`brend_id`=$brend_main_id AND `article`='$article_main'";
		} 
		else{
			$article_main = article_clear($value[1]);
			$where_main = "`brend_id`=$brend_main_id AND `article`='$article_main'";
		} 
		if (!$value[6]){
			$article_other = article_clear($value[5]);
			$where_other = "`brend_id`=$brend_other_id AND `article`='$article_other'";
		} 
		else{
			$article_other = article_clear($value[6]);
			$where_other = "`brend_id`=$brend_other_id AND `article`='$article_other'";
		} 
		$main = $db->select_one('items', 'id', $where_main);
		// debug($main, 'main');
		$other = $db->select_one('items', 'id', $where_other);
		// debug($other, 'other');
		if (empty($main)){
			$res = $db->insert(
				'items',
				[
					'brend_id' => $brend_main_id,
					'article' => $article_main,
					'article_cat' => $value[2],
					'title_full' => $value[3]
				]
			);
			if ($res === true){
				$inserted++;
				$last_main = $db->last_id();
				$db->insert('articles', ['item_id' => $last_main, 'item_diff' => $last_main]);
			} 
			else to_file("В строке $r ошибка вставки данных: ".$db->error());
		} 
		else $last_main = $main['id'];
		if (empty($other)){
			$res = $db->insert(
				'items',
				[
					'brend_id' => $brend_other_id,
					'article' => $article_other,
					'article_cat' => $value[7],
					'title_full' => $value[3]
				]
			);
			if ($res === true){
				$inserted++;
				$last_other = $db->last_id();
				$db->insert('articles', ['item_id' => $last_other, 'item_diff' => $last_other]);
			} 
			else to_file("В строке $r ошибка вставки данных: ".$db->error());
		} 
		else $last_other = $other['id'];
		if ($last_other && $last_main){
			$res = $db->query("
				INSERT INTO #analogies 
					(`item_id`, `item_diff`) 
				VALUES 
					($last_main, $last_other),
					($last_other, $last_main)
			", '');
			if ($res === true) $analogies++;
			else to_file("В строке $r ошибка вставки аналога: ".$db->error());
		}
		// if ($count_main && $count_other){
		// 	$res = $db->insert(
		// 		'articles',
		// 		['i']
		// 	);
		// }
		// echo "<hr>";
	}
	echo "
		<p>Вставлено строк номенклатуры: <b>$inserted</b>.</p>
		<p>Вставлено аналогов: <b>$analogies</b>.</p>
	";
	if ($f){
		echo "<a target='_blank' href='/admin/logs/$name'>Лог ошибок</a>";
		fclose($f);
	}
}
$act = $_GET['act'];
switch ($act){
	default:
		view();
		break;
}

if ($profiling) $db->isProfiling = true;
else $db->isProfiling = false;

function view(){
	global $status, $db, $page_title;
	$page_title = 'Файлы';
	$status = "<a href='/admin'>Главная</a> > $page_title";?>
	<div class="t_form">
		<div class="bg">
			<div class="field">
				<div class="title">Загрузка номенклатуры</div>
				<div class="value">
					<form method="post" enctype="multipart/form-data">
						<input type="hidden" name="items_submit" value="1"> 
						<input type="file" name="items">
						<input type="radio" name="treatment" value="1" id="type_1" checked>
						<label for="type_1">Заполнить, которых нет</label>
						<input type="radio" name="treatment" value="2" id="type_2">
						<label for="type_2">Обновить полностью</label>
						<input type="submit" value="Загрузить">
					</form>
				</div>
			</div>
			<div class="field">
				<div class="title">Номенклатура + аналоги</div>
				<div class="value">
					<form method="post" enctype="multipart/form-data">
						<input type="hidden" name="items_analogies" value="1"> 
						<input type="file" name="items">
						<input type="submit" value="Загрузить">
					</form>
				</div>
			</div>
		</div>
	</div>
<?}?>