<?if (!$_SESSION['user']) header('Location: /');
$title = "Корзина";
$user_id = $_SESSION['user'];
$check = $_GET['act'] == "check" ? true : false;
// debug($basket);
// debug($user); exit();
if ($_GET['act'] == 'to_offer'){
	// exit();
	$basket = get_basket_basket(true);
	// var_dump($basket); exit();
	if (!$basket){
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
	foreach ($basket as $value){
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
$basket = get_basket_basket(false);
$noReturnIsExists = false;
function get_basket_basket($isToOrder = false){
	global $db;
	$user_id = $_SESSION['user'];
	if ($isToOrder) $whereIsToOrder = "AND b.isToOrder = 1";
	$basket = $db->select_unique("
		SELECT 
			b.*,
			IF (
				i.article_cat != '', 
				i.article_cat, 
				IF (
					i.article !='',
					i.article,
					i.barcode
				)
			) as article,
			br.title as brend,
			IF (i.title_full != '', i.title_full, i.title) AS title,
			IF (f.item_id IS NOT NULL, 1, 0) AS is_favorite,
			IF (si.in_stock = 0, ps.under_order, ps.delivery) as delivery,
			ps.cipher,
			si.packaging,
			si.in_stock,
			IF (ps.noReturn, 'class=\"noReturn\" title=\"Возврат поставщику невозможен!\"', '') AS noReturn,
			CEIL(si.price * c.rate + si.price * c.rate * ps.percent / 100) as new_price
		FROM
				#basket b
		LEFT JOIN #items i ON i.id=b.item_id
		LEFT JOIN #brends br ON br.id=i.brend_id
		LEFT JOIN #store_items si ON si.item_id=b.item_id AND si.store_id=b.store_id
		LEFT JOIN #provider_stores ps ON ps.id=si.store_id
		LEFT JOIN #currencies c ON c.id=ps.currency_id
		LEFT JOIN #favorites f ON f.item_id=b.item_id AND f.user_id=$user_id
		WHERE b.user_id=$user_id $whereIsToOrder
	", '');
	if (empty($basket)) return false;
	foreach($basket as $key => $value){
		$b = & $basket[$key];
		unset($b['user_id']);
		$b['href'] = getHrefArticle($b['article']);
	} 
	return $basket;
}?>
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
		<?if (empty($basket)){?>
		<tr>
			<td colspan="9">Корзина пуста</td>
		</tr>
		<?}
		else{
			// debug($basket);
			$total_basket = 0;
			$totalToOrder = 0;
			$bl_check = false;
			foreach ($basket as $val) {
				if ($val['noReturn']) $noReturnIsExists = true;
				$total_basket += $val['price'] * $val['quan'];
				if ($val['isToOrder']) $totalToOrder += $val['price'] * $val['quan'];
				?>
				<tr>
					<td class="checkbox">
						<input <?=$val['isToOrder'] == 1 ? 'checked' : ''?> type="checkbox" name="toOrder" value="<?=$val['store_id']?>-<?=$val['item_id']?>">
					</td>
					<td>
						<b class="brend_info" brend_id="<?=$val['brend_id']?>"><?=$val['brend']?></b> 
						<a class="articul" href="<?=$val['href']?>"> <?=$val['article']?></a>
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
						<?if ($val['quan'] > $val['in_stock'] and $check){?>
							<span style="line-height: 25px; display: block" class="important">В наличии <?=$val['in_stock']?> шт.</span>
							<a href="/ajax/update_basket.php?act=update_quan&provider_id=<?=$val['provider_id']?>&item_id=<?=$val['item_id']?>&quan=<?=$val['in_stock']?>" class="update-quan">Пересчитать</a>	
						<?}?>
					</td>
					<td class="price-col">
						<?if ($val['new_price'] > $val['price'] and $check){?>
							<span class="important" style="margin-bottom: 5px; display: block">Цена изменилась</span>
							<span class="price_format"><?=$val['price']?></span>
							<i class="fa fa-rub" aria-hidden="true"></i>
							 <br> <a class="update-price" href="/ajax/update_basket.php?act=update_price&provider_id=<?=$val['provider_id']?>&item_id=<?=$val['item_id']?>&new_price=<?=$val['new_price']?>">Обновить цену</a>
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
			foreach ($basket as $val) {?>
				<div class="good">
					<div class="goods-header">
						<p>
							<input view_type="mobile" <?=$val['isToOrder'] == 1 ? 'checked' : ''?> type="checkbox" name="toOrder" value="<?=$val['store_id']?>-<?=$val['item_id']?>">
							<b class="brend_info" brend_id="<?=$val['brend_id']?>"><?=$val['brend']?></b>  
							<a href="<?=getHrefArticle($val['article'])?>" class="articul"><?=$val['article']?></a>
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
							<?if ($val['new_price'] > $val['price'] and $check){?>
								<span class="label important price-change-warning">Цена изменилась </span>
								<span class="price">
									<span class="price_format"><?=$val['price']?></span>
									<i class="fa fa-rub" aria-hidden="true"></i>
								</span>
								<a class="update-price" href="/ajax/update_basket.php?act=update_price&provider_id=<?=$val['provider_id']?>&item_id=<?=$val['item_id']?>&new_price=<?=$val['new_price']?>">Обновить цену</a>
							<?}
							else{?>
								<span style="position: relative; top: 13px" class="price">
									<span class="price_format"><?=$val['price']?></span>
									<i class="fa fa-rub" aria-hidden="true"></i>
								</span>
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
