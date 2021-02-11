<?php
use core\Provider;
use core\OrderValue;
//SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));
// require_once('functions/')
$act = $_GET['act'];
$id = $_GET['id'];

$status_id = $_POST['status_id'];
switch ($act) {
	case 'user_orders': user_orders(); break;
	case 'change': show_form('s_change'); break;
	case 'removeFromBasket':
		Provider::removeFromBasket($_GET);
		header("Location: ?view=orders&id={$_GET['order_id']}&act=change");
		break;
	case 'allInWork':
		$res_order_values = get_order_values(['order_id' => $_GET['id']], '');
		while($ov = $res_order_values->fetch_assoc()){
			if (!in_array($ov['status_id'], [5])) continue;
			if (!Provider::getIsEnabledApiOrder($ov['provider_id']) && $ov['api_title']){
				try{
					throw new Exception("API заказов " . Provider::getProviderTitle($ov['provider_id']) . " отключено");
				} catch(Exception $e){
					core\Log::insertThroughException($e, ['additional' => "osi: {$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}"]);
					continue;
				}
			} 
			
			switch($ov['provider_id']){
				case 8: //Микадо
					$mikado = new core\Provider\Mikado($db);
					$mikado->Basket_Add($ov);
					break;
				case 2: //Армтек
					Provider::addToProviderBasket($ov);
					if (isset($_GET['automaticOrder'])) core\Provider\Armtek::sendOrder();
					break;
				case 6: //Восход
					Provider::addToProviderBasket($ov);
					if (isset($_GET['automaticOrder'])) core\Provider\Abcp::sendOrder(6);
					break;
				case 13: //МПартс
					Provider::addToProviderBasket($ov);
					if (isset($_GET['automaticOrder'])) core\Provider\Abcp::sendOrder(13);
					break;
				case 15: //Росско
					Provider::addToProviderBasket($ov);
					if ($ov['store_id'] == 24 || isset($_GET['automaticOrder'])) Provider\Rossko::sendOrder($ov['store_id']);
					break;
				case 17://ForumAuto
					Provider::addToProviderBasket($ov);
					core\Provider\ForumAuto::sendOrder();
					break;
				case 18: //Autoeuro
					core\Provider\Autoeuro::putBusket($ov);
					if (isset($_GET['automaticOrder'])) core\Provider\Autoeuro::sendOrder();
					break;
				case 19://Favorit
					core\Provider\FavoriteParts::addToBasket($ov);
					if (isset($_GET['automaticOrder'])) core\Provider\FavoriteParts::toOrder();
					break;
				case 20://Autokontinent
					core\Provider\Autokontinent::addToBasket($ov);
					if (isset($_GET['automaticOrder'])) core\Provider\Autokontinent::sendOrder();
					break;
				case core\Provider\Autopiter::getParams()->provider_id:
					core\Provider\Autopiter::addToBasket($ov); 
					if (isset($_GET['automaticOrder'])) core\Provider\Autopiter::sendOrder();
					break;
			case core\Provider\Tahos::$provider_id:
					core\OrderValue::changeStatus(11, $ov);
					break;
				default:
					core\OrderValue::changeStatus(7, $ov);
			}
		}

		if (isset($_GET['automaticOrder'])){
			header("Location: /orders");
			exit();
		} 

		// header("Location: /admin/?view=orders&id={$_GET['id']}&act=change");
		break;
	case 'print':
		$order = get_order('');
		$res_order_values = get_order_values(['order_id' => $_GET['id']]);
		order_print($order, $res_order_values);
		break;
	case 'items_status':
		require_once('templates/pagination.php');
		$status_id = $_GET['status_id'];
		$items_status = $db->getFieldOnID('orders_statuses', $_GET['status_id'], 'title');
		$page_title = "Товары со статусом \"$items_status\" ";
		$status = "<a href='/admin'>Главная</a> > <a href='?view=orders'>Заказы</a> > $page_title";
		$perPage = 30;
		$linkLimit = 10;
		$all = $db->getCount('orders_values', "`status_id` = {$_GET['status_id']}");
		$page = $_GET['page'] ? $_GET['page'] : 1;
		$chank = getChank($all, $perPage, $linkLimit, $page);
		$start = $chank[$page] ? $chank[$page] : 0;
		$res_order_values = get_order_values([
			'status_id' => $_GET['status_id'],
			'limit' => "$start, $perPage"
		], '');
		items_status(compact('res_order_values', 'all', 'chank', 'page', 'status_id'));
		break;
	default:
		view();
}

