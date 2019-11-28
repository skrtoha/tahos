	<table class="articul-table">
	<tr>
		<th>Бренд</th>
		<th>Наименование</th>
		<th>Поставщик</th>
		<th>В наличии</th>
		<th>Срок</th>
		<th>Цена</th>
		<th><i class="fa fa-cart-arrow-down" aria-hidden="true"></i></th>
	</tr>
	<?if ($items){?>
		<?$is_elements = false;
		foreach ($items as $value) {
			$item = $db->select('items', 'title,id,brend_id,article', "`id`=".$value['id']);
			if (!$db->getCount('providers_items', "`item_id`=".$item[0]['id'])) continue;
			$brend = $db->getFieldOnID('brends', $item[0]['brend_id'], 'title');
			$where = "`item_id`=".$item[0]['id'];
			if (count($offers_filter)){
				foreach ($offers_filter as $k => $v) {
					if ($k == 'price_from') $where .= " AND `price`>=$v";
					if ($k == 'price_to') $where .= " AND `price`<=$v";
					if ($k == 'time_from') $where .= " AND `delivery` >= $v";
					if ($k == 'time_to') $where .= " AND `delivery`<= $v";
					if ($k == 'in_stock_only' and $v == 1) $where .= " AND `in_stock`>0";
				}
			}
			$providers_items = $db->select('providers_items', "*", $where, "delivery", true, "0,2");
			$count_providers_items = $db->getCount('providers_items', "$where"); 
			$button_row = $count_providers_items <= 2 ? "button-row" : "";
			if (!count($providers_items)) continue;
			else $is_elements = true;?>
			<tr class="<?=$button_row?>">
				<td class="button_padding"><a href="#"><?=$brend?></a> <a href="/search/<?=$item[0]['article']?>" class="articul"><?=$item[0]['article']?></a></td>
				<td class="name-col"><a href="#"><i item_id="<?=$item[0]['id']?>" class="fa fa-camera product-popup-link" aria-hidden="true"></i></a><?=$item[0]['title']?></td>
				<td>
					<ul>
						<?foreach ($providers_items as $provider_item) {?>
							<li><?=$db->getFieldOnID('providers', $provider_item['provider_id'], 'title')?></li>
						<?}?>
					</ul>
				</td>
				<td>
					<ul>
						<?foreach ($providers_items as $provider_item) {
							$packaging = $provider_item['packaging'] != 1 ? "&nbsp;(<span>уп.&nbsp;".$provider_item['packaging']."&nbsp;шт.</span>)" : "";
							if($provider_item['in_stock'] == 0) $in_stock = "Под&nbsp;заказ";
							else {
								$in_stock = $provider_item['in_stock'] > 100 ? ">100" : $provider_item['in_stock'];
								$in_stock .= $packaging;
							}?>
							<li><?=$in_stock?></li>
						<?}?>
					</ul>
				</td>
				<td>
					<ul>
						<?foreach ($providers_items as $provider_item) {?>
							<li><?=$provider_item['delivery']?> дн.</li>
						<?}?>
					</ul>
				</td>
				<td>
					<ul>
						<?foreach ($providers_items as $provider_item) {?>
							<li><?=$provider_item['price']?> р.</li>
						<?}?>
					</ul>
				</td>
				<td>
					<ul class="to-cart-list">
					<?foreach ($providers_items as $provider_item) {?>
						<li>
							<i price="<?=$provider_item['price']?>" provider_item="<?=$provider_item['id']?>" item_id="<?=$item[0]['id']?>" packaging="<?=$provider_item['packaging']?>" class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true"> 
							<?if ($baket_new[$provider_item['id']]){?>
								<i class="goods-counter"><?=$baket_new[$provider_item['id']]?></i> 
							<?}?>
							</i> 
						</li>
					<?}?>
					</ul>
				</td>
			</tr>
			<tr class="hidden-row"></tr>
			<?if ($count_providers_items > 2){?>
				<tr class="button-row">
					<td colspan="2"></td>
					<td colspan="5" style="padding-top: 0px !important;">
						<button query="<?=$where?>">Остальные предложения</button>
					</td>
				</tr>
			<?}?>
		<?}?>
		<?if (!$is_elements){?>
			<tr><td style="padding-top: 15px;">Номер детали <span style="font-weight: 700; color: black"><?=$text?></span> в базе не найден</td></tr>
		<?}
		}?>
