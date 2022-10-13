<?
// debug($_GET);
use core\Breadcrumb;
use core\Exceptions\NotFoundException;

if ($_GET['to_garage'] && $user['id'] && $_GET['modification_id']){
	$res = $db->insert(
		'garage',
		[
			'user_id' => $user['id'],
			'modification_id' => $_GET['modification_id']
		]
	);
	// echo "$db->last_query $res";
	if ($res === true){
		message("Успешно добавлено!");
		header("Location: /garage");
	}
	elseif (preg_match('/Duplicate/u', $res)){
		message('Такая модификация уже присутствует!', false);
		// $url = preg_replace('/\/to_garage\/\d+$/', '/to_garage', $_SERVER['REQUEST_URI']);
		// header("Location: $url");
	}
	else message("Произошла ошибка!", false);
}
$res_vehicels = $db->query("
	SELECT
		v.id,
		vc.title AS category,
		v.is_mosaic,
		v.title,
		v.href
	FROM
		#vehicles v
	LEFT JOIN #vehicle_categories vc ON vc.id=v.category_id
	ORDER BY pos
", '');
// debug($user);
while($row = $res_vehicels->fetch_assoc()){
		$vehicles_category[$row['category']][$row['id']] = [
			'title' => $row['title'],
			'href' => $row['href']
		];
		$vehicles_title[$row['title']] = [
			'id' => $row['id'],
			'href' => $row['href'],
			'category' => $row['category']
		];
        if ($_GET['vehicle'] == $row['href']) Breadcrumb::add('/original-catalogs/'.$row['href'], $row['title']);
}
$vt = & $vehicles_title;
$res_brends = $db->query("
	SELECT
		vm.brend_id,
		b.title,
		b.href
	FROM
		#models vm
	LEFT JOIN #brends b ON b.id=vm.brend_id
	WHERE 
		vm.vehicle_id IN (
			SELECT id FROM #vehicles WHERE href='{$_GET['vehicle']}'
		)
	GROUP BY vm.brend_id
	ORDER BY b.title
", '');
if ($res_brends->num_rows){
	while ($row = $res_brends->fetch_assoc()) {
		$letter = mb_strtoupper(substr($row['title'], 0 , 1));
		$brend_letters[$letter][$row['brend_id']]['brend_id'] = $row['brend_id'];
		$brend_letters[$letter][$row['brend_id']]['title'] = $row['title'];
		$brend_letters[$letter][$row['brend_id']]['href'] = $row['href'];
		$brend_titles[$row['brend_id']]['brend_id'] = $row['brend_id'];
		$brend_titles[$row['brend_id']]['title'] = $row['title'];
		$brend_titles[$row['brend_id']]['href'] = $row['href'];

        if ($row['href'] == $_GET['brend']) Breadcrumb::add(
            '/original-catalogs/'.$_GET['vehicle'].'/'.$_GET['brend'],
            $row['title']
        );
	}
}
$res_models = $db->query("
	SELECT
		m.id,
		m.title,
		m.href,
		CONCAT(m.vin, '00000000') AS vin
	FROM
		#models AS m
	LEFT JOIN #vehicles v ON m.vehicle_id=v.id
	LEFT JOIN #brends b ON m.brend_id=b.id
	WHERE
		b.href='{$_GET['brend']}' AND v.href='{$_GET['vehicle']}'
	ORDER BY m.title
", '');
if ($res_models->num_rows){
	while($row = $res_models->fetch_assoc()){
		$letter = mb_strtoupper(mb_substr($row['title'], 0 , 1, 'UTF-8'), 'UTF-8');
		$models[$letter][$row['id']] = [
			'title' => $row['title'],
			'href' => $row['href'],
			'vin' => $row['vin']
		];
        if ($row['href'] == $_GET['href']) Breadcrumb::add(
            '/original-catalogs/'.$_GET['vehicle'].'/'.$_GET['brend'].'/'.$_GET['model_id'].'/'.$_GET['href'].'/vin',
            $row['title']
        );
	}
}
$res_filters = $db->query("
	SELECT
		f.id,
		f.title,
		fv.id AS fv_id,
		fv.title AS fv_title,
		CAST(fv.title as UNSIGNED) as fv_title_2
	FROM
		#vehicle_filters f
	LEFT JOIN #vehicle_filter_values fv ON fv.filter_id=f.id
	LEFT JOIN #vehicles v ON f.vehicle_id=v.id
	LEFT JOIN #brends b ON f.brend_id=b.id
	WHERE
		b.href='{$_GET['brend']}' AND v.href='{$_GET['vehicle']}'
	ORDER BY
		f.id, fv_title_2, fv.title
", '');
if ($res_filters->num_rows){
	$filter_year_id = false;
	while($row = $res_filters->fetch_assoc()){
		if ($row['title'] == 'Год') $filter_year_id = $row['id'];
		$filters_str[$row['title']] = '';
		$filters[$row['id']]['title'] = $row['title'];
		if ($row['fv_id']) $filters[$row['id']]['filter_values'][$row['fv_id']] = $row['fv_title'];
	}
	$filters_str = array_keys($filters_str);
	if ($filter_year_id){
		$years_sort = array();
		uasort($filters[$filter_year_id]['filter_values'], function($a, $b){
			return $b - $a;
		});
		// array_multisort($years_sort, SORT_DESC, $filters[$filter_year_id]['filter_values']);
	}
}
$res_modifications = $db->query("
	SELECT
		mf.id,
		mf.title,
		GROUP_CONCAT(
			CONCAT(f.id, ':', fv.id)
		) AS fvs
	FROM
		#modifications mf
	LEFT JOIN #vehicle_model_fvs fvs ON fvs.modification_id=mf.id
	LEFT JOIN #vehicle_filter_values fv ON fv.id=fvs.fv_id
	LEFT JOIN #vehicle_filters f ON f.id=fv.filter_id
	WHERE
		mf.model_id={$_GET['model_id']} 
	GROUP BY mf.id
", '');

if(!$res_modifications->num_rows){
    throw new NotFoundException('Модификация не найдена');
}

// debug($filters); 
$needable_filters = array();
if ($res_modifications->num_rows){
	$years = array();
	while ($row = $res_modifications->fetch_assoc()){
		// debug($row);
		$modifications[$row['id']]['id'] = $row['id'];
		$modifications[$row['id']]['title'] = $row['title'];
		$filter_values = array();
		$array = array();
		if ($row['fvs']){
			$fvs = explode(',', $row['fvs']);
			foreach($fvs as $value){
				$fv = explode(':', $value);
				$array[$filters[$fv[0]]['title']] = $filters[$fv[0]]['filter_values'][$fv[1]];
			} 
			foreach ($filters as $id => $filter){
				$filter_values[$filter['title']] = $array[$filter['title']];
				$needable_filters[$filter['title']][] = $array[$filter['title']];
				if ($filter['title'] == 'Год') $years[] = $array[$filter['title']];
			} 
			$modifications[$row['id']]['filter_values'] = $filter_values;
		}
	}
	//exclude reduntant values of filters
	foreach ($needable_filters as $key => $value) $needable_filters[$key] = array_unique($needable_filters[$key]);
	$temp_filters = array();
	foreach($filters as $key => $value){
		if (!array_key_exists($value['title'], $needable_filters)) continue;
		$ft = & $temp_filters[$key];
		$ft['title'] = $value['title'];
		foreach($value['filter_values'] as $k => $v){
			// debug($needable_filters[$value['title']]); continue;
			if (is_numeric(array_search($v, $needable_filters[$value['title']]))) $ft['filter_values'][$k] = $v;
		}
	}
	$filters = $temp_filters;
	array_multisort($years, SORT_DESC, $modifications);
	// debug($filters);
}
$title = 'Выбор модификации';
Breadcrumb::out();
?>
<div class="auto-types">
	<?if ($_GET['to_garage']){
		$to_garage = "/to_garage";?>
		<input type="hidden" name="to_garage" value="1">
	<?}?>
	<div class="filter-form" brend=<?=$_GET['brend']?> vehicle=<?=$_GET['vehicle']?>>
		<h3>Оригинальные каталоги запчастей</h3>
		<input type="hidden" name="model_id" value="<?=$_GET['model_id']?>">
		<form action="#" method="post" filters="<?=implode(';', $filters_str)?>">
			<?if ($_GET['vehicle']){?>
				<div class="search-wrap">
					<input id="search" type="text" placeholder="Поиск по наименованию">
					<div class="search-icon"></div>
				</div>
			<?}?>
				<p>Выберите параметры фильтра:</p>
				<div class="input_box clearfix">
					<div class="input">
						<div class="select">
							<select <?=$to_garage ? 'disabled' : ''?> id="vehicle" data-placeholder="Транспортное средство">
								<option selected></option>
								<?foreach($vehicles_title as $key => $value){
                                    if ($value['href'] == $_GET['vehicle']){
                                        $selected = 'selected';
                                        Breadcrumb::add('/orginal-catalogs/'.$_GET['vehicle'], $key);
                                    }
									?>
									<option <?=$selected?> value="<?=$value['href']?>"><?=$key?></option>
								<?}?>
							</select>
						</div>
					</div>
				</div>
				<?if (count($brend_titles)){?>
						<div class="input_box clearfix">
							<div class="input">
								<div class="select">
									<select <?=$to_garage ? 'disabled' : ''?> id="brend" data-placeholder="Бренд" vehicle="<?=$_GET['vehicle']?>">
										<option selected></option>
										<?foreach($brend_titles as $key => $value){
											$selected = $_GET['brend'] == strtolower($value['title']) ? 'selected' : '';?>
											<option <?=$selected?> value="<?=$value['href']?>"><?=$value['title']?></option>
										<?}?>
									</select>
								</div>
							</div>
						</div>
					<?}?>
				<?if (!empty($filters)){
					// debug($filters);
					foreach($filters as $key => $value){
						if ($value['title'] != 'Год') continue;
						if (empty($value['filter_values'])) continue;?>
						<div class="input_box clearfix">
							<div class="input">
								<div class="select">
									<select class="select_filter" filter_id=<?=$key?> data-placeholder="<?=$value['title']?>">
										<option selected></option>
										<?foreach($value['filter_values'] as $k => $v){
											$selected = $value['title'] == 'Год' && $v == $_GET['year'] ? 'selected' : '';?>
											<option <?=$selected?> value="<?=$k?>"><?=$v?></option>
										<?}?>
									</select>
								</div>
							</div>
						</div>
					<?}?>
					<div class="clearfix"></div>
				<?}?>
				<?if (!empty($models)){?>
					<div class="input_box clearfix">
							<div class="input">
								<div class="select">
									<select class="select_model" data-placeholder="Модель">
										<option selected></option>
										<?foreach($models as $key => $value){
											foreach($value as $k => $v){
												$selected = $k == $_GET['model_id'] ? 'selected' : '';
												$val = "{$_GET['vehicle']}/{$_GET['brend']}/{$k}/{$v['href']}/vin";?>
												<option <?=$selected?> value="<?=$val?><?=$to_garage?>"><?=$v['title']?></option>
											<?}
										}?>
									</select>
								</div>
							</div>
						</div>
				<?}?>
				<?if (!empty($filters)){
					// debug($filters);
					foreach($filters as $key => $value){
						if ($value['title'] == 'Год') continue;
						if (empty($value['filter_values'])) continue;?>
						<div class="input_box clearfix">
							<div class="input">
								<div class="select">
									<select class="select_filter" filter_id=<?=$key?> data-placeholder="<?=$value['title']?>">
										<option selected></option>
										<?foreach($value['filter_values'] as $k => $v){
											$selected = $value['title'] == 'Год' && $v == $_GET['year'] ? 'selected' : '';?>
											<option <?=$selected?> value="<?=$k?>"><?=$v?></option>
										<?}?>
									</select>
								</div>
							</div>
						</div>
					<?}?>
					<div class="clearfix"></div>
				<?}?>
		</form>
	</div>
	<div class="content">
		<div class="option-panel">
			<a class="name-sort active" href="#">Наименование</a>
			<div class="view-switchs">
				<div class="view-switch mosaic-view-switch active" id="mosaic-view-switch">
					<img src="/img/icons/option-panel_mosaic_view.png" alt="Мозайкой">
				</div>
				<div class="view-switch list-view-switch" id="list-view-switch">
					<img src="/img/icons/option-panel_list-view.png" alt="Списком">
				</div>
				<div class="clearfix"></div>
			</div>
		</div>
		<div class="mosaic-view">
			<table class="wide-view" style="margin-top: 20px">
				<tbody>
					<tr>
						<th>Название</th>
						<?if (!empty($filters)){
							foreach ($filters as $value){?>
								<th><?=$value['title']?></th>
							<?}	
						}?>
					</tr>
					<?
					if (preg_match('/\/\d{4}$/', $_SERVER['REQUEST_URI'])) $uri = preg_replace('/\/\d{4}$/', '', $_SERVER['REQUEST_URI']);
					else $uri = $_SERVER['REQUEST_URI'];
					// debug($modifications);
					if (!empty($modifications)) foreach($modifications as $key => $value){
						if ($_GET['year'] && $value['filter_values']['Год'] != $_GET['year']) continue;?>
						<tr class="clickable" modification_id="<?=$value['id']?>">
							<td class="name-col">
								<a href="<?=$uri?>/<?=$value['id']?>">
									<?=$value['title']?>
								</a>
							</td>
							<?if (!empty($value['filter_values'])){
								foreach ($value['filter_values'] as $k => $v){?>
									<td><?=$v?></td>
								<?}	
							}?>
						</tr>
					<?}?>
				</tbody>
			</table>
		</div>
		<div class="list-view">
			<table class="wide-view">
				<tbody>
					<tr>
						<th>Название</th>
						<?if (!empty($filters)){
							foreach ($filters as $value){?>
								<th><?=$value['title']?></th>
							<?}	
						}?>
					</tr>
					<?if (!empty($modifications)) foreach($modifications as $key => $value){
						if ($_GET['year'] && $value['filter_values']['Год'] != $_GET['year']) continue;?>
							<tr class="clickable" modification_id="<?=$value['id']?>">
								<td class="name-col">
									<a href="<?=$uri?>/<?=$value['id']?>">
										<?=$value['title']?>
									</a>
								</td>
								<?if (!empty($value['filter_values'])){
									foreach ($value['filter_values'] as $k => $v){?>
										<td><?=$v?></td>
									<?}	
								}?>
							</tr>
						<?}?>
				</tbody>
			</table>
		</div>
	</div>
	<div class="hidden"></div>
</div>
<?function arr_fvs($str){
	global $filters;
	if (empty($str)) return false;
	$fvs = explode(',', $str);
	foreach($fvs as $value){
		$fv = explode(':', $value);
		// $array[$filters[$fv[0]]['title']] = $filters[$fv[0]]['filter_values'][$fv[1]];
		$array[$filters[$fv[0]]['title']] = $filters[$fv[0]]['filter_values'][$fv[1]];
	}
	return $array;
}?>