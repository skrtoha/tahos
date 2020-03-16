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
		case 'request_delete_item':
			$values = $db->query("
				SELECT
					urdi.user_id,
					IF(
						u.organization_name <> '',
						CONCAT_WS (' ', u.organization_name, ot.title),
						CONCAT_WS (' ', u.name_1, u.name_2, u.name_3)
					) AS name,
					urdi.item_id,
					b.title AS brend,
					i.title_full,
					i.article
				FROM
					#user_request_delete_item urdi
				LEFT JOIN
					#items i ON i.id = urdi.item_id
				LEFT JOIN
					#brends b ON b.id = i.brend_id
				LEFT JOIN
					#users u ON u.id = urdi.user_id
				LEFT JOIN 
					#organizations_types ot ON ot.id=u.organization_type
				WHERE
					urdi.is_processed = 0
			", ''); 
			request_delete_item($values);
			break;
		case 'clear_request_delete_item':
			$db->update('user_request_delete_item', ['is_processed' => 1], "`is_processed`=0");
			break;
		case 'delete_item':
			$db->delete('user_request_delete_item', "`item_id`={$_POST['item_id']} AND `user_id`={$_POST['user_id']}");
			break;
	}
	exit();
}?>
<div class="ionTabs" id="tabs_1" data-name="reports">
	<ul class="ionTabs__head">
		<li class="ionTabs__tab" data-target="nomenclature">Номенклатура</li>
		<li class="ionTabs__tab" data-target="brends">Бренды</li>
		<li class="ionTabs__tab" data-target="wrongAnalogy">Неправильный аналог</li>
		<li class="ionTabs__tab" data-target="request_delete_item">Удаление товара</li>
	</ul>
	<div class="ionTabs__body">
		<div class="ionTabs__item" data-name="nomenclature"></div>
		<div class="ionTabs__item" data-name="brends">	</div>
		<div class="ionTabs__item" data-name="wrongAnalogy"></div>
		<div class="ionTabs__item" data-name="request_delete_item"></div>
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
						<td label="Вид"><?=$item['text']?></td>
						<td label="Таблица"><?=$item['type']?></td>
						<td label="Источник"><?=$item['from']?></td>
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
function request_delete_item($values){?>
	<div id="total" style="margin-top: 10px;">Всего: <?=$values->num_rows?></div>
	<div class="actions" style="">
		<a class="clear_request_delete_item" href="/admin/?view=reports&tab=clear_request_delete_item" >Очистить</a>
	</div>
	<form method="post">
		<table class="t_table" cellspacing="1">
			<tr class="head">
				<td>Пользователь</td>
				<td>Бренд</td>
				<td>Артикул</td>
				<td>Наименование</td>
				<td></td>
			</tr>
			<?if ($values->num_rows){
				foreach($values as $v){?>
					<tr item_id="<?=$v['item_id']?>" user_id="<?=$v['user_id']?>">
						<td><a target="_blank" href="/admin/?view=users&act=change&id=<?=$v['user_id']?>"><?=$v['name']?></a></td>
						<td><?=$v['brend']?></td>
						<td><a target="_blank" href="/admin/?view=item&id=<?=$v['item_id']?>"><?=$v['article']?></a></td>
						<td><?=$v['title_full']?></td>
						<td><span title="Подтвердить удаление" class="icon-cross1"></span></td>
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