</table>
<?$is_elements = false;
if ($items){
	foreach ($items as $value) {
	$item = $db->select('items', 'title,id,brend_id,article', "`id`=".$value['id']);
	if (!$db->getCount('providers_items', "`item_id`=".$item[0]['id'])) continue;
	$brend = $db->getFieldOnID('brends', $item[0]['brend_id'], 'title');
	$where = "`item_id`=".$item[0]['id'];
	if (count($offers_filter)){
		foreach ($offers_filter as $k => $v) {
			if ($k == 'price_from') $where .= " AND `price`>=$v";
			if ($k == 'price_to') $where .= " AND `price`<=$v";
			if ($k == 'time_from') $where .= " AND `delivery` >= $v";
			if ($k == 'time_to') $where .= " AND `delivery`<= $v";
			if ($k == 'in_stock_only' and $v == 1) $where .= " AND `in_stock`>0";
		}
	}
	$providers_items = $db->select('providers_items', "*", $where, "delivery", true, "0,2");
	$count_providers_items = $db->getCount('providers_items', "$where"); 
	$button_row = $count_providers_items <= 2 ? "button-row" : "";
	if (!count($providers_items)) continue;
	else $is_elements = true;?>
	<div class="mobile-layout">
		<div class="goods-header">
			<p><a href="#"><?=$brend?></a> <a href="/search/<?=$item[0]['article']?>" class="articul"><?=$item[0]['article']?></a></p>
			<p><?=$item[0]['title']?></p>
			<i item_id="<?=$item[0]['id']?>" class="fa fa-camera product-popup-link" aria-hidden="true"></i>
		</div>
		<table class="small-view">
			<tr>
				<td>
					<ul>
						<?foreach ($providers_items as $provider_item) {?>
							<li><?=$db->getFieldOnID('providers', $provider_item['provider_id'], 'title')?></li>
						<?}?>
					</ul>
				</td>
				<td>
					<ul>
						<?foreach ($providers_items as $provider_item) {
							$packaging = $provider_item['packaging'] != 1 ? "&nbsp;(<span>уп.&nbsp;".$provider_item['packaging']."&nbsp;шт.</span>)" : "";
							if($provider_item['in_stock'] == 0) $in_stock = "Под&nbsp;заказ";
							else {
								$in_stock = $provider_item['in_stock'] > 100 ? ">100" : $provider_item['in_stock'];
								$in_stock .= $packaging;
							}?>
							<li><?=$in_stock?></li>
						<?}?>
					</ul>
				</td>
				<td>
					<ul>
						<?foreach ($providers_items as $provider_item) {?>
							<li><?=$provider_item['delivery']?> дн.</li>
						<?}?>
					</ul>
				</td>
				<td>
					<ul>
						<ul>
							<?foreach ($providers_items as $provider_item) {?>
								<li><?=$provider_item['price']?> р.</li>
							<?}?>
						</ul>
					</ul>
				</td>
				<td>
					<ul class="to-cart-list">
						<?foreach ($providers_items as $provider_item) {?>
							<li>
								<i price="<?=$provider_item['price']?>" provider_item="<?=$provider_item['id']?>" item_id="<?=$item[0]['id']?>" packaging="<?=$provider_item['packaging']?>" class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true"> 
								<?if ($baket_new[$provider_item['id']]){?>
									<i class="goods-counter"><?=$baket_new[$provider_item['id']]?></i> 
								<?}?>
								</i> 
							</li>
						<?}?>
					</ul>
				</td>
			</tr>
			<tr class="hidden-row"></tr>
			<?if ($count_providers_items > 2){?>
				<tr class="button-row">
					<td colspan="5" style="padding-top: 0px !important;">
						<button type="mobile" query="<?=$where?>">Остальные предложения</button>
					</td>
				</tr>
			<?}?>
		</table>
	</div>
<?}
}
else{
	switch ($_COOKIE['search_type']) {
		case 'articles':
			$text_search = "Номер детали";
			break;
		case 'subtitutes':
			$text_search = "Замена для детали";
			break;
		case 'analogies':
			$text_search = "Аналог для детали";
			break;
	}
	?>
	<div class="goods-header"><?=$text_search?> <span style="font-weight: 700; color: black"><?=$text?></span> в базе не найден</div>
<?}?>