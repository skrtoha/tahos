<?
use core\Provider\Autoeuro;
$abcp = new core\Provider\Abcp($_GET['item_id'], $db);
$abcp->render(13); 
$abcp->render(6);

$mikado = new core\Provider\Mikado($db);
$mikado->setArticle($abcp->item['brand'], $abcp->item['article']);

$armtek = new core\Provider\Armtek($db);
$armtek->setArticle($abcp->item['brand'], $abcp->item['article']);

$rossko = new core\Provider\Rossko($db);
$rossko->execute("{$abcp->item['article']} {$abcp->item['brand']}");

core\Provider\Autoeuro::setArticle($abcp->item['brand'], $abcp->item['article'], $_GET['item_id']);

core\Provider\Autokontinent::setArticle($abcp->item['brand'], $abcp->item['article'], $_GET['item_id']);

$title = "Список предложений";

$array = article_store_items($_GET['item_id'], [], 'articles');
// debug($array);
$store_items = array();
foreach($array['store_items'] as $key => $value){
	$store_items[] = [
		'item_id' => $key,
		'store_item' => $value
	];
}
// debug($store_items);
$hide_form = true;
foreach ($store_items as $key => $value){
	if (!empty($value['list'])) $hide_form = false;
	break;
}
if (!empty($array['prices']) && !empty($array['deliveries'])){
	$price_from = min($array['prices']);	
	$price_to = max($array['prices']);
	$time_from = min($array['deliveries']);
	$time_to = max($array['deliveries']);
}
$in_stock = $_POST['in_stock_only'] ? $_POST['in_stock_only'] : '';?>
<input type="hidden" id="item_id" value="<?=$_GET['item_id']?>">
<input type="hidden" id="price_from" value="<?=$price_from?>">
<input type="hidden" id="price_to" value="<?=$price_to?>">
<input type="hidden" id="time_from" value="<?=$time_from?>">
<input type="hidden" id="time_to" value="<?=$time_to?>">
<input type="hidden" name="isCheckedFromAbcp" value="<?=$abcp->isCheckedFromAbcp?>">
<div class="search-result">
	<h1>Список предложений</h1>
	<form class="<?=$hide_form ? 'hidden' : ''?>" id="offers-filter-form" method="post">
		<input type="hidden" name="filter_form" value="1">
		<div class="price-wrap">
			<label for="price-from">Цена от</label>
			<input type="text" id="price-from" value="<?=$price_from?>" name="price_from">
			<label for="price-to">до</label>
			<input type="text" id="price-to" value="<?=$price_to?>" name="price_to">
		</div>
		<div class="time-wrap">
			<label for="time-from">Срок от</label>
			<input type="number" id="time-from" value="<?=$time_from?>" name="time_from">
			<label for="time-to">до</label>
			<input type="number" id="time-to" value="<?=$time_to?>" name="time_to">
		</div>
		<div class="instock-wrap">
			<input type="checkbox" id="in_stock_only" <?=$in_stock ? "checked" : ""?> name="in_stock_only">
			<label for="in_stock_only">Только в наличии</label>
		</div>
		<button item_id="<?=$_GET['item_id']?>" search_type="article">Применить</button>
	</form>
	<div class="ionTabs" id="search-result-tabs" data-name="search-result-tabs">
		<ul class="ionTabs__head">
			<?$substitutes = $db->getCount('substitutes', "`item_id`={$_GET['item_id']} AND `hidden`=0");
			if ($substitutes) $substitutes = "<span>$substitutes</span>";
			else $substitutes = '';
			// debug($user);
			$res = $db->query("
				SELECT SQL_CALC_FOUND_ROWS
					COUNT(c.item_diff) 
				FROM `tahos_complects` c 
				LEFT JOIN tahos_store_items si ON si.item_id=c.item_diff 
				WHERE 
					c.item_id={$_GET['item_id']} AND si.store_id IS NOT null AND si.price>0 GROUP BY c.item_diff
				");
			$complects = $db->found_rows();
			if ($complects) $complects = "<span>$complects</span>";
			else $complects = '';
			if (!$user['show_all_analogies']) $where_hide_analogies = "
				AND si.store_id IS NOT NULL
			";
			$res_analogies = $db->query("
				SELECT 
					diff.item_diff,
					si.store_id
				FROM
					#analogies diff
				LEFT JOIN #store_items si ON si.item_id=diff.item_diff
				WHERE 
					diff.item_id={$_GET['item_id']} AND
					diff.hidden=0
					$where_hide_analogies
				GROUP BY diff.item_diff
			", '');
			if (!$res_analogies->num_rows) $analogies = '';
			else $analogies = "<span>{$res_analogies->num_rows}</span>";
			?>
			<li class="ionTabs__tab" search_type="articles" data-target="Tab_1">Артикул</li>
			<?if ($substitutes){?>
				<li class="ionTabs__tab" search_type="substitutes" data-target="Tab_2">
					<span>Замены</span>
					<?=$substitutes?>
				</li>
			<?}?>
			<?if ($analogies){?>
				<li class="ionTabs__tab" search_type="analogies" data-target="Tab_3">
					<span>Аналоги</span>
					<?=$analogies?>
				</li>
			<?}?>
			<?if ($complects){?>
				<li class="ionTabs__tab" search_type="complects" data-target="Tab_4">
					<span></span>
					<?=$complects?>
				</li>
			<?}?>
		</ul>
		<div class="ionTabs__body">
			<div class="ionTabs__item" data-name="Tab_1">
				<?require_once('core/article.php');?>
			</div>
			<div class="ionTabs__item is_others" data-name="Tab_2">
				<table class="articul-table"></table>
				<div class="mobile-layout"></div>
			</div>
			<div class="ionTabs__item is_others" data-name="Tab_3">
				<table class="articul-table"></table>
				<div class="mobile-layout"></div>
			</div>
			<div class="ionTabs__item is_others" data-name="Tab_4">
				<table class="articul-table"></table>
				<div class="mobile-layout"></div>
			</div>
			<div class="ionTabs__preloader gif"></div>
		</div>
	</div>
</div>
<div id="mgn_popup" class="product-popup mfp-hide"></div>
<div class="popup-gallery"></div>
<script type="text/javascript">
	var storesAutoeuro = [
		<?=Autoeuro::$mainStoreID?>,
		<?=Autoeuro::$minPriceStoreID?>,
		<?=Autoeuro::$minDeliveryStoreID?>
	]
</script>
