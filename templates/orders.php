<?

use core\Breadcrumb;

require_once('admin/functions/orders.function.php');
require_once('admin/functions/sendings.function.php');
$title = 'Заказы';
$days = 30;
$end = date("d.m.Y", time());
$begin = date("d.m.Y", (time() - 60*60*24*$days));


if (!isset($_GET['tab'])){
	$params = [
		'begin' => $begin, 
		'end' => $end,
		'period' => 'custom',
		'status_id' => [
			'1' => 1,
			'2' => 1,
			'3' => 1,
			'5' => 1,
			'6' => 1,
			'7' => 1,
			'8' => 1,
			'11' => 1,
            '12' => 1
		]
	];
} 
else{
	foreach($_GET as $key => $value){
		switch($key){
			case 'status_id':
				foreach($_GET['status_id'] as $key => $value) $statuses[$value] = 1;
				$params['status_id'] = $statuses;
				break;
			case 'status':
				foreach($_GET['status'] as $key => $value) $statuses[$value] = 1;
				$params['status'] = $statuses;
				break;
			default:
				$params[$key] = $value;
		}
	}
} 
// debug($params);
if ($_SESSION['user']){
	$res_orders_values = get_orders($params, '');
	$sendings = new Sendings($_SESSION['user'], $db);
	$sendings = $sendings->getSendings();
} 
else $orders_values = array();
$status_classes = [ 
	'Отменен' => 'status-return',
	'Ожидает' => 'status-stoped',
	'Завершен' => 'status-delivery',
	'В работе' => 'status-sended'
];
$orders = get_order_group($params, '');
Breadcrumb::add('/orders', 'Заказы');
Breadcrumb::out();
?>
<h1>Заказы</h1>
<?if ($user['delivery_type'] == 'Доставка'){?>
	<a href="/sending"><button class="order-button">Сформировать доставку</button></a>
<?}?>
<div class="clearfix"></div>
<div class="ionTabs" id="orders_tabs" data-name="orders">
	<ul class="ionTabs__head">
		<li class="ionTabs__tab" data-target="common">Общий список</li>
		<li class="ionTabs__tab" data-target="group">По заказам</li>
		<li class="ionTabs__tab" data-target="returns">Возвраты</li>
		<li class="ionTabs__tab" data-target="sendings">Доставки</li>
	</ul>
	<div class="ionTabs__body">
		<div class="ionTabs__item" data-name="common">
			<form action="/index.php" class="order-filter-form" method="get">
				<input type="hidden" name="view" value="orders">
				<input type="hidden" name="tab" value="common">
				<div class="search-wrap">
					<input type="text" placeholder="Поиск по артикулу" value="<?=$params['text']?>" name="text" >
					<div class="search-icon"></div>
				</div>
				<div class="checkbox-wrap">
					<input type="radio" <?=$params['period'] == 'all' ? 'checked' : ''?> name="period" id="order-filter-period-all" value="all">
					<label for="order-filter-period-all">за все время</label>
					<input type="radio" name="period" id="order-filter-period-selected" <?=$params['period'] != 'all' ? 'checked' : ''?> value="custom">
					<label for="order-filter-period-selected">за период: </label>
				</div>
				<div class="date-wrap">
					<?$disabled = $params['period'] == 'all' ? 'disabled' : ''?>
					<input type="text" class="data-pic-beg" name="begin" value="<?=$params['begin']?>" <?=$disabled?>>
					<div class="calendar-icon"></div>
				</div>
				<span> - </span>
				<div class="date-wrap"> 
						<input type="text" class="data-pic-end" name="end" value="<?=$params['end']?>" <?=$disabled?>>
						<div class="calendar-icon"></div>
					</div>
				<div class="status">
					<div class="pseudo-select">
						Статус
					</div>
					<div class="status-form">
					<div class="status-wrap clearfix">
						<p class="label">Выдано</p>
						<label class="switch">
							<input type="checkbox" id="ussued"  <?=isset($params['status_id'][1]) ? 'checked' : ''?> name="status_id[]" value="1">
							<div class="slider round <?=isset($params['status_id'][1]) ? 'checked' : ''?>"></div>
						</label>
					</div>
					<div class="status-wrap clearfix">
						<p class="label">Возврат</p>
						<label class="switch">
							<input type="checkbox" id="return_v"  <?=isset($params['status_id'][2]) ? 'checked' : ''?> name="status_id[]" value="2">
							<div class="slider round <?=isset($params['status_id'][2]) ? 'checked' : ''?>"></div>
						</label>
					</div>
					<div class="status-wrap clearfix">
						<p class="label">Заказано</p>
						<label class="switch">
							<?$checked = $params['ordered'] || empty($_POST) ? 'checked' : ''?>
							<input type="checkbox" id="ordered"  <?=isset($params['status_id'][11]) ? 'checked' : ''?> name="status_id[]" value="11">
							<div class="slider round <?=isset($params['status_id'][11]) ? 'checked' : ''?>"></div>
						</label>
					</div>
					<div class="status-wrap clearfix">
						<p class="label">Пришло</p>
						<label class="switch">
							<?$checked = $params['came'] || empty($_POST) ? 'checked' : ''?>
							<input type="checkbox"  id="came" <?=isset($params['status_id'][3]) ? 'checked' : ''?> name="status_id[]" value="3">
							<div class="slider round <?=isset($params['status_id'][3]) ? 'checked' : ''?>"></div>
						</label>
					</div>
					<div class="status-wrap clearfix">
						<p class="label">Приостановлено</p>
						<label class="switch">
							<input type="checkbox" id="suspended" <?=isset($params['status_id'][5]) ? 'checked' : ''?> name="status_id[]" value="5">
							<div class="slider round <?=isset($params['status_id'][5]) ? 'checked' : ''?>"></div>
						</label>
					</div>
					<div class="status-wrap clearfix">
						<p class="label">Нет в наличии</p>
						<label class="switch">
							<input type="checkbox" id="unvaliable" <?=isset($params['status_id'][6]) ? 'checked' : ''?> name="status_id[]" value="6">
							<div class="slider round <?=isset($params['status_id'][6]) ? 'checked' : ''?>"></div>
						</label>
					</div>
					<div class="status-wrap clearfix">
						<p class="label">В работе</p>
						<label class="switch">
							<?$checked = $params['in_work'] || empty($_POST) ? 'checked' : ''?>
							<input type="checkbox" id="in_work" <?=isset($params['status_id'][7]) ? 'checked' : ''?> name="status_id[]" value="7">
							<div class="slider round <?=isset($params['status_id'][7]) ? 'checked' : ''?>"></div>
						</label>
					</div>
					<div class="status-wrap clearfix">
						<p class="label">Отменен</p>
						<label class="switch">
							<?$checked = $params['canceled'] || empty($_POST) ? 'checked' : ''?>
							<input type="checkbox" id="canceled" <?=isset($params['status_id'][8]) ? 'checked' : ''?> name="status_id[]" value="8">
							<div class="slider round <?=isset($params['status_id'][8]) ? 'checked' : ''?>"></div>
						</label>
					</div>
				</div>
				</div>
				<button id="apply">Применить</button>
			</form>
			<div class="orders">
				<?if ($device == 'desktop' || $device == 'tablet'){?>
					<table class="orders-table">
						<tr>
							<th>Наименование</th>
							<th>Поставщик</th>
							<?if ($device == 'desktop'){?>
								<th>Дата</th>
							<?}?>
							<th>Срок</th>
							<th>Кол-во</th>
							<th>Статус</th>
							<th>Сумма</th>
							<th></th>
						</tr>
						<?if (!$res_orders_values->num_rows){?>
							<tr>
								<td colspan="8">Заказов не найдено</td>
							</tr>
						<?}
						else{
							while ($order = $res_orders_values->fetch_assoc()) {
								foreach($order as $key => $value){
									switch($key){
										case 'order_id':
											$o['id'] = $value;
											break;
										case 'price': 
										case 'quan':
										case 'ordered':
										case 'arrived':
										case 'issued':
										case 'returned':
										case 'status_id':
											$o[$key] .= $value.','; 
											break;
										case 'date_from': $o['date'] = $value; break;
									}
								}?>
								<tr order_id="<?=$order['order_id']?>" store_id="<?=$order['store_id']?>" item_id="<?=$order['item_id']?>">
									<td class="name-col">
										<b class="brend_info" brend_id="<?=$order['brend_id']?>"><?=$order['brend']?></b> 
										<a href="<?=core\Item::getHrefArticle($order['article'])?>" class="articul"><?=$order['article']?></a> 
										<?=$order['title']?>
									</td>
									<td <?=$order['noReturn']?>>
                                        <a href="" store_id="<?=$order['store_id']?>">
                                            <?=$order['cipher']?>
                                        </a>
                                    </td>
									<?if ($device == 'desktop'){?>
										<td style="padding-right: 10px"><?=$order['date_from']?></td>
									<?}?>
									<td><?=$order['date_to']?></td>
									<td class="quan"><?=$order['quan']?></td>
									<td>
										<?
										// debug($order)?>
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
                                            case 'Отменен клиентом':
                                                $summ = 0?>
                                                <span class="status-col status-block status-return">Отменен клиентом</span>
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
											case 'Отменен поставщиком':
												$summ = 0;?>
												Отменен поставщиком
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
										<?if ($order['ordered_return'] && $order['status_id'] != '2'){?>
											<span class="ordered_return status_return_<?=$order['return_status_id']?>">Возврат: <?=$order['ordered_return']?></span>
										<?}?>
									</td>
									<td>
										<span class="price_format">
											<?switch($order['status']){
												case 'Возврат':?>
													<span class="crossedout"><?=$order['price'] * $order['returned']?></span>
													<span class="new_price">0 <i class="fa fa-rub" aria-hidden="true"></i></span>
													<?break;
												case 'Выдано':?>
														<?if ($order['declined']){?>
															<span class="crossedout"><?=$order['ordered'] * $order['price']?></span>
															<span class="new_price"><?=$order['issued'] * $order['price']?><i class="fa fa-rub" aria-hidden="true"></i></span>
														<?}
														elseif ($order['returned']){?>
															<span class="crossedout"><?=$order['issued'] * $order['price']?></span>
															<span class="new_price"><?=$order['returned'] * $order['price']?><i class="fa fa-rub" aria-hidden="true"></i></span>
														<?}
														else{?>
															<?=$summ?>
															<i class="fa fa-rub" aria-hidden="true"></i>
														<?}?>
													<?break;
												case 'Заказано':?>
													<?if ($order['ordered'] < $order['quan']){?>
														<span class="crossedout"><?=$order['quan'] * $order['price']?></span>
														<span class="new_price"><?=$order['ordered'] * $order['price']?><i class="fa fa-rub" aria-hidden="true"></i></span>
													<?}
													else{?>
														<?=$summ?>
														<i class="fa fa-rub" aria-hidden="true"></i>
													<?}
													break;
												case 'Нет в наличии':
                                                case 'Отменен клиентом':?>
													<span class="crossedout"><?=$order['price'] * $order['quan']?></span>
													<span class="new_price">0 <i class="fa fa-rub" aria-hidden="true"></i></span>
													<?break;
												case 'Отменен':?>
													<span class="crossedout"><?=$order['price'] * $order['quan']?></span>
													<span class="new_price">0 <i class="fa fa-rub" aria-hidden="true"></i></span>
													<?break;
												case 'Отменен поставщиком':?>
													<span class="crossedout"><?=$order['price'] * $order['quan']?></span>
													<span class="new_price">0 <i class="fa fa-rub" aria-hidden="true"></i></span>
													<?break;
												default:?>
													<?=$summ?>
													<i class="fa fa-rub" aria-hidden="true"></i>
											<?}?>
										</span>
										<?if ($order['is_return_available']){?>
											<a days_from_purchase="<?=$order['days_from_purchase']?>" return_price="<?=$order['return_price']?>" packaging="<?=$order['packaging']?>" class="return" href="">Вернуть</a>
										<?}?>
										<?if (in_array($order['status_id'], [5, 7])){?>
											<a class="removeFromOrder" href="">Удалить</a>
										<?}?>
									</td>
									<td style="width: 70px; position: relative">
										<?if ($order['comment']){?>
											<i title="Комментарий" class="fa fa-pencil-square-o comment-btn" aria-hidden="true"></i>
											<div class="comment-block">
												<textarea class="comment_textarea" readonly placeholder="Комментарий отсутствует"><?=$order['comment']?></textarea>
												<a href="#" class="cancel_comment">Отменить</a>
											</div>
										<?}?>
										<?if ($order['correspond_id']) $href = "/correspond/{$order['correspond_id']}";
										else $href = "
											/correspond
											/{$order['order_id']}
											/{$order['store_id']}
											/{$order['item_id']}
										";?>
										<a href="<?=$href?>" title="Сообщение">
											<img  class="message <?=$order['message']?>" src="img/icons/message-icon-enable.png" alt="Сообщение">
										</a>
									</td>
								</tr>
							<?}
						}?>
					</table>
				<?}
				else{?>
					<table class="orders-table small-view">
						<?if (!$res_orders_values->num_rows){?>
						<td colspan="2">Заказов не найдено</td>
						<?}
						else{
							while ($order = $res_orders_values->fetch_assoc()) {
								foreach($order as $key => $value){
									switch($key){
										case 'order_id':
											$o['id'] = $value;
											break;
										case 'price': 
										case 'quan':
										case 'ordered':
										case 'arrived':
										case 'issued':
										case 'returned':
										case 'status_id':
											$o[$key] .= $value.','; 
											break;
										case 'date_from': $o['date'] = $value; break;
									}
								}?>
								<tr order_id="<?=$order['order_id']?>" store_id="<?=$order['store_id']?>" item_id="<?=$order['item_id']?>">
									<td>
										<span class="name-col">
											<b class="brend_info" brend_id="<?=$order['brend_id']?>"><?=$order['brend']?></b>
											<a href="<?=core\Item::getHrefArticle($order['article'])?>" class="articul"><?=$order['article']?></a> 
											<?=$order['title']?>
										</span>
										Поставщик: <strong <?=$order['noReturn']?> store_id="<?=$order['store_id']?>"><?=$order['cipher']?></strong> <br>
										Дата заказа: <strong><?=$order['date_from']?></strong> <br>
										Дата доставки: <strong><?=$order['date_to']?></strong> <br>
										Количество: <strong class="quan"><?=$order['quan']?></strong> <br>
										Статус: <strong><span class="status-col <?=$order['status_class']?>"><?=$order['status']?></span></strong> <br>
										Сумма: <strong><span class="price_format"><?=$order['price'] * $order['quan']?></span> <i class="fa fa-rub" aria-hidden="true"></i></strong>
										<?if ($order['is_return_available']){?>
											<a days_from_purchase="<?=$order['days_from_purchase']?>" return_price="<?=$order['return_price']?>" packaging="<?=$order['packaging']?>" class="return" href="">Вернуть</a>
										<?}?>
										<?if (in_array($order['status_id'], [5, 7])){?>
											<a class="removeFromOrder" href="">Удалить</a>
										<?}?>
									</td>
									<td>
										<?if ($order['correspond_id']) $href = "/correspond/{$order['correspond_id']}";
										else $href = "
											/correspond
											/{$order['order_id']}
											/{$order['store_id']}
											/{$order['item_id']}
										";?>
										<a href="<?=$href?>">
											<img  class="message <?=$order['message']?>" src="/img/icons/message-icon-enable.png" alt="Сообщение">
										</a>
									</td>
								</tr>
							<?}
						}?>
					</table>
				<?}?>
			</div>
		</div>
		<div class="ionTabs__item" data-name="group">
			<form action="/index.php" class="order-filter-form" method="get">
				<input type="hidden" name="view" value="orders">
				<input type="hidden" name="tab" value="group">
				<div class="checkbox-wrap">
					<input type="radio" <?=$params['period'] == 'all' ? 'checked' : ''?> name="period" id="order-filter-period-all-group" value="all">
					<label for="order-filter-period-all-group">за все время</label>
					<input type="radio" name="period" id="order-filter-period-selected-group" <?=$params['period'] != 'all' ? 'checked' : ''?> value="custom">
					<label for="order-filter-period-selected-group">за период: </label>
				</div>
				<div class="date-wrap">
					<input type="text" class="data-pic-beg" name="begin" value="<?=$params['begin']?>">
					<div class="calendar-icon"></div>
				</div>
				<span> - </span>
				<div class="date-wrap"> 
					<input type="text" class="data-pic-end" name="end" value="<?=$params['end']?>">
					<div class="calendar-icon"></div>
				</div>
				<div class="status">
					<div class="pseudo-select">
						Статус
					</div>
					<div class="status-form">
					<div class="status-wrap clearfix">
						<p class="label">В работе</p>
						<label class="switch">
							<input type="checkbox" id="ussued" <?=isset($params['status']['В работе']) || !isset($_GET['tab']) ? 'checked' : ''?> name="status[]" value="В работе">
							<div class="slider round <?=isset($params['status']['В работе']) ? 'checked' : ''?>"></div>
						</label>
					</div>
					<div class="status-wrap clearfix">
						<p class="label">Завершен</p>
						<label class="switch">
							<input type="checkbox" id="return_v"  <?=isset($params['status']['Завершен']) || !isset($_GET['tab']) ? 'checked' : ''?> name="status[]" value="Завершен">
							<div class="slider round <?=isset($params['status']['Завершен']) ? 'checked' : ''?>"></div>
						</label>
					</div>
					<div class="status-wrap clearfix">
						<p class="label">Ожидает</p>
						<label class="switch">
							<input type="checkbox" id="ordered"  <?=isset($params['status']['Ожидает']) || !isset($_GET['tab']) ? 'checked' : ''?> name="status[]" value="Ожидает">
							<div class="slider round <?=isset($params['status']['Ожидает']) ? 'checked' : ''?>"></div>
						</label>
					</div>
				</div>
				</div>
				<button id="apply">Применить</button>
			</form>
			<table class="orders-table">
				<tr>
					<th>Номер</th>
					<th>Дата заказа</th>
					<th>Статус</th>
					<th>Сумма</th>
				</tr>
				<?if (empty($orders)){?>
					<tr>
						<td colspan="4">Заказов не найдено</td>
					</tr>
				<?}
				else{
					foreach($orders as $order){?>
					<tr order_id="<?=$order['id']?>">
						<td><?=$order['id']?></td>						
						<td><?=$order['date']?></td>						
						<td><span class="status-col <?=$status_classes[$order['status']]?>"><?=$order['status']?></span></td>
						<td>
							<?=get_summ([
								'price' => preg_replace('/,$/', '', $order['price']),
								'quan' => preg_replace('/,$/', '', $order['quan']),
								'ordered' => preg_replace('/,$/', '', $order['ordered']),
								'arrived' => preg_replace('/,$/', '', $order['arrived']),
								'issued' => preg_replace('/,$/', '', $order['issued']),
								'returned' => preg_replace('/,$/', '', $order['returned'])
							])?>
							<i class="fa fa-rub" aria-hidden="true"></i>
						</td>						
					</tr>
					<?}
				}?>
			</table>
		</div>
		<div class="ionTabs__item" data-name="sendings">
			<table class="orders-table">
				<tr>
					<th>Номер</th>
					<th>Сумма</th>
					<th>Дата</th>
					<th>Статус</th>
				</tr>
				<?if (empty($sendings)){?>
					<tr>
						<td colspan="4">Доставок не найдено</td>
					</tr>
				<?}
				else{
					foreach($sendings as $sending){?>
					<tr sending_id="<?=$sending['id']?>">
						<td><?=$sending['id']?></td>						
						<td>
							<?=$sending['sum']?>
							<i class="fa fa-rub" aria-hidden="true"></i>
						</td>						
						<td><?=$sending['date']?></td>						
						<td><?=$sending['status']?></td>
					</tr>
					<?}
				}?>
			</table>
		</div>
		<div class="ionTabs__item" data-name="returns">
			<table class="orders-table mobile_view">
				<thead>
					<tr>
						<th></th>
						<th>Статус</th>
						<th>Сумма</th>
						<th></th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
		<div class="ionTabs__preloader gif"></div>
	</div>
</div>
<div id="mgn_popup" class="product-popup mfp-hide">
	<h1>Оформление возврата</h1>
	<table class="basket-table">
		<thead>
			<tr>
				<th>Наименование</th>
				<th>Причина</th>
				<th>Количество</th>
				<th>Сумма</th>
			</tr>
		</thead>
		<tbody></tbody>
	</table>
	<a class="button" href="">Оформить</a>
	<div style="clear: both"></div>
</div>
