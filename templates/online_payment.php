<?php
$orderInfo = \core\OrderValue::getOrderInfo($_GET['order_id']);
$prices = explode(',', $orderInfo['prices']);
$quans = explode(',', $orderInfo['quans']);
$sum = 0;
$count = count($prices);
for($i = 0; $i < $count; $i++){
    $sum += $prices[$i] * $quans[$i];
}
?>
<form id="online_payment" method="POST" action="https://yoomoney.ru/quickpay/confirm.xml">
    <input type="hidden" name="receiver" value="410013982328385">
    <input type="hidden" name="formcomment" value="<?=$orderInfo['fio']?>">
    <input type="hidden" name="short-dest" value="">
    <input type="hidden" name="label" value="account:<?=$orderInfo['user_id']?>">
    <input type="hidden" name="quickpay-form" value="donate">
    <input type="hidden" name="targets" value="Оплата заказа №<?=$_GET['order_id']?>">
    <input type="hidden" name="comment" value="Оплата заказа №<?=$_GET['order_id']?>">
    <input type="hidden" name="need-fio" value="false">
    <input type="hidden" name="need-email" value="false">
    <input type="hidden" name="need-phone" value="false">
    <input type="hidden" name="need-address" value="false">
    <input type="hidden" name="successURL" value="http://tahos.ru/orders">
    <input type="hidden" name="sum" value="<?=$sum?>" required pattern="[0-9]+">
</form>
<script>
    $(function (){
        $('#online_payment').submit();
    })
</script>
