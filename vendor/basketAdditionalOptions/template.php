<?php
use core\UserAddress;
use core\User;

/** @global string $user_id */

/** @var \core\Database $db */
$db = $GLOBALS['db'];

/** @var mysqli_result $res_user */
$res_user = User::get(['user_id' => $user_id]);
foreach($res_user as $value) $user = $value;
$debt = User::getDebt($user);

$res_basket = core\Basket::get($user['id']);
$noReturnIsExists = false;
$minDelivery = 1000000;
$maxDelivery = 0;
foreach ($res_basket as $key => $val) {
    if ($val['noReturn']) $noReturnIsExists = true;
    if ($val['delivery'] < $minDelivery) $minDelivery = $val['delivery'];
    if ($val['delivery'] > $maxDelivery) $maxDelivery = $val['delivery'];
}

$addresses = $db->select('user_addresses', '*', "`user_id` = {$user['id']}");
?>
<script src="/vendor/basketAdditionalOptions/script.js"></script>
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
                    <?if (in_array($user['bill_mode'], [User::BILL_MODE_CASHLESS, User::BILL_MODE_CASH_AND_CASHLESS])){?>
                        <label>
                            <?$checked = $user['pay_type'] == 'Безналичный' ? 'checked' : ''?>
                            <input <?=$checked?> type="radio" name="pay_type" value="Безналичный">
                            <span>Безналичный</span>
                        </label>
                    <?}
                    if (in_array($user['bill_mode'], [User::BILL_MODE_CASH, User::BILL_MODE_CASH_AND_CASHLESS])){?>
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
                    <?}?>
                </div>
            </div>
            <div class="wrapper">
                <div class="right">Выберите дату отгрузки</div>
                <?
                $dateTimeObject = new \DateTime();
                $end = clone $dateTimeObject;

                if ($minDelivery){
                    if ($minDelivery = -1) $minDelivery = 0;
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
<?
if ($noReturnIsExists){?>
    <div id="mgn_popup" class="product-popup mfp-hide">
        <b>Внимание! Следующие товары вернуть будет невозможно!</b>
        <table class="basket-table"></table>
        <a class="button" style="float: right" href="/basket/to_offer">Оформить заказ</a>
        <a class="button refuse mfp-close" style="float: left" href="#">Отказаться</a>
        <div style="clear: both"></div>
    </div>
<?}