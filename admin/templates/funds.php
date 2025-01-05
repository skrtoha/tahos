<?php
/** @global Database $db */

use core\Database;

$act = $_GET['act'];
$id = $_GET['id'];
switch ($act) {
	default:
		if (!empty($_POST)){
			$db->update('funds', ['is_payed' => $_POST['is_payed']], "`id` = {$_POST['fund_id']}");
			message('Успешно обновлено');
		}
		funds();
}
function funds(){
	global $status, $db, $page_title, $settings;
	$operations_types = [
		1 => 'Пополнение счета', 
		2 => 'Списание средств', 
		3 => 'Резервирование средств', 
		4 => 'Отмена резервирования'
	];

	require_once('templates/pagination.php');

	$where = '';
	$having = '';
	$type_operation = '';
	if (isset($_GET['type_operation'])){
		if ($_GET['type_operation']){
			$type_operation = $_GET['type_operation'];
			$where .= "f.type_operation = {$_GET['type_operation']} AND ";
		}
		else $where .= "f.type_operation IN (1, 2) AND ";
	}
	else{
		$type_operation = 1;
		$where .= "f.type_operation = 1 AND ";
	} 

	if (isset($_GET['is_payed']) && strlen($_GET['is_payed'])){
		if ($_GET['is_payed'] == 1) $where .= "f.overdue = 0 AND ";
		if ($_GET['is_payed'] == 0) $where .= "f.overdue > 0 AND ";
	}
	if (isset($_GET['search']) && $_GET['search']){
		$having = "full_name LIKE '%{$_GET['search']}%'";
	};
	if ($where) $where = substr($where, 0, -5);

	$query = core\Fund::getQueryListFunds($where, $having);

	$res_all = $db->query($query, '');
	$all = $res_all->num_rows;

	$page_title = 'Поиск по операциям';
	$status = "<a href='/admin'>Главная</a> > ";
	$status .= "<a href='?view=funds'>Финансовые операции</a> > $page_title";
	$page_title = "Финансовые операции";
	$status = "<a href='/admin'>Главная</a> > $page_title";
	$perPage = 30;
	$linkLimit = 10;
	$page = $_GET['page'] ? $_GET['page'] : 1;

	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ? $chank[$page] : 0;
	$query .= " LIMIT $start, $perPage";
	$res_funds = $db->query($query, '');
	?>
	<div id="div_form" class="actions">
		<form action="?view=funds">
			<input type="hidden" name="view" value="funds">
			<input style="width: 264px;" type="text" name="search" value="<?=$_GET['search']?>" placeholder="Поиск по пользователям">
			<select style="height: 25px" name="type_operation">
				<option value="">...все операции</option>
				<option <?=$type_operation == 1 ? 'selected' : ''?> value="1">Пополнение счета</option>
				<option <?=$type_operation == 2 ? 'selected' : ''?> value="2">Списание средств</option>
			</select>
			<div class="radio">
				<span>Оплачено</span>
				<label>
					<span>да</span>
					<?$checked = isset($_GET['is_payed']) && $_GET['is_payed'] == 1 ? 'checked' : ''?>
					<input <?=$checked?> type="radio" name="is_payed" value="1">
				</label>
				<label>
					<span>нет</span>
					<?$checked = isset($_GET['is_payed']) && $_GET['is_payed'] == 0 ? 'checked' : ''?>
					<input <?=$checked?> type="radio" name="is_payed" value="0">
				</label>
			</div>
			<input type="submit" value="Искать">
		</form>
		<div id="total">Всего операций: <?=$all?></div>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Дата</td>
			<td>Тип операции</td>
			<td>Сумма</td>
            <td>Счет</td>
			<td>Отстаток</td>
			<td>Пользователь</td>
			<td>Срок<br>платежа</td>
			<td>Оплачен</td>
			<td>Комментарий</td>
		</tr>
		<?if ($res_funds->num_rows){
			foreach($res_funds as $fund){?>
				<tr data-fund-id="<?=$fund['id']?>" class="<?=$fund['is_new'] ? 'is_new' : ''?>">
					<td label="Дата"><?=date('d.m.Y H:i', strtotime($fund['created']))?></td>
					<td label="Тип операции"><?=$operations_types[$fund['type_operation']]?></td>
					<td label="Сумма" class="price_format"><?=$fund['sum']?></td>
                    <td>
                        <?if ($fund['bill_type'] == \core\User::BILL_CASH){?>
                            Наличный
                        <?}?>
                        <?if ($fund['bill_type'] == \core\User::BILL_CASHLESS){?>
                            Безналичный
                        <?}?>
                    </td>
					<td label="Остаток" class="price_format"><?=$fund['remainder']?></td>
					<td label="Пользователь">
						<a href="?view=users&id=<?=$fund['user_id']?>&act=change"><?=$fund['full_name']?></a>
					</td>
					<td>
						<?if ($fund['type_operation'] == 2){?>
							<?=$fund['date_payment']?>
						<?}?>
					</td>
					<td>
						<?if ($fund['type_operation'] == 2){?>
							<?=(int) $fund['overdue'] > 0 ? 'Нет' : 'Да'?>
						<?}?>
					</td>
					<td label="Комментарий"><?=stripslashes($fund['comment'])?></td>
				</tr>
			<?}
		}
		else{?>
			<tr><td colspan="6">Движений средств не найдено</td></tr>
		<?}?>
	</table>
	<?$db->update('funds', ['is_new' => 0], '`is_new`=1');
	pagination($chank, $page, ceil($all / $perPage), $href = "?view=funds&search={$_GET['search']}&type_operation=$type_operation&is_payed={$_GET['is_payed']}&page=");
}?>
