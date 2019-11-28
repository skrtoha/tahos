<?
if (empty($user)) header("Location: /");
// debug($_GET);
if ($_GET['modification_id']) modification();
else garage();
function garage(){
	global $db, $title, $user;
	$title = 'Гараж';
	$res_vehicles = $db->query("
		SELECT
			v.id,
			v.title,
			v.href
		FROM
			#vehicles v
		ORDER BY
			v.title
	", '');
	$res_modifications = $db->query("
		SELECT
			g.modification_id,
			g.title AS title_garage,
			g.is_active,
			m.model_id,
			m.title AS title_modification,
			md.vin,
			md.title AS title_model,
			b.title AS title_brend,
			md.vehicle_id,
			fv.title AS year
		FROM
			#garage g
		LEFT JOIN
			#modifications m ON m.id=g.modification_id
		LEFT JOIN
			#models md ON md.id=m.model_id
		LEFT JOIN
			#vehicle_model_fvs fvs ON fvs.modification_id=g.modification_id
		LEFT JOIN
			#vehicle_filter_values fv ON fv.id=fvs.fv_id
		LEFT JOIN
			#vehicle_filters f ON f.id=fv.filter_id AND f.title='Год'
		LEFT JOIN
			#brends b ON b.id=md.brend_id
		WHERE
			g.user_id={$user['id']} AND f.title IS NOT NULL
	", '');
	$modifications = array();
	if ($res_modifications->num_rows){
		while($row = $res_modifications->fetch_assoc()){
			$array = [
				'modification_id' => $row['modification_id'],
				'title_garage' => $row['title_garage'],
				'title_modification' => $row['title_modification'],
				'model_id' => $row['model_id'],
				'vehicle_id' => $row['vehicle_id'],
				'title_brend' => $row['title_brend'],
				'title_model' => $row['title_model'],
				'vin' => $row['vin'],
				'year' => $row['year']
			];
			if ($row['is_active']) $modifications['active'][] = $array;
			else $modifications['non_active'][] = $array;
		}
	}
	// debug($modifications);
	?>
	<div class="garage">
		<a class="button add_ts" href="#add_ts_form">Добавить транспортное средство</a>
		<div class="filter-form" id="add_ts_form">
			<?if ($res_vehicles->num_rows){?>
				<h3>Добавить ТС</h3>
				<form action="/garage/select_modification/" method="post">
				<div class="search-wrap">
					<input type="text" placeholder="Поиск по наименованию">
					<div class="search-icon"></div>
				</div>
				<p>Выберите параметры фильтра:</p>
				<div class="input_box clearfix">
					<div class="input">
						<div class="select">
							<select name="vehicle_id" data-placeholder="Тип транспорта">
								<option selected></option>
								<?while($row = $res_vehicles->fetch_assoc()){?>
									<option value="<?=$row['href']?>"><?=$row['title']?></option>
								<?}?>
							</select>
						</div>
					</div>
				</div>
				<div class="input_box clearfix">
					<div class="input">
						<div class="select">
							<select disabled name="brend_id" data-placeholder="Марка">
								<option></option>
							</select>
						</div>
					</div>
				</div>
				<div class="input_box clearfix">
					<div class="input">
						<div class="select">
							<select disabled name="year_id" data-placeholder="Год выпуска">
								<option></option>
							</select>
						</div>
					</div>
				</div>
				<div class="input_box clearfix">
					<div class="input">
						<div class="select">
							<select disabled name="model_id" data-placeholder="Модель">
								<option></option>
							</select>
						</div>
					</div>
				</div>

				<button>Добавить</button>

			</form>
			<?}?>
				
		</div>
		<div class="items">
			<div class="option-panel">
				<a class="active-switch active" href="#">Активные</a>
				<a class="not-active-switch" href="#">Неактивные</a>
				<div class="view-switchs">
					<div class="view-switch mosaic-view-switch active" id="mosaic-view-switch">
						<img src="img/icons/option-panel_mosaic_view.png" alt="Мозайкой">
					</div>
					<div class="view-switch list-view-switch" id="list-view-switch">
						<img src="img/icons/option-panel_list-view.png" alt="Списком">
					</div>
				</div>

			</div>
			<div class="content">
				<div class="mosaic-view">
					<div class="active-tab">
						<?if (empty($modifications['active'])){?>
							<p class="removable">Активных моделей не найдено</p>
						<?}
						else{
							foreach($modifications['active'] as $value){?>
								<div class="item" modification_id="<?=$value['modification_id']?>">
									<a class="model-name" href="#"><?=$value['title_brend']?> <?=$value['title_model']?> (<?=$value['title_modification']?>)</a>
									<div class="clearfix"></div>
									<?if (file_exists("{$_SERVER['DOCUMENT_ROOT']}/images/models/{$value['model_id']}.jpg")){?>
										<div class="img">
											<img src="/images/models/<?=$value['model_id']?>.jpg" alt="<?=$value['title']?>">
										</div>
									<?}
									else{?>
										<div class="img">
											<img src="/images/vehicles/<?=$value['vehicle_id']?>.jpg" alt="<?=$value['title']?>">
										</div>
									<?}?>
									<div class="description">
										<div class="parametrs">
											<p>Имя: <?=$value['title_garage']?></p>
											<p>VIN: <?=$value['vin']?></p>
											<p>Год выпуска: <?=$value['year']?></p>
										</div>
									</div>
									<div class="clearfix"></div>
									<a href="" class="remove-item">Удалить</a>
								</div>
							<?}
						}?>
						<div class="clearfix"></div>
					</div>
					<div class="not-active-tab">
						<?if (empty($modifications['non_active'])){?>
							<p class="removable">Неактивных моделей не найдено</p>
						<?}
						else{
							foreach($modifications['non_active'] as $value){?>
								<div class="item" modification_id="<?=$value['modification_id']?>">
									<a class="model-name" href="#"><?=$value['title_brend']?> <?=$value['title_model']?> (<?=$value['title_modification']?>)</a>
									<div class="clearfix"></div>
									<?if (file_exists("{$_SERVER['DOCUMENT_ROOT']}/images/models/{$value['model_id']}.jpg")){?>
										<div class="img">
											<img src="/images/models/<?=$value['model_id']?>.jpg" alt="<?=$value['title']?>">
										</div>
									<?}
									else{?>
										<div class="img">
											<img src="/images/vehicles/<?=$value['vehicle_id']?>.jpg" alt="<?=$value['title']?>">
										</div>
									<?}?>
									<div class="description">
										<div class="parametrs">
											<p>Имя: <?=$value['title_garage']?></p>
											<p>VIN: <?=$value['vin']?></p>
											<p>Год выпуска: <?=$value['year']?></p>
										</div>
									</div>
									<div class="clearfix"></div>
									<a href="#" class="remove-item">Удалить из гаража</a>
							<a href="#" class="restore-item">Восстановить</a>
								</div>
							<?}
						}?>
						<div class="clearfix"></div>
					</div>
				</div>
				<div class="list-view">
					<div class="active-tab">
						<table class="wide-view">
							<tr>
								<th>Название</th>
								<th>Vin номер</th>
								<th>Год выпуска</th>
								<th>Удалить</th>
							</tr>
							<?if(empty($modifications['active'])){?>
								<tr class="removable"><td colspan="4">Активных моделей не найдено</td></tr>
							<?}
							else{
								foreach($modifications['active'] as $value){?>
									<tr modification_id="<?=$value['modification_id']?>">
										<td><a href="#"><?=$value['title_modification']?></a></td>
										<td><p><?=$value['vin']?></p></td>
										<td><p><?=$value['year']?></p></td>
										<td><a href="#" class="remove-item">Удалить</a></td>
									</tr>
								<?}
							}?>
						</table>
						<table class="middle-view">
							<tr>
								<th>Название</th>
								<th>Удалить</th>
							</tr>
							<?if (empty($modifications['active'])){?>
								<tr class="removable"><td colspan="2">Активных моделей не найдено</td></tr>
							<?}
							else{
								foreach($modifications['active'] as $value){?>
									<tr modification_id="<?=$value['modification_id']?>">
										<td><p>
											<a href="#"><?=$value['title_modification']?></a> <?=$value['year']?> г.в.
											<?if ($value['vin']){?>
											 <br> VIN: <?=$value['vin']?>
											<?}?>
										</p></td>
										<td><a href="#" class="remove-item">Удалить</a></td>
									</tr>
								<?}?>
							<?}?>
						</table>
						<table class="small-view">
							<?if (empty($modifications['active'])){?>
								<tr class="removable"><td colspan="2">Активных моделей не задано</td></tr>
							<?}
							else{
								foreach($modifications['active'] as $value){?>
									<tr modification_id="<?=$value['modification_id']?>">
										<td>
											<p>
												<a href="#"><?=$value['title_modification']?></a> <?=$value['year']?> г.в. 
													<?if ($value['vin']){?>
														<br> VIN: <?=$value['vin']?>
													<?}?>
											</p>
											<a href="#" class="remove-item">Удалить</a>
										</td>
									</tr>
								<?}
							}?>
						</table>
					</div>
					<div class="not-active-tab">
						<table class="wide-view">
							<tr>
								<th>Название</th>
								<th>Vin номер</th>
								<th>Год выпуска</th>
								<th>Восстановить</th>
								<th>Удалить</th>
							</tr>
							<?if (empty($modifications['non_active'])){?>
								<tr class="removable"><td colspan="4">Неактивных моделей не задано</td></tr>
							<?}
							else{
								foreach($modifications['non_active'] as $value){?>
									<tr modification_id="<?=$value['modification_id']?>">
										<td><a href="#"><?=$value['title_modification']?></a></td>
										<td><p><?=$value['vin']?></p></td>
										<td><p><?=$value['year']?></p></td>
										<td><a href="#" class="restore-item">Восстановить</a></td>
										<td><a href="#" class="remove-item">Удалить</a></td>
									</tr>
								<?}
							}?>
						</table>
						<table class="middle-view">
							<tr>
								<th>Название</th>
								<th>Восстановить</th>
								<th>Удалить</th>
							</tr>
							<?if (empty($modifications['non_active'])){?>
								<tr class="removable"><td colspan=#>Неактивных моделей не найдено</td></tr>
							<?}
							else{
								foreach($modifications['non_active'] as $value){?>
									<tr modification_id="<?=$value['modification_id']?>">
										<td><p>
											<a href="#"><?=$value['title_modification']?></a> <?=$value['year']?> г.в. 
											<?if ($value['vin']){?>
												<br> VIN: <?=$value['vin']?>
											<?}?>
										</p></td>
										<td><a href="#" class="restore-item">Восстановить</a></td>
										<td><a href="#" class="remove-item">Удалить</a></td>
									</tr>
								<?}
								}?>
						</table>
						<table class="small-view">
							<?if (empty($modifications['non_active'])){?>
								<tr class="removable"><td colspan="2">Неактивных моделей не задано</td></tr>
							<?}
							else{
								foreach($modifications['non_active'] as $value){?>
									<tr modification_id="<?=$value['modification_id']?>">
										<td>
											<p>
												<a href="#"><?=$value['title_modification']?></a> <?=$value['year']?> г.в. 
													<?if ($value['vin']){?>
														<br> VIN: 12DWE432BERRT232FERG
													<?}?>
											</p>
											<a href="#" class="restore-item">Восстановить</a>
											<a href="#" class="remove-item">Удалить</a>
										</td>
									</tr>
								<?}
							}?>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
<?}
function modification(){
	global $db, $title, $user;
	// error_reporting(E_ALL);
	$m = $db->select_unique("
		SELECT
			mf.id AS modification_id,
			mf.title AS modification_title,
			mf.model_id,
			md.title AS model_title,
			md.href AS model_href,
			md.vehicle_id,
			b.href AS brend_href,
			b.title AS brend,
			g.title AS garage_title,
			g.comment,
			v.href AS vehicle_href
		FROM
			#modifications mf
		LEFT JOIN
			#models md ON md.id=mf.model_id
		LEFT JOIN
			#brends b ON b.id=md.brend_id
		LEFT JOIN
			#garage g ON g.user_id={$user['id']} AND g.modification_id={$_GET['modification_id']}
		LEFT JOIN
			#vehicles v ON v.id=md.vehicle_id
		WHERE
			mf.id={$_GET['modification_id']} 
	", '');
	$m = $m[0];
	$res_categories = $db->query("
		SELECT
			c.href,
			c.title
		FROM
			#categories c
		WHERE
			c.parent_id=0
	", '');
	$title = "Гараж | {$m['model_title']}";
	?>
	<script src="/js/garage-selected-ts.js"></script>
	<input type="hidden" name="modification_id" value="<?=$_GET['modification_id']?>">
	<div class="garage-inside-ts">
		<a class="button change_ts" href="/garage">Сменить ТС</a>
		<div class="ts-info">
			<h1><?=$m['brend']?> <?=$m['model_title']?> (<?=$m['modification_title']?>)</h1>
			<div class="img-and-name">
				<div class="img-wrap">
					<?if (file_exists("{$_SERVER['DOCUMENT_ROOT']}/images/models/{$m['model_id']}.jpg")){?>
						<img src="/images/models/<?=$m['model_id']?>.jpg" alt="<?=$m['modification_title']?>">
					<?}
					else{?>
						<img src="/images/vehicles/<?=$m['vehicle_id']?>.jpg" alt="<?=$m['modification_title']?>">
					<?}?>
				</div>
				<label for="ts-name">Имя: <input type="text" name="modification_title" value="<?=$m['garage_title']?>"></label>
			</div>
			<div class="note">
				<textarea placeholder="Начните свою запись в блокноте"><?=$m['comment']?></textarea>
				<button class="save_ts_note">Сохранить</button>
			</div>
			<div class="clearfix"></div>
		</div>
		<div class="ionTabs" id="selected-ts-tabs" data-name="selected-ts-tabs">
			<ul class="ionTabs__head">
				<li class="ionTabs__tab" data-target="Tab_1_name">Основные</li>
				<li class="ionTabs__tab" data-target="Tab_2_name">Тех. информация</li>
				<li class="ionTabs__tab" data-target="Tab_3_name">документация</li>
			</ul>
			<div class="ionTabs__body">
				<div class="ionTabs__item" data-name="Tab_1_name">
					<ul>
						<li><a href="/original-catalogs/<?=$m['vehicle_href']?>/<?=$m['brend_href']?>/<?=$m['model_id']?>/<?=$m['model_href']?>/vin/<?=$_GET['modification_id']?>">Оригинальный каталог</a></li>
						<?if ($res_categories->num_rows){
							while($row = $res_categories->fetch_assoc()){?>
								<li><a href="/category/<?=$row['href']?>"><?=$row['title']?></a></li>
							<?}
						}?>
					</ul>
				</div>
				<div class="ionTabs__item" data-name="Tab_2_name">
					Контент вкладки 2
				</div>
				<div class="ionTabs__item" data-name="Tab_3_name">
					Контент вкладки 3
				</div>

				<div class="ionTabs__preloader"></div>
			</div>
		</div>
	</div>
<?}
?>
	