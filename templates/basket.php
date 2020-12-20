<?if (!$_SESSION['user']) header('Location: /');
$title = "Корзина";
$user_id = $_SESSION['user'];
if ($_GET['act'] == 'to_offer'){
	$res_basket = core\Basket::get($user_id, true);

	if (!$res_basket->num_rows){
		message('Нечего отправлять!', false);
		header('Location: /basket');
		exit();
	}

	//проверяем превышение лимита
	$available = $user['bill'] - $user['reserved_funds'];
	$totalAmountBasket = 0;
	if ($available < 0 && abs($available) > $user['credit_limit']){
		message('Превышен кредитный лимит!', false);
		header("Location: /basket");
		exit();
	}

	$res = $db->insert('orders', [
		'user_id' => $_SESSION['user'],
		'is_draft' => 0
	]);

	//закоментирована эта строка, т.к. очень часто появлялся пустой заказ
	// if ($res !== true) die ("$res | $db->last_query");

	$order_id = $db->last_id();

	$body = "<h1>Заказанные товары:</h1><table style='width: 100%;border-collapse: collapse;''>";

	foreach ($res_basket as $value){
		if (!$value['isToOrder']) continue;
		$body .= "
			<tr>
				<td style='border: 1px solid black'>{$value['brend']}</td>
				<td style='border: 1px solid black'>{$value['article']}</td>
				<td style='border: 1px solid black'>{$value['title']}</td>
				<td style='border: 1px solid black'>{$value['cipher']}</td>
				<td style='border: 1px solid black'>{$value['price']}</td>
				<td style='border: 1px solid black'>{$value['quan']}</td>
				<td style='border: 1px solid black'>{$value['comment']}</td>
			</tr>
		";
		
		$query = core\StoreItem::getQueryStoreItem();
		$query .= "
			WHERE si.store_id = {$value['store_id']} AND si.item_id = {$value['item_id']}
		";
		$result = $db->query($query, '');
		$storeItemInfo = $result->fetch_assoc();

		$res = $db->insert(
			'orders_values', 
			[
				'user_id' => $_SESSION['user'],
				'order_id' => $order_id,
				'store_id' => $value['store_id'],
				'item_id' => $value['item_id'],
				'withoutMarkup' => $storeItemInfo['priceWithoutMarkup'],
				'price' => $value['price'],
				'quan' => $value['quan'],
				'comment' => $value['comment']
		 	]
	 	);

	 	//закоментирована эта строка, т.к. очень часто появлялся пустой заказ
		// if ($res !== true) die("$res | $last_query");
		
	} 
	$body .= "</table>";

 	$db->delete('basket', "`user_id`={$_SESSION['user']} AND `isToOrder`=1");

	core\Mailer::send([
		'emails' => ['info@tahos.ru', 'skrtoha@gmail.com'],
		'subject' => 'Новый заказ на tahos.ru',
		'body' => $body
	]);
	
 	if ($user['isAutomaticOrder'] && core\User::noOverdue($user_id)){
		header("Location: /admin/?view=orders&id=$order_id&act=allInWork&automaticOrder=1");
		exit();
	}

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
			$basketResult = [];
			foreach ($res_basket as $key => $val) {
				$checkbox = '';
				$val['pp'] = core\Provider::getPrice([
					'provider_id' => $val['provider_id'],
					'store_id' => $val['store_id'],
					'item_id' => $val['item_id'],
					'price' => $val['price'],
					'article' => $val['article'],
					'brend' => $val['provider_brend'],
					'in_stock' => $val['in_stock'],
					'user_id' => $_SESSION['user'],
				]);
				if ($val['pp']){
					if (
						$val['pp']['available'] == -1 ||
						($val['pp']['available'] > 0 && $val['pp']['available'] < $val['quan']) ||
						($val['pp']['price'] > 0 && $val['pp']['price'] > $val['price'])
					){
						$val['isToOrder'] = 0;
						$checkbox = 'disabled';
						$db->update('basket', ['isToOrder' => 0], "`store_id` = {$val['store_id']} AND `item_id` = {$val['item_id']}");
					}

					//обновление прайса в случае изменения количества 
					if (
						$val['pp']['available'] == -1 ||
						($val['pp']['available'] > 0 && $val['pp']['available'] < $val['quan'])
					){
						$res = $db->update('store_items', ['quan' => $val['pp']['available']], "`store_id` = {$val['store_id']} AND `item_id` = {$val['item_id']}");
						//после тестирования удалить
						core\Log::insert([
							'url' => 'Обновление в прайсах после срабатывания api',
							'text' => json_encode($val),
							'additional' => json_encode($val['pp'])
						]);
					}
				}

				//если товар был в корзине, но в прайсе его больше нет
				if (!$val['provider_id']){
					$val['pp']['available'] = -1;
					$val['isToOrder'] = 0;
				} 

				if ($val['noReturn']) $noReturnIsExists = true;
				$total_basket += $val['price'] * $val['quan'];
				if ($val['isToOrder']) $totalToOrder += $val['price'] * $val['quan'];
				$basketResult[] = $val;
				?>
				<!-- <tr>
					<td colspan="9">
						<?debug($val, 'basket'); debug($val['pp'], 'providerPrice')?>
					</td>
				</tr> -->
				<tr class="good">
					<td class="checkbox">
						<?$disabled = !$val['provider_id'] ? 'disabled'  : '';?>
						<input <?=$disabled?> <?=$val['isToOrder'] == 1 ? 'checked' : ''?> <?=$checkbox?> type="checkbox" name="toOrder" value="<?=$val['store_id']?>-<?=$val['item_id']?>">
					</td>
					<td>
						<b class="brend_info" brend_id="<?=$val['brend_id']?>"><?=$val['brend']?></b> 
						<a class="articul" href="<?=core\Item::getHrefArticle($val['article'])?>"> <?=$val['article_cat'] ? $val['article_cat'] : $val['article']?></a>
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
						<?if ($val['pp']){?>
							<input type="hidden" name="available" value="<?=$val['pp']['available']?>">
							<?$active = $val['pp']['available'] > 0 && $val['pp']['available'] < $val['quan'] ? 'active' : ''?>
							<span class="available <?=$active?>">В наличии <?=$val['pp']['available']?> шт.</span>
							<?if($val['pp']['available'] == -1){?>
								<span class="not_available">Нет в наличии</span>
							<?}?>
						<?}?>
					</td>
					<td class="price-col">
						<?if ($val['pp']['price'] > 0 && $val['pp']['price'] > $val['price']){?>
							<span class="important" style="margin-bottom: 5px; display: block">Цена изменилась</span>
							<span class="price_format"><?=$val['price']?></span>
							<i class="fa fa-rub" aria-hidden="true"></i>
							 <br> <a class="update-price" href="/ajax/update_basket.php?act=update_price&store_id=<?=$val['store_id']?>&item_id=<?=$val['item_id']?>&price=<?=$val['pp']['price']?>">Обновить цену</a>
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
		<?if (!$res_basket->num_rows){?>
		<p>Корзина пуста</p>
		<?}
		else{
			$bl_check = false;
			foreach ($basketResult as $val) {
				$checkbox = '';
				?>
				<div class="good">
					<div class="goods-header">
						<p>
							<?$disabled = !$val['provider_id'] ? 'disabled'  : '';?>
							<input <?=$disabled?> view_type="mobile" <?=$val['isToOrder'] == 1 ? 'checked' : ''?> type="checkbox" <?=$checkbox?> name="toOrder" value="<?=$val['store_id']?>-<?=$val['item_id']?>">
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
								<span class="important" style="margin-bottom: 5px; display: block">Цена изменилась</span>
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
					<?if ($val['pp']){?>
						<input type="hidden" name="available" value="<?=$val['pp']['available']?>">
						<?$active = $val['pp']['available'] > 0 && $val['pp']['available'] < $val['quan'] ? 'active' : ''?>
						<span class="available <?=$active?>">В наличии <?=$val['pp']['available']?> шт.</span>
						<?if($val['pp']['available'] == -1){?>
							<span class="not_available">Нет в наличии</span>
						<?}?>
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
