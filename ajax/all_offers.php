<?php  
require_once ("../class/database_class.php");
require_once ("../core/functions.php");
session_start();

$db = new DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

if ($_SESSION['user']){
	$user = $db->select('users', 'currency_id,markup', '`id`='.$_SESSION['user']);
	$user = $user[0];
}
$item = $db->select('items', "*", "`id`=".$_POST['item_id']);
$providers_items = json_decode(str_replace('#', '"', $_POST['providers_items']), true);
$providers = json_decode(str_replace('#', '"', $_POST['providers']), true);
$currencies = $db->select('currencies', 'id,rate,designation', '', '', '', '', true);
if ($_POST['type'] == 'full'){?>
	<td style="padding: 20px 0 0 0;text-align:left">
		<b style="font-weight: 700"><?=$_POST['brend']?></b>
		<a href="<?=getHrefArticle($_POST['article'])?>" class="articul"><?=$_POST['article']?></a>
	</td>
	<!-- наименование с фотоаппаратом -->
	<td class="name-col" style="padding-top: 20px;text-align:left">
		<?$i = $item[0];
		if ($i['applicability'] or $i['characteristics'] or $i['foto'] or $i['full_desc']){?>
			<a href="#"><i item_id="<?=$item[0]['id']?>" class="fa fa-camera product-popup-link" aria-hidden="true"></i></a>
		<?}?>
		<?=$item[0]['title_full']?>
	</td>
	<!-- шифр поставщика -->
	<td>
		<ul>
			<?foreach ($providers_items as $provider_item) {?>
				<li><?=$providers[$provider_item['provider_id']]['cipher']?></li>
			<?}?>
		</ul>
	</td>
	<!-- в наличии -->
	<td>
		<ul>
			<?foreach ($providers_items as $provider_item) {
				$packaging = $provider_item['packaging'] != 1 ? "&nbsp;(<span>уп.&nbsp;".$provider_item['packaging']."&nbsp;шт.</span>)" : "";
				if($provider_item['in_stock'] == 0) $in_stock = "Под&nbsp;заказ";
				else {
					$in_stock = $provider_item['in_stock'] > 100 ? ">100" : $provider_item['in_stock'];
					$in_stock .= $packaging;
				}?>
				<li><?=$in_stock?></li>
			<?}?>
		</ul>
	</td>
	<!-- срок поставки -->
	<td>
		<ul>
			<?foreach ($providers_items as $provider_item) {
				//если есть в наличии, то отображаем срок поставки
				if ($provider_item['in_stock']) $delivery = $providers[$provider_item['provider_id']]['delivery'];
				//если нет, то срок под заказ
				else $delivery = $providers[$provider_item['provider_id']]['under_order']?>
				<li><?=$delivery?> дн.</li>
			<?}?>
		</ul>
	</td>
	<!-- цена -->
	<td>
		<ul>
			<?$designation = $user ? $currencies[$user['currency_id']]['designation'] : $currencies[1]['designation'];
			foreach ($providers_items as $key => $provider_item){?>
				<li>
					<?=$provider_item['final_price'].$designation?> 
				</li>
			<?}?>
		</ul>
	</td>
	<td>
		<ul class="to-cart-list">
		<?foreach ($providers_items as $provider_item) {?>
			<li>
				<i price="<?=$provider_item['basket']?>" 
					provider_item="<?=$provider_item['id']?>" 
					item_id="<?=$item[0]['id']?>" 
					basket_id="<?=$provider_item['basket_id']?>"
					packaging="<?=$provider_item['packaging']?>" class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">
					<?$count_basket = $db->select('basket', 'quan', '`user_id`='.$_SESSION['user'].' AND `provider_item`='.$provider_item['id']);
					if ($count_basket[0]['quan']){?>
						<i class="goods-counter"><?=$count_basket[0]['quan']?></i> 
					<?}?>
				</i> 
			</li>
		<?}?>
		</ul>
	</td>
<?}
else{?>
	<td style="padding-top: 0">
		<ul>
			<?foreach ($providers_items as $provider_item) {?>
				<li><?=$providers[$provider_item['provider_id']]['cipher']?></li>
			<?}?>
		</ul>
	</td>
	<td style="padding-top: 0">
		<ul>
			<?foreach ($providers_items as $provider_item) {
				$packaging = $provider_item['packaging'] != 1 ? "&nbsp;(<span>уп.&nbsp;".$provider_item['packaging']."&nbsp;шт.</span>)" : "";
				if($provider_item['in_stock'] == 0) $in_stock = "Под&nbsp;заказ";
				else {
					$in_stock = $provider_item['in_stock'] > 100 ? ">100" : $provider_item['in_stock'];
					$in_stock .= $packaging;
				}?>
				<li class="td_1"><?=$in_stock?></li>
			<?}?>
		</ul>
	</td>
	<td>
		<ul>
			<?foreach ($providers_items as $provider_item) {?>
				<li><?=$provider_item['delivery']?> дн.</li>
			<?}?>
		</ul>
	</td>
	<td>
		<ul>
			<ul>
			<?$designation = $user ? $currencies[$user['currency_id']]['designation'] : $currencies[1]['designation'];
			foreach ($providers_items as $key => $provider_item){?>
				<li>
					<?=$provider_item['final_price'].$designation?> 
				</li>
			<?}?>
			</ul>
		</ul>
	</td>
	<td>
		<ul class="to-cart-list">
			<?foreach ($providers_items as $provider_item) {?>
			<li>
				<i price="<?=$provider_item['basket']?>" 
					provider_item="<?=$provider_item['id']?>" 
					item_id="<?=$item[0]['id']?>" 
					packaging="<?=$provider_item['packaging']?>" class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">
					<?$count_basket = $db->select('basket', 'quan', '`user_id`='.$_SESSION['user'].' AND `provider_item`='.$provider_item['id']);
					if ($count_basket[0]['quan']){?>
						<i class="goods-counter"><?=$count_basket[0]['quan']?></i> 
					<?}?>
				</i> 
			</li>
		<?}?>
		</ul>
	</td>
<?}?>