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
		<?$count_providers_items = $db->getCount('providers_items', "`item_id`=$item_id"); 
		$item = $db->select('items', 'title,id,brend_id,article', "`id`=$item_id");
		$brend = $db->getFieldOnID('brends', $item[0]['brend_id'], 'title');
		if ($count_providers_items > 0){
			$is_elements = false;
			$where = "`item_id`=".$item[0]['id'];
			if (count($offers_filter)){
				foreach ($offers_filter as $k => $v) {
					if ($k == 'price_from' and $v) $where .= " AND `price`>=$v";
					if ($k == 'price_to' and $v) $where .= " AND `price`<=$v";
					if ($k == 'time_from' and $v) $where .= " AND `delivery` >= $v";
					if ($k == 'time_to' and $v) $where .= " AND `delivery`<= $v";
					if ($k == 'in_stock_only' and $v == 1) $where .= " AND `in_stock`>0";
				}
			}
			$min_price = $db->select('providers_items', 'id', $where, 'price', true, '0,1');
			$min_provider_item = $db->select('providers_items', '*', "`id`=".$min_price[0]['id']);
			$providers_items[] = $min_provider_item[0];
			$min_delivery = $db->select('providers_items', 'id', $where, 'delivery', true, '0,1');
			$min_delivery_item = $db->select('providers_items', '*', "`id`=".$min_delivery[0]['id']);
			$providers_items[] = $min_delivery_item[0];
			$button_row = $count_providers_items <= 2 ? "button-row" : "";
			if (!count($providers_items)) continue;
			else $is_elements = true;?>
			<tr class="<?=$button_row?> first-full">
				<td style="padding-top: 20px;">
					<a href="#"><?=$brend?></a> 
					<a href="/search/<?=$item[0]['article']?>" class="articul"><?=$item[0]['article']?></a>
				</td>
				<td class="name-col" style="padding-top: 20px;">
					<a href="#"><i item_id="<?=$item[0]['id']?>" class="fa fa-camera product-popup-link" aria-hidden="true"></i></a>
					<?=$item[0]['title']?>
				</td>
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
			<tr class="hidden-row second-full"></tr>
			<?if ($count_providers_items > 2){?>
				<tr class="button-row active">
					<td colspan="2"></td>
					<td colspan="5" style="padding-top: 0px !important;">
						<button type="full" query="<?=$where?>" item_id="<?=$item[0]['id']?>">Остальные предложения</button>
					</td>
				</tr>
			<?if (!$is_elements){?>
				<tr><td style="padding-top: 15px;">Нет данных</td></tr>
			<?}
			}?>
	</table>
	<div class="mobile-layout">
		<div class="goods-header">
			<p><a href="#"><?=$brend?></a> <a href="/search/<?=$item[0]['article']?>" class="articul"><?=$item[0]['article']?></a></p>
			<p><?=$item[0]['title']?></p>
			<i item_id="<?=$item[0]['id']?>" class="fa fa-camera product-popup-link" aria-hidden="true"></i>
		</div>
		<table class="small-view">
			<tr class="first-mobile">
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
			<tr class="second-mobile"></tr>
			<tr class="hidden-row"></tr>
			<?if ($count_providers_items > 2){?>
				<tr class="button-row">
					<td colspan="5" style="padding-top: 0px !important;">
						<button type="mobile" query="<?=$where?>" item_id="<?=$item[0]['id']?>">Остальные предложения</button>
					</td>
				</tr>
			<?}?>
	<?}
		else{?>
			<td class="button_padding"><a href="#"><?=$brend?></a> <a href="/search/<?=$item[0]['article']?>" class="articul"><?=$item[0]['article']?></a></td>
			<td class="name-col"><a href="#"><i item_id="<?=$item[0]['id']?>" class="fa fa-camera product-popup-link" aria-hidden="true"></i></a><?=$item[0]['title']?></td>
			<td colspan="4" style="padding-top: 20px">Поставщиков не найдено</td>
		<?}?>
		</table>
	</div>
