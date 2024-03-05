<?
/** @var $db Database */

require_once($_SERVER['DOCUMENT_ROOT'].'/admin/functions/orders.function.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/admin/functions/sendings.function.php');

use core\Database;
use core\OrderValue;
use core\Payment\Paykeeper;

if (isset($_GET['act']) && $_GET['act'] == 'edit') $title = 'Редактирование заказа';
else $title = "Просмотр заказа";
$res_orders_values = OrderValue::get(['order_id' => $_GET['id']], '');
$orderInfo = OrderValue::getOrderInfo($_GET['id'], '');
$editOrderInfo = $orderInfo['is_suspended'] && isset($_GET['act']) && $_GET['act'] == 'edit' ? true : false;
$total = 0;
$status_classes = [ 
	'Отменен' => 'status-return',
	'Ожидает' => 'status-stoped',
	'Завершен' => 'status-delivery',
	'В работе' => 'status-sended'
];?>

<h1><?=$title?></h1>
<div class="clearfix"></div>
<div class="orders">
    <?if ($orderInfo['pay_type'] == 'Онлайн'){
        $paykeeperInvoice = Database::getInstance()->select_one('order_paykeeper_invoice', '*', "`order_id` = {$orderInfo['id']}");
        if (!$paykeeperInvoice['payed']){?>
            <p>
                <a class="button pay-order" href="<?= Paykeeper::getLinkPay($paykeeperInvoice['invoice_id'])?>">Оплатить заказ</a>
            </p>
        <?}?>
    <?}?>

    <?if ($orderInfo['date_issue'] && $orderInfo['delivery']){?>
        <h3>Информация</h3>
        <form id="orderInfo" style="margin-bottom: 20px">
            <table class="mobile_view"  cellspacing="1">
                <thead>
                    <tr class="head">
                        <th>Тип оплаты</th>
                        <th>Доставка</th>
                        <th>Дата отгрузки</th>
                        <th>Адрес</th>
                        <th>Весь заказ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td label="Тип оплаты">
                            <?if ($editOrderInfo){?>
                                <select name="pay_type">
                                    <?$selected = $orderInfo['pay_type'] == 'Наличный' ? 'selected' : ''?>
                                    <option <?=$selected?> value="Наличный">Наличный</option>
                                    <?$selected = $orderInfo['pay_type'] == 'Безналичный' ? 'selected' : ''?>
                                    <option <?=$selected?> value="Безналичный">Безналичный</option>
                                </select>
                            <?}
                            else{?>
                                <?=$orderInfo['pay_type']?>
                            <?}?>
                        </td>
                        <td label="Доставка">
                            <?if ($editOrderInfo){?>
                                <select name="delivery">
                                    <?$selected = $orderInfo['delivery'] == 'Доставка' ? 'selected' : ''?>
                                    <option <?=$selected?> value="Доставка">Доставка</option>
                                    <?$selected = $orderInfo['delivery'] == 'Самовывоз' ? 'selected' : ''?>
                                    <option <?=$selected?> value="Самовывоз">Самовывоз</option>
                                </select>
                            <?}
                            else{?>
                                <?=$orderInfo['delivery']?>
                            <?}?>
                        </td>
                        <td label="Дата отгрузки">
                            <?$dateTimeObject = DateTime::createFromFormat('Y-m-d 00:00:00', $orderInfo['date_issue']);
                            if ($editOrderInfo){
                                $end = clone $dateTimeObject;
                                if ($orderInfo['minDelivery']){
                                    $begin = $dateTimeObject->add(new DateInterval("P{$orderInfo['min_delivery']}D"));
                                }
                                else $begin = $dateTimeObject;
                                $end = $end->add(new DateInterval("P{$orderInfo['max_delivery']}D"));
                                ?>
                                <input type="hidden" id="min_date" value="<?=$begin->format('d.m.Y')?>">
                                <input type="hidden" id="max_date" value="<?=$end->format('d.m.Y')?>">
                                <div class="date-wrap">
                                    <input type="text" class="data-pic-beg" name="date_issue" value="<?=$dateTimeObject->format('d.m.Y');?>">
                                    <div class="calendar-icon"></div>
                                </div>
                            <?}
                            else{?>
                                <?=$dateTimeObject->format('d.m.Y');?>
                            <?}?>
                        </td>
                        <td label="Адрес">
                            <?if ($editOrderInfo){
                                $addresses = $db->select('user_addresses', '*', "`user_id` = {$_SESSION['user']}");
                                $hidden = $orderInfo['delivery'] == 'Самовывоз' ? 'hidden' : ''; ?>
                                <select name="address_id" class="<?=$hidden?>">
                                    <?if (!empty($addresses)){
                                        foreach($addresses as $row){
                                            $selected = $row['id'] == $orderInfo['address_id'] ? 'selected' : ''?>
                                            <option value="<?=$row['id']?>" <?=$selected?>>
                                                <?=\core\UserAddress::getString(
                                                    $row['id'],
                                                    json_decode($row['json'], true)
                                                )?>
                                            </option>
                                        <?}?>
                                    <?}?>
                                </select>
                                <?$hidden = $orderInfo['delivery'] == 'Доставка' ? 'hidden' : '';
                                $issueAddress = $db->getFieldOnID('issues', $orderInfo['user_issue'], 'adres');?>
                                <span id="user_issue" class="<?=$hidden?>">
                                    <?=$issueAddress?>
                                </span>
                            <?}
                            else{
                                if ($orderInfo['delivery'] == 'Доставка'){?>
                                    <?=\core\UserAddress::getString(
                                        $orderInfo['address_id'],
                                        json_decode($orderInfo['json'], true)
                                    )?>
                                <?}
                                else{?>
                                    <?=$db->getFieldOnID('issues', $orderInfo['user_issue'], 'adres');?>
                                <?}?>
                            <?}?>
                        </td>
                        <td label="Весь заказ">
                            <?if ($editOrderInfo){
                                $checked = $orderInfo['entire_order'] == 1 ? 'checked' : ''; ?>
                                <input <?=$checked?> type="checkbox" name="entire_order" value="1">
                            <?}
                            else{?>
                                <?=$orderInfo['entire_order'] == 1 ? 'Да' : 'Нет'?>
                            <?}?>
                        </td>
                    </tr>
                </tbody>

            </table>
        </form>
    <?}?>
	<?if ($device == 'desktop' || $device == 'tablet'){?>
        <h3>Товары</h3>
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
                        <td <?=$order['noReturn']?>>
                            <a href="" store_id="<?=$order['store_id']?>"><?=$order['cipher']?></a>
                        </td>
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
                            case 'Возврат':
                                $summ = 0;
                                ?>
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
                                default:?>
                                    <?=$summ?>
                                    <i class="fa fa-rub" aria-hidden="true"></i>
                                <?}?>
                        </span>
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
							<b class="brend_info" brend_id="<?=$order['brend_id']?>"><?=$order['brend']?></b>
                            <br>
                            <a href="<?=core\Item::getHrefArticle($order['article'])?>" class="articul">
                                <?=$order['article']?>
                            </a>
                            <br>
                            <?=$order['title_full']?>
                            <br><br>
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
		<a class="button edit" style="float: left;margin-top:20px" href="/orders">Готово</a>
	<?}
	else{?>
		<a class="button" style="float: left;margin-top:20px" href="/order/<?=$_GET['id']?>/edit">Редактировать</a>
	<?}?>
</div>
