<?$title = 'Оригинальные каталоги';
if ($_GET['vehicle'] == 'legkovie-avtomobili'){
	$title = 'Легковые автомобили';?>
	<input type="hidden" name="user_id" value="<?=$_SESSION['user'] ? $_SESSION['user'] : ''?>">
	<div id="parts-catalog"
	  data-key="TWS-4F5CE688-B8A5-4903-9E1C-0E125922134F"
	  data-target="new_window"
	  data-back-url="/search/article/{article}"
	  data-language="ru"
	></div>
	<script type="text/javascript" src="https://gui.parts-catalogs.com/v2/parts-catalogs.js"></script>
<?
}
else{
	error_reporting(E_ERROR);
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
	while($row = $res_vehicels->fetch_assoc()){
			$vehicles_category[$row['category']][$row['id']] = [
				'title' => $row['title'],
				'href' => $row['href']
			];
			$vehicles_mosaic[$row['id']] = [
				'title' => $row['title'],
				'href' => $row['href'],
				'is_mosaic' => $row['is_mosaic']
			];
	}
	// debug($vehicles_mosaic);
	if ($_GET['vehicle']){
		$res_brends = $db->query("
			SELECT
				fv.brend_id,
				b.title,
				b.href
			FROM
				#vehicle_filters fv
			LEFT JOIN #brends b ON b.id=fv.brend_id
			WHERE 
				fv.vehicle_id IN (
					SELECT id FROM #vehicles WHERE href='{$_GET['vehicle']}'
				)
			GROUP BY fv.brend_id
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
			}
		}
	}
	if ($_GET['vehicle'] && $_GET['brend']){
		// debug($_GET);
		//если зашли с гаража с применением только фильтра года
		if($_GET['brend'] && $_GET['vehicle'] && $_GET['year']){
			$res_models = $db->query("
				SELECT
					mf.model_id AS id,
					md.title,
					md.href
				FROM
					#vehicle_model_fvs fvs
				LEFT JOIN
					#brends b ON b.href='{$_POST['brend']}'
				LEFT JOIN
					#vehicles v ON v.href='{$_POST['vehicle']}'
				LEFT JOIN
					#vehicle_filters f ON f.title='Год' AND b.id=f.brend_id AND f.vehicle_id=v.id
				LEFT JOIN
					#vehicle_filter_values fv ON fv.id=fvs.fv_id
				LEFT JOIN
					#modifications mf ON mf.id=fvs.modification_id
				LEFT JOIN
					#models md ON md.id=mf.model_id AND md.is_removed=0
				WHERE
					fv.title='{$_GET['year']}'
				GROUP BY
					md.title
				ORDER BY
					mf.title
			", '');
		}
		else{
			$res_models = $db->query("
				SELECT
					m.id,
					m.title,
					m.href
				FROM
					#models AS m
				LEFT JOIN #vehicles v ON m.vehicle_id=v.id
				LEFT JOIN #brends b ON m.brend_id=b.id
				WHERE
					b.href='{$_GET['brend']}' AND v.href='{$_GET['vehicle']}'
				ORDER BY m.title
			", '');
		}
		if ($res_models->num_rows){
			while($row = $res_models->fetch_assoc()){
				$letter = mb_strtoupper(mb_substr($row['title'], 0 , 1, 'UTF-8'), 'UTF-8');
				$models[$letter][$row['id']] = [
					'title' => $row['title'],
					'href' => $row['href']
				];
			}
			if (!empty($years)) $years = array_keys($years);
			// debug($years);
		}
		$res_years = $db->query("
			SELECT
				fv.id,
				fv.title,
				CAST(fv.title AS UNSIGNED) as title_2
			FROM
				#vehicle_filter_values fv
			LEFT JOIN
				#vehicle_filters f ON f.id=fv.filter_id
			LEFT JOIN 
				#vehicles v ON f.vehicle_id=v.id
			LEFT JOIN 
				#brends b ON f.brend_id=b.id
			WHERE
				b.href='{$_GET['brend']}' AND 
				v.href='{$_GET['vehicle']}' AND
				f.title='Год'
			ORDER BY
				title_2 DESC
		", '');
	}
	?>
	<div class="auto-types">
		<!-- обозначение если ссылка пришла из гаража -->
		<?if (preg_match('/\/\d{4}$/', $_SERVER['REQUEST_URI'])) $is_garage = true;
		if ($is_garage){?>
			<input type="hidden" name="is_garage" value="1">
		<?}?>
		<div class="filter-form" brend=<?=$_GET['brend']?> vehicle=<?=$_GET['vehicle']?>>
			<h3>Оригинальные каталоги запчастей</h3>
			<form action="#" method="post">
				<?if ($_GET['vehicle'] && $_GET['brend']){?>
					<div class="search-wrap">
						<input id="search" type="text" placeholder="Поиск по наименованию">
						<div class="search-icon"></div>
					</div>
				<?}?>
					<p>Выберите параметры фильтра:</p>
					<div class="input_box clearfix">
						<div class="input">
							<div class="select">
								<select <?=$is_garage ? 'disabled' : ''?> id="vehicle" data-placeholder="Транспортное средство">
									<option selected></option>
									<?foreach($vehicles_mosaic as $key => $value){
										$selected = $value['href'] == $_GET['vehicle'] ? 'selected' : '';?>
										<option <?=$selected?> value="<?=$value['href']?>"><?=$value['title']?></option>
									<?}?>
								</select>
							</div>
						</div>
					</div>
					<?if (count($brend_titles)){?>
							<div class="input_box clearfix">
								<div class="input">
									<div class="select">
										<select id="brend" data-placeholder="Бренд" vehicle="<?=$_GET['vehicle']?>">
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
					<?if ($res_years->num_rows){?>
						<div class="input_box clearfix">
							<div class="input">
								<div class="select">
									<select class="select_year" data-placeholder="Год">
										<option selected></option>
										<?while ($row = $res_years->fetch_assoc()){
											$selected = $row['title'] == $_GET['year'] ? 'selected' : '';?>
											<option <?=$selected?> value="<?=$row['title']?>"><?=$row['title']?></option>
										<?}?>
									</select>
								</div>
							</div>
						</div>
					<?}?>
					<?if (!empty($models)){?>
						<div class="input_box clearfix">
								<div class="input">
									<div class="select">
										<select class="select_model" data-placeholder="Модель">
											<option selected></option>
											<?$garage = $is_garage ? '/garage' : '';
											foreach($models as $key => $value){
												foreach($value as $k => $v){?>
													<option value="/original-catalogs/<?=$_GET['vehicle']?>/<?=$_GET['brend']?>/<?=$k?>/<?=$v['href']?>/vin"><?=$v['title']?></option>
												<?}
											}?>
										</select>
									</div>
								</div>
							</div>
					<?}?>
					
					<?if (!empty($filters)){
						foreach($filters as $key => $value){
							if (empty($value['filter_values'])) continue;?>
							<div class="input_box clearfix">
								<div class="input">
									<div class="select">
										<select class="select_filter" filter_id=<?=$value['id']?> data-placeholder="<?=$key?>">
											<option selected></option>
											<?foreach($value['filter_values'] as $k => $v){?>
												<option value="<?=$k?>"><?=$v?></option>
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
				<?if (!$_GET['vehicle'] && !$_GET['brend']){?>
					<div class="items">
						<?foreach($vehicles_mosaic as $key => $value){
							if (!$value['is_mosaic']) continue;?>
							<div class="item">
								<a href="/original-catalogs/<?=$value['href']?>"></a>
								<div class="img-wrap">
									<?$src = file_exists(core\Config::$imgPath . "/vehicles/$key.jpg") ? "$key.jpg" : 'no_image.jpg';?>
									<img src="<?=core\Config::$imgUrl?>/vehicles/<?=$src?>" alt="<?=$value['title']?>">
								</div>
								<a href="/original-catalogs/<?=$value['href']?>"><?=$value['title']?></a>
							</div>
						<?}?>
					</div>
				<?}
				elseif (!$_GET['brend'] && $_GET['vehicle']){?>
						<div class="item_brends">
							<?if (!empty($brend_titles)) foreach($brend_titles as $key => $row) {?>
								<div class="item">
									<a href="/original-catalogs/<?=strtolower($_GET['vehicle'])?>/<?=$row['href']?>"></a>
									<?$filePath = array_shift(glob(core\Config::$imgPath . "/brends/{$row['brend_id']}.*"));
									$pathinfo = pathinfo($filePath);
									$src = core\Config::$imgUrl . "/brends/{$pathinfo['basename']}";
									if ($src){?>
										<div class="img-wrap">
											<img src="<?=$src?>" alt="<?=$row['title']?>">
										</div>
									<?}?>
									<p><?=$row['title']?></p>
								</div>
							<?}?>
						</div>
					<?}
				elseif ($_GET['brend'] && $_GET['vehicle']){
					if (!empty($models)){
						// debug($models);
						//если зашли с гаража с применением только фильтра года
						if($_GET['brend'] && $_GET['vehicle'] && $_GET['year']){?>
							<div style="margin-top: 20px" class="name-block">
								<p class="letter"><?=$_GET['year']?></p>
								<div class="models">
									<?foreach($models as $value){
										foreach($value as $k => $v){?>
											<a href="/original-catalogs/<?=$_GET['vehicle']?>/<?=$_GET['brend']?>/<?=$k?>/<?=$v['href']?>/vin/to_garage"><?=$v['title']?></a>
										<?}
										}?>
								</div>
							</div>
						<?}
						else{
							foreach($models as $key => $value){?>
							<div style="margin-top: 20px" class="name-block">
								<p class="letter"><?=$key?></p>
								<div class="models">
									<?foreach($value as $k => $v){?>
										<a href="/original-catalogs/<?=$_GET['vehicle']?>/<?=$_GET['brend']?>/<?=$k?>/<?=$v['href']?>/vin"><?=$v['title']?></a>
									<?}?>
								</div>
							</div>
						<?}
						}
					} 
				}?>
			</div>
			<div class="list-view">
				<?if (!$_GET['vehicle'] && !$_GET['brend']) foreach($vehicles_category as $key => $value){?>
					<div class="type-block">
						<p class="title"><?=$key?></p>
						<div class="types">
							<?foreach($value as $k => $v){?>
								<a href="/original-catalogs/<?=$v['href']?>"><?=$v['title']?></a>
							<?}?>
						</div>
					</div>
				<?}
				elseif (!$_GET['brend'] && $_GET['vehicle']) foreach($brend_letters as $key => $value){?>
						<div class="name-block">
							<p class="letter"><?=$key?></p>
							<div class="models">
								<?foreach($value as $k => $v){?>
									<a href="/original-catalogs/<?=strtolower($_GET['vehicle'])?>/<?=strtolower($v['title'])?>">
										<?=$v['title']?>
									</a>
								<?}?>
							</div>
						</div>
					<?}
				elseif ($_GET['brend'] && $_GET['vehicle']){
					// debug($models);
					if (!empty($models)){
						// debug($models);
						//если зашли с гаража с применением только фильтра года
						if($_GET['brend'] && $_GET['vehicle'] && $_GET['year']){?>
							<div style="margin-top: 20px" class="name-block">
								<p class="letter"><?=$_GET['year']?></p>
								<div class="models">
									<?foreach($models as $value){
										foreach($value as $k => $v){?>
											<a href="/original-catalogs/<?=$_GET['vehicle']?>/<?=$_GET['brend']?>/<?=$k?>/<?=$v['href']?>/vin/to_garage"><?=$v['title']?></a>
										<?}
										}?>
								</div>
							</div>
						<?}
						else{
							foreach($models as $key => $value){?>
							<div style="margin-top: 20px" class="name-block">
								<p class="letter"><?=$key?></p>
								<div class="models">
									<?foreach($value as $k => $v){?>
										<a href="/original-catalogs/<?=$_GET['vehicle']?>/<?=$_GET['brend']?>/<?=$k?>/<?=$v['href']?>/vin"><?=$v['title']?></a>
									<?}?>
								</div>
							</div>
						<?}
						}
					} 
				}?>
			</div>
		</div>
		<div class="hidden">
		</div>
	</div>
<?}?>
	
