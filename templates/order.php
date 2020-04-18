<?
require_once('admin/functions/orders.function.php');
require_once('admin/functions/sendings.function.php');
if (isset($_GET['act']) && $_GET['act'] == 'edit') $title = 'Редактирование заказа';
else $title = "Просмотр заказа";
$res_orders_values = get_order_values('');
$total = 0;
$status_classes = [ 
	'Отменен' => 'status-return',
	'Ожидает' => 'status-stoped',
	'Завершен' => 'status-delivery',
	'В работе' => 'status-sended'
];
?>
<h1><?=$title?></h1>
<div class="clearfix"></div>
<div class="orders">
	<?if ($device == 'desktop' || $device == 'tablet'){?>
		<table class="orders-table">
			<tr>
				<th>Наименование</th>
				<th>Поставщик</th>
				<th>Кол-во</th>
				<th>Статус</th>
				<th>Сумма</th>
				<?if (isset($_GET['act'])){?>
					<th></th>
				<?}?>
			</tr>
			<?if (!$res_orders_values->num_rows){?>
				<tr>
					<td colspan="8">Заказов не найдено</td>
				</tr>
			<?}
			else{
				while ($order = $res_orders_values->fetch_assoc()) {
					$blocked = !in_array($order['status_id'], [7, 5]) || (!isset($_GET['act'])) ? 'blocked' : false;?>
					<tr order_id="<?=$order['order_id']?>" store_id="<?=$order['store_id']?>" item_id="<?=$order['item_id']?>" class="<?=$blocked?>">
						<td class="name-col">
							<b class="brend_info" brend_id="<?=$order['brend_id']?>"><?=$order['brend']?></b> 
							<a href="<?=core\Item::getHrefArticle($order['article'])?>" class="articul"><?=$order['article']?></a> 
							<?=$order['title_full']?>
						</td>
						<td <?=$order['noReturn']?>><?=$order['cipher']?></td>
						<td>
							<?if (!$blocked){?>
								<div store_id="<?=$order['store_id']?>" item_id="<?=$order['item_id']?>" packaging="<?=$order['packaging']?>" class="count-block" summand="<?=$order['price']?>">
									<span class="minus">-</span>
									<input value="<?=$order['quan']?>">
									<span class="plus">+</span>
								</div>
							<?}
							else{?>
								<?=$order['quan']?>
							<?}?>
						</td>
						<td>
							<span class="status-col <?=$order['status_class']?>">
							<?switch($order['status']){
								case 'Заказано':
									$summ = $order['ordered'] * $order['price'];?>
									Заказано 
									<?if ($order['ordered'] < $order['quan']){?>
										- <?=$order['ordered']?> шт.
									<?}
									break;
								case 'Выдано':
									$summ = !$order['issued'] ? $order['issued'] * $order['price'] : ($order['issued'] - $order['returned']) * $order['price']?>
									Выдано
									<?if (
											(
												$order['issued'] && 
												(
													$order['arrived'] < $order['ordered'] || 
													$order['issued'] < $order['arrived'] ||
													$order['ordered'] < $order['quan'] 
												)
											) ||
											(
												$order['returned'] &&
												$order['arrived'] == $order['issued'] &&
												$order['returned'] < $order['arrived']
											)
										){?>
										- <?=$order['issued'] - $order['returned']?> шт.
									<?}
									if ($order['issued'] < $order['arrived']){?>
										<span class="status-col status-block status-sended">Пришло - <?=$order['arrived'] - $order['issued']?> шт.</span>
									<?}
									if ($order['returned']){?>
										<span class="status-col status-block status-return">Возврат - <?=$order['returned']?> шт.</span>
									<?}
									break;
								case 'Возврат':?>
									<span class="status-col status-block status-return">Возврат</span>
									<?break;
								case 'В работе':
									$summ = $order['price'] * $order['quan']?>
									В работе
									<?break;
								case 'Нет в наличии':
									$summ = 0?>
									<span class="status-col status-block status-return">Нет в наличии</span>
									<?break;
								case 'На отправке':
									$summ = $order['arrived'] * $order['price'];?>
									На отправке
									<?if ($order['issued'] < $order['arrived'] && $order['issued'] < $order['ordered'] && $order['ordered'] < $order['quan']){?>
										- <?=$order['arrived'] - $order['issued']?> шт.
									<?}
									if ($order['issued'] && $order['issued'] < $order['arrived']){?>
										<span class="status-col status-delivery status-block">Выдано - <?=$order['arrived'] - $order['issued']?> шт.</span>
									<?}
									break;
								case 'Отменен':
									$summ = 0;?>
									Отменен
									<?break;
								case 'Приостановлено':
									$summ = $order['quan'] * $order['price']?>
									Приостановлено
									<?break;
								case 'Пришло':
									$summ = $order['arrived'] * $order['price']?>
									Пришло
									<?if (!$order['issued'] && ($order['arrived'] < $order['ordered'] || $order['ordered'] < $order['quan'])){?>
										- <?=$order['arrived']?> шт.
									<?}
									if ($order['issued'] && $order['issued'] < $order['arrived']){?>
										- <?=$order['arrived'] - $order['issued']?> шт.
										<span class="status-col status-delivery status-block">Выдано - <?=$order['issued']?> шт.</span>
									<?}
									break;
							}
							if ($order['arrived'] && $order['arrived'] < $order['ordered'] && !$order['declined']){?>
								<span class="status-block status-expecting">Ожидается - <?=$order['ordered'] - $order['arrived']?> шт.</span>
							<?}
							if (
									(
										$order['ordered'] && 
										$order['ordered'] < $order['quan']
									) ||
									(
										$order['declined'] &&
										$order['arrived'] < $order['ordered']
									)
								){
								$declined = !$order['declined'] ? $order['quan'] - $order['ordered'] : $order['quan'] - $order['arrived'];?>
								<span class="status-col status-block status-refused">Отказ - <?=$declined?> шт.</span>
							<?}
							?>
							</span>
						</td>
						<td>
							<span class="price_format">
								<?$total += $summ?>
								<?=$summ?>
							</span>
							<i class="fa fa-rub" aria-hidden="true"></i>
						</td>
						<?if (isset($_GET['act'])){?>
							<td style="width: 70px; position: relative">
								<?if (!$blocked){?>
									<i title="Комментарий" class="fa fa-pencil-square-o comment-btn" aria-hidden="true"></i>
									<div class="comment-block">
										<textarea class="comment_textarea" placeholder="Напишите Ваш комментарий"><?=$order['comment']?></textarea>
										<button class="save_comment">Сохранить</button>
										<a href="#" class="cancel_comment">Отменить</a>
									</div>
									<span act="delete" class="delete-btn" type_view="big">
										<i style="margin: 0" class="fa fa-times" aria-hidden="true"></i>
									</span>
								<?}?>
							</td>
						<?}?>
					</tr>
				<?}
			}?>
		</table>
	<?}
	else{?>
		<table class="orders-table small-view">
			<?if (!$res_orders_values->num_rows){?>
				<tr><td colspan="2">Заказов не найдено</td></tr>
			<?}
			else{
				while($order = $res_orders_values->fetch_assoc()){
					$blocked = !in_array($order['status_id'], [7, 5]) || (!isset($_GET['act'])) ? 'blocked' : '';?>
					<tr order_id="<?=$order['order_id']?>" store_id="<?=$order['store_id']?>" item_id="<?=$order['item_id']?>" class="<?=$blocked?>">
						<td>
							<b class="brend_info" brend_id="<?=$order['brend_id']?>"><?=$order['brend']?></b> <br> <a href="<?=getHrefArticle($order['article'])?>" class="articul"><?=$order['article']?></a> <br> <?=$order['title_full']?> <br><br>
							Поставщик: <strong <?=$order['noReturn']?>><?=$order['cipher']?></strong> <br>
							Количество: 
								<?if (!$blocked){?>
									<div style="display: inline-block" store_id="<?=$order['store_id']?>" item_id="<?=$order['item_id']?>" packaging="<?=$order['packaging']?>" class="count-block" summand="<?=$order['price']?>">
										<span class="minus">-</span>
										<input value="<?=$order['quan']?>">
										<span class="plus">+</span>
									</div><br>
								<?}
								else{?>
									<?=$order['quan']?><br>
								<?}?>
								Статус: <strong>
								<span class="status-col <?=$order['status_class']?>">
									<?switch($order['status']){
										case 'Заказано':
											$summ = $order['ordered'] * $order['price'];?>
											Заказано 
											<?if ($order['ordered'] < $order['quan']){?>
												- <?=$order['ordered']?> шт.
											<?}
											break;
										case 'Выдано':
											$summ = !$order['issued'] ? $order['issued'] * $order['price'] : ($order['issued'] - $order['returned']) * $order['price']?>
											Выдано
											<?if (
													(
														$order['issued'] && 
														(
															$order['arrived'] < $order['ordered'] || 
															$order['issued'] < $order['arrived'] ||
															$order['ordered'] < $order['quan'] 
														)
													) ||
													(
														$order['returned'] &&
														$order['arrived'] == $order['issued'] &&
														$order['returned'] < $order['arrived']
													)
												){?>
												- <?=$order['issued'] - $order['returned']?> шт.
											<?}
											if ($order['issued'] < $order['arrived']){?>
												<span class="status-col status-block status-sended">Пришло - <?=$order['arrived'] - $order['issued']?> шт.</span>
											<?}
											if ($order['returned']){?>
												<span class="status-col status-block status-return">Возврат - <?=$order['returned']?> шт.</span>
											<?}
											break;
										case 'Возврат':?>
											<span class="status-col status-block status-return">Возврат</span>
											<?break;
										case 'В работе':
											$summ = $order['price'] * $order['quan']?>
											В работе
											<?break;
										case 'Нет в наличии':
											$summ = 0?>
											<span class="status-col status-block status-return">Нет в наличии</span>
											<?break;
										case 'На отправке':
											$summ = $order['arrived'] * $order['price'];?>
											На отправке
											<?if ($order['issued'] < $order['arrived'] && $order['issued'] < $order['ordered'] && $order['ordered'] < $order['quan']){?>
												- <?=$order['arrived'] - $order['issued']?> шт.
											<?}
											if ($order['issued'] && $order['issued'] < $order['arrived']){?>
												<span class="status-col status-delivery status-block">Выдано - <?=$order['arrived'] - $order['issued']?> шт.</span>
											<?}
											break;
										case 'Отменен':
											$summ = 0;?>
											Отменен
											<?break;
										case 'Приостановлено':
											$summ = $order['quan'] * $order['price']?>
											Приостановлено
											<?break;
										case 'Пришло':
											$summ = $order['arrived'] * $order['price']?>
											Пришло
											<?if (!$order['issued'] && ($order['arrived'] < $order['ordered'] || $order['ordered'] < $order['quan'])){?>
												- <?=$order['arrived']?> шт.
											<?}
											if ($order['issued'] && $order['issued'] < $order['arrived']){?>
												- <?=$order['arrived'] - $order['issued']?> шт.
												<span class="status-col status-delivery status-block">Выдано - <?=$order['issued']?> шт.</span>
											<?}
											break;
									}
									if ($order['arrived'] && $order['arrived'] < $order['ordered'] && !$order['declined']){?>
										<span class="status-block status-expecting">Ожидается - <?=$order['ordered'] - $order['arrived']?> шт.</span>
									<?}
									if (
											(
												$order['ordered'] && 
												$order['ordered'] < $order['quan']
											) ||
											(
												$order['declined'] &&
												$order['arrived'] < $order['ordered']
											)
										){
										$declined = !$order['declined'] ? $order['quan'] - $order['ordered'] : $order['quan'] - $order['arrived'];?>
										<span class="status-col status-block status-refused">Отказ - <?=$declined?> шт.</span>
									<?}
									?>
								</span>
							</strong> <br>
							Сумма: <strong><span class="price_format">
								<?$total += $summ?>
								<?=$summ?>
							</span> <i class="fa fa-rub" aria-hidden="true"></i></strong><br>
							<?if (!$blocked){?>
								Комментарий: 
								<div class="comment-block">
									<textarea class="comment_textarea" placeholder="Напишите Ваш комментарий"><?=$order['comment']?></textarea>
								</div>
							<?}?>
						</td>
					</tr>
				<?}
			}?>
		</table>
	<?}?>
	<p class="total">
		Итого:
		<span>
			<span style="padding: 0" id="basket_basket" class="price_format"><?=$total?></span>
			<i class="fa fa-rub" aria-hidden="true"></i>
		</span>
	</p>
	<?if (isset($_GET['act']) && $_GET['act'] == 'edit'){?>
		<a class="button" style="float: left;margin-top:20px" href="/orders">Готово</a>
	<?}
	else{?>
		<a class="button" style="float: left;margin-top:20px" href="/order/<?=$_GET['id']?>/edit">Редактировать</a>
	<?}?>
</div>
