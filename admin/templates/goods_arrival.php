<?php
use core\Managers;
use core\GoodsArrival;

// debug($_POST); 

switch($_GET['act']){	
	case 'detail_view':
		$page_title = 'Просмотр';
		$status = "<a href=\"/\">Главная</a> > <a href=\"/admin/?view=goods_arrival\">Прибытие товаров</a> > $page_title";
		$res_goods_arrival = GoodsArrival::get([
			'where' => "gai.arrival_id = {$_GET['arrival_id']}"
		], '');
		if (!empty($_POST)){
			$db->delete('goods_arrival_items', "`arrival_id` = {$_GET['arrival_id']}");
			$db->update(
				'goods_arrival', 
				[
					'provider_id' => $_POST['provider_id'],
					'store_id' => $_POST['store_id']
				],
				"`id` = {$_GET['arrival_id']}"
			);
			foreach($_POST['items'] as $item_id => $item){
				$db->delete('store_items', "`store_id` = {$_POST['store_id']} AND `item_id` = $item_id");
				GoodsArrival::insertItem([
					'arrival_id' => $_GET['arrival_id'],
					'item_id' => $item_id,
					'in_stock' => $item['in_stock'],
					'price' => $item['price'],
					'packaging' => $item['packaging']
				]);
			}
			message('Успешно сохранено!');
			header("Location: /admin/?view=goods_arrival&arrival_id={$_GET['arrival_id']}&act=detail_view");
		}
		$arrivalInfo = array();
		foreach($res_goods_arrival as $row){
			$arrivalInfo['provider_id'] = $row['provider_id'];
			$arrivalInfo['store_id'] = $row['store_id'];
			$arrivalInfo['store'] = $row['store'];
			$arrivalInfo['items'][$row['item_id']]['brend'] = $row['brend'];
			$arrivalInfo['items'][$row['item_id']]['article'] = $row['article'];
			$arrivalInfo['items'][$row['item_id']]['title_full'] = $row['title_full'];
			$arrivalInfo['items'][$row['item_id']]['price'] = $row['price'];
			$arrivalInfo['items'][$row['item_id']]['in_stock'] = $row['in_stock'];
			$arrivalInfo['items'][$row['item_id']]['packaging'] = $row['packaging'];
		}
		edit($arrivalInfo);
		break;
	case 'create': 
		$page_title = 'Создание';
		$status = "<a href=\"/\">Главная</a> > <a href=\"/admin/?view=goods_arrival\">Прибытие товаров</a> > $page_title";
		if (!empty($_POST)){
			$res_goods_arrival = $db->insert('goods_arrival', [
				'provider_id' => $_POST['provider_id'],
				'store_id' => $_POST['store_id']
			]);
			$arrival_id = $db->last_id();
			foreach($_POST['items'] as $item_id => $value){
				GoodsArrival::insertItem([
					'arrival_id' => $arrival_id,
					'item_id' => $item_id,
					'in_stock' => $value['in_stock'],
					'price' => $value['price'],
					'packaging' => $value['packaging']
				]);
			}
		}
		edit();
		break;
	default: 
		require_once($_SERVER['DOCUMENT_ROOT'] . '/admin/templates/pagination.php');
		$page_title = 'Прибытие товаров';
		$status = "<a href=\"/\">Главная</a> > $page_title";
		$res_all = GoodsArrival::get(['groupBy' => 'ga.id'], '');
		$all = $res_all->num_rows;
		if (!$all){
			commonList();
			break;
		}
		$perPage = 30;
		$linkLimit = 10;
		$page = $_GET['page'] ? $_GET['page'] : 1;
		$chank = getChank($all, $perPage, $linkLimit, $page);
		$start = $chank[$page] ? $chank[$page] : 0;
		commonList(GoodsArrival::get(['limit' => "$start, $perPage", 'groupBy' => 'ga.id'], ''));
}

