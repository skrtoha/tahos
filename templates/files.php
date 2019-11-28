<?
// require_once('../class/PHPExcel.php');
if ($_POST['items_submit']){
	require_once ('../class/PHPExcel/IOFactory.php');
	$xls = PHPExcel_IOFactory::load($_FILES['items']['tmp_name']);
	$xls->setActiveSheetIndex(0);
	$sheet = $xls->getActiveSheet();
	$rowIterator = $sheet->getRowIterator();
	$i = -1;
	foreach ($rowIterator as $row) {
		$i++;
		$cellIterator = $row->getCellIterator();
		foreach($cellIterator as $cell){
			$items[$i][] = $cell->getCalculatedValue();
		}
	}
	$errors = array();
	$inserted = '';
	$updated = '';
	$brends = array();
	//удаляем строку с наименованием
	array_shift($items);
	$count = count($items);
	//создаем новый массив с уникальными брендами
	if ($count){
		$temp = (unique_multidim_array($items, 0));
		foreach ($temp as $value){
			$brend_title = $value[0];
			$brend = $db->select('brends', 'id,parent_id', "`title`='$brend_title'");
			if (count($brend)){
				$brend = $brend[0];
				$brend_id = $brend['parent_id'] ? $brend['parent_id'] : $brend['id'];
				$brends[$brend_title] = $brend_id;
			}
			else{
				message("Бренд $brend_title в базе не найден!", false);
				header('Location: ?view=files');
				exit();
			}
		} 
		$depth = $_POST['depth'];
		if ($count >= $depth * 3){
			$cycles = floor($count / $depth);
			$remainder = $count - $cycles * $depth;
			for ($cycle = 0; $cycle < $cycles; $cycle++){
				if (!$db->getCount('items', get_query('where'))){
					$db->query(get_query('insert'));
				}
				else check_alone();
			}
			if ($remainder){
				$i = $i - 1;
				if (!$db->getCount('items', get_query_remainder('where'))){
					$db->query(get_query_remainder('insert'));
				}
				else check_alone_remainder();			
			}
		}
		if (count($errors)) $msg = '<span style="color: red">В следующих строках возникли ошибки: <b>'.implode(', ', $errors). '</b></span>.<br>';
		if ($msg){
			if ($inserted) $msg .= "Вставлено новых строк: <b>$inserted</b>.<br>";
			if ($updated) $msg .= "Обновлено строк: <b>$updated</b><br><br>";
		}
		else{
			if ($inserted) $msg = "Вставлено новых строк: <b>$inserted</b>.<br>";
			if ($updated){
				if ($msg)	$msg .= "Обновлено строк: <b>$updated</b><br>";
				else $msg = "Обновлено строк: <b>$updated</b><br>";
			} 
		}
	}
	echo $msg;
}
$act = $_GET['act'];
switch ($act) {
	default:
		view();
		break;
}
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
						<input type="text" style="width: 50px" value="3" name="depth">
						<input type="submit" value="Загрузить">
					</form>
				</div>
			</div>
		</div>
	</div>
<?}?>