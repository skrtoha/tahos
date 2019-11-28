<?$act = $_GET['act'];
if ($_POST['currency_id']){
	$db->update('currencies', array('rate' => $_POST['rate']), '`id`='.$_POST['currency_id']);
	message('Успешно изменено!');
	header('Location: ?view=currencies');
}
switch ($act) {
	default:
		view();
}
function view(){
	global $status, $db, $page_title;
	$currencies = $db->select('currencies', '*', '', '', '', '', true);
	$page_title = "Валюта";
	$status = "<a href='/admin'>Главная</a> > $page_title"?>
	<a href="?view=get_currencies">Обновить курс валют</a>
	<span>Обновлено: <?=date('d.m.Y H:i', $db->getFieldOnID('settings', 1, 'currencies_update'))?></span>
	<div style="height: 10px"></div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Название</td>
			<td>Курс</td>
		</tr>
		<?foreach($currencies as $id => $currency){?>
			<tr>
				<td><?=$currency['title']?></td>
				<td>
					<form method="post">
						<input style="padding-left: 10px;height: 34px" type="text" name="rate" value="<?=$currency['rate']?>">
						<input type="hidden" name="currency_id" value="<?=$id?>">
						<input type="submit" value="Изменить">
					</form>
				</td>
			</tr>
		<?}?>
		</table>
<?}?>