function commonList($res_goods_arrival = object){
	?>
	<div id="total" style="margin-top: 10px;">Всего: <?=$res_goods_arrival->num_rows?></div>
	<div class="actions">
		<a href="/admin/?view=goods_arrival&act=create">Создать</a>
	</div>
	<table id="commonList" class="t_table" cellspacing="1">
		<thead>
			<tr class="head">
			<th>id</th>
			<th>Поставщик</th>
			<th>Склад</th>
			<th>Дата</th>
		</tr>
		</thead>
		<tbody>
			<?if ($res_goods_arrival->num_rows){
				foreach($res_goods_arrival as $ga){?>
					<tr href="?view=goods_arrival&arrival_id=<?=$ga['id']?>&act=detail_view">
						<td><?=$ga['id']?></td>
						<td><?=$ga['provider']?></td>
						<td><?=$ga['store']?></td>
						<td><?=$ga['created']?></td>
					</tr>
				<?}?>
				<?pagination($chank, $page, ceil($all / $perPage), $href = "?view=goods_arrival&page=");?>
			<?}
			else{?>
				<tr><td colspan="4">Прибытий товаров не найдено</td></tr>
			<?}?>
		</tbody>
	</table>
<?}
function edit($arrivalInfo = array()){
	?>
	<div class="t_form">
		<div class="bg">
			<form action="" method="post" enctype="multipart/form-data">
				<div class="field">
					<div class="title">Поставщик</div>
					<div class="value">
						<?$providers = $GLOBALS['db']->select('providers', ['id', 'title'], '', 'title', true);?>
						<select name="provider_id">
							<?foreach($providers as $provider){
								$selected = $provider['id'] == $arrivalInfo['provider_id'] ? 'selected' : ''?>
								<option <?=$selected?>  value="<?=$provider['id']?>"><?=$provider['title']?></option>
							<?}?>
						</select>
					</div>
				</div>
				<div class="field">
					<div class="title">Склад</div>
					<div class="value" id="store">
						<?if (isset($arrivalInfo['store_id'])){?>
							<div class="store_id">
								<input type="hidden" name="store_id" readonly value="<?=$arrivalInfo['store_id']?>">
								<span><?=$arrivalInfo['store']?></span>
								<span class="icon-cross1"></span>
							</div>
						<?}?>
						<input type="text" class="intuitive_search" placeholder="Шифр или название склада">
					</div>
				</div>
				<div class="field">
					<div class="title">Товары</div>
					<div class="value" id="goods">
						<input class="intuitive_search" style="width: 264px;" type="text" placeholder="Поиск для добавления" required>
						<table id="added_goods" style="<?=!empty($arrivalInfo['items']) ? 'display: block' : ''?>"> 
							<thead>
								<th>Наименование</th>
								<th>Количество</th>
								<th>Цена</th>
								<th>Сумма</th>
								<th>Упаковка</th>
								<th></th>
							</thead>
							<tbody>
								<?if (!empty($arrivalInfo['items'])){
									foreach($arrivalInfo['items'] as $item_id => $item){?>
										<tr class="item_id">
											<td>
												<input type="hidden" name="items[<?=$item_id?>]" readonly value="<?=$item_id?>">
												<span><?=$item['brend']?> - <?=$item['article']?> - <?=$item['title_full']?></span>
											</td>
											<td>
												<input type="text" name="items[<?=$item_id?>][in_stock]" value="<?=$item['in_stock']?>">
											</td>
											<td>
												<input type="text" value="<?=$item['price']?>" name="items[<?=$item_id?>][price]">
											</td>
											<td class="summ"><?=$item['price'] * $item['in_stock']?></td>
											<td>
												<input type="text" name="items[<?=$item_id?>][packaging]" value="<?=$item['packaging']?>">
											</td>
											<td><span class="icon-cross1"></span></td>
										</tr>
									<?}
								}?>
							</tbody>
						</table>
					</div>
				</div>
				<input type="submit" value="Сохранить">
			</form>
		</div>
	</div>
<?}?>

