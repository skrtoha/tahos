<?

use core\Breadcrumb;

if (empty($user)) header("Location: /");
if ($_GET['modification_id']) modification();
else garage();

Breadcrumb::add('/garage', 'Гараж');

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
    $query = \core\Garage::getQuery();
    $query .= "WHERE g.user_id={$user['id']}";
	$res_modifications = $db->query($query, '');
	$modifications = array();
	if ($res_modifications->num_rows){
		while($row = $res_modifications->fetch_assoc()){
			$isFromPartsCatalogs = is_numeric($row['modification_id']);
            if (!$isFromPartsCatalogs){
                $array = explode(',', $row['modification_id']);
                $vin = $array[3];
            }
            else $vin = $row['vin'];
			$array = [
				'modification_id' => $row['modification_id'],
				'title_garage' => $row['title_garage'],
				'title_modification' => $isFromPartsCatalogs ? $row['title'] : $row['title_garage'],
                'phone' => $row['phone'],
                'owner' => $row['owner'],
				'model_id' => $row['model_id'],
				'vehicle_id' => $row['vehicle_id'],
				'title_brend' => $row['title_brend'],
				'title_model' => $row['title_model'],
				'vin' => $vin,
				'year' => $row['year']
			];
			if ($row['is_active']) $modifications['active'][] = $array;
			else $modifications['non_active'][] = $array;
		}
	}
    Breadcrumb::out();
    ?>
	<div class="garage">
		<a class="button add_ts" href="#add_ts_form">Добавить транспортное средство</a>
		<div class="filter-form" id="add_ts_form">
            <form action="/" method="post">
                <div class="search-wrap">
                    <input type="text" placeholder="Поиск по наименованию">
                    <div class="search-icon"></div>
                </div>
            </form>
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
                        <div class="wrapper">
                            <?if (empty($modifications['active'])){?>
                                <p class="removable">Активных моделей не найдено</p>
                            <?}
                            else{
                                foreach($modifications['active'] as $value){?>
                                    <div class="item" modification_id="<?=$value['modification_id']?>">
                                        <a class="model-name" href="#">
                                            <?=$value['title_brend']?> <?=$value['title_model']?> <?=$value['title_modification'] ? "{$value['title_modification']}" : ''?>
                                        </a>
                                        <div class="clearfix"></div>
                                        <?if (file_exists(core\Config::$imgPath . "/models/{$value['model_id']}.jpg")){?>
                                            <div class="img">
                                                <img src="<?=core\Config::$imgUrl?>/models/<?=$value['model_id']?>.jpg" alt="<?=$value['title']?>">
                                            </div>
                                        <?}
                                        else{
                                            $vehicle_id = is_numeric($value['modification_id']) ? $value['vehicle_id'] : 11;?>
                                            <div class="img">
                                                <img src="<?=core\Config::$imgUrl?>/vehicles/<?=$vehicle_id?>.jpg" alt="<?=$value['title']?>">
                                            </div>
                                        <?}?>
                                        <div class="description">
                                            <div class="parametrs">
                                                <p><b>Владелец:</b> <?=$value['owner']?></p>
                                                <p><b>VIN:</b> <?=$value['vin']?></p>
                                                <p><b>Год выпуска:</b> <?=$value['year']?></p>
                                                <p><b>Телефон:</b> <?=$value['phone']?></p>
                                            </div>
                                        </div>
                                        <div class="clearfix"></div>
                                        <a href="" class="remove-item">Удалить</a>
                                        <a href="" class="edit-item">Редактировать</a>
                                        <div class="clearfix"></div>
                                    </div>
                                <?}
                            }?>
                        </div>
					</div>
					<div class="not-active-tab">
                        <div class="wrapper">
                            <?if (empty($modifications['non_active'])){?>
                                <p class="removable">Неактивных моделей не найдено</p>
                            <?}
                            else{
                                foreach($modifications['non_active'] as $value){?>
                                    <div class="item" modification_id="<?=$value['modification_id']?>">
                                        <a class="model-name" href="#">
                                            <?=$value['title_brend']?> <?=$value['title_model']?> (<?=$value['title_modification']?>)
                                        </a>
                                        <div class="clearfix"></div>
                                        <?if (file_exists(core\Config::$imgPath . "/models/{$value['model_id']}.jpg")){?>
                                            <div class="img">
                                                <img src="<?=core\Config::$imgUrl?>/models/<?=$value['model_id']?>.jpg" alt="<?=$value['title']?>">
                                            </div>
                                        <?}
                                        else{
                                            $vehicle_id = is_numeric($value['modification_id']) ? $value['vehicle_id'] : 11;?>
                                            <div class="img">
                                                <img src="<?=core\Config::$imgUrl?>/vehicles/<?=$vehicle_id?>.jpg" alt="<?=$value['title']?>">
                                            </div>
                                        <?}?>
                                        <div class="description">
                                            <div class="parametrs">
                                                <p><b>Владелец:</b> <?=$value['owner']?></p>
                                                <p><b>VIN:</b> <?=$value['vin']?></p>
                                                <p><b>Год выпуска:</b> <?=$value['year']?></p>
                                                <p><b>Телефон:</b> <?=$value['phone']?></p>
                                            </div>
                                        </div>
                                        <div class="clearfix"></div>
                                        <a href="#" class="remove-item">Удалить из гаража</a>
                                        <a href="#" class="restore-item">Восстановить</a>
                                    </div>
                                <?}
                            }?>
                        </div>
					</div>
				</div>
				<div class="list-view">
					<div class="active-tab">
						<table class="wide-view">
							<tr>
								<th>Название</th>
								<th>VIN</th>
                                <th>Владелец</th>
                                <th>Год выпуска</th>
                                <th></th>
							</tr>
							<?if(empty($modifications['active'])){?>
								<tr class="removable"><td colspan="4">Активных моделей не найдено</td></tr>
							<?}
							else{
								foreach($modifications['active'] as $value){?>
									<tr modification_id="<?=$value['modification_id']?>">
										<td>
                                            <?=$value['title_brend']?>
                                            <?=$value['title_model']?>
                                            <?=$value['title_modification'] ? "{$value['title_modification']}" : ''?>
                                        </td>
										<td><p><?=$value['vin']?></p></td>
										<td><p><?=$value['owner']?></p></td>
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
                                            <?if ($value['owner']){?>
                                                <br>Владелец: <?=$value['owner']?>
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
                                                <?if ($value['owner']){?>
                                                    <br>Владелец: <?=$value['owner']?>
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
	if (is_numeric($_GET['modification_id'])){
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
				#garage g ON g.user_id={$user['id']} AND g.modification_id='{$_GET['modification_id']}'
			LEFT JOIN
				#vehicles v ON v.id=md.vehicle_id
			WHERE
				mf.id='{$_GET['modification_id']}' 
		", '');
		$m = $m[0];
		$title = "Гараж | {$m['model_title']}";
	}
	else{
		$garage = $db->select_one('garage', '*', "`user_id` = {$user['id']} AND `modification_id` = '{$_GET['modification_id']}'");
		$m = [];
		$m['modification_title'] = $garage['title'];
		$m['comment'] = $garage['comment'];
		$title = "Гараж | {$m['modification_title']}";
		preg_match('/^[\w а-яА-Я-]+,/', $_GET['modification_id'], $coincidences);
		$m['brend'] = strtoupper(substr($coincidences[0], 0, -1));
	}
	
	$res_categories = $db->query("
		SELECT
			c.href,
			c.title
		FROM
			#categories c
		WHERE
			c.parent_id=0
	", '');
    Breadcrumb::add('/garage/'.$_GET['modification_id'], $m['modification_title']);
    Breadcrumb::out();
	?>
	<script src="/js/garage-selected-ts.js"></script>
	<input type="hidden" name="modification_id" value="<?=$_GET['modification_id']?>">
	<div class="garage-inside-ts">
		<a class="button change_ts" href="/garage">Сменить ТС</a>
		<div class="ts-info">
			<h1><?=$m['model_title']?> <?=$m['modification_title']?></h1>
			<div class="img-and-name">
				<div class="img-wrap">
					<?if (file_exists(core\Config::$imgPath . "/models/{$m['model_id']}.jpg")){?>
						<img src="<?=core\Config::$imgUrl?>/models/<?=$m['model_id']?>.jpg" alt="<?=$m['modification_title']?>">
					<?}
					else{?>
						<?$vehicle_id = is_numeric($_GET['modification_id']) ? $m['vehicle_id'] : 11?>
						<img src="<?=core\Config::$imgUrl?>/vehicles/<?=$vehicle_id?>.jpg" alt="<?=$m['modification_title']?>">
					<?}?>
				</div>
				<?if (is_numeric($_GET['modification_id'])){?>
					<label for="ts-name">Имя: <input type="text" name="modification_title" value="<?=$m['garage_title']?>"></label>
				<?}?>
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
                <?if (!is_numeric($_GET['modification_id'])){?>
                    <li class="ionTabs__tab" data-target="Tab_2_name">Заказы</li>
                <?}?>
			</ul>
			<div class="ionTabs__body">
				<div class="ionTabs__item" data-name="Tab_1_name">
					<ul>
						<li>
							<?if (is_numeric($_GET['modification_id'])){?>
								<a href="/original-catalogs/<?=$m['vehicle_href']?>/<?=$m['brend_href']?>/<?=$m['model_id']?>/<?=$m['model_href']?>/vin/<?=$_GET['modification_id']?>">
									Оригинальный каталог
								</a>
							<?}
							else{
								$data = explode(',', $_GET['modification_id']);
								$href = "/original-catalogs/legkovie-avtomobili#/groups?catalogId={$data[0]}";
								if ($data[1]) $href .= "&modelId={$data[1]}";
								if ($data[2]) $href .= "&carId={$data['2']}";
								if ($data[3]) $href .= "&q={$data[3]}";
								?>
								<a href="<?=$href?>">
									Оригинальный каталог
								</a>
							<?}?>
						</li>
						<?if ($res_categories->num_rows){
							while($row = $res_categories->fetch_assoc()){?>
								<li><a href="/category/<?=$row['href']?>"><?=$row['title']?></a></li>
							<?}
						}?>
					</ul>
				</div>
                <?if (!is_numeric($_GET['modification_id'])){?>
                    <div class="ionTabs__item" data-name="Tab_2_name">
                        <?
                        $array = explode(',', $_GET['modification_id']);
                        $query = \core\Item::getQueryItemInfo(['itemVin']);
                        $query .= "
                            WHERE
                                iv.vin LIKE '%{$array[3]}%'
                        ";
                        $itemList = $db->query($query);
                        if ($itemList->num_rows){?>
                            <table id="item_vin">
                                <?foreach($itemList as $item){?>
                                    <tr>
                                        <td><?=$item['created']?></td>
                                        <td>
                                            <a href="/article/<?=$item['id']?>-<?=$item['article']?>">
                                                <?=$item['brend']?> <?=$item['article']?> <?=$item['title_full']?>
                                            </a>
                                        </td>
                                    </tr>
                                <?}?>
                            </table>
                        <?}?>
                    </div>
                <?}?>
				<div class="ionTabs__preloader"></div>
			</div>
		</div>
	</div>
<?}
?>
	
