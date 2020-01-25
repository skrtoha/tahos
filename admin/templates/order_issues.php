<?php
// debug($_GET);
// require_once('../functions/order_issues.function.php');
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
$issues = new Issues($user_id, $db);
// debug($issues);
if (isset($_GET['ajax'])) $issues->getAjax();
if ($_GET['act'] == 'print') $issues->print($_GET['issue_id']);
$status = "<a href='/admin'>Главная</a> > ";
if ($_GET['user_id'] && !$_GET['issued']){
	// print_r($_SERVER);
	// debug($_POST); exit();
	if (!empty($_POST['income'])) $issues->setIncome();
	$user = $issues->getUser();
	$page_title = "Выдача товара";
	$res_orders_values = $issues->getOrderValues();
	$status .= "
		<a href='?view=users'>Пользователи</a> >
		<a href='?view=users&act=change&id={$_GET['user_id']}'>
			{$user['name_1']} {$user['name_2']} {$user['name_3']}
		</a> >
		$page_title
	";
}
elseif($_GET['issue_id']){
	$page_title = "Выдача №{$_GET['issue_id']}";
	$status .= '<a href="/admin/?view=order_issues&page='.$_GET['page'].'">Выдачи товара</a> > '.$page_title;
	$array = $issues->getIssueWithUser($_GET['issue_id']);
	$user = $array['user'];
}
elseif ($_GET['issued']){
	$user = $issues->getUser();
	$page_title = 'Выданные товары';
	$status .= "
		<a href='?view=users'>Пользователи</a> >
		<a href='?view=users&act=change&id={$_GET['user_id']}'>
			{$user['name_1']} {$user['name_2']} {$user['name_3']}
		</a> >
		$page_title
	";
}
else{
	$page_title = 'Выдачи товара';
	$status .= $page_title;
}?>
<?if ($_GET['user_id'] && !$_GET['issued']){?>
	<input type="hidden" name="user_id" value="<?=$_GET['user_id']?>">
	<form id="user_order_issues" method="post">
		<table class="t_table" cellspacing="1">
			<tr class="head">
				<td>Поставщик</td>
				<td>Бренд</td>
				<td>Артикул</td>
				<td>Наименование</td>
				<td>Цена</td>
				<td>Доступно</td>
				<td>Сумма</td>
				<td>Комментарий</td>
				<td>№ заказа</td>
				<td>Дата</td>
				<td><input type="checkbox" name="all" value="1"></td>
			</tr>
			<?if (!$res_orders_values->num_rows){?>
				<tr>
					<td colspan="12">Товаров для выдачи не найдено</td>
				</tr>
			<?}
			else{
				while ($v = $res_orders_values->fetch_assoc()){?>
					<tr class="status_<?=$v['class']?>">
						<td><?=$v['cipher']?></td>
						<td><?=$v['brend']?></td>
						<td><?=$v['article']?></td>
						<td><?=$v['title_full']?></td>
						<td><span class="price_format"><?=$v['price']?></span></td>
						<td class="amount">
							<?=$v['arrived'] - $v['issued']?>
						</td>
						<td><span class="price_format"><?=$v['price'] * $v['arrived']?></span></td>
						<td><?=$v['comment']?></td>
						<td><a href="?view=orders&id=<?=$v['order_id']?>&act=change"><?=$v['order_id']?></a></td>
						<td><?=$v['created']?></td>
						<td>
							<input type="hidden" value="<?=$v['arrived'] - $v['issued']?>">
							<input type="checkbox" name="income[<?=$v['order_id']?>:<?=$v['item_id']?>:<?=$v['store_id']?>]" value="<?=$v['arrived'] - $v['issued']?>">
						</td>
					</tr>
				<?}
			}?>
			<tr>
				<td style="text-align: right" colspan="12"><input  type="submit" value="Отправлено"></td>
			</tr>
		</table>
	</form>
<?}
elseif($_GET['issue_id']){?>
	<h3 style="float: left">Данные о выдаче</h3>
	<div id="order_print_div">
		<a target="_blank" href="/admin/?view=order_issues&act=print&issue_id=<?=$_GET['issue_id']?>" >Печать</a>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Номер</td>
			<td>Пользователь</td>
			<td>Сумма</td>
			<td>Дата заказа</td>
		</tr>
		<tr>
			<td><?=$_GET['issue_id']?></td>
			<td>
				<a href="?view=users&act=funds&id=<?=$user['id']?>">
					<?=$user['name']?>
				</a> 
				(<b class="price_format"><?=$user['available']?></b> руб.)
			</td>
			<td class="price_format"><?=$array['summ']?></td>
			<td><?=$array['created']?></td>
		</tr>
	</table>
	<h3 style="margin-top: 10px">Товары в выдаче</h3>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Наименование</td>
			<td>Цена</td>
			<td>Выдано</td>
			<td>Сумма</td>
			<td>Комментарий</td>
		</tr>
		<?foreach ($array['issue_values'] as $iv){?>
			<tr>
				<td><?=$iv['brend']?></td>
				<td><?=$iv['article']?></td>
				<td><?=$iv['title_full']?></td>
				<td class="price_format"><?=$iv['price']?></td>
				<td><?=$iv['issued']?></td>
				<td><?=$iv['price'] * $iv['issued']?></td>
				<td><?=$iv['comment']?></td>
			</tr>
		<?}?>
	</table>
<?}
elseif($_GET['issued']){?>
	<input type="hidden" name="user_id" value="<?=$_GET['user_id']?>">
	<input type="hidden" name="page" value="<?=isset($_GET['page']) ? $_GET['page'] : 1?>">
	<input type="hidden" name="totalNumber" value="<?=$issues->getCount('order_issues', "`user_id`={$_GET['user_id']}")?>">
	<table id="user_issue_values" class="t_table" cellspacing="1"></table>
	<div id="pagination-container"></div>
<?}
else{?>
	<input type="hidden" name="page" value="<?=isset($_GET['page']) ? $_GET['page'] : 1?>">
	<input type="hidden" name="totalNumber" value="<?=$issues->db->getCount('order_issues')?>">
	<table id="common_list" class="t_table" cellspacing="1"></table>
	<div id="pagination-container"></div>
<?}?>

		