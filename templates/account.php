<?
/** @global \core\Database $db */
/** @global array $user */

use core\Breadcrumb;
use core\User;

$title = 'Счет';

if (!$_SESSION['user']) header("Location: /");

$designation = $user['designation'];

$bonuses_exist = false;

$params = [];
$parts = parse_url($_SERVER['REQUEST_URI']);
parse_str($parts['query'], $query);
if (!empty($query)){
    if (isset($query['end'])) $params['end'] = $query['end'];
    if (isset($query['begin'])) $params['begin'] = $query['begin'];
    if (isset($query['period'])) $params['period'] = $query['period'];
}
else{
    $dateTime = new DateTime();
    $params['end'] = $dateTime->format('d.m.Y');
    $params['begin'] = $dateTime->sub(new DateInterval('P30D'))->format('d.m.Y');
}

Breadcrumb::add('/account', 'Счет');
Breadcrumb::out();
?>
<div class="account">
	<div class="sidebar">
		<div class="balance-block">
			<h1>Баланс</h1>
            <?if (in_array($user['bill_mode'], [User::BILL_MODE_CASH, User::BILL_MODE_CASH_AND_CASHLESS])){?>
                <div class="balance balance-cash">
                    <h2>Наличный</h2>
                    <p>Кредитный лимит: <span class="credit_limit"><?=$user['credit_limit_cash']?></span><?=$designation?></p>
                    <p>Средств на счету: <span class="account-money "><?=$user['bill_cash']?></span><?=$user['designation']?></p>
                </div>
            <?}?>

            <?if (in_array($user['bill_mode'], [User::BILL_MODE_CASHLESS, User::BILL_MODE_CASH_AND_CASHLESS])){?>
                <div class="balance balance-cashless">
                    <h2>Безналичный</h2>
                    <p>Кредитный лимит: <span class="credit_limit"><?=$user['credit_limit_cashless']?></span><?=$designation?></p>
                    <p>Средств на счету: <span class="account-money "><?=$user['bill_cashless']?></span><?=$designation?></p>
                </div>
            <?}?>
			<p>Зарезервировано: <span class="account-debts "><?=$user['reserved_cash'] + $user['reserved_cashless']?></span><span style="color: red"><?=$designation?></span></p>
			<p>
                Итого:
                <span class="account-total">
                    <?=$user['bill_total'] - $user['reserved_cash'] - $user['reserved_cashless']?>
                </span>
                <span style="color: #0081bc"><?=$designation?></span></p>
			<?if ($user['bonus_program']){?>
				<p>Бонусы: <span class="account-bonus"><?=$user['bonus_count']?><?=$designation?></span></p>
			<?}?>
            <p>Отсрочка платежа: <span class="account-total"><?=$user['defermentOfPayment']?> д.</span></p>
			<button id="payment">Пополнить счет</button>
		</div>
		<div class="account-history-block">
			<h1>История счета</h1>
			<form>
				<div class="checkbox-wrap">
                    <?$checked = isset($params['period']) && $params['period'] == 'all' ? 'checked' : ''?>
					<input <?=$checked?> type="radio" name="period" id="order-filter-period-all" value="all">
					<label for="order-filter-period-all">за все время</label>
					<br><br>
                    <?$checked = $params['period'] != 'all' ? 'checked' : '';?>
					<input <?=$checked?> type="radio" name="period" id="order-filter-period-selected" value="selected">
					<label for="order-filter-period-selected">за период </label>
				</div>
				<div class="date-wrap">
					<input type="text" name="begin" id="data-pic-beg" <?=$params['period'] == 'all' ? 'disabled' : ''?> value="<?=$params['begin']?>">
					<div class="calendar-icon"></div>
				</div>
				<span> - </span>
				<div class="date-wrap">
					<input type="text" name="end" id="data-pic-end" <?=$params['period'] == 'all' ? 'disabled' : ''?>  value="<?=$params['end']?>">
					<div class="calendar-icon"></div>
				</div>
				<button>Применить</button>
			</form>
		</div>
	</div>
	<div class="ionTabs" id="account-history-tabs" data-name="account-history-tabs">
		<ul class="ionTabs__head">
			<li class="ionTabs__tab" data-target="common">История счета</li>

            <?if ($user['bill_mode'] == User::BILL_MODE_CASH_AND_CASHLESS){?>
                <li class="ionTabs__tab" data-target="cash">Наличные</li>
                <li class="ionTabs__tab" data-target="cashless">Безналичные</li>
            <?}?>
			<?if ($user['bonus_program']){?>
				<li class="ionTabs__tab" data-target="Tab_2_name">История бонусов</li>
			<?}?>
		</ul>
		<div class="ionTabs__body">
			<div class="ionTabs__item" data-name="common"></div>
            <?if ($user['bill_mode'] == User::BILL_MODE_CASH_AND_CASHLESS){?>
                <div class="ionTabs__item" data-name="cash"></div>
                <div class="ionTabs__item" data-name="cashless"></div>
            <?}?>
			<?if ($user['bonus_program']){
                $res_bonuses = $db->query("
                    SELECT 
                        * FROM 
                        #funds 
                    WHERE 
                        `type_operation` IN (3,4) AND 
                        `user_id`={$_SESSION['user']} AND
                        transfered=1
                ", '');
                ?>
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
        </div>
			<div class="ionTabs__preloader"></div>
		</div>
	</div>
</div>