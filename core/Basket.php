<?php
namespace core;
class Basket{
	public static function get($user_id, $isToOrder = false, $flag = '')
	{
		if ($isToOrder) $whereIsToOrder = "
		    AND b.isToOrder = 1
		    AND si.price IS NOT NULL
		    AND ps.cipher IS NOT NULL
        ";
		return $GLOBALS['db']->query("
			SELECT 
			b.*,
			i.article,
			i.article_cat,
			i.brend_id,
			br.title AS brend,
			IF(pb.title IS NOT NULL, pb.title, br.title) AS provider_brend,
			IF (i.title_full != '', i.title_full, i.title) AS title,
			IF (f.item_id IS NOT NULL, 1, 0) AS is_favorite,
			CASE
				WHEN aok.order_term IS NOT NULL THEN aok.order_term
				ELSE
					IF (si.in_stock = 0, ps.under_order, ps.delivery)
			END AS delivery,
			ps.cipher,
			ps.provider_id,
            ps.title AS providerStore,
			p.api_title,
			si.packaging,
			si.in_stock,
			IF (ps.noReturn, 'class=\"noReturn\" title=\"Возврат поставщику невозможен!\"', '') AS noReturn,
			CEIL(si.price * c.rate + si.price * c.rate * ps.percent / 100) AS new_price
		FROM
				#basket b
		LEFT JOIN #items i ON i.id=b.item_id
		LEFT JOIN #brends br ON br.id=i.brend_id
		LEFT JOIN #store_items si ON si.item_id=b.item_id AND si.store_id=b.store_id
		LEFT JOIN #provider_stores ps ON ps.id=si.store_id
		LEFT JOIN #providers p ON p.id = ps.provider_id
		LEFT JOIN #provider_brends pb ON pb.brend_id = i.brend_id AND pb.provider_id = ps.provider_id
		LEFT JOIN #currencies c ON c.id=ps.currency_id
		LEFT JOIN #favorites f ON f.item_id=b.item_id AND f.user_id=$user_id
		LEFT JOIN #autoeuro_order_keys aok ON aok.item_id = si.item_id AND aok.store_id = si.store_id
		WHERE b.user_id=$user_id $whereIsToOrder
		", $flag);
	}

    public static function getWithFullListOfStoreItems($user_id){
        $output = [];
        $res_basket = self::get($user_id);
        $itemIdList = [];
        foreach($res_basket as $row){
            $itemIdList[] = $row['item_id'];
            $output[$row['item_id']] = [
                'article' => $row['article'],
                'brend' => $row['brend'],
                'item_id' => $row['item_id'],
                'title_full' => addslashes($row['title']),
                'store_id' => $row['store_id'],
                'quan' => $row['quan'],
                'price' => $row['price'],
                'comment' => $row['comment']
            ];
        }

        $storeItems = [];
        $query = StoreItem::getQueryStoreItem();
        $query .= " WHERE si.item_id IN (".implode(',', $itemIdList).")";
        $result = $GLOBALS['db']->query($query);
        foreach($result as $row){
            $si = & $storeItems[$row['item_id']];
            $si[$row['store_id']] = [
                'cipher' => $row['cipher'],
                'in_stock' => $row['in_stock'],
                'packaging' => $row['packaging'],
                'price' => $row['price'],
                'withoutMarkup' => $row['priceWithoutMarkup'],
                'provider' => $row['provider']
            ];
        }

        foreach($output as $item_id => & $row){
            $row['stores'] = $storeItems[$item_id];
            foreach($storeItems[$item_id] as $store_id => $store){
                if ($store_id == $row['store_id']){
                    $row['price'] = $store['price'];
                    $row['withoutMarkup'] = $store['withoutMarkup'];
                }
            }
        }
        return $output;
    }

    public static function sendToOrder(array $user){
        /** @var Database $db */
        $db = $GLOBALS['db'];

        $res_basket = self::get($user['id'], true);

        if (!$res_basket->num_rows){
            message('Нечего отправлять!', false);
            header("Location: {$_SERVER['HTTP_REFERER']}");
            exit();
        }

        //проверяем превышение лимита
        $available = $user['bill'] - $user['reserved_funds'];
        if ($available < 0 && abs($available) > $user['credit_limit']){
            message('Превышен кредитный лимит!', false);
            header("Location: {$_SERVER['HTTP_REFERER']}");
            exit();
        }

        $additional_options = json_decode($_COOKIE['additional_options'], true);
        $dateTimeObject = \DateTime::createFromFormat('d.m.Y', $additional_options['date_issue']);

        $body = "<h1>Заказанные товары:</h1><table style='width: 100%;border-collapse: collapse;''>";

        $order_id = false;
        foreach ($res_basket as $value){
            if (!$value['isToOrder']) continue;

            //если товара уже нету в прайсах, то тоже пропускаем
            if (!$value['cipher']) continue;

            if (!$order_id){
                $insertOrder = [
                    'user_id' => $user['id'],
                    'is_draft' => 0,
                    'delivery' => $additional_options['delivery'],
                    'address_id' => $additional_options['address_id'],
                    'pay_type' => $additional_options['pay_type'],
                    'date_issue' => $dateTimeObject->format('Y-m-d'),
                    'entire_order' => $additional_options['entire_order']
                ];

                $res = $db->insert('orders', $insertOrder);
                if ($res !== true) die ("$res | $db->last_query");
                $order_id = $db->last_id();
            }

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

            $query = StoreItem::getQueryStoreItem();
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

            if ($res !== true) die("$res | $db->last_query");

            $db->delete('basket', "`user_id`={$_SESSION['user']} AND store_id = {$value['store_id']} AND `item_id` = {$value['item_id']}");
        }
        $body .= "</table>";

        if ($additional_options['vin_select']) $db->insert('item_vin', [
            'vin' => $additional_options['vin_select'],
            'item_id' => $value['item_id']
        ]);
        if ($additional_options['vin_input']) $db->insert('item_vin', [
            'vin' => $additional_options['vin_input'],
            'item_id' => $value['item_id']
        ]);

        $mailer = new Mailer(Mailer::TYPE_INFO);
        $mailer->send([
            'emails' => ['info@tahos.ru', 'skrtoha@gmail.com'],
            'subject' => 'Новый заказ на tahos.ru',
            'body' => $body
        ]);

        if ($user['isAutomaticOrder'] && User::noOverdue($user['id'])){
            header("Location: /admin/?view=orders&id=$order_id&act=allInWork&automaticOrder=1");
            exit();
        }

        if (in_array($additional_options['pay_type'], ['Онлайн'])){
            header("Location: /online_payment/$order_id");
            die();
        }
    }

    public static function getHtmlAdditionalOptions(array $user, $minDelivery, $maxDelivery){
        /** @var Database $db */
        $db = $GLOBALS['db'];

        $addresses = $db->select('user_addresses', '*', "`user_id` = {$user['id']}");
        ?>
        <div id="additional_options" class="product-popup mfp-hide">
            <h2>Дополнительные параметры заказа</h2>
            <div class="content">
                <form action="">
                    <div class="wrapper">
                        <div class="left">Выберите способ доставки</div>
                        <div class="right">
                            <label>
                                <?$checked = $user['delivery_type'] == 'Самовывоз' || empty($addresses) ? 'checked' : ''?>
                                <input type="radio" name="delivery" value="Самовывоз" <?=$checked?>>
                                <span>Самовывоз из <?=$user['issue_adres']?></span>
                            </label>
                            <?if (!empty($addresses)){?>
                                <label>
                                    <?$checked = $user['delivery_type'] == 'Доставка' ? 'checked' : ''?>
                                    <input type="radio" name="delivery" value="Доставка" <?=$checked?>>
                                    <span>Доставка в:</span>
                                </label>

                                <?$disabled = $user['delivery_type'] == 'Самовывоз' ? 'disabled' : ''; ?>
                                <select name="address_id" <?=$disabled?>>
                                    <?$counter = 0;
                                    foreach($addresses as $row){?>
                                        <option value="<?=$row['id']?>" <?=$row['is_default'] == 1 ? 'selected' : ''?>>
                                            <?=UserAddress::getString($row['id'], json_decode($row['json'], true))?>
                                        </option>
                                    <?}?>
                                </select>
                            <?}?>
                        </div>
                    </div>
                    <div class="wrapper">
                        <div class="left">Выберите способ оплаты</div>
                        <div class="right">
                            <?if ($user['user_type'] == 'entity'){?>
                                <label>
                                    <?$checked = $user['pay_type'] == 'Безналичный' ? 'checked' : ''?>
                                    <input <?=$checked?> type="radio" name="pay_type" value="Безналичный">
                                    <span>Безналичный</span>
                                </label>
                            <?}
                            else{
                                if ($user['pay_type'] == 'Безналичный') $user['pay_type'] = 'Наличный';
                            }?>
                            <label>
                                <?$checked = $user['pay_type'] == 'Наличный' ? 'checked' : ''?>
                                <input <?=$checked?> type="radio" name="pay_type" value="Наличный">
                                <span>Наличный</span>
                            </label>
                            <label>
                                <?$checked = $user['pay_type'] == 'Онлайн' ? 'checked' : ''?>
                                <input <?=$checked?> type="radio" name="pay_type" value="Онлайн">
                                <span>Онлайн оплата</span>
                            </label>
                        </div>
                    </div>
                    <div class="wrapper">
                        <div class="right">Выберите дату отгрузки</div>
                        <?
                        $dateTimeObject = new \DateTime();
                        $end = clone $dateTimeObject;

                        if ($minDelivery){
                            $begin = $dateTimeObject->add(new \DateInterval("P{$minDelivery}D"));
                        }
                        else $begin = $dateTimeObject;

                        $end = $end->add(new \DateInterval("P{$maxDelivery}D"));
                        ?>
                        <input type="hidden" name="min_date" value="<?=$begin->format('d.m.Y')?>">
                        <input type="hidden" name="max_date" value="<?=$end->format('d.m.Y')?>">
                        <div class="left">
                            <input type="text" name="date_issue" value="">
                            <div class="calendar-icon"></div>
                        </div>
                    </div>
                    <div class="wrapper">
                        <div class="right"></div>
                        <div class="left">
                            <label>
                                <input type="checkbox" name="entire_order" value="1">
                                <span>
                            Хочу получить заказ целиком
                            (заказ будет отгружен после поступления всех позиций на склад)
                        </span>
                            </label>
                        </div>
                    </div>
                    <div class="wrapper vin">
                        <? $carList = $db->select(
                            'garage',
                            'modification_id,title,owner',
                            "`user_id` = {$user['id']} AND `modification_id` NOT REGEXP '^[[:digit:]]+$'"
                        );?>
                        <a href="#">Привязать к VIN</a>
                        <div class="right">
                            <?if (!empty($carList)){?>
                                <div class="car_left">
                                    <select name="vin_select">
                                        <option value="">...выберите из гаража</option>
                                        <?foreach($carList as $car){
                                            $array = explode(',', $car['modification_id']);
                                            $title = $array[3];
                                            if ($car['title']) $title .= " - {$car['title']}";
                                            if ($car['owner']) $title .= " - {$car['owner']}";
                                            ?>
                                            <option value="<?=$array[3]?>"><?=$title?></option>
                                        <?}?>
                                        <option value="1">vin</option>
                                    </select>
                                </div>
                            <?}?>
                            <div class="car_right">
                                <input type="text" name="vin_input" placeholder="Добавить по VIN">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <a class="button" href="/basket/to_offer">Оформить заказ</a>
        </div>
    <?}

    public static function getHtmlNoReturn(){?>
        <div id="mgn_popup" class="product-popup mfp-hide">
            <b>Внимание! Следующие товары вернуть будет невозможно!</b>
            <table class="basket-table"></table>
            <a class="button" style="float: right" href="/basket/to_offer">Оформить заказ</a>
            <a class="button refuse mfp-close" style="float: left" href="#">Отказаться</a>
            <div style="clear: both"></div>
        </div>
    <?}
}
