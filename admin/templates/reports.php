<?
$page_title = 'Отчеты';
$status = '<a href="/">Главная</a> > Отчеты';
$tab = isset($_GET['tab']) ? 'nomenclature' : $_GET['tab'];
$reports = new Reports($tab, $db);
if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'){
	switch($_POST['tab']){
		case 'nomenclature': 
			switch($_POST['act']){
				case 'clear':
					$reports->nomenclatureClear();
					break;
				case 'hide': $reports->nomenclatureHide(); break;
				default: $items = $reports->getNomenclature(); viewLog($items);
			}
			break;
		case 'brends': 
			switch($_POST['act']){
				case 'clear': $reports->brendsClear(); break;
				default: $items = $reports->getBrends(); viewLog($items);
			}
			break;
		case 'wrongAnalogy':
			if ($_POST['act'] == 'clear'){
				$reports->clearWrongAnalogies();
				break;
			}
			if ($_POST['act'] == 'removeWrongAnalogy'){
				$reports->removeWrongAnalogy();
				break;
			}
			$values = $reports->getWrongAnalogies();
			wrongAnalogy($values);
			break;
	}
	exit();
}?>
<div class="ionTabs" id="tabs_1" data-name="reports">
	<ul class="ionTabs__head">
		<li class="ionTabs__tab" data-target="nomenclature">Номенклатура</li>
		<li class="ionTabs__tab" data-target="brends">Бренды</li>
		<li class="ionTabs__tab" data-target="wrongAnalogy">Неправильный аналог</li>
	</ul>
	<div class="ionTabs__body">
		<div class="ionTabs__item" data-name="nomenclature"></div>
		<div class="ionTabs__item" data-name="brends">	</div>
		<div class="ionTabs__item" data-name="wrongAnalogy"></div>
		<div class="ionTabs__preloader"></div>
	</div>
</div>
<?function viewLog($items){?>
	<div id="total" style="margin-top: 10px;">Всего: <?=$items->num_rows?></div>
	<div class="actions" style="">
		<a class="clearLog" href="#" >Очистить</a>
	</div>
	<form method="post">
		<table class="t_table" cellspacing="1">
			<tr class="head">
				<td>Вид</td>
				<td>Таблица</td>
				<td>Источник</td>
				<td></td>
			</tr>
			<?if ($items->num_rows){
				foreach($items as $item){?>
					<tr>
						<td><?=$item['text']?></td>
						<td><?=$item['type']?></td>
						<td><?=$item['from']?></td>
						<td>
							<?if ($_POST['tab'] == 'nomenclature'){?>
								<?if (!$item['is_processed']){?>
								<input type="checkbox" name="<?=$item['type']?>" value="<?=$item['item_id']?>-<?=$item['item_diff']?>">
								<?}?>
							<?}?>
						</td>
					</tr>
				<?}
				if ($_POST['tab'] == 'nomenclature'){?>
					<tr>
						<td style="text-align: right" colspan="4"><input type="submit" value="Скрыть отмеченные"></td>
					</tr>
				<?}?>
			<?}
			else{?>
				<tr>
					<td colspan="2">Ничего не найдено</td>
				</tr>
			<?}?>
		</table>
	</form>
	<div id="pagination-container"></div>
<?}
function wrongAnalogy($values){?>
	<div id="total" style="margin-top: 10px;">Всего: <?=count($values)?></div>
	<div class="actions" style="">
		<a class="clearLog" href="#" >Очистить</a>
	</div>
	<form method="post">
		<table class="t_table" cellspacing="1">
			<tr class="head">
				<td>Вид</td>
				<td>Пользователь</td>
				<td></td>
			</tr>
			<?if (count($values)){
				foreach($values as $v){?>
					<tr>
						<td><?=$v['text']?></td>
						<td><?=$v['from']?></td>
						<td><a class="removeWrongAnalogy" item_id="<?=$v['param1']?>" item_diff="<?=$v['param2']?>" href="">Удалить связку</a></td>
					</tr>
				<?}
			}
			else{?>
				<tr>
					<td colspan="3">Ничего не найдено</td>
				</tr>
			<?}?>
		</table>
	</form>
	<div id="pagination-container"></div>
<?}
