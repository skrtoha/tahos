<?if (!$_SESSION['user']) header('Location: /');
$title = "Корзина";
$user_id = $_SESSION['user'];
// debug($basket, 'basket');
// debug($user); exit();
if ($_GET['act'] == 'to_offer'){
	// exit();
	$res_basket = core\Basket::get($user_id, true);
	if (!$basket->num_rows){
		message('Нечего отправлять!', false);
		header('Location: /basket');
		exit();
	}
	$res = $db->insert('orders', [
		'user_id' => $_SESSION['user'],
		'is_draft' => 0
	]);
	if ($res !== true) die ("$res | $db->last_query");
	$order_id = $db->last_id();
	foreach ($res_basket as $value){
		if (!$value['isToOrder']) continue;
		$res = $db->insert(
			'orders_values', 
			[
				'user_id' => $_SESSION['user'],
				'order_id' => $order_id,
				'store_id' => $value['store_id'],
				'item_id' => $value['item_id'],
				'price' => $value['price'],
				'quan' => $value['quan'],
				'comment' => $value['comment']
		 	]
		 	// ,['print_query' => 1]
	 	);
		if ($res !== true) die("$res | $last_query");
		if ($user['isAutomaticOrder']){
			$armtek = new core\Provider\Armtek($db);
			if ($armtek->isKeyzak($value['store_id'])){
				$value['order_id'] = $order_id;
				$armtek->toOrder($value);
			} 
		}
	} 
 	$db->delete('basket', "`user_id`={$_SESSION['user']} AND `isToOrder`=1");
	message('Успешно отправлено в заказы!');
	header('Location: /orders');
}
$res_basket = core\Basket::get($_SESSION['user']);
$noReturnIsExists = false;
?>
<div class="basket">
	<h1>Корзина</h1>
	<table class="basket-table">
		<tr>
			<th></th>
			<th>Бренд</th>
			<th style="text-align: left;padding-left: 20px;">Наименование</th>
			<th>Поставщик</th>
			<th>Срок</th>
			<th class="amount">Кол-во</th>
			<th>Цена</th>
			<th>Сумма</th>
			<th></th>
			<th><img id="basket_clear" src="/img/icons/icon_trash.png" alt="Удалить" title="Очистить корзину"></th>
		</tr>
		<?if (!$res_basket->num_rows){?>
		<tr>
			<td colspan="9">Корзина пуста</td>
		</tr>
		<?}
		else{
			// debug($basket);
			$total_basket = 0;
			$totalToOrder = 0;
			$bl_check = false;
			foreach ($res_basket as $key => $val) {
				$checkbox = '';
				$pp = core\Provider::getPrice([
					'provider_id' => $val['provider_id'],
					'store_id' => $val['store_id'],
					'item_id' => $val['item_id'],
					'article' => $val['article'],
					'brend' => $val['provider_brend'],
					'in_stock' => $val['in_stock'],
					'user_id' => $_SESSION['user'],
				]);
				$basket[$key] = $val;
				$basket[$key]['pp'] = $pp;
				if ($pp){
					if (
						$pp['available'] == -1 ||
						($pp['available'] > 0 && $pp['available'] < $val['quan']) ||
						($pp['price'] > 0 && $pp['price'] > $val['price'])
					){
						$val['isToOrder'] = 0;
						$checkbox = 'disabled';
						$db->update('basket', ['isToOrder' => 0], "`store_id` = {$val['store_id']} AND `item_id` = {$val['item_id']}");
					} 
				}

				if ($val['noReturn']) $noReturnIsExists = true;
				$total_basket += $val['price'] * $val['quan'];
				if ($val['isToOrder']) $totalToOrder += $val['price'] * $val['quan'];
				?>
				<!-- <tr>
					<td colspan="9">
						<?debug($val, 'basket'); debug($pp, 'providerPrice')?>
					</td>
				</tr> -->
				<tr class="good">
					<td class="checkbox">
						<input <?=$val['isToOrder'] == 1 ? 'checked' : ''?> <?=$checkbox?> type="checkbox" name="toOrder" value="<?=$val['store_id']?>-<?=$val['item_id']?>">
					</td>
					<td>
						<b class="brend_info" brend_id="<?=$val['brend_id']?>"><?=$val['brend']?></b> 
						<a class="articul" href="<?=core\Item::getHrefArticle($val['article'])?>"> <?=$val['article']?></a>
					</td>
					<td  style="text-align: left; padding-left: 10px">
						<?if ($user_id){
							if ($val['is_favorite']){?>
								<i item_id="<?=$val['item_id']?>" class="fa fa-star favorites-btn" aria-hidden="true"></i>
							<?}
							else{?>
								<i item_id="<?=$val['item_id']?>" class="fa fa-star-o favorites-btn" aria-hidden="true"></i>
							<?}?>
						<?}?>	
						<span class="title"><?=$val['title']?></span>
					</td>
					<td <?=$val['noReturn']?>><?=$val['cipher']?></td>
					<td class="delivery-time"><?=$val['delivery']?> дн.</td>
					<td>
						<div store_id="<?=$val['store_id']?>" item_id="<?=$val['item_id']?>" packaging="<?=$val['packaging']?>" class="count-block" summand="<?=$val['price']?>">
							<span class="minus">-</span>
							<input value="<?=$val['quan']?>">
							<span class="plus">+</span>
						</div>
						<?if ($pp){?>
							<input type="hidden" name="available" value="<?=$pp['available']?>">
							<?$active = $pp['available'] > 0 && $pp['available'] < $val['quan'] ? 'active' : ''?>
							<span class="available <?=$active?>">В наличии <?=$pp['available']?> шт.</span>
							<?if($pp['available'] == -1){?>
								<span class="not_available">Нет в наличии</span>
							<?}?>
						<?}?>
					</td>
					<td class="price-col">
						<?if ($pp['price'] > 0 && $pp['price'] > $val['price']){?>
							<span class="important" style="margin-bottom: 5px; display: block">Цена изменилась</span>
							<span class="price_format"><?=$val['price']?></span>
							<i class="fa fa-rub" aria-hidden="true"></i>
							 <br> <a class="update-price" href="/ajax/update_basket.php?act=update_price&store_id=<?=$val['store_id']?>&item_id=<?=$val['item_id']?>&price=<?=$pp['price']?>">Обновить цену</a>
						<?}
						else{?>
							<span class="price_format"><?=$val['price']?></span>
							<i class="fa fa-rub" aria-hidden="true"></i>
						<?}?>
					</td>
					<td class="subtotal">
						<span class="price_format"><?=$val['price'] * $val['quan']?></span>
						<i class="fa fa-rub" aria-hidden="true"></i>
					</td>
					<td style="position: relative">
						<i item_id="<?=$val['item_id']?>" store_id="<?=$val['store_id']?>" class="fa fa-pencil-square-o comment-btn" aria-hidden="true"></i>
						<div class="comment-block">
							<textarea class="comment_textarea" placeholder="Напишите Ваш комментарий"><?=$val['comment']?></textarea>
							<label>
								<input type="checkbox" class="to_all_positions">
								Добавить ко всем позициям
							</label>
							<button class="save_comment">Сохранить</button>
							<a href="#" class="cancel_comment">Отменить</a>
						</div>
					</td>
					<td>
						<span act="delete" class="delete-btn" type_view="big">
							<i style="margin: 0" class="fa fa-times" aria-hidden="true"></i>
						</span>
					</td>
				</tr>
			<?}
		}?>
	</table>
	<div class="mobile-view">
		<?if (empty($basket)){?>
		<p>Корзина пуста</p>
		<?}
		else{
			foreach ($basket as $val) {
				$pp = & $val['pp'];
				if ($pp){
					if (
						$pp['available'] == -1 ||
						($pp['available'] > 0 && $pp['available'] < $val['quan']) ||
						($pp['price'] > 0 && $pp['price'] > $val['price'])
					){
						$val['isToOrder'] = 0;
						$checkbox = 'disabled';
						$db->update('basket', ['isToOrder' => 0], "`store_id` = {$val['store_id']} AND `item_id` = {$val['item_id']}");
					} 
				}
				?>
				<div class="good">
					<?if ($pp){?>
						<input type="hidden" name="available" value="<?=$pp['available']?>">
					<?}?>
					<div class="goods-header">
						<p>
							<input view_type="mobile" <?=$val['isToOrder'] == 1 ? 'checked' : ''?> type="checkbox" <?=$checkbox?> name="toOrder" value="<?=$val['store_id']?>-<?=$val['item_id']?>">
							<b class="brend_info" brend_id="<?=$val['brend_id']?>"><?=$val['brend']?></b>  
							<a href="<?=core\Item::getHrefArticle($val['article'])?>" class="articul"><?=$val['article']?></a>
						</p>
						<p class="title"><?=$val['title']?></p>
						<p <?=$val['noReturn']?>> <?=$val['cipher']?> <span class="delivery-time"><?=$val['delivery']?> дн.</span></p>
						<i class="fa fa-pencil-square-o comment-btn" store_id="<?=$val['store_id']?>" aria-hidden="true" item_id="<?=$val['item_id']?>"></i>
						<div class="comment-block">
							<textarea class="comment_textarea" placeholder="Напишите Ваш комментарий"><?=$val['comment']?></textarea>
							<label>
								<input type="checkbox" class="to_all_positions">
								Добавить ко всем позициям
							</label>
							<button class="save_comment">Сохранить</button>
							<a href="#" class="cancel_comment">Отменить</a>
						</div>
					</div>
					<div class="goods-footer">
						<div class="price-block">
							<?if ($val['pp']['price'] > 0 && $val['pp']['price'] > $val['price']){?>
							<span style="text-align: left; display: block;" class="important" style="margin-bottom: 5px; display: block">Цена изменилась</span>
							<span class="price_format"><?=$val['price']?></span>
							<i class="fa fa-rub" aria-hidden="true"></i>
							 <br> <a class="update-price" href="/ajax/update_basket.php?act=update_price&store_id=<?=$val['store_id']?>&item_id=<?=$val['item_id']?>&price=<?=$val['pp']['price']?>">Обновить цену</a>
						<?}
						else{?>
							<span class="price_format"><?=$val['price']?></span>
							<i class="fa fa-rub" aria-hidden="true"></i>
						<?}?>
						</div>
						<div class="subtotal-block">
							<span class="label">Сумма:</span>
							<span class="subtotal">
								<span class="price_format"><?=$val['price'] * $val['quan']?></span>
								<i class="fa fa-rub" aria-hidden="true"></i></span>
						</div>
						<div class="clearfix"></div>
					</div>
					<span view_type="mobile" class="delete-btn">
						<i class="fa fa-times" aria-hidden="true"></i>
					</span>
					<div 
						store_id="<?=$val['store_id']?>" 
						item_id="<?=$val['item_id']?>" 
						packaging="<?=$val['packaging']?>" 
						class="count-block" 
						summand="<?=$val['price']?>">
						<span class="minus">-</span>
						<input type="number" readonly value="<?=$val['quan']?>">
						<span class="plus">+</span>
					</div>
					<?if ($val['quan'] > $val['in_stock'] and $check){?>
							<span style="line-height: 25px; display: block" class="important">В наличии <?=$val['in_stock']?> шт.</span>
							<a href="/ajax/update_basket.php?act=update_quan&provider_id=<?=$val['provider_id']?>&item_id=<?=$val['item_id']?>&quan=<?=$val['in_stock']?>" class="update-quan">Пересчитать</a>	
						<?}?>
					<?if ($user_id){
						if ($val['is_favorite']){?>
							<i item_id="<?=$val['item_id']?>" class="fa fa-star favorites-btn" aria-hidden="true"></i>
						<?}
						else{?>
						<i item_id="<?=$val['item_id']?>" class="fa fa-star-o favorites-btn" aria-hidden="true"></i>
						<?}
					}?>	
				</div>
			<?}?>
		<?}?>
	</div>
	<?if (!empty($basket)){?>
		<p class="total">
			Сумма в заказ:
			<span>
				<span style="padding: 0" id="totalToOrder" class="price_format"><?=$totalToOrder?></span>
				<i class="fa fa-rub" aria-hidden="true"></i>
			</span>
			Итого:
			<span>
				<span style="padding: 0" id="basket_basket" class="price_format"><?=$total_basket?></span>
				<i class="fa fa-rub" aria-hidden="true"></i>
			</span></p>
		<a class="button" style="float: right" href="/basket/to_offer">Оформить заказ</a>
	<?}?>
</div>
<?if ($noReturnIsExists){?>
	<div id="mgn_popup" class="product-popup mfp-hide">
		<h1>Внимание! Следующие товары вернуть будет невозможно!</h1>
		<table class="basket-table">
			<tr>
				<th>Бренд</th>
				<th>Артикул</th>
				<th>Название</th>
			</tr>
		</table>
		<a class="button" style="float: right" href="/basket/to_offer">Оформить заказ</a>
		<a class="button refuse mfp-close" style="float: left" href="#">Отказаться</a>
		<div style="clear: both"></div>
	</div>
<?}?>
