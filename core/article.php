<?
/* @var string $device */
/* @var array $store_items */
/* @var array $basket */

$hidden = isInBasketExists($store_items) ? '' : 'hidden';
$inBasket = json_encode(getInBasket($basket));
use core\User;
?>
<?if ($device == 'tablet' || $device == 'desktop'){?>
	<table class="articul-table">
		<tr class="shown">
			<th>Бренд</th>
			<th>Наименование</th>
			<th>Поставщик</th>
			<th>В наличии</th>
			<th>Срок</th>
			<th class="price">Цена</th>
			<th class="quan <?=$hidden?>">К заказу</th>
			<th><i class="fa fa-cart-arrow-down" aria-hidden="true"></i></th>
		</tr>
		<?foreach($store_items as $value){
			$store_item = $value['store_item'];
			$si = & $store_item;
			$csi = isset($si['list']) ? count($si['list']) : null;
			$si_price = $si['min_price'];
			$si_delivery = $si['min_delivery'];
			if ($csi > 0 || !empty($si['prevails'])){
				$button_row = ($csi <= 2)  ? "button-row" : "";?>
				<tr class="<?=$button_row?> shown first-full">
					<!-- бренд с артикулом -->
					<td style="padding: 20px 0 0 0;text-align:left">
						<b class="brend_info" brend_id="<?=$si['brend_id']?>">
							<?=$si['brend']?>
						</b>
						<a href="/article/<?=$si['item_id']?>-<?=$si['article']?>" class="articul">
							<?=$si['article']?>
						</a>
					</td>
					<!-- наименование с фотоаппаратом -->
					<td class="name-col" style="padding-top: 18px;text-align:left">
						<?$class = '';
							if($si['is_desc'] || $si['photo']){
								if ($si['is_desc']) $class ='fa-cog';
								if ($si['photo']) $class = 'fa-camera';?>
								<a href="#">
									<i item_id="<?=$si['item_id']?>" class="fa <?=$class?> product-popup-link" aria-hidden="true"></i>
								</a>
							<?}?>
							<?=$si['title_full']?>
					</td>
					<!-- шифр поставщика -->
					<td>
						<?if (!empty($si['prevails'])){?>
							<ul class="prevail">
								<?foreach($si['prevails'] as $value){?>
									<li>
										<a href="" store_id="<?=$value['store_id']?>"><?=$value['cipher']?></a>
									</li>
								<?}?>
							</ul>
						<?}?>
						<?if (!empty($si_price)){?>
							<ul>
								<li <?=$si_price['noReturn']?>>
									<a href="" store_id="<?=$si_price['store_id']?>"><?=$si_price['cipher']?></a>
								</li>
								<?if (!empty($si_delivery)){?>
									<li <?=$si_delivery['noReturn']?>>
										<a href="" store_id="<?=$si_delivery['store_id']?>"><?=$si_delivery['cipher']?></a>
									</li>
								<?}?>
							</ul>
						<?}?>
					</td>
					<!-- в наличии -->
					<td>
						<?if (!empty($si['prevails'])){?>
							<ul class="prevail">
								<?foreach($si['prevails'] as $value){?>
									<li>
										<?=$value['in_stock']?>
										<?=$value['packaging_text']?>
									</li>
								<?}?>
							</ul>
						<?}?>
						<?if (!empty($si_price)){?>
							<ul>
								<li>
									<?=$si_price['in_stock']?>
									<?=$si_price['packaging_text']?>
								</li>
								<?if (!empty($si_delivery)){?>
									<li>
										<?=$si_delivery['in_stock']?>
										<?=$si_delivery['packaging_text']?>
									</li>
								<?}?>
							</ul>
						<?}?>
					</td>
					<!-- срок поставки -->
					<td>
						<?if (!empty($si['prevails'])){?>
							<ul class="prevail">
								<?foreach($si['prevails'] as $value){?>
									<li><?=$value['delivery_date']?></li>
								<?}?>
							</ul>
						<?}?>
						<?if (!empty($si_price)){?>
							<ul>
								<li><?=$si_price['delivery_date']?></li>
								<?if (!empty($si_delivery)){?>
									<li><?=$si_delivery['delivery_date']?></li>
								<?}?>
							</ul>
						<?}?>
					</td>
					<!-- цена -->
					<td class="price">
						<?if (!empty($si['prevails'])){?>
							<ul class="prevail">
								<?foreach($si['prevails'] as $value){?>
									<li>
										<?=User::getHtmlUserPrice($value['price'], $user['designation'])?>
									</li>
								<?}?>
							</ul>
						<?}?>
						<?if (!empty($si_price)){?>
							<ul>
								<li>
									<?=User::getHtmlUserPrice($si_price['price'], $user['designation'])?>
								</li>
								<?if (!empty($si_delivery)){?>
									<li>
										<?=User::getHtmlUserPrice($si_delivery['price'], $user['designation'])?>
									</li>
								<?}?>
							</ul>
						<?}?>
					</td>
					<!-- количество -->
					<td class="quan <?=$hidden?>">
						<?if (!empty($si['prevails'])){?>
							<ul class="prevail">
								<?foreach($si['prevails'] as $value){?>
									<li packaging="<?=$value['packaging']?>" store_id="<?=$value['store_id']?>" item_id="<?=$si['item_id']?>" class="count-block">
										<?if ($value['in_basket']){?>
											<input value="<?=$value['in_basket']?>">
										<?}?>
									</li>
								<?}?>
							</ul>
						<?}?>
						<?if (!empty($si_price)){?>
							<ul>
								<li packaging="<?=$si_price['packaging']?>" store_id="<?=$si_price['store_id']?>" item_id="<?=$si['item_id']?>" class="count-block">
									<?if ($si_price['in_basket']){?>
										<input value="<?=$si_price['in_basket']?>">
									<?}?>
								</li>
								<?if (!empty($si_delivery)){?>
										<li packaging="<?=$si_delivery['packaging']?>" store_id="<?=$si_delivery['store_id']?>" item_id="<?=$si['item_id']?>" class="count-block">
											<?if ($si_delivery['in_basket']){?>
												<input value="<?=$si_delivery['in_basket']?>">
											<?}?>
										</li>
								<?}?>
							</ul>
						<?}?>
					</td>
					<!-- значок корзины -->
					<td>
						<?if (!empty($si['prevails'])){?>
							<ul class="prevail to-cart-list">
								<?foreach($si['prevails'] as $value){?>
									<li>
										<i price="<?=$value['price']?>" 
											store_id="<?=$value['store_id']?>" 
											item_id="<?=$si['item_id']?>" 
											packaging="<?=$value['packaging']?>" class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">
											<?if ($value['in_basket']){?>
												<i class="goods-counter"><?=$value['in_basket']?></i> 
											<?}?>
										</i> 
									</li>
								<?}?>
							</ul>
						<?}?>
						<?if (!empty($si_price)){?>
							<ul class="to-cart-list">
								<li>
									<i price="<?=$si_price['price']?>" 
										store_id="<?=$si_price['store_id']?>" 
										item_id="<?=$si['item_id']?>" 
										packaging="<?=$si_price['packaging']?>" class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">
										<?if ($si_price['in_basket']){?>
											<i class="goods-counter"><?=$si_price['in_basket']?></i> 
										<?}?>
									</i> 
								</li>
								<?if (!empty($si_delivery)){?>
									<li>
										<i price="<?=$si_delivery['price']?>" 
											store_id="<?=$si_delivery['store_id']?>" 
											item_id="<?=$si['item_id']?>" 
											packaging="<?=$si_delivery['packaging']?>" class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">
											<?if ($si_delivery['in_basket']){?>
												<i class="goods-counter"><?=$si_delivery['in_basket']?></i> 
											<?}?>
										</i> 
									</li>
								<?}?>
							</ul>
						<?}?>
					</td>
				</tr>
				<?if ($csi > 2){?>
					<tr class="hidden-row second-full">
						<!-- бренд с артикулом -->
						<td style="padding: 20px 0 0 0;text-align:left">
							<b class="brend_info" brend_id="<?=$si['brend_id']?>">
								<?=$si['brend']?>
							</b>
							<a href="<?=core\Item::getHrefArticle($si['article'])?>" class="articul">
								<?=$si['article']?>
							</a>
						</td>
						<!-- наименование с фотоаппаратом -->
						<td class="name-col" style="padding-top: 20px;text-align:left">
							<?$class = '';
							if($si['is_desc'] || $si['foto']){
								if($si['is_desc']) $class ='fa-cog';
								if ($si['photo']) $class = 'fa-camera';?>
								<a href="#">
									<i item_id="<?=$si['item_id']?>" class="fa <?=$class?> product-popup-link" aria-hidden="true"></i>
								</a>
							<?}?>
							<?=$si['title_full']?>
						</td>
						<!-- шифр поставщика -->
						<td>
							<?if (!empty($si['prevails'])){?>
								<ul class="prevail">
									<?foreach($si['prevails'] as $value){?>
										<li>
											<a href="" store_id="<?=$value['store_id']?>"><?=$value['cipher']?></a>
										</li>
									<?}?>
								</ul>
							<?}?>
							<ul>
								<?foreach($si['list'] as $key => $value){?>
									<li <?=$value['noReturn']?>>
										<a href="" store_id="<?=$value['store_id']?>"><?=$value['cipher']?></a>
									</li>
								<?}?>
							</ul>
						</td>
						<!-- в наличии -->
						<td>
							<?if (!empty($si['prevails'])){?>
								<ul class="prevail">
									<?foreach($si['prevails'] as $value){?>
										<li>
											<?=$value['in_stock']?>
											<?=$value['packaging_text']?>
										</li>
									<?}?>
								</ul>
							<?}?>
							<ul>
								<?foreach($si['list'] as $key => $value){?>
									<li>
										<?=$value['in_stock']?>
										<?=$value['packaging_text']?>
									</li>
								<?}?>
							</ul>
						</td>
						<!-- срок поставки -->
						<td>
							<?if (!empty($si['prevails'])){?>
								<ul class="prevail">
									<?foreach($si['prevails'] as $value){?>
										<li><?=$value['delivery_date']?></li>
									<?}?>
								</ul>
							<?}?>
							<ul>
								<?foreach($si['list'] as $key => $value){?>
									<li><?=$value['delivery_date']?></li>
								<?}?>
							</ul>
						</td>
						<!-- цена -->
						<td class="price">
							<?if (!empty($si['prevails'])){?>
								<ul class="prevail">
									<?foreach($si['prevails'] as $value){?>
										<li>
											<?=User::getHtmlUserPrice($value['price'], $user['designation'])?>
										</li>
									<?}?>
								</ul>
							<?}?>
							<ul>
								<?foreach($si['list'] as $key => $value){?>
									<li>
										<?=User::getHtmlUserPrice($value['price'], $user['designation'])?>
									</li>
								<?}?>
							</ul>
						</td>
						<!-- количество -->
						<td class="quan <?=$hidden?>">
							<?if (!empty($si['prevails'])){?>
								<ul class="prevail">
									<?foreach($si['prevails'] as $value){?>
											<li prevail="1" packaging="<?=$value['packaging']?>" store_id="<?=$value['store_id']?>" item_id="<?=$si['item_id']?>" class="count-block">
												<?if ($value['in_basket']){?>
													<input value="<?=$value['in_basket']?>">
												<?}?>
											</li>
									<?}?>
								</ul>
							<?}?>
							<ul>
								<?foreach($si['list'] as $key => $value){?>
									<li packaging="<?=$value['packaging']?>" store_id="<?=$value['store_id']?>" item_id="<?=$si['item_id']?>" class="count-block">
										<?if ($value['in_basket']){?>
											<input value="<?=$value['in_basket']?>">
										<?}?>
									</li>
								<?}?>
							</ul>
						</td>
						<!-- значок корзины -->
						<td>
							<?if (!empty($si['prevails'])){?>
								<ul class="to-cart-list prevail">
									<?foreach($si['prevails'] as $value){?>
										<li>
											<i price="<?=$value['price']?>" 
												store_id="<?=$value['store_id']?>" 
												item_id="<?=$si['item_id']?>" 
												packaging="<?=$value['packaging']?>" 
												class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">
												<?if ($value['in_basket']){?>
													<i class="goods-counter"><?=$value['in_basket']?></i> 
												<?}?>
											</i> 
										</li>
									<?}?>
								</ul>
							<?}?>
							<ul class="to-cart-list">
								<?foreach($si['list'] as $key => $value){?>
									<li>
										<i price="<?=$value['price']?>" 
											store_id="<?=$value['store_id']?>" 
											item_id="<?=$si['item_id']?>" 
											packaging="<?=$value['packaging']?>" 
											class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">
											<?if ($value['in_basket']){?>
												<i class="goods-counter"><?=$value['in_basket']?></i> 
											<?}?>
										</i> 
										</li>
								<?}?>
							</ul>
						</td>
					</tr>
					<tr class="button-row active shown">
						<td colspan="<?=$hidden ? 7 : 8?>" style="padding-top: 0px !important; text-align: center">
							<button type="full"></button>
						</td>
					</tr>
				<?}
			}
			else{?>
				<tr class="shown button-row empty">
					<td class="button_padding">
						<b class="brend_info" brend_id="<?=$si['brend_id']?>"><?=$si['brend']?></b>
						<a href="<?=core\Item::getHrefArticle($si['article'])?>" class="articul"><?=$si['article']?></a>
						<input type="hidden" id="item_id" value="<?=$si['item_id']?>">
					</td>
					<td class="name-col">
					<?$class = '';
						if($si['is_desc'] || $si['photo']){
							if($si['is_desc']) $class ='fa-cog';
							if ($si['photo']) $class = 'fa-camera';?>
							<a href="#">
								<i item_id="<?=$si['item_id']?>" class="fa <?=$class?> product-popup-link" aria-hidden="true"></i>
							</a>
						<?}?>
						<?=$si['title_full']?>
					</td>
					<td colspan="5" style="">
						<?if (!isset($_GET['noUseAPI'])){?>
							<div class="emptyAvailabilityMainStores">
								<img src="<?=core\Config::$imgUrl?>/preload.gif">
								<p>Идет поиск по складам партнеров...</p>
							</div>
						<?}
						else{?>
							<p style="padding: 0">Поставщиков не найдено.</p>
						<?}?>
					</td>
				</tr>
			<?}
		}?>
	</table>
<?}
else{?>
	<div class="mobile-layout">
		<?foreach($store_items as $store_item){
			$si = & $store_item['store_item'];
			// debug($si);
			$csi = count($si['list']);
			$si_price = $si['min_price'];
			$si_delivery = $si['min_delivery'];?>
			<div class="goods-header">
				<p>
					<b class="brend_info" brend_id="<?=$si['brend_id']?>"><?=$si['brend']?></b>
					<a href="<?=core\Item::getHrefArticle($si['article'])?>" class="articul"><?=$si['article']?></a>
				</p>
				<p><?=$si['title_full']?></p>
			</div>
			<?$class = '';
			if($si['is_desc'] || $si['foto']){
				if($si['is_desc']) $class ='fa-cog';
				if ($si['photo']) $class = 'fa-camera';?>
				<a href="#">
					<i item_id="<?=$si['item_id']?>" class="fa <?=$class?> product-popup-link" aria-hidden="true"></i>
				</a>
			<?}?>
			<table class="small-view">
				<?if ($csi > 0  || !empty($si['prevails'])){?>
					<tr class="first-mobile shown">
						<td>
							<?if (!empty($si['prevails'])){?>
								<ul class="prevail">
									<?foreach($si['prevails'] as $value){?>
										<li>
											<a href="" store_id="<?=$value['store_id']?>"><?=$value['cipher']?></a>
										</li>
									<?}?>
								</ul>
							<?}?>
							<?if (!empty($si_price)){?>
								<ul>
									<li <?=$si_price['noReturn']?>>
										<a href="" store_id="<?=$si_price['store_id']?>"><?=$si_price['cipher']?></a>
									</li>
									<?if (!empty($si_delivery)){?>
										<li <?=$si_delivery['noReturn']?>>
											<a href="" store_id="<?=$si_delivery['store_id']?>"><?=$si_delivery['cipher']?></a>
										</li>
									<?}?>
								</ul>
							<?}?>
						</td>
						<td>
							<?if (!empty($si['prevails'])){?>
								<ul class="prevail">
									<?foreach($si['prevails'] as $value){?>
										<li>
											<?=$value['in_stock']?>
											<?=$si_price['packaging_text']?>	
										</li>
									<?}?>
								</ul>
							<?}?>
							<?if (!empty($si_price)){?>
								<ul>
									<li>
										<?=$si_price['in_stock']?>
										<?=$si_price['packaging_text']?>
									</li>
									<?if (!empty($si_delivery)){?>
										<li>
											<?=$si_delivery['in_stock']?>
											<?=$si_delivery['packaging_text']?>
										</li>
									<?}?>
								</ul>
							<?}?>
						</td>
						<td>
							<?if (!empty($si['prevails'])){?>
								<ul class="prevail">
									<?foreach($si['prevails'] as $value){?>
										<li><?=$value['delivery_date']?></li>
									<?}?>
								</ul>
							<?}?>
							<?if (!empty($si_price)){?>
								<ul>
									<li><?=$si_price['delivery_date']?></li>
									<?if (!empty($si_delivery)){?>
										<li><?=$si_delivery['delivery_date']?></li>
									<?}?>
								</ul>
							<?}?>
						</td>
						<td class="price">
							<?if (!empty($si['prevails'])){?>
								<ul class="prevail">
									<?foreach($si['prevails'] as $value){?>
										<li>
											<?=User::getHtmlUserPrice($value['price'], $user['designation'])?>
										</li>
									<?}?>
								</ul>
							<?}?>
							<?if (!empty($si_price)){?>
								<ul>
									<li>
										<?=User::getHtmlUserPrice($si_price['price'], $user['designation'])?>
									</li>
									<?if (!empty($si_delivery)){?>
										<li>
											<?=User::getHtmlUserPrice($si_delivery['price'], $user['designation'])?>
										</li>
									<?}?>
								</ul>
							<?}?>
						</td>
						<!-- количество -->
						<td class="quan <?=$hidden?>">
							<?if (!empty($si['prevails'])){?>
								<ul class="prevail">
									<?foreach($si['prevails'] as $value){?>
										<li prevail="1" packaging="<?=$value['packaging']?>" store_id="<?=$value['store_id']?>" item_id="<?=$si['item_id']?>" class="count-block">
											<?if ($value['in_basket']){?>
												<input value="<?=$value['in_basket']?>">
											<?}?>
										</li>
									<?}?>
								</ul>
							<?}?>
							<?if (!empty($si_price)){?>
								<ul>
									<li packaging="<?=$si_price['packaging']?>" store_id="<?=$si_price['store_id']?>" item_id="<?=$si['item_id']?>" class="count-block">
										<?if ($si_price['in_basket']){?>
											<input value="<?=$si_price['in_basket']?>">
										<?}?>
									</li>
									<?if (!empty($si_delivery)){?>
										<li packaging="<?=$si_delivery['packaging']?>" store_id="<?=$si_delivery['store_id']?>" item_id="<?=$si['item_id']?>" class="count-block">
											<?if ($si_delivery['in_basket']){?>
												<input value="<?=$si_delivery['in_basket']?>">
											<?}?>
										</li>
									<?}?>
								</ul>
							<?}?>
						</td>
						<td>
							<?if (!empty($si['prevails'])){?>
								<ul class="prevail">
									<?foreach($si['prevails'] as $value){?>
										<li>
											<i price="<?=$value['price']?>" 
												store_id="<?=$value['store_id']?>" 
												item_id="<?=$si['item_id']?>" 
												packaging="<?=$value['packaging']?>" class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">
												<?if ($value['in_basket']){?>
													<i class="goods-counter"><?=$value['in_basket']?></i> 
												<?}?>
											</i> 
										</li>
									<?}?>
								</ul>
							<?}?>
							<?if (!empty($si_price)){?>
								<ul class="to-cart-list">
								<li>
									<i price="<?=$si_price['price']?>" 
										store_id="<?=$si_price['store_id']?>" 
										item_id="<?=$si['item_id']?>" 
										packaging="<?=$si_price['packaging']?>" class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">
										<?if ($si_price['in_basket']){?>
											<i class="goods-counter"><?=$si_price['in_basket']?></i> 
										<?}?>
									</i> 
								</li>
								<?if (!empty($si_delivery)){?>
									<li>
										<i price="<?=$si_delivery['price']?>" 
											store_id="<?=$si_delivery['store_id']?>" 
											item_id="<?=$si['item_id']?>" 
											packaging="<?=$si_delivery['packaging']?>" class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">
											<?if ($si_delivery['in_basket']){?>
												<i class="goods-counter"><?=$si_delivery['in_basket']?></i> 
											<?}?>
										</i> 
									</li>
								<?}?>
							</ul>
							<?}?>
						</td>
					</tr>
					<tr class="second-mobile">
						<td>
							<?if (!empty($si['prevails'])){?>
								<ul class="prevail">
									<?foreach($si['prevails'] as $value){?>
										<li <?=$value['noReturn']?>>
											<a href="" store_id="<?=$value['store_id']?>"><?=$value['cipher']?></a>
										</li>
									<?}?>
								</ul>
							<?}?>
							<ul>
								<?foreach($si['list'] as $key => $value){?>
									<li <?=$value['noReturn']?>>
										<a href="" store_id="<?=$value['store_id']?>"><?=$value['cipher']?></a>
									</li>
								<?}?>
							</ul>
						</td>
						<td>
							<?if (!empty($si['prevails'])){?>
								<ul class="prevail">
									<?foreach($si['prevails'] as $value){?>
										<li>
											<?=$value['in_stock']?>
											<?=$value['packaging_text']?>
										</li>
									<?}?>
								</ul>
							<?}?>
							<ul>
								<?foreach($si['list'] as $key => $value){?>
									<li>
										<?=$value['in_stock']?>
										<?=$value['packaging_text']?>
									</li>
								<?}?>
							</ul>
						</td>
						<td>
							<?if (!empty($si['prevails'])){?>
								<ul class="prevail">
									<?foreach($si['prevails'] as $value){?>
										<li><?=$value['delivery_date']?></li>
									<?}?>
								</ul>
							<?}?>
							<ul>
								<?foreach($si['list'] as $key => $value){?>
									<li><?=$value['delivery_date']?></li>
								<?}?>
							</ul>
						</td>
						<td class="price">
							<?if (!empty($si['prevails'])){?>
								<ul class="prevail">
									<?foreach($si['prevails'] as $value){?>
										<li>
											<?=User::getHtmlUserPrice($value['price'], $user['designation'])?>
										</li>
									<?}?>
								</ul>
							<?}?>
							<ul>
								<ul>
									<?foreach($si['list'] as $key => $value){?>
										<li>
											<?=User::getHtmlUserPrice($value['price'], $user['designation'])?>
										</li>
									<?}?>
								</ul>
							</ul>
						</td>
						<!-- количество -->
						<td class="quan <?=$hidden?>">
							<?if (!empty($si['prevails'])){?>
								<ul class="prevail">
									<?foreach($si['prevails'] as $value){?>
										<li prevail="1" packaging="<?=$value['packaging']?>" store_id="<?=$value['store_id']?>" item_id="<?=$si['item_id']?>" class="count-block">
											<?if ($value['in_basket']){?>
												<input value="<?=$value['in_basket']?>">
											<?}?>
										</li>
									<?}?>
								</ul>
							<?}?>
							<ul>
								<?foreach($si['list'] as $key => $value){?>
									<li packaging="<?=$value['packaging']?>" store_id="<?=$value['store_id']?>" item_id="<?=$si['item_id']?>"class="count-block">
										<?if ($value['in_basket']){?>
											<input value="<?=$value['in_basket']?>">
										<?}?>
									</li>
								<?}?>
							</ul>
						</td>
						<td>
							<?if (!empty($si['prevails'])){?>
								<ul class="prevail">
									<?foreach($si['prevails'] as $value){?>
										<li>
											<i price="<?=$value['price']?>" 
												store_id="<?=$value['store_id']?>" 
												item_id="<?=$si['item_id']?>" 
												packaging="<?=$value['packaging']?>" 
												class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">
												<?if ($value['in_basket']){?>
													<i class="goods-counter"><?=$value['in_basket']?></i> 
												<?}?>
											</i> 
										</li>
									<?}?>
								</ul>
							<?}?>
							<ul class="to-cart-list">
								<?foreach($si['list'] as $key => $value){?>
									<li>
										<i price="<?=$value['price']?>" 
											store_id="<?=$value['store_id']?>" 
											item_id="<?=$si['item_id']?>" 
											packaging="<?=$value['packaging']?>" 
											class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">
											<?if ($value['in_basket']){?>
												<i class="goods-counter"><?=$value['in_basket']?></i> 
											<?}?>
										</i> 
										</li>
								<?}?>
							</ul>
						</td>
					</tr>
					<?if ($csi > 2){?>
						<tr class="button-row active shown">
							<td colspan="<?=$isInBasket ? 8 : 7?>" style="padding-top: 0px !important;">
								<button></button>
							</td>
						</tr>
					<?}
				}
				else{?>
					<tr class="shown first-mobile empty">
						<td colspan="5">
							<?if (!isset($_GET['noUseAPI'])){?>
								<div class="emptyAvailabilityMainStores">
									<img src="<?=core\Config::$imgUrl?>/preload.gif">
									<p>Идет поиск по складам партнеров...</p>
								</div>
							<?}
							else{?>
								<p style="padding: 0">Поставщиков не найдено.</p>
							<?}?>
						</td>
					</tr>
				<?}?>
			</table>
		<?}?>
	</div>
<?}?>

<div id="analogies">
    <h3 class="title_analogies">Аналоги</h3>
    <div class="itemInsertable" id="Tab__search-result-tabs__Tab_3">
        <table class="articul-table"></table>
        <div class="mobile-layout"></div>
    </div>
</div>

<script type="text/javascript">
	var isInBasket = <?=$isInBasket ? 'true' : 'false'?>;
	var inBasket = JSON.parse('<?=$inBasket?>');
</script>
<?require_once ($_SERVER['DOCUMENT_ROOT'].'/vendor/blueimp/template.php');?>