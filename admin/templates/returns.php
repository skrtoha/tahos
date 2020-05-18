<?php
use core\Returns;
switch ($_GET['act']){
	case 'form': 
		$is_validated = true;
		$array = explode('-', $_GET['osi']);
		$page_title = "Редактирование";
		$status = "<a href='/'>Главная</a> > ";
		$status .= "<a href='/admin/?view=returns'>Возвраты</a> > $page_title";
		$osi = [
			'order_id' => $array[0],
			'store_id' => $array[1],
			'item_id' => $array[2]
		];
		$db->update('returns', ['is_new' => 0], core\Provider::getWhere($osi));
		$res_return = Returns::get($osi);
		$return = $res_return->fetch_assoc();
		if (!empty($_POST)){
			if ($_POST['quan'] > $return['quan'] || !$_POST['quan']){
				$is_validated = false;
				message('Количество указано неккоректно!', false);
			}
			if ($is_validated){
				$db->update(
					'returns',
					[
						'return_price' => $_POST['return_price'],
						'quan' => $_POST['quan'],
						'status_id' => $_POST['status_id'],
						'comment' => $_POST['comment']
					],
					core\Provider::getWhere($osi)
				);
				if ($_POST['status_id'] == 3){
					core\OrderValue::changeStatus(2, [
						'order_id' => $return['order_id'],
						'store_id' => $return['store_id'],
						'item_id' => $return['item_id'],
						'price' => $_POST['return_price'],
						'quan' => $_POST['quan'],
						'user_id' => $return['user_id'],
					]);
				}
				message('Успешно сохранено!');
				if (isset($_GET['is_stay'])) header("Location: ?view=returns&act=form&osi={$_GET['osi']}");
				else header("Location: ?view=returns");
			} 
		}
		$return = array_merge($return, $_POST);
		form($return, Returns::getStatuses()); 
		break;
	default: 
		if (isset($_GET['dateFrom'])){
			$dateFrom = DateTime::createFromFormat('d.m.Y H:i', $_GET['dateFrom']);
		} 
		else {
			$dateFrom = new DateTime();
			$dateFrom->sub(new DateInterval('P30D'));
		}
		if (isset($_GET['dateTo'])){
			$dateTo = DateTime::createFromFormat('d.m.Y H:i', $_GET['dateTo']);
		} 
		else $dateTo = new DateTime();
		
		$res_returns = Returns::get([
			'dateFrom' => $dateFrom,
			'dateTo' => $dateTo,
			'status_id' => $_GET['status_id'],
			'article' => $_GET['article']
		]);
		$page_title = 'Возвраты';
		views([
			'res_returns' => $res_returns, 
			'statuses' => Returns::getStatuses(),
			'dateFrom' => $dateFrom,
			'dateTo' => $dateTo,
			'status_id' => $_GET['status_id'] ? $_GET['status_id'] : '',
			'article' => $_GET['article'] ? $_GET['article'] : ''
		]);
}
function views(array $params){
	?>
	<form id="filter">
		<input type="hidden" name="view" value="returns">
		<input class="datetimepicker filter" name="dateFrom" type="text" value="<?=$params['dateFrom']->format('d.m.Y H:i')?>">
		<input class="datetimepicker filter" name="dateTo" type="text" value="<?=$params['dateTo']->format('d.m.Y H:i')?>">
		<select name="status_id" class="filter">
			<option value="">...статус</option>
			<?foreach($params['statuses'] as $status){?>
				<option <?=$status['id'] == $params['status_id'] ? 'selected' : ''?>  value="<?=$status['id']?>"><?=$status['title']?></option>
			<?}?>
		</select>
		<input placeholder="Артикул" type="text" class="filter" value="<?=$params['article']?> " name="article" >
	</form>
	<div id="total">Всего: <?=$params['res_returns']->num_rows?></div>
	<div style="clear: both"></div>
	<div class="actions"></div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Пользователь</td>
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Наименование</td>
			<td>Причина</td>
			<td>Статус</td>
			<td>Дата</td>
		</tr>
		<?if ($params['res_returns']->num_rows){
			foreach($params['res_returns'] as $value){?>
				<tr class="<?=$value['is_new'] ? 'is_new' : ''?>" osi="<?=$value['order_id']?>-<?=$value['store_id']?>-<?=$value['item_id']?>">
					<td label="Пользователь"><?=$value['fio']?></td>
					<td label="Бренд"><?=$value['brend']?></td>
					<td label="Артикул"><?=$value['article']?></td>
					<td label="Наименование"><?=$value['title_full']?></td>
					<td label="Причина"><?=$value['reason']?></td>
					<td label="Статус"><?=$value['status']?></td>
					<td label="Дата"><?=$value['created']?></td>
				</tr>
			<?}
		}
		else{?>
			<tr><td colspan="7">Возвратов не найдено</td></tr>
		<?}?>
		</table>
<?}
function form($return, $statuses){
	//debug($return);?>
	<div style="margin-top: 10px" class="t_form">
		<div class="bg">
			<form id="returns" method="post" enctype="multipart/form-data">
				<div class="field">
					<div class="title">Заказ</div>
					<div class="value">
						<a target="_blank" href="/admin/?view=orders&id=<?=$return['order_id']?>&act=change"><?=$return['order_id']?></a>
					</div>
				</div>
				<div class="field">
					<div class="title">Шифр</div>
					<div class="value">
						<a class="store" store_id="<?=$return['store_id']?>"><?=$return['cipher']?></a>		
					</div>
				</div>
				<div class="field">
					<div class="title">Бренд</div>
					<div class="value"><?=$return['brend']?></div>
				</div>
				<div class="field">
					<div class="title">Артикул</div>
					<div class="value"><?=$return['article']?></div>
				</div>
				<div class="field">
					<div class="title">Наименование</div>
					<div class="value"><?=$return['title_full']?></div>
				</div>
				<div class="field">
					<div class="title">Пользователь</div>
					<div class="value">
						<a target="_blank" href="/admin/?view=users&act=change&id=<?=$return['user_id']?>"><?=$return['fio']?></a>
					</div>
				</div>
				<div class="field">
					<div class="title">Причина</div>
					<div class="value"><?=$return['reason']?></div>
				</div>
				<div class="field">
					<div class="title">Цена</div>
					<div class="value">
						<p>В заказе: <b><?=$return['price'] * $return['quan']?></b> руб. по <b><?=$return['price']?></b> руб. за шт.</p>
						<p>С учетом комиссии за 1 шт: 
							<input type="text" name="return_price" value="<?=$return['return_price']?>"> руб.</b>
						</p>
					</div>
				</div>
				<div class="field">
					<div class="title">Количество</div>
					<div class="value">
						<input type="text" name="quan" value="<?=$return['quan']?>">
					</div>
				</div>
				<div class="field">
					<div class="title">Статус</div>
					<div class="value">
						<select name="status_id">
							<?foreach($statuses as $s){?>
								<option <?=$s['id'] == $return['status_id'] ? 'selected' : ''?> value="<?=$s['id']?>"><?=$s['title']?></option>
							<?}?>
						</select>
					</div>
					<div class="field">
					<div class="title">Комментарий</div>
					<div class="value">
						<input type="text" name="comment" value="<?=$return['comment']?>">
					</div>
				</div>
				<div class="value">
					<input type="submit" class="button" value="Сохранить и выйти">
					<input class="is_stay button" type="submit" value="Сохранить и остаться">
				</div>
			</form>
		</div>
	</div>
<?}
?>