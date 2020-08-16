<?
function get_order($flag = ''){
	global $db;
	$query = "
		SELECT
			o.id,
			DATE_FORMAT(o.created, '%d.%m.%Y %H:%i:%s') AS date,
			GROUP_CONCAT(ov.status_id) AS statuses,
			GROUP_CONCAT(ov.price) AS prices,
			GROUP_CONCAT(ov.quan) AS quans,
			GROUP_CONCAT(ov.ordered) AS ordered,
			GROUP_CONCAT(ov.arrived) AS arrived,
			GROUP_CONCAT(ov.issued) AS issued,
			GROUP_CONCAT(ov.returned) AS returned,
			IF(
				u.organization_name != '',
				CONCAT_WS(' ', ot.title, u.organization_name),
				CONCAT_WS(' ', u.name_1, u.name_2, u.name_3) 
			) AS organization,
			CONCAT_WS(' ', u.name_1, u.name_2, u.name_3) AS fio,
			if (u.delivery_type = 'Самовывоз', u.issue_id, 1) as user_issue,
			o.user_id,
			u.bill,
			u.reserved_funds,
			u.deferment_of_payment,
			u.pay_type,
			o.is_new,
			o.is_draft
		FROM
			#orders o
		LEFT JOIN #orders_values ov ON ov.order_id=o.id
		LEFT JOIN #users u ON u.id=o.user_id
		LEFT JOIN #organizations_types ot ON ot.id=u.organization_type
		WHERE o.id={$_GET['id']}
	";
	if ($flag) return $db->query($query, $flag);
	$order = $db->select_unique($query, $flag);
	return $order[0];
}
function get_order_values(array $params = [], string $flag = ''): mysqli_result
{
	global $db;
	$where = '';
	$limit = '';
	if (!empty($params)){
		foreach($params as $key => $value){
			switch($key){
				case 'order_id':
				case 'item_id':
				case 'status_id':
					$where .= "ov.$key = '$value' AND ";
					break;
				case 'limit':
					$limit = "LIMIT $value";
					break;
			}
		}
	}
	if ($where){
		$where = substr($where, 0, -5);
		$where = "WHERE $where";
	}
	return $db->query("
		SELECT
			ps.cipher,
			i.brend_id,
			b.title AS brend,
			IF (i.title_full != '', i.title_full, i.title) AS title_full,
			IF (
				i.article_cat != '', 
				i.article_cat, 
				IF (
					i.article !='',
					i.article,
					i.barcode
				)
			) AS article,
			IF (si.packaging IS NOT NULL, si.packaging, 1) AS packaging,
			ov.order_id,
			ov.store_id,
			ov.item_id,
			ov.price,
			ov.quan,
			ov.ordered,
			ov.arrived,
			ov.issued,
			ov.declined,
			ov.returned,
			(ov.price * ov.quan) AS sum,
			ov.comment,
			os.id AS status_id,
			os.title AS status,
			os.class AS status_class,
			o.user_id,
			u.bill,
			u.reserved_funds,
			ps.delivery,
			ps.title AS providerStore,
			ps.provider_id,
			mzc.ZakazCode,
			IF(r.item_id IS NOT NULL, 1, 0) return_ordered,
			IF (ps.noReturn, 'class=\"noReturn\" title=\"Возврат поставщику невозможен!\"', '') AS noReturn,
			c.id AS correspond_id,
			IF(ps.calendar IS NOT NULL, ps.calendar, p.calendar) AS  calendar,
			IF(ps.workSchedule IS NOT NULL, ps.workSchedule, p.workSchedule) AS  workSchedule,
			(
				SELECT 
					COUNT(id)
				FROM 
					#messages
				WHERE correspond_id=c.id
			) as count
		FROM
			#orders_values ov
		LEFT JOIN #provider_stores ps ON ps.id=ov.store_id
		LEFT JOIN #store_items si ON si.store_id=ov.store_id AND si.item_id=ov.item_id
		LEFT JOIN #returns r ON r.order_id = ov.order_id AND r.store_id=ov.store_id AND r.item_id=ov.item_id
		LEFT JOIN #providers p ON p.id=ps.provider_id
		LEFT JOIN #items i ON i.id=ov.item_id
		LEFT JOIN #brends b ON b.id=i.brend_id
		LEFT JOIN #orders_statuses os ON os.id=ov.status_id
		LEFT JOIN #orders o ON ov.order_id=o.id
		LEFT JOIN #users u ON u.id=o.user_id
		LEFT JOIN #corresponds c 
		ON
			c.order_id=ov.order_id AND
			c.store_id=ov.store_id AND
			c.item_id=ov.item_id
		LEFT JOIN
			#mikado_zakazcode mzc ON mzc.item_id = ov.item_id 
		$where
		ORDER BY o.created DESC
		$limit
	", $flag);
}
function order_print($order, $res_orders_values){
	global $db;
	$issue = $db->select_one('issues', ['title', 'adres'], "`id`={$order['user_issue']}");?>
	<head>
		<title></title>
		<link rel="stylesheet" type="text/css" href="/css/admin.css">
		<link rel="shortcut icon" href="/img/favicon/favicon.png" type="image/x-icon">
		<link rel="apple-touch-icon" href="/img/favicon/apple-touch-icon.png">
		<link rel="apple-touch-icon" sizes="72x72" href="/img/favicon/apple-touch-icon-72x72.png">
		<link rel="apple-touch-icon" sizes="114x114" href="/img/favicon/apple-touch-icon-114x114.png">
		<script src="/js/libs.min.js"></script>
	</head>
	<div id="order_print">
		<script>
			window.onload =	 function () {window.print();}
		</script>
		<h1>Договор-заказ №<?=$order['id']?> от <?=preg_replace('/ \d\d:\d\d:\d\d/', '', $order['date'])?></h1>
		<p><?=$issue['adres']?></p>
		<p><?=$issue['title']?> (в дальнейшем ПОСТАВЩИК) с одной стороны и <?=$order['organization']?> (именуемый в дальнейшем ЗАКАЗЧИК), заключили настоящий договор о нижеследующем:</p>
		<p><b>ПОСТАВЩИК</b> обязуется поставить товары:</p>
		<?=getTableOrder($res_orders_values)?>
		<p style="white-space: nowrap">
			<span>Поставщик  ______________</span>
			<span><strong>Клиент: <span id="square"></span></strong></span>
			<span><?=$order['fio']?></span>
		</p>
		<p>Тип оплаты: <?=$order['pay_type']?></p>
		<p>Отсрочка платежа: <?=$order['deferment_of_payment']?> дней.</p>
		<p><strong>Подбор номенклатуры произведен в моем присутствии по данным, предоставленным мною.</strong></p>
		<p>______________________________</p>
	</div>
<?}
function get_summ($order){
	// debug($order);
	if (!$order['price']) return 0;
	$summ = 0;
	$statuses = explode(',', $order['statuses']);
	$prices = explode(',', $order['price']);
	$quans = explode(',', $order['quan']);
	$ordered = explode(',', $order['ordered']);
	$arrived = explode(',', $order['arrived']);
	$issued = explode(',', $order['issued']);
	$returned = explode(',', $order['returned']);
	$count = count($prices);
	for ($i = 0; $i < $count; $i++){
		if (in_array($statuses[$i], [6, 8])) continue;
		$summ += $prices[$i] * $quans[$i];
		if ($ordered[$i]) $summ -= ($quans[$i] - $ordered[$i]) * $prices[$i];

		//закоментировано, т.к. если пришел не весь товар, то сумма уменьшалась
		// if ($arrived[$i]) $summ -= ($ordered[$i] - $arrived[$i]) * $prices[$i];
		
		if ($issued[$i]) $summ -= ($arrived[$i] - $issued[$i]) * $prices[$i];
		if ($returned[$i]) $summ -= $returned[$i] * $prices[$i];
	} 
	return $summ;
}
function get_order_statuses($status){
	global $db;
	switch ($status) {
		case 1: $in = array(2); break;
		// case 2: $in = array(9); break;
		case 2: $in = array(); break;
		case 3: $in = array(1); break;
		case 4: $in = array(2); break;
		case 5: $in = array(7); break;
		case 6: $in = array(); break;
		case 11: $in = array(8, 3); break;
		case 7: $in = array(11, 6); break;
		case 8: $in = array(); break;
		case 10: $in = array(4, 3); break;
	}
	return $db->select('orders_statuses', '*', '`id` IN ('.implode(',', $in).')');
}
function get_status($s){
	$statuses = explode(',', $s);
	$canceled = true;
	foreach($statuses as $value){
		if (in_array($value, [3, 7, 11])) return 'В работе';
	}
	foreach($statuses as $value){
		if ($value != 8){
			$canceled = false;
			break;
		}
	}
	if ($canceled) return 'Отменен';
	foreach ($statuses as $value){
		if ($value == 5) return 'Ожидает';
	}
	$done = true;
	$done_statuses = array(1, 2, 3, 4, 6, 8, 9);
	foreach($statuses as $value){
		if (!in_array($value, $done_statuses)){
			$done = false;
			break;
		}
	}
	if ($done) return 'Завершен';
	return 'Ожидает';
}
function getUser($user_id){
	global $db;
	$q_user = "
		SELECT 
			u.*,
			c.designation, 
			c.rate, 
			u.delivery_type,
			u.bonus_count,
			i.title AS issue_title,
			i.desc AS issue_desc,
			i.adres AS issue_adres,
			i.telephone AS issue_telephone,
			i.email AS issue_email,
			i.twitter AS issue_twitter,
			i.vk AS issue_vk,
			i.facebook AS issue_facebook,
			i.google AS issue_google,
			i.ok AS issue_ok,
			i.coords AS issue_coords,
			c.id as currency_id
		FROM #users u
		LEFT JOIN #currencies c ON c.id=u.currency_id
		LEFT JOIN #issues i ON i.id=u.issue_id
		WHERE u.id={$user_id}
	";
	$user = $db->select_unique($q_user, '');
	$user = $user[0];
	return $user;
}
/**
 * [Возвращает имя класса для для подсвечивания в списке заказов]
 * @param  [string] $status В работе|Завершен|Ожидает
 * @return [stirng]         имя класса
 */
function get_status_color($status){
	switch($status){
		case 'Завершен': return 'status_1'; break;
		case 'Ожидает': return 'status_5'; break;
		case 'В работе': return 'status_7'; break;
	}
}
/**
 * [getTableOrder Получение html-таблицы товаров в заказе]
 * @param  [array] $orderValues Список товаров
 * @return [string] html-код таблицы
 */
function getTableOrder($orderValues){
	ob_start();?>
	<table border="1">
			<tr>
				<th>№</th>
				<th>Бренд</th>
				<th>Артикул</th>
				<th>Наименование</th>
				<th>Цена</th>
				<th>Кол-во</th>
				<th>Сумма</th>
				<th>Комментарий</th>
			</tr>
			<? $i = 1;
			$max = 0;
			foreach($orderValues as $row){
				if (in_array($row['status'], ['Возврат', 'Нет в наличии', 'Отменен'])) continue;?>
				<tr>
					<td class="td_center"><?=$i?></td>
					<td><?=$row['brend']?></td>
					<td><?=$row['article']?></td>
					<td><?=$row['title_full']?></td>
					<td class="td_center"><?=$row['price']?></td>
					<td class="td_center"><?=$row['quan']?></td>
					<td class="td_center"><?=$row['sum']?></td>
					<td><?=$row['comment']?></td>
				</tr>
			<?$i++;
			$total += $row['sum'];
			if ($row['delivery'] > $max) $max = $row['delivery'];
			}?>
			<tr>
				<td style="text-align: right" colspan="8">Итого: <?=$total?> руб.</td>
			</tr>
		</table>
		<?
		return ob_get_clean();
	}

?>