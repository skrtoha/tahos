<?php
$sendings = new Sendings(NULL, $db);
if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) $sendings->getAjax();
if ($_GET['act'] == 'sent'){
	$sendings->setSent($_GET['id']);
	message('Успешно сохранено!');
	header("Location: /admin/?view=sendings");
}
$page_title = $sendings->pageTitle;
$status = $sendings->status;
$act = isset($_GET['act']) ? $_GET['act'] : null;
if (!isset($_GET['id'])) view();
else show_form();
function view(){
	global $sendings;
	$totalNumber = $sendings->getTotalNumber()?>
	<form>
		<input type="hidden" name="view" value="sendings">
		<input type="text" placeholder="Поиск по пользователю или id" name="search" value="<?=$sendings->searchText?>">
		<select name="status">
			<option value="">...статус</option>
			<option <?=$_GET['status'] == 'Ожидает' ? 'selected' : ''?> value="Ожидает">Ожидает</option>
			<option <?=$_GET['status'] == 'Отправлено' ? 'selected' : ''?> value="Отправлено">Отправлено</option>
		</select>
		<input type="submit" value="Искать">
	</form>
	<div id="total" style="margin-top: 10px;">Всего: <?=$totalNumber?></div>
	<div class="clearfix"></div>
	<input type="hidden" name="page" value="<?=isset($_GET['page']) ? $_GET['page'] : 1?>">
	<input type="hidden" name="totalNumber" value="<?=$totalNumber?>">
	<table id="common_list" class="t_table" cellspacing="1"></table>
	<div id="pagination-container"></div>
	<?}
function show_form(){
	global $sendings;
	$sending = $sendings->getSendings();
	$sending = $sending[0];
	// debug($sending);
	$sending_values = $sendings->getSendingValues($sending['issue_id']);?>
	<h3>Общие данные</h3>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Номер</td>
			<td>Дата формирования</td>
			<td>Статус</td>
			<td>Сумма</td>
			<td>Пользователь</td>
		</tr>
		<tr>
			<td><?=$sending['id']?></td>
			<td><?=$sending['date']?></td>
			<td><?=$sending['status']?></td>
			<td class="price_format"><?=$sending['sum']?></td>
			<td>
				<a href="?view=users&act=change&id=<?=$sending['user_id']?>"><?=$sending['fio']?></a>
			</td>
		</tr>
	</table>
	<h3 style="margin-top: 10px">Данные о доставке</h3>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Получатель</td>
			<td>Способ доставки</td>
			<td>Индекс</td>
			<td>Город</td>
			<td>Улица</td>
			<td>Дом</td>
			<td>Квартира</td>
			<td>Телефон</td>
			<td>Паспорт</td>
			<td>Страхование</td>
		</tr>
		<tr>
			<td><?=$sending['fio'];?></td>
			<td><?=$sending['sub_delivery']?></td>
			<td><?=$sending['index']?></td>
			<td><?=$sending['city']?></td>
			<td><?=$sending['street']?></td>
			<td><?=$sending['house']?></td>
			<td><?=$sending['flat']?></td>
			<td><?=$sending['telefon']?></td>
			<td><?=$sending['pasport']?></td>
			<td><?=$sending['insure'] ? 'Да' : 'Нет'?></td>
		</tr>
	</table>
	<div style="height: 10px"></div>
	<h3 style="float: left" style="margin-top: 10px">Товары в доставке</h3>
	<table style="clear: both" class="t_table" cellspacing="1">
		<tr class="head">
			<td>Поставщик</td>
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Наименование</td>
			<td>Цена</td>
			<td>Кол-во</td>
			<td>Сумма</td>
			<td>Комментарий</td>
		</tr>
		<?foreach($sending_values as $sv){?>
			<tr>
				<td><?=$sv['store']?></td>
				<td><?=$sv['brend']?></td>
				<td><?=$sv['article']?></td>
				<td><?=$sv['title_full']?></td>
				<td><?=$sv['price']?></td>
				<td><?=$sv['issued']?></td>
				<td><?=$sv['sum']?></td>
				<td><?=$sv['comment']?></td>
			</tr>
		<?}?>
	</table>
	<a id="sended" href="/admin/?view=sendings&id=<?=$_GET['id']?>&act=sent">Отправлено</a>
<?}?>