<?php
$page_title = "Соединения";
$status = '<a href="">Главная</a> > Соединения';

if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'){
	switch($_GET['tab']){
		case 'common_list':
			$res_common_list = core\Connection::getCommonList($_GET['pageSize'], $_GET['pageNumber'], $_GET);
			if (!$res_common_list->num_rows) break;
			$common_list = [];
			foreach($res_common_list as $value) $common_list[] = $value;
			echo json_encode($common_list);
			break;
		case 'getCommonListTotalNumber':
			echo core\Connection::getCommonList(null, null, array_merge($_GET, ['getCount' => true]));
			break;
		case 'denied_addresses':
			echo json_encode($db->select('denied_addresses', '*', '', 'ip', true));
			break;
		case 'add_denied_address':
			$res = $db->insert('denied_addresses', ['ip' => $_GET['text']]);
			if ($res === true) echo "ok";
			else echo $res;
			break;
		case 'remove_denied_address':
			$db->delete('denied_addresses', "`ip` = '{$_GET['text']}'");
			break;
		case 'remove_forbidden_page':
			$db->delete('forbidden_pages', "`page` = '{$_GET['page']}'");
			break;
		case 'add_forbidden_page':
			$res = $db->insert('forbidden_pages', ['page' => $_GET['page']]);
			if ($res === true) echo "ok";
			else echo $res;
			break;
		case 'forbidden_pages':
			echo json_encode($db->select('forbidden_pages', '*', '', 'page', true));
			break;
		case 'statistics':
			echo json_encode(core\Connection::getStatistics($_GET['dateFrom'], $_GET['dateTo'], [
				'pageSize' => $_GET['pageSize'],
				'pageNumber' => $_GET['pageNumber']
			]));
			break;
		case 'getStatisticsTotalNumber':
			echo core\Connection::getStatistics($_GET['dateFrom'], $_GET['dateTo'], ['getCount' => true]);
			break;
		case 'getDetailedInformationAboutIP':
			$commonList = core\Connection::getCommonList(null, null, [
				'ip' => $_GET['ip'],
				'dateFrom' => $_GET['dateFrom'],
				'dateTo' => $_GET['dateTo']
			]);
			if (!$commonList->num_rows) return false;
			$output = [];
			foreach($commonList as $row) $output[] = [
				'url' => $row['url'],
				'created' => $row['created']
			];
			echo json_encode($output);
			break;
	}
	exit();
}?>
<div class="ionTabs" id="tabs_1" data-name="reports">
	<ul class="ionTabs__head">
		<li class="ionTabs__tab" data-target="statistics">Статистика</li>
		<li class="ionTabs__tab" data-target="common_list">Общий список</li>
		<li class="ionTabs__tab" data-target="denied_addresses">Запрещенные ip</li>
		<li class="ionTabs__tab" data-target="forbidden_pages">Запрещенные страницы</li>
	</ul>
	<div class="ionTabs__body">
		<div class="ionTabs__item" data-name="statistics">
			<?
			$dateTo = new DateTime();
			$dateFrom = new DateTime();
			$dateFrom->sub(new DateInterval('P1D'));
			?>
			<input class="datetimepicker" name="dateFrom" type="text" value="<?=$dateFrom->format('d.m.Y H:i')?>">
			<input class="datetimepicker" name="dateTo" type="text" value="<?=$dateTo->format('d.m.Y H:i')?>">
			<div class="actions"></div>
			<span class="total">
				Всего: <input type="text" readonly name="totalNumber" value="">
			</span>
			<table id="statistics" class="t_table" cellspacing="1">
				<thead class="head">
					<tr class="head">
						<th>IP</th>
						<th>Количество</th>
						<th>Комментарий</th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
			<div class="pagination-container"></div>
		</div>
		<div class="ionTabs__item" data-name="common_list">
				<?
			$dateTo = new DateTime();
			$dateFrom = new DateTime();
			$dateFrom->sub(new DateInterval('P10D'));
			?>
			<input class="datetimepicker" name="dateFrom" type="text" value="<?=$dateFrom->format('d.m.Y H:i')?>">
			<input class="datetimepicker" name="dateTo" type="text" value="<?=$dateTo->format('d.m.Y H:i')?>">
			<div class="actions">
				<label>
					<input class="filter" type="checkbox" value="1" name="isHiddenAdminPages" checked="checked">
					Скрыть администратора
				</label>
				<label style="margin-left: 10px">Менеджер</label>
				<select name="manager_id" class="filter">
					<option value="">...выбрать менеджера</option>
					<?$managers = core\Managers::get()?>
					<?foreach($managers as $m){?>
						<option value="<?=$m['id']?>"><?=$m['login']?></option>
					<?}?>
				</select>
			</div>
			<span class="total">
				Всего: <input type="text" readonly name="totalNumber" value="">
			</span>
			<table id="common_list" class="t_table" cellspacing="1">
				<thead class="head">
					<tr class="head">
						<th>IP</th>
						<th>Страница</th>
						<th>Пользователь/Менеджер</th>
						<th>Комментарий</th>
						<th>Заблокировано</th>
						<th>Дата</th>
					</tr>
					<tr>
						<td label="IP"><input class="filter" type="text" name="ip"></td>
						<td label="Страница"><input class="filter" type="text" name="url"></td>
						<td label="Пользователь">
							<?$res_users = $db->query("
								SELECT
									u.id,
									" . core\User::getUserFullNameForQuery() . " AS name
								FROM 
									#users u
								LEFT JOIN 
									#organizations_types ot ON ot.id=u.organization_type
								ORDER BY
									name
							", '')?>
							<select class="filter" data-placeholder="выберите..." name="user_id">
								<option value=""></option>
								<?foreach($res_users as $user){?>
									<option value="<?=$user['id']?>"><?=$user['name']?></option>
								<?}?>
							</select>
						</td>
						<td label="Комментарий"><input class="filter" type="text" name="comment"></td>
						<td label="Заблокировано">
							<select class="filter" name="isDeniedAccess">
								<option></option>
								<option value="1">Да</option>
								<option value="0">Нет</option>
							</select>
						</td>
						<td></td>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
			<div class="pagination-container"></div>
		</div>
		<div class="ionTabs__item" data-name="denied_addresses">
			<a href="#" id="add_denied_address">Добавить</a>
		</div>
		<div class="ionTabs__item" data-name="forbidden_pages">
			<a href="#" id="add_forbidden_page">Добавить</a>
		</div>
		<div class="ionTabs__preloader"></div>
	</div>
</div>
