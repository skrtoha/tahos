<?php

use core\Managers;
use core\User as UserCore;

class User{
	public static function getHtmlActions(array $user){
		ob_start();?>
		<a href="?view=users&act=funds&id=<?=$user['id']?>">Движение средств</a>
		<a href="?view=orders&act=user_orders&id=<?=$user['id']?>">Заказы</a>
		<a href="?view=users&act=basket&id=<?=$user['id']?>">Корзина</a>
		<a href="?view=correspond&user_id=<?=$user['id']?>">Написать сообщение</a>
		<a href="?view=order_issues&user_id=<?=$user['id']?>">На выдачу</a>
		<a href="?view=order_issues&user_id=<?=$user['id']?>&issued=1">Выданные</a>
		<a href="?view=users&id=<?=$user['id']?>&act=search_history">История поиска</a>
		<a href="?view=users&id=<?=$user['id']?>&act=basket">Товары в корзине</a>
        <?if (!Managers::isActionForbidden('Пользователи', 'Авторизация')){?>
            <a href="/admin/?view=authorization&auth=<?=$_GET['id']?>">Авторизоваться</a>
        <?}?>
        <?if (in_array($user['bill_mode'], [UserCore::BILL_CASH, UserCore::BILL_MODE_CASH_AND_CASHLESS])){?>
            <a class="return_money" href="#">Вернуть средства</a>
        <?}?>
		<a href="?view=users&id=<?=$user['id']?>&act=delete" class="delete_item">Удалить</a>
		<div style="width: 100%; height: 10px"></div>
		<?
		return ob_get_clean();
	}
}