function view(){
	global $status, $db, $page_title, $settings;
	require_once('templates/pagination.php');
	if ($_GET['search'] || $_GET['status']){
		if (is_numeric($_GET['search'])) $where = "WHERE o.id={$_GET['search']}";
		else{
			$where = '';
			$having = "HAVING fio LIKE '%{$_GET['search']}%'";
		} 
		if ($_GET['status']) $having .= " AND status='{$_GET['status']}'";
		$page_title = 'Поиск по номену или ФИО';
		$status = "<a href='/admin'>Главная</a> > <a href='?view=orders'>Заказы</a> > $page_title";
	}
	else{
		$page_title = "Заказы";
		$status = "<a href='/admin'>Главная</a> > $page_title";
	}
	$res_all = $db->query("
		SELECT
			o.id
		FROM
			#orders o
		LEFT JOIN #orders_values ov ON ov.order_id=o.id
		$where
		GROUP BY o.id
		$having
	", '');
	$all = $res_all->num_rows;
	$perPage = 30;
	$linkLimit = 10;
	$page = $_GET['page'] ? $_GET['page'] : 1;
	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ? $chank[$page] : 0;
	$orders = $db->query("
		SELECT
			o.id,
			DATE_FORMAT(o.created, '%d.%m.%Y %H:%i') AS date,
			GROUP_CONCAT(ov.price) AS prices,
			GROUP_CONCAT(ov.quan) AS quans,
			GROUP_CONCAT(ov.ordered) AS ordered,
			GROUP_CONCAT(ov.arrived) AS arrived,
			GROUP_CONCAT(ov.issued) AS issued,
			GROUP_CONCAT(ov.returned) AS returned,
			GROUP_CONCAT(ov.declined) AS declined,
			IF(
				o.is_draft,
				'Черновик',
				getOrderStatus(GROUP_CONCAT(ov.status_id))
			) AS status,
			" . core\User::getUserFullNameForQuery() . " AS fio,
			o.user_id,
			o.is_draft,
			o.is_new
		FROM
			#orders o
		LEFT JOIN #orders_values ov ON ov.order_id=o.id
		LEFT JOIN #users u ON u.id=o.user_id
		LEFT JOIN #organizations_types ot ON ot.id=u.organization_type
		$where
		GROUP BY ov.order_id
		$having
		ORDER BY o.is_new DESC, o.created DESC
		LIMIT $start,$perPage
	", '');
	?>
	<div id="total" style="margin-top: 10px;">Всего: <?=$all?></div>
	<div class="actions">
		<form type="get">
			<input type="hidden" name="view" value="orders">
			<input type="text" name="search" value="<?=$_GET['search']?>" placeholder="Поиск по номеру или ФИО">
			<select name="status">
				<option value="">...выберите статус</option>
				<option <?=$_GET['status'] == 'Черновик' ? 'selected' : ''?> value="Черновик">Черновик</option>
				<option <?=$_GET['status'] == 'В работе' ? 'selected' : ''?> value="В работе">В работе</option>
				<option <?=$_GET['status'] == 'Завершен' ? 'selected' : ''?> value="Завершен">Завершен</option>
				<option <?=$_GET['status'] == 'Ожидает' ? 'selected' : ''?> value="Ожидает">Ожидает</option>
			</select>
			<input type="submit" value="Искать">
		</form>
		<form id="changeStatus" style="position: relative;left: 10px;">
			<input type="hidden" name="view" value="orders">
			<input type="hidden" name="act" value="items_status">
			<select name="status_id">
				<option value="">...статус товара</option>
				<?foreach(OrderValue::getStatuses() as $s){?>
					<option <?=$s['id'] == $params['status_id'] ? 'selected' : ''?>  value="<?=$s['id']?>"><?=$s['title']?></option>
				<?}?>
			</select>
		</form>
		<?$commonItemsToOrders = core\Provider::getCommonItemsToOrders();
		$countItemsToOrder = core\Provider::getCountItemsToOrders($commonItemsToOrders);?>
		<?if ($countItemsToOrder){?>
			<a style="margin-left: 15px;" href="?view=providers&act=itemsToOrder">
				Товары ожидающие отправку в заказ 
				<strong style="color: red">(<?=$countItemsToOrder?>)</strong>
			</a>
		<?}?>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Номер</td>
			<td>Дата заказа</td>
			<td>Статус</td>
			<td>Сумма</td>
			<td>Пользователь</td>
		</tr>
		<?if (!empty($orders)){
			foreach($orders as $order){
				$is_new = $order['is_new'] ? 'is_new' : '';
				?>
				<tr class="orders_box <?=$is_new?> <?=get_status_color($order['status'])?>" href="?view=orders&id=<?=$order['id']?>&act=change">
					<td label="Номер"><?=$order['id']?></td>
					<td label="Дата заказа"><?=$order['date']?></td>
					<td label="Статус"><?=$order['status']?></td>
					<td label="Сумма" class="price_format">
						<?=get_summ([
							'price' => $order['prices'],
							'quan' => $order['quans'],
							'ordered' => $order['ordered'],
							'arrived' => $order['arrived'],
							'issued' => $order['issued'],
							'returned' => $order['returned'],
							'declined' => $order['declined']
						])?>
					</td>
					<td label="Пользователь"><a href="?view=orders&act=user_orders&id=<?=$order['user_id']?>"><?=$order['fio']?></a></td>
				</tr>
			<?}
		}
		else{?>
			<tr><td colspan="5">Заказов не найдено</td></tr>
		<?}?>
	</table>
	<?pagination($chank, $page, ceil($all / $perPage), $href = "?view=orders&status={$_GET['status']}&search={$_GET['search']}&page=");
}
function show_form($act){
	global $status, $db, $page_title;
	$id = $_GET['id'];
	$db->update('orders', array('is_new' => 0), "`id`=$id");
	switch($act){
		case 's_change':
			$order = get_order('');
			$res_order_values = get_order_values(['order_id' => $_GET['id']], '');
			$page_title = "Просмотр заказа";
			break;
		case 's_add':
			$page_title = "Добавление заказа";
			break;
	}
	$status = "<a href='/admin'>Главная</a> > <a href='?view=orders'>Заказы</a> > $page_title";?>
	<h3 style="float: left">Данные о заказе</h3>
	<input type="hidden" name="order_id" value="<?=$_GET['id']?>">
	<div id="order_print_div">
		<a target="_blank" href="/admin/?view=orders&act=print&id=<?=$_GET['id']?>" >Печать</a>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Номер</td>
			<td>Пользователь</td>
			<td>Сумма</td>
			<td>Статус</td>
			<td>Дата заказа</td>
		</tr>
		<tr>
			<td label="Номер"><?=$order['id']?></td>
			<td label="Пользователь">
				<a href="?view=users&act=funds&id=<?=$order['user_id']?>">
					<?=$order['fio']?>
				</a> 
				(<b class="price_format"><?=$order['bill'] - $order['reserved_funds']?></b> руб.)
			</td>
			<td label="Сумма" class="price_format total"><?=get_summ([
				'statuses' => $order['statuses'],
				'price' => $order['prices'],
				'quan' => $order['quans'],
				'ordered' => $order['ordered'],
				'arrived' => $order['arrived'],
				'issued' => $order['issued'],
				'declined' => $order['declined'],
				'returned' => $order['returned']
			])?></td>
			<td label="Статус"><?=$order['is_draft'] ? 'Черновик' : get_status($order['statuses'])?></td>
			<td label="Дата заказа"><?=$order['date']?></td>
		</tr>
	</table>
	<h3 style="margin-top: 10px">Товары в заказе</h3>
	<a class="allInWork" href="/admin/?view=orders&id=<?=$_GET['id']?>&act=allInWork">В работе для всех</a>
	<div style="clear: both"></div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Поставщик</td>
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Наименование</td>
			<td>Цена<br>закупки</td>
			<td>Цена</td>
			<td>Кол-во</td>
			<td>Сумма</td>
			<td>
				Доставка<br>
				Выдача
			</td>
			<td>Комментарий</td>
			<?if (!$order['is_draft']){?>
				<td>Статус</td>
				<td></td>
			<?}?>
		</tr>
		<?if (!$res_order_values->num_rows){?>
			<td colspan="12">Произошла ошибка</td>
		<?}
		else{
			while ($ov = $res_order_values->fetch_assoc()){
				$res_stringLog = core\Provider::getStringLog($ov);
				$selector = "store_id='{$ov['store_id']}' item_id='{$ov['item_id']}'";?>
				<?if (!$order['is_draft']){?>
					<tr <?=$selector?>>
						<td colspan="10">
							<?$v = 0;
							if ($ov['correspond_id']){
								$href = "?view=correspond&id={$ov['correspond_id']}";
								$v = $ov['count'];
							} 
							else $href = "
								?view=correspond
								&user_id={$ov['user_id']}
								&order_id={$_GET['id']}
								&store_id={$ov['store_id']}
								&item_id={$ov['item_id']}
							";?>
							<a href="<?=$href?>">Переписка в товаре (<?=$v?>)</a>
							<?if ($ov['return_ordered']){?>
								<a href="/admin/?view=returns&act=form&osi=<?=$ov['order_id']?>-<?=$ov['store_id']?>-<?=$ov['item_id']?>">Заявка на возврат</a>
							<?}?>
							<?if (in_array($ov['status_id'], [5, 7])){?>
								<a class="editOrderValue" href="#" osi="<?=$ov['order_id']?>-<?=$ov['store_id']?>-<?=$ov['item_id']?>">Редактировать</a>
							<?}?>
						</td>
					</tr>
				<?}?>
				<tr <?=$selector?> class="status_<?=$order['is_draft'] ? '' : $ov['status_id']?>">
					<td class="storeInfo" label="Поставщик">
						<a class="store" store_id="<?=$ov['store_id']?>"><?=$ov['cipher']?></a>
						<?if ($ov['provider_id'] && core\Provider::isInBasket($ov)){
							$builtQuery = http_build_query([
								'provider_id' => $ov['provider_id'],
								'order_id' => $ov['order_id'],
								'store_id' => $ov['store_id'],
								'item_id' => $ov['item_id'],
								'typeOrganization' => $ov['typeOrganization']
							]);
							?>
							<a class="removeFromBasket" href="?view=orders&act=removeFromBasket&<?=$builtQuery?>">
								Удалить из корзины
							</a>
						<?}?>
						<?if ($res_stringLog->num_rows){?>
							<a href="#" class="show_stringLog">Показать лог</a>
							<table class="stringLog">
								<?foreach($res_stringLog as $value){?>
									<tr>
										<td><?=$value['date']?></td>
										<td><?=$value['text']?></td>
									</tr>
								<?}?>
							</table>
						<?}?>
					</td>
					<td label="Бренд"><?=$ov['brend']?></td>
					<td label="Артикул"><a href="/admin/?view=items&act=item&id=<?=$ov['item_id']?>"><?=$ov['article']?></a></td>
					<td label="Наименование"><?=$ov['title_full']?></td>
					<td label="Цена закупки">
						<?=$ov['withoutMarkup']?>
					</td>
					<td label="Цена" class="price_format">
						<?if (!$order['is_draft']){?>
							<?=$ov['price']?>
						<?}
						else{?>
							<input <?=$ov['store_id'] ? 'readonly' : ''?> type="text" name="price" value="<?=$ov['price']?>">
						<?}?>
					</td>
					<td label="Кол-во">
						<?if (!$order['is_draft']){?>
							Заказ - <?=$ov['quan']?> шт.
						<?}
							else{?>
								<input type="text" name="quan" value="<?=$ov['quan']?>">
							<?}?>
						<?switch($ov['status_id']){
							case 1://выдано
								$summ = $ov['price'] * ($ov['issued'] - $ov['returned']);
								if ($ov['issued'] < $ov['arrived']){?>
									<br>Пришло - <?=($ov['arrived'] - $ov['issued'])?> шт.
								<?}
								if ($ov['issued'] && $ov['issued'] < $ov['arrived']){?>
									<br>Выдано - <a href="" class="issued_change"><?=$ov['issued']?></a> шт.
								<?}
								if ($ov['issued'] && $ov['arrived'] < $ov['ordered']){?>
									<br>Выдано - <?=$ov['issued']?> шт.
								<?}
								if ($ov['returned']){?>
									<br>Возврат - <?=$ov['returned']?> шт.
								<?}
								if ($ov['arrived'] < $ov['ordered'] && !$ov['declined']){?>
									<br>Ожидается - <a class="arrived_change" href=""><?=($ov['ordered'] - $ov['arrived'])?></a> шт.
								<?}
								break;
							case 2://возврат
								$summ = 0;
								break;
							case 3://пришло
								//закоментировано, т.к. если пришел не весь товар, то сумма уменьшалась
								// $summ = $ov['price'] * ($ov['arrived'] - $ov['issued']);
								$summ = $ov['price'] * $ov['ordered'];
								if ($ov['issued'] && $ov['issued'] < $ov['arrived']){?>
									<br>Пришло - <?=$ov['arrived'] - $ov['issued']?> шт.
									<br>Выдано - <?=$ov['issued']?> шт.
								<?}
								if ($ov['arrived'] < $ov['ordered'] && !$ov['issued']){?>
									<br>Пришло - <?=$ov['arrived']?> шт.
									<?if ($ov['arrived'] < $ov['ordered'] && !$ov['declined']){?>
										<br>Ожидается - <a class="arrived_change" href=""><?=($ov['ordered'] - $ov['arrived'])?></a> шт.
									<?}
								}
								break;
							case 5://приостановлено
								$summ = $ov['price'] * $ov['quan'];
								break;
							case 7://в работе
								$summ = $ov['quan'] * $ov['price'];
								break;
							case 8: $summ = 0; break;	
							case 11://заказано
								$summ = $ov['price'] * $ov['ordered'];
								if ($ov['ordered'] < $ov['quan']){?>
									<br>Заказано - <?=$ov['ordered']?> шт.
								<?}
								break;
						}
						if (
								($ov['ordered'] && $ov['ordered'] < $ov['quan']) || 
								($ov['arrived'] < $ov['ordered'] && $ov['declined'])
							){
							$declined = !$ov['declined'] ? ($ov['quan'] - $ov['ordered']) : ($ov['quan'] - $ov['arrived']);?>
							<br>Отказ - <?=$declined?> шт.
						<?}?>
					</td>
					<td label="Сумма" class="price_format sum">
						<?if (!$order['is_draft']){?>
							<?=$summ?>
						<?}
						else{?>
							<?=$ov['sum']?>
						<?}?>
					</td>
					
					<td label="Доставка">
						<?if ($ov['status_id'] == 1){
							$res_order_issues_values = $db->query("
								SELECT
									oiv.issue_id,
									DATE_FORMAT(oi.created, '%d.%m.%Y') AS date
								FROM
									#order_issue_values oiv
								LEFT JOIN
									#order_issues oi ON oi.id = oiv.issue_id
								WHERE
									oiv.order_id = {$ov['order_id']} AND oiv.store_id = {$ov['store_id']} AND oiv.item_id = {$ov['item_id']}
							", '');
							$oiv = $res_order_issues_values->fetch_assoc();?>
							<a href="/admin/?view=order_issues&issue_id=<?=$oiv['issue_id']?>"><?=$oiv['date']?></a>
						<?}
						else{?>
							<?=Provider::getDiliveryDate(
								json_decode($ov['workSchedule'], true), 
								json_decode($ov['calendar'], true),
								$ov['delivery']
							)?>
						<?}?>
					</td>
					<td label="Комментарий">
						<?if ($order['is_draft']){?>
							<input type="text" name="comment" value="<?=$ov['comment']?>">
						<?}
						else{?>
							<?=$ov['comment']?>
						<?}?>
					</td>
					<?if (!$order['is_draft']){?>
						<td label="Статус" class="change_status">
							<form method="post">
								<input type="hidden" name="user_id" value="<?=$ov['user_id']?>">
								<input type="hidden" name="order_id" value="<?=$ov['order_id']?>">
								<input type="hidden" name="store_id" value="<?=$ov['store_id']?>">
								<input type="hidden" name="item_id" value="<?=$ov['item_id']?>">

								<input type="hidden" name="quan" value="<?=$ov['quan']?>">
								<input type="hidden" name="ordered" value="<?=$ov['ordered']?>">
								<input type="hidden" name="arrived" value="<?=$ov['arrived']?>">
								<input type="hidden" name="issued" value="<?=$ov['issued']?>">
								<input type="hidden" name="returned" value="<?=$ov['returned']?>">

								<input type="hidden" name="price" value="<?=$ov['price']?>">
								<input type="hidden" name="bill" value="<?=$ov['bill']?>">
								<input type="hidden" name="reserved_funds" value="<?=$ov['reserved_funds']?>">
								<input type="hidden" name="brend" value="<?=$ov['brend']?>">
								<input type="hidden" name="article" value="<?=$ov['article']?>">
								<input type="hidden" name="title" value="<?=$ov['title_full']?>">
								<b><?=$ov['status']?></b>
								<?$no_show = array(9, 6, 8, 10, 12);
								if (!in_array($ov['status_id'], $no_show)){
									$orders_statuses = get_order_statuses($ov['status_id']);?>
									<br>новый статус:
									<select class="change_status" name="status_id">
									<option value="">...выбрать</option>
										<?foreach($orders_statuses as $order_status){
											$selected = $ov['status_id'] == $order_status['id'] ? 'selected' : '';?>
											<option <?=$selected?> value="<?=$order_status['id']?>"><?=$order_status['title']?></option>
										<?}?>
									</select>
								<?}?>
							</form>
						</td>
						<td>
							<?$disabled = $ov['status_id'] == 5 ? '' : 'disabled';?>
								<input <?=$disabled?> type="checkbox" name="return_to_basket">
						</td>
					<?}?>
					<?if ($order['is_draft']){?>
						<td><span class="icon-cancel-circle"></span></td>
					<?}?>
				</tr>
			<?}
		}?>
	</table>
	<div class="actions">
	<?if (!$order['is_draft']){?>
		<button id="to_basket" order_id="<?=$_GET['id']?>">Вернуть отмеченные в корзину</button>
	<?}else{?>
			<button>Отправить в заказ</button>
	<?}?>
	</div>
<?}
function user_orders(){
	global $status, $db, $page_title;
	if (!empty($_POST['income'])) setIncome();
	$id = $_GET['id'];
	$user = $db->select_one('users', ['name_1', 'name_2', 'name_3'], "`id`=$id");
	$status = "
		<a href='/admin'>Главная</a> > 
		<a href='?view=users'>Пользователи</a> >
		<a href='?view=users&act=change&id=$id'>
			{$user['name_1']} {$user['name_2']} {$user['name_3']}
		</a> >
	";
	$where = "o.user_id=$id";
	if (!isset($_GET['income'])){
		$page_title = 'Заказы пользователя';
		$status .= $page_title;
	}
	else{
		$page_title = "Пришло";
		$status .= '<a href="/admin/?view=orders&act=user_orders&id='.$_GET['id'].'">Заказы пользвателя</a> > ';
		$status .= $page_title;
		$where .= " AND ov.status_id=3";
	}
	$query = "
		SELECT
			ps.cipher,
			b.title AS brend,
			i.article,
			i.id AS item_id,
			IF (i.title_full<>'', i.title_full, i.title) AS title_full,
			ov.price,
			ov.quan,
			ov.comment,
			o.id AS order_id,
			DATE_FORMAT(o.created, '%d.%m.%Y %H:%i') as created,
			os.title AS status,
			os.class AS class
		FROM
			#orders_values ov
		LEFT JOIN #orders o ON o.id=ov.order_id
		LEFT JOIN #provider_stores ps ON ps.id=ov.store_id
		LEFT JOIN #items i ON i.id=ov.item_id
		LEFT JOIN #brends b ON i.brend_id=b.id
		LEFT JOIN #orders_statuses os ON os.id=ov.status_id
		WHERE
			$where
		ORDER BY o.created DESC
	";
	$res_orders_values = $db->query($query, '');
	?>
	<input type="hidden" id="user_id" value="<?=$id?>">
	<form action="" method="post">
		<table class="t_table" cellspacing="1">
			<tr class="head">
				<td>Поставщик</td>
				<td>Бренд</td>
				<td>Артикул</td>
				<td>Наименование</td>
				<td>Цена</td>
				<td>Кол-во</td>
				<td>Сумма</td>
				<td>Комментарий</td>
				<td>№ заказа</td>
				<td>Дата</td>
				<td>Статус</td>
				<?if (isset($_GET['income'])){?>
					<td></td>
				<?}?>
			</tr>
			<?if (!$res_orders_values->num_rows){?>
				<td colspan="12">Заказов пользователя не найдено</td>
			<?}
			else{
				while ($v = $res_orders_values->fetch_assoc()){?>
					<tr class="status_<?=$v['class']?>">
						<td label="Поставщик"><?=$v['cipher']?></td>
						<td label="Бренд"><?=$v['brend']?></td>
						<td label="Артикул"><?=$v['article']?></td>
						<td label="Наименование"><?=$v['title_full']?></td>
						<td label="Цена"><span class="price_format"><?=$v['price']?></span></td>
						<td label="Кол-во"><?=$v['quan']?></td>
						<td label="Сумма"><span class="price_format"><?=$v['price'] * $v['quan']?></span></td>
						<td label="Комментарий"><?=$v['comment']?></td>
						<td label="№ заказа"><a href="?view=orders&id=<?=$v['order_id']?>&act=change"><?=$v['order_id']?></a></td>
						<td label="Дата"><?=$v['created']?></td>
						<td label="Статус" class="change_status"><?=$v['status']?></td>
						<?if (isset($_GET['income'])){?>
							<td label=""><input type="checkbox" name="income[]" value="<?=$v['order_id']?>:<?=$v['item_id']?>"></td>
						<?}?>
					</tr>
				<?}
			}?>
			<?if (isset($_GET['income'])){?>
				<tr>
					<td style="text-align: right" colspan="12"><input  type="submit" value="Отправлено"></td>
				</tr>
			<?}?>
		</table>
	</form>
<?}
function items_status(array $params = []){
	extract($params);
	?>
	<form id="changeStatus" style="margin: 0 0 10px 0; float: left">
		<input type="hidden" name="view" value="orders">
		<input type="hidden" name="act" value="items_status">
		<select name="status_id">
			<option value="">...статус товара</option>
			<?foreach(OrderValue::getStatuses() as $s){?>
				<option <?=$s['id'] == $params['status_id'] ? 'selected' : ''?>  value="<?=$s['id']?>"><?=$s['title']?></option>
			<?}?>
		</select>
	</form>
	<div id="total">Всего: <?=$all?></div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Поставщик</td>
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Наименование</td>
			<td>Цена</td>
			<td>Кол-во</td>
			<td>Сумма</td>
			<td>Комментарий</td>
			<?if (!$order['is_draft']){?>
				<td>Статус</td>
				<td></td>
			<?}?>
		</tr>
		<?if (!$res_order_values->num_rows){?>
			<td colspan="12">Ничего не найдено</td>
		<?}
		else{
			while ($ov = $res_order_values->fetch_assoc()){
				$res_stringLog = core\Provider::getStringLog($ov);
				$selector = "store_id='{$ov['store_id']}' item_id='{$ov['item_id']}'";?>
				<?if (!$order['is_draft']){?>
					<tr <?=$selector?>>
						<td colspan="10">
							<?$v = 0;
							if ($ov['correspond_id']){
								$href = "?view=correspond&id={$ov['correspond_id']}";
								$v = $ov['count'];
							} 
							else $href = "
								?view=correspond
								&user_id={$ov['user_id']}
								&order_id={$_GET['id']}
								&store_id={$ov['store_id']}
								&item_id={$ov['item_id']}
							";?>
							<a href="<?=$href?>">Переписка в товаре (<?=$v?>)</a>
							<?if ($ov['return_ordered']){?>
								<a href="/admin/?view=returns&act=form&osi=<?=$ov['order_id']?>-<?=$ov['store_id']?>-<?=$ov['item_id']?>">Заявка на возврат</a>
							<?}?>
						</td>
					</tr>
				<?}?>
				<tr <?=$selector?> class="status_<?=$order['is_draft'] ? '' : $ov['status_id']?>">
					<td label="Поставщик">
						<a class="store" store_id="<?=$ov['store_id']?>"><?=$ov['cipher']?></a>
						<?if ($ov['provider_id'] && core\Provider::isInBasket($ov)){?>
							<a class="removeFromBasket" href="?view=orders&act=removeFromBasket&<?=http_build_query($ov)?>">
								Удалить из корзины
							</a>
						<?}?>
						<?if ($res_stringLog->num_rows){?>
							<a href="#" class="show_stringLog">Показать лог</a>
							<table class="stringLog">
								<?foreach($res_stringLog as $value){?>
									<tr>
										<td><?=$value['date']?></td>
										<td><?=$value['text']?></td>
									</tr>
								<?}?>
							</table>
						<?}?>
					</td>
					<td label="Бренд"><?=$ov['brend']?></td>
					<td label="Артикул"><a href="/admin/?view=items&act=item&id=<?=$ov['item_id']?>"><?=$ov['article']?></a></td>
					<td label="Наименование"><?=$ov['title_full']?></td>
					<td label="Цена" class="price_format">
						<?if (!$order['is_draft']){?>
							<?=$ov['price']?>
						<?}
						else{?>
							<input <?=$ov['store_id'] ? 'readonly' : ''?> type="text" name="price" value="<?=$ov['price']?>">
						<?}?>
					</td>
					<td label="Кол-во">
						<?if (!$order['is_draft']){?>
							Заказ - <?=$ov['quan']?> шт.
						<?}
							else{?>
								<input type="text" name="quan" value="<?=$ov['quan']?>">
							<?}?>
						<?switch($ov['status_id']){
							case 1://выдано
								$summ = $ov['price'] * ($ov['issued'] - $ov['returned']);
								if ($ov['issued'] < $ov['arrived']){?>
									<br>Пришло - <?=($ov['arrived'] - $ov['issued'])?> шт.
								<?}
								if ($ov['issued'] && $ov['issued'] < $ov['arrived']){?>
									<br>Выдано - <a href="" class="issued_change"><?=$ov['issued']?></a> шт.
								<?}
								if ($ov['issued'] && $ov['arrived'] < $ov['ordered']){?>
									<br>Выдано - <?=$ov['issued']?> шт.
								<?}
								if ($ov['returned']){?>
									<br>Возврат - <?=$ov['returned']?> шт.
								<?}
								if ($ov['arrived'] < $ov['ordered'] && !$ov['declined']){?>
									<br>Ожидается - <a class="arrived_change" href=""><?=($ov['ordered'] - $ov['arrived'])?></a> шт.
								<?}
								break;
							case 2://возврат
								$summ = 0;
								break;
							case 3://пришло
								//закоментировано, т.к. если пришел не весь товар, то сумма уменьшалась
								// $summ = $ov['price'] * ($ov['arrived'] - $ov['issued']);
								$summ = $ov['price'] * $ov['ordered'];
								if ($ov['issued'] && $ov['issued'] < $ov['arrived']){?>
									<br>Пришло - <?=$ov['arrived'] - $ov['issued']?> шт.
									<br>Выдано - <?=$ov['issued']?> шт.
								<?}
								if ($ov['arrived'] < $ov['ordered'] && !$ov['issued']){?>
									<br>Пришло - <?=$ov['arrived']?> шт.
									<?if ($ov['arrived'] < $ov['ordered'] && !$ov['declined']){?>
										<br>Ожидается - <a class="arrived_change" href=""><?=($ov['ordered'] - $ov['arrived'])?></a> шт.
									<?}
								}
								break;
							case 5://приостановлено
								$summ = $ov['price'] * $ov['quan'];
								break;
							case 7://в работе
								$summ = $ov['quan'] * $ov['price'];
								break;
							case 8: $summ = 0; break;	
							case 11://заказано
								$summ = $ov['price'] * $ov['ordered'];
								if ($ov['ordered'] < $ov['quan']){?>
									<br>Заказано - <?=$ov['ordered']?> шт.
								<?}
								break;
						}
						if (
								($ov['ordered'] && $ov['ordered'] < $ov['quan']) || 
								($ov['arrived'] < $ov['ordered'] && $ov['declined'])
							){
							$declined = !$ov['declined'] ? ($ov['quan'] - $ov['ordered']) : ($ov['quan'] - $ov['arrived']);?>
							<br>Отказ - <?=$declined?> шт.
						<?}?>
					</td>
					<td label="Сумма" class="price_format sum">
						<?if (!$order['is_draft']){?>
							<?=$summ?>
						<?}
						else{?>
							<?=$ov['sum']?>
						<?}?>
					</td>
					<td label="Комментарий">
						<?if ($order['is_draft']){?>
							<input type="text" name="comment" value="<?=$ov['comment']?>">
						<?}
						else{?>
							<?=$ov['comment']?>
						<?}?>
					</td>
					<?if (!$order['is_draft']){?>
						<td label="Статус" class="change_status">
							<form method="post">
								<input type="hidden" name="user_id" value="<?=$ov['user_id']?>">
								<input type="hidden" name="order_id" value="<?=$ov['order_id']?>">
								<input type="hidden" name="store_id" value="<?=$ov['store_id']?>">
								<input type="hidden" name="item_id" value="<?=$ov['item_id']?>">

								<input type="hidden" name="quan" value="<?=$ov['quan']?>">
								<input type="hidden" name="ordered" value="<?=$ov['ordered']?>">
								<input type="hidden" name="arrived" value="<?=$ov['arrived']?>">
								<input type="hidden" name="issued" value="<?=$ov['issued']?>">
								<input type="hidden" name="returned" value="<?=$ov['returned']?>">

								<input type="hidden" name="price" value="<?=$ov['price']?>">
								<input type="hidden" name="bill" value="<?=$ov['bill']?>">
								<input type="hidden" name="reserved_funds" value="<?=$ov['reserved_funds']?>">
								<input type="hidden" name="brend" value="<?=$ov['brend']?>">
								<input type="hidden" name="article" value="<?=$ov['article']?>">
								<input type="hidden" name="title" value="<?=$ov['title_full']?>">
								<b><?=$ov['status']?></b>
								<?$no_show = array(9, 6, 8, 10);
								if (!in_array($ov['status_id'], $no_show)){
									$orders_statuses = get_order_statuses($ov['status_id']);?>
									<select class="change_status" name="status_id">
									<option value="">...новый статус</option>
										<?foreach($orders_statuses as $order_status){
											$selected = $ov['status_id'] == $order_status['id'] ? 'selected' : '';?>
											<option <?=$selected?> value="<?=$order_status['id']?>"><?=$order_status['title']?></option>
										<?}?>
									</select>
								<?}?>
							</form>
							<a href="/admin/?view=orders&id=<?=$ov['order_id']?>&act=change">В заказ</a>
						</td>
						<td>
							<?$disabled = $ov['status_id'] == 5 ? '' : 'disabled';?>
								<input <?=$disabled?> type="checkbox" name="return_to_basket">
						</td>
					<?}?>
					<?if ($order['is_draft']){?>
						<td><span class="icon-cancel-circle"></span></td>
					<?}?>
				</tr>
			<?}
		}?>
	</table>
	<?pagination($chank, $page, ceil($all / $perPage), $href = "?view=orders&act=items_status&status_id=$status_id&page=");?>
<?}
?>
