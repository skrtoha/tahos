<?php  
require_once ("../core/DataBase.php");
require_once ("../core/functions.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

session_start();
$basket = $db->select('basket', '*', '`user_id`='.$_SESSION['user'])?>
<table class="cart-popup-table">
	<tr>
		<th>Наименование</th>
		<th>Кол-во</th>
		<th>Цена</th>
		<th><img id="basket_clear" src="/img/icons/icon_trash.png" alt="Удалить"></th>
	</tr>
	<?$total_basket = 0;
	$total_quan = 0;
	foreach ($basket as $val) {
		$provider_item = $db->select("providers_items", "item_id", "`id`=".$val['provider_item']);
		$item = $db->select('items', "title_full,brend_id,article", '`id`='.$provider_item[0]['item_id']);
		$total_basket += $val['price'] * $val['quan'];
		$total_quan += $val['quan'];
		$brend = $db->getFieldOnID('brends', $item[0]['brend_id'], 'title');?>
		<tr>
			<td><?=$brend?> <a class="articul" href="<?=core\Item::getHrefArticle($item[0]['article'])?>"><?=$item[0]['article']?></a> <?=$item[0]['title_full']?></td>
			<td basket_id=<?=$val['id']?>><?=$val['quan']?> шт.</td>
			<td><?=$val['price'] * $val['quan']?><?=$db->getFieldOnID('currencies', 1, 'designation')?></td>
			<td><span division="<?=$val['price']?>" provider_item="<?=$provider_item[0]['id']?>" quan="<?=$val['quan']?>" item_id="<?=$val['id']?>" class="delete-btn"><i class="fa fa-times" aria-hidden="true"></i></span></td>
		</tr>
	<?}?>
	<tr>
		<th>Итого</th>
		<th><span id="total_quan"><?=$total_quan?></span>&nbsp;шт.</th>
		<th colspan="2"><span id="total_basket"><?=$total_basket?></span><i class="fa fa-rub" aria-hidden="true"></i></th>
	</tr>
</table>
<button>Перейти в корзину</button>
	