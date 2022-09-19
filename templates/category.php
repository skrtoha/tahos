<?

use core\Breadcrumb;
use core\Item;
$href = $_GET['href'];
$category = $db->select('categories', '*', "`parent_id`=0 AND `href`='$href'");
$category = $category[0];
$category_id = $category['id'];
$subs = $db->select('categories', '*', "`parent_id`=$category_id", 'pos', true);


if (!$_GET['sub']){
    $title = $category['title'];
    Breadcrumb::add("/$view/{$_GET['href']}", $title);
}
else{
    Breadcrumb::add("/$view/{$_GET['href']}", $category['title']);
	if (count($subs)){
		foreach ($subs as $key => $value){
			if($value['href'] == $_GET['sub']){
				$sub_id = $value['id'];
				$title = $value['title'];
                Breadcrumb::add("/$view/{$_GET['href']}/{$_GET['sub']}", $title);
			}
		}
	}

	$params = ['view' => 'mosaic-view'];
	$params['comparing'] = isset($_GET['comparing']) && $_GET['comparing'] == 'on';
	if (isset($_GET['search'])) $params['search'] = $_GET['search'];
	if (isset($_GET['fv'])) $params['fv'] = $_GET['fv'];
	/*if (isset($_GET['fv'])){
		$params['fv'] = array();
		foreach($_GET['fv'] as $filter_id => $fv_ids){
			foreach($fv_ids as $fv) $params['fv'][] = $fv;
		}
	} */
	if (isset($_GET['sliders'])) $params['sliders'] = $_GET['sliders'];
	$params['perPage'] = $_GET['perPage'] ? $_GET['perPage'] : 20;
	$params['pageNumber'] = $_GET['pageNumber'] ? $_GET['pageNumber'] : 1;
	
	// debug($_GET);
	// debug($params);

	$items = core\Item::getItemsByCategoryID($sub_id, $params);
	$filtersInitial = core\Filter::getFilterValuesByCategoryID($sub_id);

	//добавление информации о том, выбрана ли позиция
	if (!empty($filtersInitial) && !empty($params['fv'])){
		
		//если режим сравнения не включен, то нам не нужно проверять какие фильтры доступны
		if (!$params['comparing']){
			$filtersApplied = core\Filter::getFilterValuesByCategoryID($sub_id, $params);
		}

		foreach($filtersInitial as $title => $filter){
			foreach($filter['filter_values'] as $fv_id => $fv){
				if (core\Filter::isSelectedFilterValue($fv['id'], $params['fv'])){
					$filtersInitial[$title]['filter_values'][$fv_id]['added'] = 'added';
					$filtersInitial[$title]['filter_values'][$fv_id]['disabled'] = 'disabled';
				}
				else {
					$filtersInitial[$title]['filter_values'][$fv_id]['added'] = '';
					$filtersInitial[$title]['filter_values'][$fv_id]['disabled'] = '';
				}
				if (!$params['comparing']){
					if (!isset($filtersApplied[$title]['filter_values'][$fv_id])){
						$filtersInitial[$title]['filter_values'][$fv_id]['disabled'] = 'disabled';
					}
				}
			}
		}
	}

	//проверка нужно ли блокировать, если выбрано единственное значение
	if (!$params['comparing']){
		foreach($filtersInitial as $title => $filter){
			$filtersInitial[$title]['hidden'] = 'hidden';
			foreach($filter['filter_values'] as $fv_id => $fv){
				if (!$fv['added'] && !$fv['disabled']){
					$filtersInitial[$title]['hidden'] = '';
				}
				if ($filter['slider']){
					$filtersInitial[$title]['from'] = $filtersApplied[$title]['min'];
					$filtersInitial[$title]['to'] = $filtersApplied[$title]['max'];
				}
			}
		}
	}
}
Breadcrumb::out();
// debug($subs);
?>
<div class="catalogue catalogue-filter">
	<input type="hidden" name="href" value="<?=$_GET['href']?>">
	<input type="hidden" name="sub" value="<?=$_GET['sub']?>">
	<div class="filter-form">
		<h3><?=$category['title']?></h3>
		<form id="filter" action="#" method="post">
			<input type="hidden" name="category_id" value="<?=$sub_id?>">
			<?if (isset($filtersInitial) && count($filtersInitial)){?>
				<div class="input-wrap">
					<label for="comparing">
						Режим сравнения
					</label>
					<label class="switch">
						<input name="comparing" id="comparing" type="checkbox" <?=isset($_GET['comparing']) && $_GET['comparing'] ? 'checked' : ''?>>
						<div class="slider round"></div>
					</label>
				</div>
			<?}?>
			<?if (count($subs)){?>
				<div class="search-wrap">
					<input name="search" id="search" type="text" placeholder="Поиск по наименованию">
					<div class="search-icon"></div>
				</div>
				<p>Выберите параметры фильтра:</p>
				<div class="input_box clearfix">
					<div class="input">
						<div class="select">
							<select class="subcategory" data-placeholder="<?=$category['title']?>" id="parameter">
								<option selected></option>
								<?foreach($subs as $value){
									$sel = $value['href'] == $_GET['sub'] ? 'selected' : '';?>
									<option <?=$sel?> value="<?=$_GET['href']?>/<?=$value['href']?>"><?=$value['title']?></option>
								<?}?>
							</select>
						</div>
					</div>
				</div>
			<?}
			if (isset($filtersInitial) && count($filtersInitial)){
				foreach ($filtersInitial as $filter){
					if (!$filter['slider']){?>
						<div class="input_box clearfix">
							<div class="input">
								<div class="select">
									<select filter_id="<?=$filter['id']?>" class="filter <?=$filter['hidden']?>" data-placeholder="<?=$filter['title']?>">
										<option selected></option>
										<?if (!empty($filter['filter_values'])){
											$checked = [];
											foreach($filter['filter_values'] as $value){
												if ($value['added']) $checked[] = [
													'id' => $value['id'],
													'title' => $value['title'],
													'added' => $value['added'],
													'disabled' => $value['disabled'],
													'filter_id' => $filter['id']
												]?>
												<option class="<?=$value['added']?>" <?=$value['disabled']?> value="<?=$value['id']?>"><?=$value['title']?></option>
											<?}
										}?>
									</select>
								</div>
							</div>
							<div class="selected">
								<?foreach($checked as $value){?>
									<label class="filter_value">
										<input type="hidden" name="fv[<?=$value['filter_id']?>][]" value="<?=$value['id']?>">
										<?=$value['title']?> 
										 <span class="icon-cross1"></span>
									</label>
								<?}?>
							</div>
						</div>
					<?}
					else{?>
						<div class="input_box volume_input clearfix">
							<p><?=$filter['title']?></p>
							<div class="input">
								<input class="slider" from="<?=$filter['from']?>" to="<?=$filter['to']?>" min="<?=$filter['min']?>" max="<?=$filter['max']?>" type="text" freak="sliders[<?=$filter['id']?>]">
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
			<?$active = !isset($_GET['sort']) || $_GET['sort'] == 'title_full' ? 'active' : '';
			$direction = $_GET['direction'] == 'desc' ? 'desc' : '';?>
			<a sort="title_full" class="<?=$active?> <?=$direction?>" href="#">Наименование</a>
			<?if ($_GET['sub']){?>
				<?$active = isset($_GET['sort']) && $_GET['sort'] == 'price' ? 'active' : '';?>
				<a sort="price"  class="<?=$active?> <?=$direction?>" href="#">Цена</a>
				<?$active = isset($_GET['sort']) && $_GET['sort'] == 'rating' ? 'active' : '';?>
				<a sort="rating" class="<?=$active?> <?=$direction?>" href="#">Рейтинг</a>
			<?}?>
			<?if ($_GET['sub']){?>
				<div id="perPage">
					<span>Отображать по:</span>
					<?foreach(core\Config::$categoryPerPage as $page){?>
						<a class="perPage <?=$params['perPage'] == $page ? 'checked' : ''?>" href=""><?=$page?></a>
					<?}?>
				</div>
			<?}?>
			<div class="view-switchs">
				<div class="view-switch mosaic-view-switch <?=!isset($_GET['viewTab']) || $_GET['viewTab'] == 'mosaic-view' ? 'active' : ''?>" id="mosaic-view">
					<img src="/img/icons/option-panel_mosaic_view.png" alt="Мозайкой">
				</div>
				<div class="view-switch list-view-switch <?=$_GET['viewTab'] == 'list-view' ? 'active' : ''?>" id="list-view">
					<img src="/img/icons/option-panel_list-view.png" alt="Списком">
				</div>
			</div>
		</div>
		<div class="content">
			<input type="hidden" name="pageNumber" value="<?=$_GET['pageNumber'] ? $_GET['pageNumber'] : 1?>">
			<div class="mosaic-view <?=!isset($_GET['viewTab']) || $_GET['viewTab'] == 'mosaic-view' ? '' : 'hidden'?>">
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
				}?>
				<div class="goods"></div>
				<div class="pagination-container"></div>
			</div>
			<div class="list-view <?=$_GET['viewTab'] == 'list-view' ? '' : 'hidden'?>">
                <?if (!$_GET['sub']){?>
                    <div>
                        <?foreach($subs as $value){?>
                            <a href="<?=$href?>/<?=$value['href']?>"><?=$value['title']?></a>
                        <?}?>
                    </div>
				<?}
				else{?>
					<table class="goods"></table>
					<div class="pagination-container"></div>
			    <?}?>
			</div>
		</div>
	</div>
</div>
<div id="mgn_popup" class="product-popup mfp-hide"></div>
<div class="popup-gallery"></div>
<?require_once ($_SERVER['DOCUMENT_ROOT'].'/vendor/blueimp/template.php');?>