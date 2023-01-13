<?php
class User{
	public static function getHtmlActions($user_id){
		ob_start();?>
		<a href="?view=users&act=funds&id=<?=$user_id?>">Движение средств</a>
		<a href="?view=orders&act=user_orders&id=<?=$user_id?>">Заказы</a>
		<a href="?view=users&act=basket&id=<?=$user_id?>">Корзина</a>
		<a href="?view=correspond&user_id=<?=$user_id?>">Написать сообщение</a>
		<a href="?view=order_issues&user_id=<?=$user_id?>">На выдачу</a>
		<a href="?view=order_issues&user_id=<?=$user_id?>&issued=1">Выданные</a>
		<a href="?view=users&id=<?=$user_id?>&act=search_history">История поиска</a>
		<a href="?view=users&id=<?=$user_id?>&act=basket">Товары в корзине</a>
		<a class="return_money" href="#">Вернуть средства</a>
		<a href="?view=users&id=<?=$user_id?>&act=delete" class="delete_item">Удалить</a>
		<div style="width: 100%; height: 10px"></div>
		<?
		return ob_get_clean();
	}
}