<?$href = $_GET['href'];
$category = $db->select('categories', '*', "`parent_id`=0 AND `href`='$href'");
$category = $category[0];
$category_id = $category['id'];
$_SESSION['start'] = 0;
$subs = $db->select('categories', '*', "`parent_id`=$category_id", 'pos', true);
if (!$_GET['sub']) $title = $category['title'];
else{
	if (count($subs)){
		foreach ($subs as $key => $value){
			if($value['href'] == $_GET['sub']){
				$sub_id = $value['id'];
				$title = $value['title'];
			} 
		}
	}
	$filters = get_filters($sub_id);
	$items = category_items_without_filters($sub_id);
	$c_items = count($items);
	// echo var_dump(strpos('52', ','));
} 
?>
<div class="catalogue catalogue-filter">
	<input type="hidden" id="category_id" value="<?=$category['id']?>">
	<input type="hidden" id="sub_id" value="<?=$sub_id?>">
	<input type="hidden" id="category_href" value="<?=$category['href']?>">
	<input type="hidden" id="user_id" value="<?=$_SESSION['user']?>">
	<input type="hidden" id="filters" value="<?=str_replace('"', '#', json_encode($filters))?>">
	<input type="hidden" id="filters_on" value="">
	<?if ($c_items > 4){?>
		<div id="sub_filter"></div>
	<?}?>
	<div class="filter-form">
		<h3><?=$category['title']?></h3>
		<form action="#" method="post">
			<?if (count($subs)){?>
				<div class="search-wrap">
					<input id="search" type="text" placeholder="Поиск по наименованию">
					<div class="search-icon"></div>
				</div>
				<p>Выберите параметры фильтра:</p>
				<div class="input_box clearfix">
					<div class="input">
						<div class="select">
							<select name="sub" class="subcategory" data-placeholder="<?=$category['title']?>" id="parameter">
								<option selected></option>
								<?foreach($subs as $value){
									$sel = $value['href'] == $_GET['sub'] ? 'selected' : '';?>
									<option <?=$sel?> value="<?=$value['href']?>"><?=$value['title']?></option>
								<?}?>
							</select>
						</div>
					</div>
				</div>
			<?}
			if (isset($filters) && count($filters)){
				foreach ($filters as $id => $filter){
					if (!$filter['slider']){?>
						<div class="input_box clearfix">
							<div class="input">
								<div class="select">
									<select name="<?=$id?>" data-placeholder="<?=$filter['title']?>">
										<option selected></option>
										<?if (count($filter['filters_values'])){
											foreach($filter['filters_values'] as $k => $value){?>
												<option value="<?=$k?>"><?=$value?></option>
											<?}
										}?>
									</select>
								</div>
							</div>
						</div>
					<?}
					else{
						$min = $db->getMin('filters_values', 'title + 0', "`filter_id`=$id");
						$max = $db->getMax('filters_values', 'title + 0', "`filter_id`=$id");
						?>
						<div class="input_box volume_input clearfix">
							<p><?=$filter['title']?></p>
							<div class="input">
								<input class="slider" from="<?=$min?>" to="<?=$max?>" min="<?=$min?>" max="<?=$max?>" type="text" name="<?=$id?>">
							</div>
						</div>
					<?}?>
				<?}
			}?>
		<?if ($sub_id){?>
			<input type="reset" value="Сбросить">
		<?}?>
		</form>
	</div>
	<div class="items">
		<div class="mobile-sort-block">
			<p>
				Сортировать по: <a type="title_full" id="sort-change-mobile" href="#">Наименованию</a>
				<span id="sort-direction-mobile" class="up"></span>
			</p>
			<div class="sort-block" style="display: none;">
				<ul>
					<li><a type="price" href="#">Цене</a></li>
					<li><a type="rating" href="#">Рейтингу</a></li>
					<li><a type="title_full" href="#">Наименованию</a></li>
				</ul>
			</div>
		</div>
		<div class="option-panel">
			<a sort="title_full" class="name-sort active" href="#">Наименование</a>
			<?if ($_GET['sub']){?>
				<a sort="price" class="price-sort" href="#">Цена</a>
				<a sort="rating" class="rating-sort" href="#">Рейтинг</a>
			<?}?>
			<div class="view-switchs">
				<div class="view-switch mosaic-view-switch active" id="mosaic-view-switch">
					<img src="/img/icons/option-panel_mosaic_view.png" alt="Мозайкой">
				</div>
				<div class="view-switch list-view-switch" id="list-view-switch">
					<img src="/img/icons/option-panel_list-view.png" alt="Списком">
				</div>
			</div>
		</div>
		<div class="content">
			<div class="mosaic-view" <?=$_GET['sub'] ? "style='display: flex'" : ''?>>
				<?if (!$_GET['sub']){
					if (count($subs)){?>
						<div class="flex">
							<?foreach($subs as $value){?>
								<div class="item">
									<a href="/category/<?=$href?>/<?=$value['href']?>"><h3><?=$value['title']?></h3></a>
								</div>
							<?}?>
						</div>
					<?}
					else{?>
						<p>Подкатегорий не найдено.</p>
					<?}
				}
				else{
					if (!empty($items)){
						foreach ($items as $item){?>
						<div class="item_1 product-popup-link" item_id="<?=$item['id']?>">
							<div class="product">
								<p>
									<b class="brend_info" brend_id="<?=$item['brend_id']?>"><?=$item['brend']?></b> 
									<a href="/article/<?=$item['id']?>-<?=$item['article']?>" class="articul"><?=$item['article']?></a> 
								</p>
								<p><strong><?=$item['title_full']?></strong></p>
								<div class="pic-and-description">
									<div class="img-wrap">
										<?if ($item['foto']){?>
											<img src="<?=core\Config::$imgUrl?>/items/small/<?=$item['id']?>/<?=$item['foto']?>">
										<?}
										else{?>
											<img src="/images/no_foto.png" alt="Фото отсутствует">
										<?}?>
									</div>
									<div class="description">
										<?if (isset($item['filters_values']) && count($item['filters_values'])){
											foreach ($item['filters_values'] as $value){?>
												<p><?=$value?></p>
											<?}
										}?>
									</div>
								</div>
								<div class="clearfix"></div>
								<div class="rating no_selectable">
									<?=getHtmlRating($item['rating'])?>
								</div>
							</div>
							<div class="price-and-delivery">
								<p class="price">от <span><?=$item['price']?></span></p>
								<p class="delivery">от <?=$item['delivery']?> дн.</p>
							</div>
						</div>
					<?}
					}
					else{?>
						<div>Товаров данной подкатегории не найдено.</div>
					<?}
				}?>		
			</div>
			<div class="list-view">
				<div>
					<?if (!$_GET['sub']){
						foreach($subs as $value){?>
						<a href="<?=$href?>/<?=$value['href']?>"><?=$value['title']?></a>
					<?}?>
				</div>
				<?}
				else{?>
					<table class="wide-view">
						<tbody>
							<tr>
								<th>Название</th>
								<?if (count($filters)){
									foreach ($filters as $value){?>
										<th><?=$value['title']?></th>
									<?}	
								}?>
								<th>Рейтинг</th>
								<th>Доставка</th>
								<th>Цена</th>
							</tr>
							<?if (!empty($items)){
								foreach ($items as $item){?>
									<tr class="product-popup-link" item_id="<?=$item['id']?>">
										<td class="name-col">
											<b class="brend_info" brend_id="<?=$item['brend_id']?>"><?=$item['brend']?></b> 
											<a href="<?=core\Item::getHrefArticle($item['article'])?>" class="articul"><?=$item['article']?></a> 
											<?=$item['title_full']?>
										</td>
										<?if (count($filters)){
											foreach ($filters as $id => $filter){?>
												<td><?=$item['filters_values'][$id]?></td>
											<?}	
										}?>
										<td class="rating"><?=getHtmlRating($item['rating'])?></td>
										<td><?=$item['delivery']?></td>
										<td><?=$item['price']?></td>
									</tr>
								<?}
							}?>
						</tbody>
					</table>
			<?}?>
			</div>
		</div>
	</div>
	</div>
</div>
<div id="mgn_popup" class="product-popup mfp-hide"></div>
<div class="popup-gallery"></div>