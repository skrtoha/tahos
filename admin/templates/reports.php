<?
/** @global Database $db */

use core\Database;
use core\Item;use core\Provider\Tahos;

$page_title = 'Отчеты';
$status = '<a href="/">Главная</a> > Отчеты';
$tab = isset($_GET['tab']) ? 'nomenclature' : $_GET['tab'];
$reports = new Reports($tab, $db);
if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'){
	switch($_POST['tab']){
        case 'logs':
            break;
		case 'nomenclature': 
			switch($_POST['act']){
				case 'clear':
					$reports->nomenclatureClear();
					break;
				case 'hide': $reports->nomenclatureHide(); break;
				default: $items = $reports->getNomenclature(); viewLog($items);
			}
			break;
		case 'brends': 
			switch($_POST['act']){
				case 'clear': $reports->brendsClear(); break;
				default: $items = $reports->getBrends(); viewLog($items);
			}
			break;
		case 'wrongAnalogy':
			if ($_POST['act'] == 'clear'){
				$reports->clearWrongAnalogies();
				break;
			}
			if ($_POST['act'] == 'hideWrongAnalogy'){
				$reports->hideWrongAnalogy();
				break;
			}
			if ($_POST['act'] == 'deleteAnalogy'){
				$reports->deteleAnalogy($_POST);
			}
			$values = $reports->getWrongAnalogies();
			wrongAnalogy($values);
			break;
		case 'request_delete_item':
			$values = $db->query("
				SELECT
					urdi.user_id,
					" . core\User::getUserFullNameForQuery() . " AS name,
					urdi.item_id,
					b.title AS brend,
					i.title_full,
					i.article
				FROM
					#user_request_delete_item urdi
				LEFT JOIN
					#items i ON i.id = urdi.item_id
				LEFT JOIN
					#brends b ON b.id = i.brend_id
				LEFT JOIN
					#users u ON u.id = urdi.user_id
				LEFT JOIN 
					#organizations_types ot ON ot.id=u.organization_type
				WHERE
					urdi.is_processed = 0
			", ''); 
			request_delete_item($values);
			break;
		case 'clear_request_delete_item':
			$db->update('user_request_delete_item', ['is_processed' => 1], "`is_processed`=0");
			break;
		case 'delete_item':
			$db->delete('user_request_delete_item', "`item_id`={$_POST['item_id']} AND `user_id`={$_POST['user_id']}");
			break;
		case 'purchaseability':
			$output = [];
			$from = core\Connection::getTimestamp($_POST['dateFrom']);
			$to = core\Connection::getTimestamp($_POST['dateTo']);
			$res_purchaseability = $db->query("
				SELECT
					ov.item_id,
					i.title_full,
					i.brend_id,
					b.title AS brend,
					i.article,
					COUNT(ov.item_id) AS purchases,
					IF(si.in_stock > 0, si.in_stock, '') AS tahos_in_stock,
					o.created
				FROM
					#orders_values ov
				LEFT JOIN
					#orders o ON o.id = ov.order_id
				LEFT JOIN	
					#items i ON i.id = ov.item_id
				LEFT JOIN
					#brends b ON b.id = i.brend_id
				LEFT JOIN
					#store_items si ON si.item_id = ov.item_id AND si.store_id = {$_POST['store_id']}
				WHERE
					o.created BETWEEN '$from' AND '$to'
				GROUP BY
					ov.item_id
				ORDER BY
					purchases DESC
			", '');
			if (!$res_purchaseability->num_rows) return;
			foreach($res_purchaseability as $value) $output[] = $value;
			echo json_encode($output);
			break;
		case 'searchHistory':
			$output = [];
			$from = core\Connection::getTimestamp($_POST['dateFrom']);
			$to = core\Connection::getTimestamp($_POST['dateTo']);
			$where = "
			    c.created BETWEEN '$from' AND '$to' AND
				c.brend_article IS NOT NULL
			";
			if ($_POST['search']) $where .= " AND c.brend_article LIKE '%{$_POST['search']}%'";
			
			$res_search = $db->query("
				SELECT
				    @temp := REPLACE(c.url, '/article/', '') as temp,
				    REGEXP_REPLACE (c.url, '^/article/.*?-', '') AS article,
					@item_id := REGEXP_REPLACE(@temp, '-.*$', '') AS item_id,
				    c.brend_article,
				   COUNT(c.url) AS cnt
				FROM 
					#connections c
				WHERE
				    $where
				GROUP BY
					c.brend_article
				ORDER BY
					cnt DESC
			", '');
			if (!$res_search->num_rows) return;
			foreach($res_search as $value) $output[] = [
				'brend_article' => $value['brend_article'],
				'item_id' => $value['item_id'],
				'count' => $value['cnt']
			];
			echo json_encode($output);
			break;
		case 'remainsMainStore':
            $selfStores = Tahos::getSelfStores();

            if (!isset($_POST['store_id'])) $store_id = $selfStores[0]['id'];
            else $store_id = $_POST['store_id'];

            $output = [];
			$res = $db->query("
				SELECT DISTINCT 
					i.id,
					i.brend_id,
					b.title AS brend,
					i.article,
					i.article_cat,
					i.title_full,
					si.in_stock,
					si.price
				FROM 
					#store_items si
				LEFT JOIN
					#items i ON i.id = si.item_id
				LEFT JOIN
					#required_remains rr ON rr.item_id = si.item_id
				LEFT JOIN
					#brends b ON b.id = i.brend_id
				WHERE
					si.in_stock < rr.requiredRemain AND
					si.store_id = $store_id
				ORDER BY 
					b.title
			", '');
			if ($res->num_rows){
				foreach($res as $value) $output[] = $value;
			}
			echo json_encode($output);
			break;
	}
	exit();
}?>
<div class="ionTabs" id="tabs_1" data-name="reports">
	<ul class="ionTabs__head">
		<li class="ionTabs__tab" data-target="nomenclature">Номенклатура</li>
		<li class="ionTabs__tab" data-target="brends">Бренды</li>
		<li class="ionTabs__tab" data-target="wrongAnalogy">Неправильный аналог</li>
		<li class="ionTabs__tab" data-target="request_delete_item">Удаление товара</li>
		<li class="ionTabs__tab" data-target="purchaseability">Покупаемость</li>
		<li class="ionTabs__tab" data-target="searchHistory">История поиска</li>
		<li class="ionTabs__tab" data-target="remainsMainStore">Остатки</li>
	</ul>
	<div class="ionTabs__body">
		<div class="ionTabs__item" data-name="nomenclature"></div>
		<div class="ionTabs__item" data-name="brends">	</div>
		<div class="ionTabs__item" data-name="wrongAnalogy"></div>
		<div class="ionTabs__item" data-name="request_delete_item"></div>
		<div class="ionTabs__item" data-name="purchaseability">
			<?
			$dateTo = new DateTime();
			$dateFrom = new DateTime();
			$dateFrom->sub(new DateInterval('P90D'));
            $selfStores = Tahos::getSelfStores();
			?>
            <form action="/admin/?view=reports" class="filter-form">
                <select name="store_id">
                    <?foreach($selfStores as $row){?>
                        <option value="<?=$row['id']?>"><?=$row['title']?></option>
                    <?}?>
                </select>
                <input class="datetimepicker" name="dateFrom" type="text" value="<?=$dateFrom->format('d.m.Y H:i')?>">
                <input class="datetimepicker" name="dateTo" type="text" value="<?=$dateTo->format('d.m.Y H:i')?>">
                <input type="submit" value="Искать">
            </form>
			<table class="t_table">
				<thead>
					<tr class="head">
						<td>Бренд</td>
						<td>Артикул</td>
						<td>Название</td>
						<td>Заказов</td>
						<td>На складе</td>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
		<div class="ionTabs__item" data-name="searchHistory">
			<?
			$dateTo = new DateTime();
			$dateFrom = new DateTime();
			$dateFrom->sub(new DateInterval('P30D'));
			?>
            <form action="/admin/?view=reports" class="filter-form">
                <input class="datetimepicker" name="dateFrom" type="text" value="<?=$dateFrom->format('d.m.Y H:i')?>">
                <input class="datetimepicker" name="dateTo" type="text" value="<?=$dateTo->format('d.m.Y H:i')?>">
                <input type="text" name="search" placeholder="Поиск по бренду или артикулу">
                <input type="submit" value="Искать">
            </form>
			<table class="t_table">
				<thead>
					<tr class="head">
						<td>Артикул</td>
						<td>Количество</td>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
		<div class="ionTabs__item" data-name="remainsMainStore">
            <select name="self_store_id">
                <?foreach(Tahos::getSelfStores() as $row){?>
                    <option value="<?=$row['id']?>"><?=$row['title']?></option>
                <?}?>
            </select>
			<table class="t_table" cellspacing="1">
				<thead>
					<tr class="head">
						<td>Бренд</td>
						<td>Артикул</td>
						<td>Название</td>
						<td>Остаток</td>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
		<div class="ionTabs__preloader"></div>
	</div>
</div>
<?function viewLog($items){?>
	<div id="total" style="margin-top: 10px;">Всего: <?=$items->num_rows?></div>
	<div class="actions" style="">
		<a class="clearLog" href="#" >Очистить</a>
	</div>
	<form method="post">
		<table class="t_table" cellspacing="1">
			<tr class="head">
				<td>Вид</td>
				<td>Таблица</td>
				<td>Источник</td>
				<td></td>
			</tr>
			<?if ($items->num_rows){
				foreach($items as $item){?>
					<tr>
						<td label="Вид"><?=$item['text']?></td>
						<td label="Таблица"><?=$item['type']?></td>
						<td label="Источник"><?=$item['from']?></td>
						<td>
							<?if ($_POST['tab'] == 'nomenclature'){?>
								<?if (!$item['is_processed']){?>
								<input type="checkbox" name="<?=$item['type']?>" value="<?=$item['item_id']?>-<?=$item['item_diff']?>">
								<?}?>
							<?}?>
						</td>
					</tr>
				<?}
				if ($_POST['tab'] == 'nomenclature'){?>
					<tr>
						<td style="text-align: right" colspan="4"><input type="submit" value="Скрыть отмеченные"></td>
					</tr>
				<?}?>
			<?}
			else{?>
				<tr>
					<td colspan="2">Ничего не найдено</td>
				</tr>
			<?}?>
		</table>
	</form>
	<div id="pagination-container"></div>
<?}
function wrongAnalogy($values){?>
	<div id="total" style="margin-top: 10px;">Всего: <?=count($values)?></div>
	<div class="actions" style="">
		<a class="clearLog" href="#" >Очистить</a>
	</div>
	<form method="post">
		<table class="t_table" cellspacing="1">
			<tr class="head">
				<td>Вид</td>
				<td>Пользователь</td>
				<td></td>
			</tr>
			<?if (count($values)){
				foreach($values as $v){?>
					<tr>
						<td><?=$v['text']?></td>
						<td><?=$v['from']?></td>
						<td>
							<a class="hideWrongAnalogy" item_id="<?=$v['param1']?>" item_diff="<?=$v['param2']?>" href="">
								<span title="Скрыть" class="icon-eye-blocked"></span>
							</a>
							<a class="deleteAnalogy" item_id="<?=$v['param1']?>" item_diff="<?=$v['param2']?>" href="">
								<span title="Удалить" class="icon-cross1"></span>
							</a>
						</td>
					</tr>
				<?}
			}
			else{?>
				<tr>
					<td colspan="3">Ничего не найдено</td>
				</tr>
			<?}?>
		</table>
	</form>
	<div id="pagination-container"></div>
<?}
function request_delete_item($values){?>
	<div id="total" style="margin-top: 10px;">Всего: <?=$values->num_rows?></div>
	<div class="actions" style="">
		<a class="clear_request_delete_item" href="/admin/?view=reports&tab=clear_request_delete_item" >Очистить</a>
	</div>
	<form method="post">
		<table class="t_table" cellspacing="1">
			<tr class="head">
				<td>Пользователь</td>
				<td>Бренд</td>
				<td>Артикул</td>
				<td>Наименование</td>
				<td></td>
			</tr>
			<?if ($values->num_rows){
				foreach($values as $v){?>
					<tr item_id="<?=$v['item_id']?>" user_id="<?=$v['user_id']?>">
						<td><a target="_blank" href="/admin/?view=users&act=change&id=<?=$v['user_id']?>"><?=$v['name']?></a></td>
						<td><?=$v['brend']?></td>
						<td><a target="_blank" href="/admin/?view=items&act=item&id=<?=$v['item_id']?>"><?=$v['article']?></a></td>
						<td><?=$v['title_full']?></td>
						<td><span title="Подтвердить удаление" class="icon-cross1"></span></td>
					</tr>
				<?}
			}
			else{?>
				<tr>
					<td colspan="3">Ничего не найдено</td>
				</tr>
			<?}?>
		</table>
	</form>
	<div id="pagination-container"></div>
<?}
