<?php
$page_title = 'Главная';
?>
<div id="index">
	<h2>Статистика продаж</h2>
	<div class="index" id="order_funds">
		<?$dateTo = new DateTime();
		$dateTo = $dateTo->format('d.m.Y H:i');
		$dateFrom = new DateTime();
		$dateFrom = $dateFrom->format('01.m.Y 00:00');
		$res_users = $db->query("
			SELECT
				u.id,
				IF(
					u.organization_name <> '',
					CONCAT_WS (' ', u.organization_name, ot.title),
					CONCAT_WS (' ', u.name_1, u.name_2, u.name_3)
				) AS name
			FROM 
				#users u
			LEFT JOIN 
				#organizations_types ot ON ot.id=u.organization_type
			ORDER BY
				name
		", '')?>
		<div class="actions">
			<form class="filters">
				<input class="datetimepicker" name="dateFrom" type="text" value="<?=$dateFrom?>">
				 - 
				<input class="datetimepicker" name="dateTo" type="text" value="<?=$dateTo?>">
				<select class="filter" data-placeholder="пользователь..." name="user_id">
					<option value=""></option>
					<?foreach($res_users as $user){?>
						<option value="<?=$user['id']?>"><?=$user['name']?></option>
					<?}?>
				</select>
			</form>
		</div>
		<table class="t_table" cellspacing="1">
			<thead>
				<tr class="head">
					<th>Общая сумма</th>
					<th>Закупка</th>
					<th>Прибыль</th>
					<th>Процент</th>
				</tr>
			</thead>
			<tbody>
				<?=core\Index::getHtmlOrderFunds($dateFrom, $dateTo, null)?>
			</tbody>
		</table>
	</div>
</div>