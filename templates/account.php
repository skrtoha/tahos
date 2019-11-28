<?$title = 'Счет';
$period = $_POST['period'];
if ($period){
	switch ($period){
		case 'all': $where_date = ''; break;
		case 'selected':
			$begin = get_date_account($_POST['begin']);
			$end = get_date_account($_POST['end']);
			$where_date = "`created` >= '{$begin['year']}-{$begin['month']}-{$begin['day']} 0:0:0' AND ";
			$where_date .= "`created` <= '{$end['year']}-{$end['month']}-{$end['day']} 23:59:59' AND ";
			break;
	}
}
else{
	$a = getdate(time() - 60 * 60 * 24 * 30);
	// $begin = mktime(0,0,0, $array['mon'], $array['mday'], $array['year']);
	$begin = "{$a['year']}-{$a['mon']}-{$a['mday']} 0:0:0";
	$a = getdate(time());
	$end = "{$a['year']}-{$a['mon']}-{$a['mday']} 23:59:59";
	$where_date = "`created`>='$begin' AND `created`<='$end' AND ";
}

if (!$_SESSION['user']) header("Location: /");
$designation = $user['designation'];
$funds = $db->select('funds', '*', "`type_operation` IN (1,2) AND $where_date `user_id`=".$_SESSION['user'], 'created', false);
$operations_types = array(
	1 => 'Пополнение счета', 
	2 => 'Списание средств',
	3 => 'Зачисление бонусов',
	4 => 'Списание бонусов'
);
$bonuses_exist = false;
$res_bonuses = $db->query("
	SELECT 
		* FROM 
		#funds 
	WHERE 
		`type_operation` IN (3,4) AND 
		`user_id`={$_SESSION['user']} AND
		transfered=1
", '');

// debug($funds);
?>
<div class="account">
	<div class="sidebar">
		<div class="balance-block">
			<h1>Баланс</h1>
			<?$bill = payment_funds('bill', $user);?>
			<p>Средств на счету: <span class="account-money "><?=$bill?></span><?=$designation?></p>
			<?$reserved_funds = payment_funds('reserved_funds', $user);?>
			<p>Зарезервировано: <span class="account-debts "><?=$reserved_funds?></span><span style="color: red"><?=$designation?></span></p>
			<p>Итого: <span class="account-total "><?=payment_funds('bill', $user, true)?></span><span style="color: #0081bc"><?=$designation?></span></p>
			<?if ($user['bonus_program']){?>
				<p>Бонусы: <span class="account-bonus"><?=$user['bonus_count']?><?=$designation?></span></p>
			<?}?>
			<button id="payment">Пополнить счет</button>
		</div>
		<div class="account-history-block">
			<h1>История счета</h1>
			<form method="post">
				<div class="checkbox-wrap">
					<input type="radio" name="period" id="order-filter-period-all" value="all" <?=$period == 'all' ? 'checked' : ''?>>
					<label for="order-filter-period-all">за все время</label>
					<br><br>
					<input type="radio" name="period" id="order-filter-period-selected" value="selected" <?=($period == 'selected' or !$period) ? 'checked' : ''?>>
					<label for="order-filter-period-selected">за период </label>
				</div>
				<div class="date-wrap">
					<?$value = $_POST['begin'] ? $_POST['begin'] : begin_date()?>
					<input type="text" name="begin" id="data-pic-beg" <?=$period == 'all' ? 'disabled' : ''?> value="<?=$value?>">
					<div class="calendar-icon"></div>
				</div>
				<span> - </span>
				<div class="date-wrap">
					<?$value = $_POST['end'] ? $_POST['end'] : end_date()?>
					<input type="text" name="end" id="data-pic-end" <?=$period == 'all' ? 'disabled' : ''?>  value="<?=$value?>">
					<div class="calendar-icon"></div>
				</div>
				<button>Применить</button>
			</form>
		</div>
	</div>
	<div class="ionTabs" id="account-history-tabs" data-name="account-history-tabs">
		<ul class="ionTabs__head">
			<li class="ionTabs__tab" data-target="Tab_1_name">История счета</li>
			<?if ($user['bonus_program']){?>
				<li class="ionTabs__tab" data-target="Tab_2_name">История бонусов</li>
			<?}?>
		</ul>
		<div class="ionTabs__body">
			<div class="ionTabs__item" data-name="Tab_1_name">
				<table>
					<tr>
						<th>Вид операции</th>
						<th>Товар</th>
						<th>Дата</th>
						<th>Сумма</th> 
					</tr>
					<?if (isset($funds) && count($funds)){
						foreach ($funds as $fund){?>
							<tr>
								<td><?=$operations_types[$fund['type_operation']]?></td>
								<td class="name-col"><?=stripslashes($fund['comment'])?></td>
								<td><?=date('d.m.Y H:i', strtotime($fund['created']))?></td>
								<td>
									<?$color = $fund['type_operation'] == 1 ? 'positive-color' : 'negative-color';
									$minus_plus = $fund['type_operation'] == 1 ? '+' : '-';
									?>
									<span class="<?=$color?>">
										<?=$minus_plus?>
										<span class="price_format"><?=$fund['sum']?></span><i class="fa fa-rub" aria-hidden="true"></i>
									</span>
								</td>
							</tr>
						<?}
					}
					else{?>
						<tr><td colspan="4">Операций со счетом не найдено</td></tr>
					<?}?>
				</table>
				<table class="small-view">
					<tr>
						<th>Операция</th>
						<th>Дата</th>
						<th>Сумма</th>
					</tr>
					<?if (isset($funds) && count($funds)){
						foreach ($funds as $fund){
							if (in_array($fund['type_operation'], [3,4])) continue;

							?>
							<tr>
								<td class="name-col"><?=stripslashes($fund['comment'])?></td>
								<td><?=date('d.m.Y H:i', $fund['date'])?></td>
								<td>
									<?$color = $fund['type_operation'] == 1 ? 'positive-color' : 'negative-color';
									$minus_plus = $fund['type_operation'] == 1 ? '+' : '-';?>
									<span class="<?=$color?>">
										<?=$minus_plus?>
										<span class="price_format"><?=$fund['sum']?></span><i class="fa fa-rub" aria-hidden="true"></i>
									</span>
								</td>
							</tr>
					<?}
					}?>
				</table>
			</div>
			<?if ($user['bonus_program']){?>
				<div class="ionTabs__item" data-name="Tab_2_name">
					<table>
						<tr>
							<th>Вид операции</th>
							<th>Товар</th>
							<th>Дата</th>
							<th>Сумма</th>
						</tr>
						<?if ($res_bonuses->num_rows){
							while ($fund = $res_bonuses->fetch_assoc()){
								if (!$fund['transfered']){
									$db->query("
										UPDATE
											#users
										SET
											`bonus_count`=`bonus_count`+{$fund['sum']}
										WHERE
											`id`={$user['id']}
									", '');
									$db->query("
										UPDATE
											#funds
										SET
											`transfered`=1
										WHERE
											`id`={$fund['id']}
									", '');
								}?>
								<tr>
									<td><?=$operations_types[$fund['type_operation']]?></td>
									<td class="name-col"><?=stripslashes($fund['comment'])?></td>
									<td><?=date('d.m.Y H:i', strtotime($fund['created']))?></td>
									<td>
										<?$color = $fund['type_operation'] == 3 ? 'positive-color' : 'negative-color';
										$minus_plus = $fund['type_operation'] == 3 ? '+' : '-';?>
										<span class="<?=$color?>">
											<?=$minus_plus?>
											<span class="price_format"><?=$fund['sum']?></span><i class="fa fa-rub" aria-hidden="true"></i>
										</span>
									</td>
								</tr>
								<?}
						}
						else{?>
							<tr><td colspan="4">Операций со бонусами не найдено</td></tr>
						<?}?>
					</table>
					<table class="small-view">
						<tr>
							<th>Операция</th>
							<th>Дата</th>
							<th>Сумма</th>
						</tr>
						<?if ($bonuses_exist){
							foreach ($funds as $fund){
								if (in_array($fund['type_operation'], [1,2])) continue;?>
								<tr>
									<td class="name-col"><?=stripslashes($fund['comment'])?></td>
									<td><?=date('d.m.Y H:i', $fund['date'])?></td>
									<td>
										<?$color = $fund['type_operation'] == 1 ? 'positive-color' : 'negative-color';
										$minus_plus = $fund['type_operation'] == 1 ? '+' : '-';
										?>
										<span class="<?=$color?>">
											<?=$minus_plus?>
											<span class="price_format"><?=$fund['sum']?></span><i class="fa fa-rub" aria-hidden="true"></i>
										</span>
									</td>
								</tr>
						<?}
						}
						else{?>
							<tr><td colspan=3>История бонусов отсутсвует</td></tr>
						<?}?>
					</table>
				</div>
			<?}?>
			<div class="ionTabs__preloader"></div>
		</div>
	</div>
</div>