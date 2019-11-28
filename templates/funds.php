<?php
$act = $_GET['act'];
$id = $_GET['id'];
switch ($act) {
	default:
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
	// debug($_POST);
	$search = $_POST['search'] ? $_POST['search'] : $_GET['search'];
	if ($_POST['search_submit']){
		$type_operation = $_POST['type_operation'];
		$search = $_POST['search'];
	} 
	else{
		// debug($_GET);
		$type_operation = $_GET['page'] ? $_GET['type_operation'] : 1;
		$search = $_GET['search'];
	} 
	// echo $type_operation;
	$searchable = true;
	$where = '';
	if ($search){
		$array = explode(' ', $search);
		$where_users = '';
		foreach ($array as $value){
			if ($value) $where_users .= "(`name_1` LIKE '%$value%' OR `name_2` LIKE '%$value%' OR `name_3` LIKE '%$value%') OR ";
		}
		$where_users = substr($where_users, 0, -4);
		$users = $db->select('users', 'id', $where_users);
		if (count($users)){
			foreach ($users as $user) $in[] = $user['id'];
			$where .= "`user_id` IN (".implode(',', $in).") AND ";
		}
		else $searchable = false;
	} 
	if ($type_operation){
		$where .= "`type_operation`=$type_operation AND ";
		// debug($type_operation);
	} 
	else $where .= "`type_operation` NOT IN (3,4) AND ";
	$where = substr($where, 0, -5);
	// debug($where);
	$page_title = 'Поиск по операциям';
	$status = "<a href='/admin'>Главная</a> > ";
	$status .= "<a href='?view=funds'>Финансовые операции</a> > $page_title";
	$page_title = "Финансовые операции";
	$status = "<a href='/admin'>Главная</a> > $page_title";
	$perPage = 30;
	$linkLimit = 10;
	if ($searchable){
		$all = $db->getCount('funds', $where);
		$page = $_GET['page'] ? $_GET['page'] : 1;
		$chank = getChank($all, $perPage, $linkLimit, $page);
		$start = $chank[$page] ? $chank[$page] : 0;
		$funds = $db->select('funds', '*', $where, 'date', false, "$start,$perPage", true);
	}
	else{
		$all = 0;
		$funds = array();
	}?>
	<div id="div_form" class="actions">
		<form method="post" action="?view=funds">
			<input style="width: 264px;" type="text" name="search" value="<?=$search?>" placeholder="Поиск по пользователям">
			<input type="hidden" name="search_submit" value="1">
			<select style="height: 25px" name="type_operation">
				<option value="">...все операции</option>
				<option <?=$type_operation == 1 ? 'selected' : ''?> value="1">Пополнение счета</option>
				<option <?=$type_operation == 2 ? 'selected' : ''?> value="2">Списание средств</option>
			</select>
			<input type="submit" value="Искать">
		</form>
		<div id="total">Всего операций: <?=$all?></div>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Дата</td>
			<td>Тип операции</td>
			<td>Сумма</td>
			<td>Отстаток</td>
			<td>Пользователь</td>
			<td>Комментарий</td>
		</tr>
		<?if (count($funds)){
			foreach($funds as $id => $fund){?>
				<tr class="<?=$fund['is_new'] ? 'is_new' : ''?>">
					<td><?=date('d.m.Y H:i', $fund['date'])?></td>
					<td><?=$operations_types[$fund['type_operation']]?></td>
					<td class="price_format"><?=$fund['sum']?></td>
					<td class="price_format"><?=$fund['remainder']?></td>
					<?$user = $db->select('users', 'name_1,name_2,name_3,bill', '`id`='.$fund['user_id']);
					$fio = $user[0]['name_1'].' '.$user[0]['name_2'].' '.$user[0]['name_3'];?>
					<td><a href="?view=users&id=<?=$fund['user_id']?>&act=change"><?=$fio?></a></td>
					<td><?=stripslashes($fund['comment'])?></td>
				</tr>
			<?}
		}
		else{?>
			<tr><td colspan="6">Движений средст не найдено</td></tr>
		<?}?>
	</table>
	<?$db->update('funds', ['is_new' => 0], '`is_new`=1');
	pagination($chank, $page, ceil($all / $perPage), $href = "?view=funds&search=$search&type_operation=$type_operation&page=");
}?>