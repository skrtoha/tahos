<?php
namespace core;
use function Matrix\add;

class Basket{
	public static function get($user_id, $isToOrder = false, $flag = ''){
        static $output;
        if ($output[$user_id]) return $output[$user_id];

		if ($isToOrder) $whereIsToOrder = "
		    AND b.isToOrder = 1
		    AND si.price IS NOT NULL
		    AND ps.cipher IS NOT NULL
        ";
        $output[$user_id] = $GLOBALS['db']->query("
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
        return $output[$user_id];
	}

    public static function getWithFullListOfStoreItems($user_id){
        $output = [];
        $res_basket = self::get($user_id);
        $itemIdList = [];
        foreach($res_basket as $row){
            $itemIdList[] = $row['item_id'];
            $output["{$row['item_id']}-{$row['store_id']}"] = [
                'article' => $row['article'],
                'brend' => $row['brend'],
                'item_id' => $row['item_id'],
                'title_full' => addslashes($row['title']),
                'store_id' => $row['store_id'],
                'quan' => $row['quan'],
                'price' => $row['price'],
                'comment' => $row['comment'],
                'isToOrder' => $row['isToOrder']
            ];
        }

        $storeItems = [];

        /** @var \mysqli_result $result */
        $result = User::get(['id' => $user_id]);
        $userInfo = $result->fetch_assoc();

        $query = StoreItem::getQueryStoreItem($userInfo['discount']);
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

        foreach($output as $value => & $row){
            $array = explode('-', $value);
            $row['stores'] = $storeItems[$array[0]];
            foreach($storeItems[$array[0]] as $store_id => $store){
                if ($store_id == $row['store_id']){
                    if ($store['price'] > $row['price']) $row['price'] = $store['price'];
                    $row['withoutMarkup'] = $store['withoutMarkup'];
                }
            }
        }
        return $output;
    }

    public static function sendToOrder(array $user){
        /** @var Database $db */
        $db = $GLOBALS['db'];

        if (strpos($_SERVER['REQUEST_URI'], 'view=users') === false){
            $additional_options = json_decode($_COOKIE['additional_options'], true);
            $dateTimeObject = \DateTime::createFromFormat('d.m.Y', $additional_options['date_issue']);
        }
        else $dateTimeObject = new \DateTime();

        $res_basket = self::get($user['id'], true);

        if (!$res_basket->num_rows){
            message('Нечего отправлять!', false);
            header("Location: {$_SERVER['HTTP_REFERER']}");
            exit();
        }

        //проверяем превышение лимита
        $limitExceeded = false;
        if ($additional_options['pay_type'] == 'Наличный' || $additional_options['pay_type'] == 'Онлайн'){
            $available = $user['bill_cash'] - $user['reserved_cash'];
            if ($available < 0 && abs($available) > $user['credit_limit_cash']) $limitExceeded = true;
            $bill_type = User::BILL_CASH;
        }
        if ($additional_options['pay_type'] == 'Безналичный'){
            $available = $user['bill_cashless'] - $user['reserved_cashless'];
            if ($available < 0 && abs($available) > $user['credit_limit_cashless']) $limitExceeded = true;
            $bill_type = User::BILL_CASHLESS;
        }
        if ($limitExceeded){
            message('Превышен кредитный лимит!', false);
            header("Location: {$_SERVER['HTTP_REFERER']}");
            exit();
        }

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
                    'entire_order' => $additional_options['entire_order'],
                    'bill_type' => $bill_type
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

            $db->delete('basket', "`user_id`={$user['id']} AND store_id = {$value['store_id']} AND `item_id` = {$value['item_id']}");
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
        return $order_id;
    }

    public static function addToBasket($params){
        /** @var Database $db */
        $db = $GLOBALS['db'];

        return $db->insert(
            'basket',
            [
                'user_id' => $params['user_id'],
                'store_id' => $params['store_id'],
                'item_id' => $params['item_id'],
                'quan' => $params['quan'],
                'price' => $params['price'],
            ],
            ['duplicate' => "`quan` = `quan` + {$params['quan']}"]
        );
    }
}
