<?$user_id = $_SESSION['user'];
$item_id = $_GET['item_id'];
$title = "Поставщики";
$item = $db->select('items', 'title,brend_id', "`id`=$item_id");
print_r($item);
$temp = json_decode($_COOKIE['offers_filter'], true);
$offers_filter = $temp['offers_filter'];
$basket = json_decode($_COOKIE['basket'], true);
if (count($basket['basket'])) foreach ($basket['basket'] as $value) $baket_new[$value['id']] = $value['quan'];?>
<div class="search-result">
	<!-- <div class="breadcrumbs">
		<a href="#">Tahos.ru</a>
		<span>Результаты запроса</span>
	</div> -->
	<h1>Список предложений</h1>
	<?if (count($item)){?>
		<form action="#" id="offers-filter-form">
		<div class="price-wrap">
			<label for="price-from">Цена от</label>
			<?$value = $offers_filter['price_from'] ? $offers_filter['price_from'] : $db->getMin('providers_items', 'price', "`item_id`=$item_id");?> 
			<input type="number" id="price-from" value="<?=$value?>">
			<label for="price-to">до</label>
			<?$value = $offers_filter['price_to'] ? $offers_filter['price_to'] : $db->getMax('providers_items', 'price', "`item_id`=$item_id");?> 
			<input type="number" id="price-to" value="<?=$value?>">
		</div>

		<div class="time-wrap">
			<label for="time-from">Срок от</label>
			<?$value = $offers_filter['time_from'] ? $offers_filter['time_from'] : $db->getMin('providers_items', 'delivery', "`item_id`=$item_id")?> 
			<input type="number" id="time-from" value="<?=$value?>">
			<label for="time-to">до</label>
			<?$value = $offers_filter['time_to'] ? $offers_filter['time_to'] : $db->getMax('providers_items', 'delivery', "`item_id`=$item_id")?> 
			<input type="number" id="time-to" value="<?=$value?>">
		</div>
		<div class="instock-wrap">
			<input type="checkbox" id="in_stock_only" <?=$offers_filter['in_stock_only'] ? "checked" : ""?>>
			<label for="in_stock_only">Только в наличии</label>
		</div>
		<button>Применить</button>
	</form>
	<?}?>
	

	<div class="ionTabs" id="search-result-tabs" data-name="search-result-tabs">
		<?$search_type = $_COOKIE['search_type'];?>
		<ul class="ionTabs__head">
			<li class="ionTabs__tab <?=$search_type == "articles" ? "ionTabs__tab_state_active" : ""?>" search_type="articles" data-target="Tab_1_name">Запрошенный артикул</li>
			<li class="ionTabs__tab <?=$search_type == "subtitutes" ? "ionTabs__tab_state_active" : ""?>" search_type="subtitutes" data-target="Tab_2_name">
				Замены
				<span><?=get_count_anal_subst($text, 'subtitutes')?></span>
			</li>
			<li class="ionTabs__tab <?=$search_type == "analogies" ? "ionTabs__tab_state_active" : ""?>" search_type="analogies" data-target="Tab_3_name">
				Аналоги
				<span><?=get_count_anal_subst($text, 'analogies')?></span>
			</li>
		</ul>
		<div class="ionTabs__body">
			<div class="ionTabs__item" data-name="Tab_1_name">
				<?if ($search_type == 'articles' or !$search_type) require_once('core/search_article.php');?>
			</div>
			<div class="ionTabs__item" data-name="Tab_2_name">
				<?if ($search_type == 'subtitutes') require_once('core/search.php');?>
			</div>
			<div class="ionTabs__item" data-name="Tab_3_name">
				<?if ($search_type == 'analogies') require_once('core/search.php');?>
			</div>
			<div class="ionTabs__preloader"></div>
		</div>
	</div>
</div>
<div id="mgn_popup" class="white-popup mfp-hide"></div>
