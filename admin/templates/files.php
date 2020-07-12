<?
set_time_limit(0);
require_once ($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

//Закоментировать для включения лога запросов
$isUseProfiling = false;

$profiling = $db->isProfiling;
if ($isUseProfiling) $db->isProfiling = true;

if ($_POST['items_submit']){
	$log_name = 'items_'.date('d.m.Y_H-i-s').'.txt';
	$log = new Katzgrau\KLogger\Logger('logs', Psr\Log\LogLevel::WARNING, array(
		'filename' => $log_name,
		'dateFormat' => 'G:i:s'
	));
	$db->isProfiling = false;
	$xls = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['items']['tmp_name']);
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
		else $article = trim($value[1]);
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
if ($_POST['items_analogies']){
	$insertedItems = 0;
	$insertedAnalogies = 0;
	if ($_FILES['items']['error']) die("Ошибка загрузки файла. Код ошибки: {$_FILES['items']['error']}");

	core\Timer::start();
	$xls = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['items']['tmp_name']);
	$price = new core\Price($db, 'items_analogies');

	$xls->setActiveSheetIndex(0);
	$sheet = $xls->getActiveSheet();
	$rowIterator = $sheet->getRowIterator();
	$r = 0;

	foreach ($rowIterator as $row) {
		$item_analogy_id = false;
		$item_main_id = false;

		$cellIterator = $row->getCellIterator();
		$value = array();
		foreach($cellIterator as $cell) $value[] = $cell->getCalculatedValue();
		$r++;//счетчик строк в файле

		// if ($r > 100) break;
		//debug($value); //echo "<hr>"; continue;

		if (
				!$value[0] || 											//основной бренд пуст
				(!$value[1] && !$value[2]) ||		//артикул и каталожный номер пусты
				!$value[4] ||											//бренд аналога пуст
				(!$value[5] && !$value[6])			//артикул и каталожный номер аналога пусты
			){
			$price->log->error("В стоке $r ошибочные данные.");
			continue;
		}

		$brendMain = $price->getBrendId($value[0]);
		$brendAnalogy = $price->getBrendId($value[4]);
		if (!$brendMain || !$brendAnalogy) continue;
		// debug($value);
		$articleMain = article_clear($value[1] ? $value[1] : $value[2]);
		$articleAnalogy = article_clear($value[5] ? $value[5] : $value[6]);

		$resMain = $db->insert('items', [
			'brend_id' => $brendMain,
			'article' => $articleMain,
			'article_cat' => $value[2],
			'title_full' => $value[3]
		]);
		if ($resMain === true){
			$insertedItems++;
			$item_main_id = $db->last_id();
			$db->insert('articles', ['item_id' => $item_main_id, 'item_diff' => $item_main_id]);
		} 
		else{
			$itemMain = $db->select_one('items', 'id', "`article` = '{$articleMain}' AND `brend_id` = $brendMain");
			$item_main_id = $itemMain['id'];
		}
		if (!$item_main_id){
			$price->log->error("Ошибка получения основного id номенклатуры в $r.");
			continue;
		}

		if ($_POST['create_analogies']){
			$resAnalogy = $db->insert('items', [
				'brend_id' => $brendAnalogy,
				'article' => $articleAnalogy,
				'article_cat' => $value[6],
				'title_full' => $value[3]
			]);
			if ($resAnalogy === true){
				$insertedItems++;
				$item_analogy_id = $db->last_id();
				$db->insert('articles', ['item_id' => $item_analogy_id, 'item_diff' => $item_analogy_id]);
			} 
		}

		if (!$item_analogy_id){
			$itemAnalogy = $db->select_one('items', 'id', "`article`='{$articleAnalogy}' AND `brend_id` = $brendAnalogy");
			if (empty($itemAnalogy)){
				$price->log->warning("В строке $r не найдено {$value[4]} - $articleAnalogy");
				continue;
			}
			else $item_analogy_id = $itemAnalogy['id'];
		}

		if ($item_analogy_id && $item_main_id){
			$res1 = $db->insert('analogies', ['item_id' => $item_analogy_id, 'item_diff' => $item_main_id]);
			$res2 = $db->insert('analogies', ['item_id' => $item_main_id, 'item_diff' => $item_analogy_id]);
			if ($res1 === true && $res2 === true) $insertedAnalogies++;
		}
	}
	echo "
		<p>Время обработки: ".core\Timer::end()." секунд.</p>
		<p>Вставлено строк номенклатуры: <b>$insertedItems</b>.</p>
		<p>Вставлено аналогов: <b>$insertedAnalogies</b>.</p>
		<a target='_blank' href='/admin/logs/{$price->nameFileLog}'>Лог ошибок</a>
	";
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
						<label>
							<input type="checkbox" name="create_analogies" value="1">
							<span>создавать аналоги</span>
						</label>
						<input type="submit" value="Загрузить">
					</form>
				</div>
			</div>
		</div>
	</div>
<?}?>