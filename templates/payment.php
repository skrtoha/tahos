<? use core\Breadcrumb;

$title = "Баланс";
if ($_POST['bonus_current']){
	// debug($_POST); exit();
	$savable = true;
	if ($_POST['bonus_count'] > $user['bonus_count']){
		message('Указано неккоректное количество!', false);
		$savable = false;
	}
	if ($savable){
		$db->query("
			UPDATE
				#users
			SET
				`bill` = `bill` + {$_POST['bonus_count']},
				`bonus_count` = `bonus_count` - {$_POST['bonus_count']}
			WHERE
				`id`={$_SESSION['user']}
		");
		$db->insert(
			'funds',
			[
				'type_operation' => 4,
				'sum' => $_POST['bonus_count'],
				'remainder' => $user['bonus_count'] - $_POST['bonus_count'],
				'user_id' => $_SESSION['user'],
				'comment' => 'Списание бонусов'
			],
			['print_query' => false]
		);
		header("Location: /payment");
	}
}
if (!$_SESSION['user']) header("Location: /");
$fio = $user['name_1'].' '.$user['name_2'].' '.$user['name_3'];

$designation = $db->getFieldOnID('currencies', $user['currency_id'], 'designation');
Breadcrumb::add('/payment', 'Баланс');
Breadcrumb::out();
?>
<div class="payment">
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
	</div>
	<div class="pay-methods-wrap">
		<div class="pay-methods">
			<div class="method selected" data-target="paykeeper">
				<div class="img"><img src="/img/pay-methods/logo3v.png" alt="mirpay"></div>
				<p>Карта Мир</p>
			</div>
			<div class="method" data-target="yandex-money">
				<div class="img"><img src="/img/pay-methods/io.svg" alt="Yoomoney"></div>
				<p>Yoomoney</p>
			</div>
			<div class="method" data-target="mobile">
				<div class="img"><img src="/img/pay-methods/mobile-phone.png" alt="Оплата через мобильный"></div>
				<p>Мобильный <br>телефон</p>
			</div>
			<div class="method" data-target="cash">
				<div class="img"><img src="/img/pay-methods/cash.png" alt="Оплата наличными"></div>
				<p>Наличные</p>
			</div>
			<?if ($user['bonus_program']){?>
				<div class="method" data-target="bonus">
					<div class="img"></div>
					<p>Пополнение с бонусного счета</p>
				</div>
			<?}?>
		</div>
	</div>
	<div class="clearfix"></div>
	<div class="pay-block-wrap">
		<div class="pay-block show" id="paykeeper">
			<form method="POST" action="https://yoomoney.ru/quickpay/confirm.xml">
				<div class="pay-logo">
					<img src="/img/pay-methods/logo3v.png" alt="Карта Мир">
				</div>
				<h4>Пополнить счет картой on-line</h4>
				<div class="amount-wrap">
					<label for="amount">Сумма пополнения: </label>
					<input type="number" data-type="number" name="amount" id="amount" placeholder="0,00" required pattern="[0-9]+">
					<span>Руб.</span>
				</div>
				<button type="submit">Оплатить</button>
			</form>
		</div>
		<div class="pay-block" id="yandex-money">
			<form method="POST" action="https://yoomoney.ru/quickpay/confirm.xml">
				<input type="hidden" name="receiver" value="410013982328385">
				<input type="hidden" name="formcomment" value="Пополнение счета пользователя">
				<input type="hidden" name="short-dest" value="">
				<input type="hidden" name="label" value="account:<?=$_SESSION['user']?>">
				<input type="hidden" name="quickpay-form" value="donate">
				<input type="hidden" name="targets" value="Пополнение счета пользователя">
				<input type="hidden" name="comment" value="Пополнение счета для <?=$_SESSION['user']?>">
				<input type="hidden" name="need-fio" value="false">
				<input type="hidden" name="need-email" value="false">
				<input type="hidden" name="need-phone" value="false">
				<input type="hidden" name="need-address" value="false">
				<input type="hidden" name="successURL" value="http://tahos.ru/account">
				<input type="hidden" name="paymentType" value="PC">
				<div class="pay-logo">
					<img src="/img/pay-methods/io.svg" alt="Яндекс деньги">
				</div>
				<h4>Оплатить через Yoomoney</h4>
				<div class="amount-wrap">
					<label for="amount">Сумма пополнения: </label>
					<input type="number" data-type="number" name="sum" id="amount" placeholder="0,00" required pattern="[0-9]+">
					<span>руб.</span>
				</div>
				<button type="submit">Оплатить</button>
			</form>
		</div>
		<div class="pay-block" id="mobile">
			<form method="POST" action="https://yoomoney.ru/quickpay/confirm.xml">
				<input type="hidden" name="receiver" value="410013982328385">
				<input type="hidden" name="formcomment" value="Пополнение счета пользователя">
				<input type="hidden" name="short-dest" value="">
				<input type="hidden" name="label" value="account:<?=$_SESSION['user']?>">
				<input type="hidden" name="quickpay-form" value="donate">
				<input type="hidden" name="targets" value="Пополнение счета пользователя">
				<input type="hidden" name="comment" value="Пополнение счета для <?=$_SESSION['user']?>">
				<input type="hidden" name="need-fio" value="false">
				<input type="hidden" name="need-email" value="false">
				<input type="hidden" name="need-phone" value="false">
				<input type="hidden" name="need-address" value="false">
				<input type="hidden" name="paymentType" value="MC">
				<input type="hidden" name="successURL" value="http://tahos.ru/account">
				<div class="pay-logo">
					<img src="/img/pay-methods/mobile-phone.png" alt="Оплатить через мобильный">
				</div>
				<h4>Оплатить через мобильный телефон</h4>

				<div class="amount-wrap">
					<label for="amount">Сумма пополнения: </label>
					<input type="number" data-type="number" name="sum" id="amount" placeholder="0,00" required pattern="[0-9]+">
					<span>Руб.</span>
				</div>
				<p>При оплате через мобильного оператора, платеж автоматически не зачисляется. Сообщите об оплате администратору</p>
				<button type="submit">Оплатить</button>
			</form>
		</div>
		<div class="pay-block" id="cash">
			<div class="pay-logo">
				<img src="/img/pay-methods/cash.png" alt="Оплатить наличными">
			</div>
			<h4>Оплатить наличными в офисе</h4>
			<p>Адреса:</p>
			<p><a href="#">Вологда, Окружное шоссе 9Б</a></p>
			<p><a href="#">Вологда, Окружное шоссе 9Б</a></p>
		</div>
		<?if ($user['bonus_program']){?>
			<div class="pay-block" id="bonus">
				<h4>Пополнить с бонусного счета</h4>
				<form id="from_bonus" action="" method="post">
					<input type="hidden" name="bonus_current" value="<?=$user['bonus_count']?>">
					<input type="text" value="<?=$user['bonus_count']?>" name="bonus_count"><br>
					<input type="submit" value="Пополнить">
				</form>
			</div>
		<?}?>
	</div>
</div>
	