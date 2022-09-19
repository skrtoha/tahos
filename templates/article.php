<?
use core\Breadcrumb;
use core\Provider\Autoeuro;

/** @var \core\Database $db */
$abcp = new core\Provider\Abcp($_GET['item_id'], $db);

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && core\Config::$isUseApiProviders){
    
	$abcp->render(13);
	$abcp->render(6);

	core\Provider\Impex::getData(['article' => $abcp->item['article']]);

    $mikado = new core\Provider\Mikado($db);
    $mikado->setArticle($abcp->item['brand'], $abcp->item['article']);

    $armtek = new core\Provider\Armtek($db);
    $armtek->setArticle($abcp->item['brand'], $abcp->item['article']);

    $rossko = new core\Provider\Rossko($db);
    $rossko->execute("{$abcp->item['brand']} {$abcp->item['article']}");

    $autoeuro = new Autoeuro($_GET['item_id']);
    $autoeuro->setArticle($abcp->item['brand'], $abcp->item['article']);

    core\Provider\Autokontinent::setArticle($abcp->item['brand'], $abcp->item['article'], $_GET['item_id']);

    core\Provider\ForumAuto::setArticle($_GET['item_id'], $abcp->item['brand'], $abcp->item['article']);

	core\Provider\Autopiter::setArticle($abcp->item['brand'], $abcp->item['article']);
	
	core\Provider\Berg::setArticle($abcp->item['brand'], $abcp->item['article'], $_GET['item_id']);

	exit();
}

$title = "Список предложений";

$filters = [];

$array = article_store_items($_GET['item_id'], $filters, 'articles');
$store_items = array();
foreach($array['store_items'] as $key => $value){
	$store_items[] = [
		'item_id' => $key,
		'store_item' => $value
	];
}

Breadcrumb::add(
    "/{$_GET['view']}/{$store_items[0]['item_id']}-{$store_items[0]['store_item']['article']}/noUseAPI",
    $store_items[0]['store_item']['title_full']
);
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

if (isset($user['markupSettings'])) $ms = & $user['markupSettings'];
else $ms = false;

$in_stock = $_POST['in_stock_only'] ? $_POST['in_stock_only'] : '';
Breadcrumb::out();
?>
<input type="hidden" id="item_id" value="<?=$_GET['item_id']?>">
<input type="hidden" id="price_from" value="<?=$price_from?>">
<input type="hidden" id="price_to" value="<?=$price_to?>">
<input type="hidden" id="time_from" value="<?=$time_from?>">
<input type="hidden" id="time_to" value="<?=$time_to?>">
<input type="hidden" name="isCheckedFromAbcp" value="<?=$abcp->isCheckedFromAbcp?>">
<input type="hidden" name="noUseAPI" value="<?=isset($_GET['noUseAPI']) ? 1 : 0?>">
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
		<button item_id="<?=$_GET['item_id']?>" search_type="analogies">Применить</button>
	</form>
	<div class="ionTabs" id="search-result-tabs" data-name="search-result-tabs">
		<ul class="ionTabs__head">
			<?$substitutes = $db->getCount('item_substitutes', "`item_id`={$_GET['item_id']} AND `hidden`=0");
			if ($substitutes) $substitutes = "<span>$substitutes</span>";
			else $substitutes = '';
			// debug($user);
			$res = $db->query("
				SELECT SQL_CALC_FOUND_ROWS
					COUNT(c.item_diff) 
				FROM #item_complects c
				LEFT JOIN #store_items si ON si.item_id=c.item_diff
				WHERE 
					c.item_id={$_GET['item_id']} AND si.store_id IS NOT null AND si.price>0 GROUP BY c.item_diff
				");
			$complects = $db->found_rows();
			if ($complects) $complects = "<span>$complects</span>";
			else $complects = '';
			if (!$user['show_all_analogies']) $where_hide_analogies = "
				AND si.store_id IS NOT NULL
			";?>
			<li class="ionTabs__tab" search_type="articles" data-target="Tab_1">Артикул</li>
			<?if ($substitutes){?>
				<li class="ionTabs__tab" search_type="substitutes" data-target="Tab_2">
					<span>Замены</span>
					<?=$substitutes?>
				</li>
			<?}?>
			<?if ($complects){?>
				<li class="ionTabs__tab" search_type="complects" data-target="Tab_4">
					<span></span>
					<?=$complects?>
				</li>
			<?}?>
			<li class="user_markup">
				<a class="user_markup" href="#">%</a>
				<div id="user_markup">
					<!-- <div class="background"></div> -->
					<h3>Клиентская наценка</h3>
					<form>
						<label class="markup">
							<?$checked = $ms && $ms['withUserMarkup'] == 'on' ? 'checked' : ''?>
							<input type="checkbox" name="withUserMarkup" <?=$checked?>>
							<span>показывать с наценкой</span>
						</label>
						<?$disabled = $ms && $ms['withUserMarkup'] == 'on' ? '' : 'disabled'?>
						<input type="text" name="markup" <?=$disabled?> value="<?=$ms && $ms['markup'] ? $ms['markup'] : '10'?>"> 
						<span>%</span>
						<div class="clearfix"></div>
						<label class="showType">
							<?$checked = ($ms && $ms['showType'] == 'double') || !$ms ? 'checked' : ''?>
							<input <?=$disabled?> type="radio" name="showType" value="double" <?=$checked?>>
							<span>нацененную и закупочную цены</span>
						</label>
						<label class="showType">
							<?$checked = $ms && $ms['showType'] == 'single' ? 'checked' : ''?>
							<input type="radio" <?=$disabled?> <?=$checked?> name="showType" value="single">
							<span>только нацененную цену</span>
						</label>
						<label class="showInBasket">
							<?$checked = $ms && $ms['showInBasket'] == 'on' ? 'checked' : ''?>
							<input type="checkbox" <?=$disabled?> name="showInBasket" <?=$checked?>>
							<span>показывать в корзине</span>
						</label>
						<button>Применить</button>
					</form>
				</div>
			</li>
		</ul>
		<div class="ionTabs__body">
			<div class="ionTabs__item itemInsertable" data-name="Tab_1">
				<?require_once('core/article.php');?>
			</div>
			<div class="ionTabs__item is_others itemInsertable" data-name="Tab_2">
				<table class="articul-table"></table>
				<div class="mobile-layout"></div>
			</div>
			<div class="ionTabs__item is_others itemInsertable" data-name="Tab_4">
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
		<?=Autoeuro::getParams()->mainStoreID?>,
		<?=Autoeuro::getParams()->minPriceStoreID?>,
		<?=Autoeuro::getParams()->minDeliveryStoreID?>
	];
</script>
