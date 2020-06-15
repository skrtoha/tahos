<?
use core\Managers;
use core\Item;

if ($_POST['item_image_submit']){
	if (Managers::isActionForbidden('Номенклатура', 'Изменение')){
		Managers::handlerAccessNotAllowed();
	} 
	$item_id = $_POST['item_id'];
	$image = set_image($_FILES['image'], $item_id);
	if (!$image['error']){
		$title = $image['name'];
		$db->insert('fotos', ['item_id' => $item_id, 'title' => $title]);
		message('Фото успешно загружено!');?>
		<li foto_name="<?=$title?>">
			<div>
				<a class="loop" href="#">Увеличить</a>
				<a table="fotos" class="delete_foto" href="#">Удалить</a>
			</div>
			<img src="<?=core\Config::$imgUrl?>/items/small/<?=$item_id?>/<?=$title?>" alt="">
		</li>
	<?}
	else message($image['error'], false);
	exit();
}
$act = $_GET['act'];
if ($_POST['form_submit']){
	$db->delete('items_values', "`item_id` = {$_GET['id']}");
	if (Managers::isActionForbidden('Номенклатура', 'Изменение')){
		Managers::handlerAccessNotAllowed();
	} 
	if (isset($_POST['fv'])){
		foreach($_POST['fv'] as $fv){
			$db->insert('items_values', [
				'item_id' => $_GET['id'],
				'value_id' => $fv
			]/*, ['print' => true]*/);
		}
		unset($_POST['fv']);
	}
	foreach($_POST as $key => $value){
		if ($key == 'form_submit') continue;
		if ($key == 'is_stay') continue;
		if ($key == 'language_id') continue;
		if ($key == 'translate') continue;
		$array[$key] = $value;
	}
	if ($array['article_cat'] && !$array['article']) $array['article'] = article_clear($array['article_cat']);
	if (!$array['article_cat'] && !$array['article'] && $array['barcode']) $array['article'] = $array['barcode'];
	if (!$array['is_blocked']) $array['is_blocked'] = 0;
	if (isset($_GET['id'])){
		$db->delete('items_titles', "`item_id`={$_GET['id']}");
		$res = core\Item::update($array, ['id' => $_GET['id']]);
		$last_id = $_GET['id'];
	} 
	else{
		$res = $db->insert(
			'items', 
			$array
		); 
		if ($res === true){
			$last_id = $db->last_id();
			$db->insert('articles', ['item_id' => $last_id, 'item_diff' => $last_id]);
		}
	} 
	if ($res === true) {
		if (!empty($_FILES['foto'])){
			$res_image = set_image($_FILES['foto'], $last_id);
			core\Item::update(['foto' => $res_image['name']], ['id' => $last_id]);
		} 
		if (!empty($_POST['translate'])){
			$i = 0;
			foreach($_POST['translate'] as $key => $value){
				if (!$_POST['translate'][$i]) continue;
				$db->insert(
					'items_titles', 
					[
						'item_id' => $last_id,
						'language_id' => $_POST['language_id'][$i],
						'title' => $_POST['translate'][$i]
					]
				);
				$i++;
			}
		}
		message('Успешно сохранено!');
		if ($_POST['is_stay']) header("Location: /admin/?view=items&act=item&id=$last_id");
		else header("Location: /admin/?view=items");
	}
	else message($res, false);
}
switch ($act) {
	case 'related': related(); break;
	case 'related_search': related_search(); break;
	case 'complect': complect(); break;
	case 'complect_search': complect_search(); break;
	case 'delete_related':
		if (Managers::isActionForbidden('Номенклатура', 'Удаление')){
			Managers::handlerAccessNotAllowed();
		} 
		$db->delete('articles', "`item_id`={$_GET['item_id']} AND `item_diff`={$_GET['item_diff']}");
		$db->delete('articles', "`item_diff`={$_GET['item_id']} AND `item_id`={$_GET['item_diff']}");
		message('Успешно удалено!');
		header("Location: /admin/?view=items&act=related&id={$_GET['item_id']}");
		break;
	case 'related_add':
		if (Managers::isActionForbidden('Номенклатура', 'Добавление')){
			Managers::handlerAccessNotAllowed();
		} 
		$db->insert('articles',['item_id' => $_GET['item_id'], 'item_diff' => $_GET['item_related']], ['print_query' => false]);
		$db->insert('articles',['item_id' => $_GET['item_related'], 'item_diff' => $_GET['item_id']], ['print_query' => false]);
		message('Успешно добавлено!');
		header("Location: /admin/?view=items&act=related&id={$_GET['item_id']}");
		break;
	case 'delete_complect':
		if (Managers::isActionForbidden('Номенклатура', 'Удаление')){
			Managers::handlerAccessNotAllowed();
		} 
		$db->delete('complects', "`item_id`={$_GET['item_id']} AND `item_diff`={$_GET['item_diff']}");
		message('Успешно удалено!');
		header("Location: /admin/?view=items&act=complect&id={$_GET['item_id']}");
		break;
	case 'complect_add':
		if (Managers::isActionForbidden('Номенклатура', 'Добавление')){
			Managers::handlerAccessNotAllowed();
		} 
		$db->insert('complects',['item_id' => $_GET['item_id'], 'item_diff' => $_GET['item_related']]);
		message('Успешно добавлено!');
		header("Location: /admin/?view=items&act=complect&id={$_GET['item_id']}");
		break;
	case 'delete_foto':
		if (Managers::isActionForbidden('Номенклатура', 'Удаление')){
			Managers::handlerAccessNotAllowed();
		} 
		$id = $_GET['id'];
		core\Item::update(['foto' => ''], ['id' => $id]);
		unlink(core\Config::$imgPath . "/items/big/$id/{$_GET['title']}");
		unlink(core\Config::$imgPath . "/items/small/$id/{$_GET['title']}");
		message('Фото успешно удалено');
		header("Location: ?view=items&act=item&id={$_GET['id']}");
		break;
	case 'change':
		show_form('s_change');
		break;
	case 'add':
		if (Managers::isActionForbidden('Номенклатура', 'Добавление')){
			Managers::handlerAccessNotAllowed();
		} 
		item('s_add');
		break;
	case 'history': history(); break;
	case 'prices': prices(); break;
	case 'substitutes': analogies_substitutes('substitutes'); break;
	case 'analogies': analogies_substitutes('analogies'); break;
	case 'substitutes_search': analogies_substitutes_search('substitutes'); break;
	case 'analogies_search': analogies_substitutes_search('analogies'); break;
	case 'analogies_add':
		if (Managers::isActionForbidden('Номенклатура', 'Добавление')){
			Managers::handlerAccessNotAllowed();
		} 
		analogies_substitutes_add('analogies'); 
		break;
	case 'substitutes_add': 
		if (Managers::isActionForbidden('Номенклатура', 'Добавление')){
			Managers::handlerAccessNotAllowed();
		} 
		analogies_substitutes_add('substitutes'); 
		break;
	case 'substitutes_delete': 
		if (Managers::isActionForbidden('Номенклатура', 'Удаление')){
			Managers::handlerAccessNotAllowed();
		} 
		analogies_substitutes_delete('substitutes'); 
		break;
	case 'analogies_delete': 
		if (Managers::isActionForbidden('Номенклатура', 'Удаление')){
			Managers::handlerAccessNotAllowed();
		} 
		analogies_substitutes_delete('analogies'); 
		break;
	case 'clearAnalogies':
		if (Managers::isActionForbidden('Номенклатура', 'Удаление')){
			Managers::handlerAccessNotAllowed();
		} 
		core\Item::clearAnalogies($_GET['id']);
		message('Список аналогов успешно очищен');
		header("Location: /admin/?view=items&act=analogies&item_id={$_GET['id']}");
		break;
	case 'delete':
		if (Managers::isActionForbidden('Номенклатура', 'Удаление')){
			Managers::handlerAccessNotAllowed();
		} 
		if ($db->delete('items', "`id`=".$_GET['id'])){
			$db->query("
				UPDATE #settings SET `countItems` = `countItems` - 1 WHERE `id`=1
			");
			message('Успешно удалено!');
			header("Location: ?view=items");
		}
		break;
	case 'search': search(); break;
	case 'block_item': 
		core\Timer::start();
		core\Item::blockItem(); 
		echo "Обработка заняла ".core\Timer::end()." секунд";
		break;
	case 'items': items(); break;
	case 'item': item('s_change'); break;
	default: items();
}
function item($act){
	global $db, $page_title, $status;
	$category_id = $db->select('categories_items', 'category_id', "`item_id`=".$item['id']);
	$category_id = $category_id[0]['category_id'];
	$languages = $db->select('languages', "*", '', 'title');
	switch($act){
		case 's_add': 
			$page_title = 'Добавление товара';
			break;
		case 's_change':
			$id = $_GET['id'];
			$item = $db->select('items', '*', "`id`=$id");
			$item = $item[0];
			$page_title = 'Редактирование товара';
			$translates = array();
			if (empty($_POST['translate'])){
				$res_translates = $db->query("
					SELECT 
						it.*,
						l.id AS language_id,
						l.title AS language_title
					FROM 
						#items_titles it 
					LEFT JOIN
						#languages l
					ON
						l.id=it.language_id
					WHERE 
						`item_id`=$id
				", '');
				if ($res_translates->num_rows){
					while ($row = $res_translates->fetch_assoc()) {
						$translates[$row['language_id']] = $row['title'];
					}
				}
			}
			else{
				$i = 0;
				foreach($_POST['translate'] as $key => $value){
					$translates[$_POST['language_id'][$i]] = $_POST['translate'][$i];
					$i++;
				}
			} 
	}
	$status = "<a href='/admin'>Главная</a> > <a href='?view=items'>Номенклатура</a> > $page_title";?>
	<input type="hidden" id="item_id" value="<?=$id?>">
	<? if ($act != 's_add'){?> 
		<div id="need_similar">
			<?$count = $db->getCount('complects', "`item_id`={$item['id']} AND `item_diff`<>{$item['id']}");
			$class = $count ? 'red' : '';?>
			<a class="<?=$class?>" href="?view=items&act=complect&id=<?=$item['id']?>">Комплектность(<?=$count?>)</a>
			<?$count = $db->getCount('articles', "`item_id`={$item['id']} AND `item_diff`<>{$item['id']}");
			$class = $count ? 'red' : '';?>
			<a class="<?=$class?>" href="?view=items&act=related&id=<?=$item['id']?>">Подобные(<?=$count?>)</a>
			<?$count = $db->getCount('store_items', "`item_id`=".$item['id']);
			$class = $count ? 'red' : '';?>
			<a class="<?=$class?>" href="?view=items&act=prices&id=<?=$item['id']?>">Прайсы(<?=$count?>)</a>
			<?$count = $db->getCount('substitutes', "`item_id`=".$item['id']);
			$class = $count ? 'red' : '';?>
			<a class="<?=$class?>" href="?view=items&act=substitutes&item_id=<?=$item['id']?>">Замены(<?=$count?>)</a>
			<?$count = $db->getCount('analogies', "`item_id`=".$item['id']);
			$class = $count ? 'red' : '';?>
			<a class="<?=$class?>" href="?view=items&act=analogies&item_id=<?=$item['id']?>">Аналоги(<?=$count?>)</a>
			<a href="?view=items&act=history&item_id=<?=$item['id']?>">История</a>
			<?if (!Managers::isActionForbidden('Номенклатура', 'Удаление')){?>
				<a style="float: right" href="?view=items&id=<?=$item['id']?>&act=delete" class="delete_item" item_id="<?=$item['id']?>">Удалить</a>
			<?}?>
		</div>
	<?}?>
	<div class="t_form">
		<div class="bg">
			<form method="post" enctype="multipart/form-data">
				<input type="hidden" name="form_submit" value=<?=$act == 's_change' ? 1 : 2?>>
				<input type="hidden" name="is_stay" value="">
				<div class="field">
					<div class="title">Бренд</div>
					<div class="value" id="brends">
						<input type="hidden" >
						<?$brends = $db->select('brends', 'id,title',  '`parent_id`=0', 'title', true, '', true);
						$brend_id = $item['brend_id']?>
						<select style="opacity: 1000" name="brend_id">
							<option value="">ничего не выбрано</option>
							<?foreach ($brends as $k => $v){
								if ($_GET['new_brend']) $selected = $k == $_GET['new_brend'] ? 'selected' : '';
								else $selected = $k == $brend_id ? 'selected' : '';?>
								<option <?=$selected?>  value="<?=$k?>"><?=$v['title']?></option>
							<?}?>
						</select>
						<?$from_item = $act == 's_add' ? 'new_item' : $item['id']?>
						<a href="?view=brends&act=add&from_item=<?=$from_item?>" class="brend_change" act="brends_ch">Добавить новый</a>
					</div>
				</div>
				<div class="field">
					<div class="title">Артикул</div>
					<div class="value"><input type=text name="article" value="<?=$_POST['article'] ? $_POST['article'] : $item['article']?>"></div>
				</div>
				<div class="field">
					<div class="title">Артикул по каталогу</div>
					<div class="value"><input type=text name="article_cat" value="<?=$_POST['article_cat'] ? $_POST['article_cat'] : $item['article_cat']?>"></div>
				</div>
				<div class="field">
					<div class="title">Штрих-код<br>
					<span style="margin-top: 5px;display: block;font-size: 12px;color:grey">(13 цифр)</span></div>
					<div class="value"><input type="text" pattern="[0-9]{9,13}" name="barcode" value="<?=$_POST['barcode'] ? $_POST['barcode'] : $item['barcode']?>"></div>
				</div>
				<div class="field">
					<div class="title">Название</div>
					<div class="value"><input type=text name="title_full" value="<?=htmlspecialchars($_POST['title_full'] ? $_POST['title_full'] : $item['title_full'])?>"></div>
				</div>
				<div class="field">
					<div class="title">Короткое название</div>
					<div class="value"><input type=text name="title" value="<?=$_POST['title'] ? $_POST['title'] : $item['title']?>"></div>
				</div>
				<div class="field">
					<div class="title">Перевод</div>
					<div class="value">
						<button id="language_add">Добавить</button>
						<div id="item_translate">
							<?if (!empty($translates)){
								foreach($translates as $language_id => $title){?>
									<label class="item_translate">
										<select name="language_id[]">
											<?foreach($languages as $l){
												$selected = $l['id'] == $language_id ? 'selected' : ''?>
												<option <?=$selected?> value="<?=$l['id']?>"><?=$l['title']?></option>
											<?}?>
										</select>
										<input type=text name="translate[]" value="<?=$title?>">
										<span class="icon-cross translate_delete"></span>
									</label>
								<?}
							}?>
						</div>
						<script>
							var languages = JSON.parse('<?=json_encode($languages)?>');
						</script>
					</div>
				</div>
				<div class="field">
					<div class="title">Описание</div>
					<div class="value">
						<a href="#" class="hide">Показать</a>
						<div style="margin-top: 10px;display: none">
							<textarea  class="need" name="full_desc" class="htmlarea" style=""><?=$_POST['full_desc'] ? $_POST['full_desc'] : $item['full_desc']?></textarea>
						</div>
					</div>
				</div>
				<div class="field">
					<div class="title">Характеристики</div>
					<div class="value">
						<a href="" class="hide">Показать</a>
						<div style="margin-top: 10px;display: none">
							<textarea class="need" name="characteristics" class="htmlarea" style=""><?=$_POST['characteristics'] ? $_POST['characteristics'] : $item['characteristics']?></textarea>
						</div>
					</div>
				</div>
				<div class="field">
					<div class="title">Применяемость</div>
					<div class="value">
						<a href="" class="hide">Показать</a>
						<div style="margin-top: 10px;display: none">
							<textarea class="need" name="applicability" class="htmlarea" style=""><?=$_POST['applicability'] ? $_POST['applicability'] : $item['applicability']?></textarea>
						</div>
					</div>
				</div>
					<div class="field">
						<div class="title">Основное фото</div>
						<div class="value">
							<?if ($item['foto']){?>	
								<ul item_id="<?=$item['id']?>" id="fotos_item_1">
									<li foto_name="<?=$item['foto']?>">
										<div>
											<a class="loop" href="#">Увеличить</a>
											<a class="delete_item" href="?view=items&id=<?=$item['id']?>&act=delete_foto&title=<?=$item['foto']?>">Удалить</a>
										</div>
										<img src="<?=core\Config::$imgUrl?>/items/small/<?=$item['id']?>/<?=$item['foto']?>" alt="">
									</li>
								</ul>
							<?}
							else{?><input type="file" name="foto"><?}?>
						</div>
					</div> 
				<? if ($act != 's_add'){?> 
					<div class="field">
						<div class="title">Другие фото</div>
						<div class="value">
								<a href="" class="hide">Показать</a>
								<div style="display: none; margin-top: 10px">
								<?$fotos = $db->select('fotos', 'item_id,title', "`item_id`=".$item['id']);?>
								<ul item_id="<?=$_GET['id']?>" style="padding-left: 0" id="fotos_item">
									<?if (count($fotos)){
										foreach($fotos as $foto){?>
											<li foto_name="<?=$foto['title']?>">
												<div>
													<a class="loop" href="#">Увеличить</a>
													<a table="fotos" class="delete_foto" href="#">Удалить</a>
												</div>
												<img src="<?=core\Config::$imgUrl?>/items/small/<?=$foto['item_id']?>/<?=$foto['title']?>" alt="">
											</li>
										<?}
									}?>
								</ul>
								<div id="temp_foto"></div>
								<input type="button" accept="image/*" value="Загрузить фото" id="click_image_item">
								</div>
						</div>
					</div>
				<?}?> 
				<div class="field">
					<div class="title">Вес, гр.</div>
					<div class="value"><input type=text name="weight" value="<?=$_POST['weight'] ? $_POST['weight'] : $item['weight']?>"></div>
				</div>
				<div class="field">
					<div class="title">Тип упаковки</div>
					<div class="value">
						<?$measures = $db->select('measures', '*');?>
						<select name="measure_id">
							<option value="0">ничего не выбрано</option>
							<?foreach($measures as $measure){
								if ($_POST['form_submit']) $selected = $_POST['measure_id'] == $measure['id'] ? 'selected' : '';
								else $selected = $item['measure_id'] == $measure['id'] ? 'selected' : ''?>
								<option <?=$selected?> value="<?=$measure['id']?>"><?=$measure['title']?></option>
							<?}?>
						</select>
					</div>
				</div>
				<div class="field">
					<div class="title">Количество в упаковке</div>
					<div class="value"><input type=text name="amount_package" value="<?=$_POST['amount_package'] ? $_POST['amount_package'] : $item['amount_package']?>"></div>
				</div>
				<div class="field">
					<div class="title">Метаданные</div>
					<div class="value"><input type=text name="meta_desc" value="<?=$_POST['meta_desc'] ? $_POST['meta_desc'] : $item['meta_desc']?>"></div>
				</div>
				<div class="field">
					<div class="title">Ключевые слова</div>
					<div class="value"><input type=text name="meta_key" value="<?=$_POST['meta_key'] ? $_POST['meta_key'] : $item['meta_key']?>"></div>
				</div>
				<div class="field">
					<div class="title">Рейтинг</div>
					<div class="value"><input type=text name="rating" value="<?=$_POST['rating'] ? $_POST['rating'] : $item['rating']?>"></div>
				</div>
				<div class="field">
					<div class="title">Примечание</div>
					<div class="value"><input type=text name="comment" value="<?=$_POST['comment'] ? $_POST['comment'] : $item['comment']?>"></div>
				</div>
				<? if ($act != 's_add'){?>
				<div class="field">
					<div class="title">Категории</div>
					<div class="value">
						<a href="" id="add_category">Добавить</a>
						<div id="properties_categories" item_id="<?=$_GET['id']?>">
							<?$categories = $db->select('categories', '*', '', '', '', '', true);
							$category_items = $db->select('categories_items', 'category_id', "`item_id`=".$item['id']);
							if (count($category_items)){
								foreach ($category_items as $category_item) {?>
										<span title="Удалить" class="properties" category_id="<?=$category_item['category_id']?>">
											<?$category = $db->select('categories', '*', "`id`=".$category_item['category_id']);
											$category = $category[0]?>
											<b><?=$db->getFieldOnID('categories', $category['parent_id'], 'title')?></b> > <?=$category['title']?>
										</span>
									<?}?>
							<?}?>
						</div>
					</div>
				</div>
				<?}?>
				<? if ($act != 's_add'){?> 
					<div class="field">
						<div class="title">Свойства</div>
						<div class="value" id="properties">
							<?$array = Item::getFiltersByItemID($_GET['id']);
							if (!empty($array)){?>
								<div id="category_items" item_id="<?=$_GET['id']?>">
									<?foreach($array as $category){?>
										<div class="category_item">
											<table class="category" category_id="<?=$category['id']?>">
												<?foreach($category['filters'] as $filter){
													$checked = [];
													?>
													<tr>
														<td><?=$filter['title']?></td>
														<td>
															<select filter_id="<?=$filter['id']?>">
																<option value="">...выберите</option>
																<?foreach($filter['filter_values'] as $fv){
																	if($fv['checked']) $checked[] = $fv;
																	?>
																	<option  <?=$fv['checked'] ? 'disabled' : ''?> value="<?=$fv['id']?>"><?=$fv['title']?></option>
																<?}?>
															</select>
															<div class="checked" filter_id="<?=$filter['id']?>">
																<?if (!empty($checked)){
																	foreach($checked as $ch){?>
																		<label class="filter_value">
																			<input type="hidden" name="fv[]" value="<?=$ch['id']?>">
																			<?=$ch['title']?>
																			<span class="icon-cross1"></span>
																		</label>
																	<?}?>
																<?}?>
															</div>
														</td>
													</tr>
												<?}?>
											</table>
										</div>
									<?}?>
								</div>
							<?}
							else{?>
								<p>Свойства не найдены либо не выбраны категории товара</p>
							<?}?>
						</div>
					</div>
				<?}?>
				<? if ($act != 's_add'){?> 
					<div class="field">
						<div class="title">Заблокировано</div>
						<div class="value">
							<input type="checkbox" <?=$_POST['is_blocked'] || $item['is_blocked'] ? 'checked' : ''?> name="is_blocked" value="1">
						</div>
					</div>
				<?}?>
				<div class="value">
					<input type="submit" class="button" value="Сохранить и выйти">
					<input id="is_stay" type="submit" class="button" value="Сохранить и остаться">
				</div>
			</form>
		</div>
	</div>
	<form style="display: none" id="upload_image" action="/admin/?view=items&act=item&id=<?=$item['id']?>?>" enctype="multipart/form-data" method="post">
		<input id="item_image" name="image" type="file">
		<input type="hidden" value="<?=$item['id']?>" name="item_id">
		<input type="hidden" name="item_image_submit" value="1" style="display: none">
	</form>
	<div class="actions"><a href="<?=$_SERVER['HTTP_REFERER']?>">Назад</a></div>
	<div class="popup-gallery"></div>
<?}
function history(){
	global $status, $db, $page_title;
	$page_title = "История товара";
	$res_item = $db->query("
		SELECT
			i.id,
			i.brend_id,
			b.title AS brend,
			i.article,
			i.title_full,
			i.source,
			DATE_FORMAT(i.created, '%d.%m.%Y %H:%i:%s') AS date
		FROM
			#items i
		LEFT JOIN
			#brends b ON b.id = i.brend_id
		WHERE
			i.id = {$_GET['item_id']}
	", '');
	$item = $res_item->fetch_assoc();
	$resOrderValues = $db->query("
		SELECT
			ov.item_id,
			ov.order_id,
			ov.store_id,
			ov.price,
			DATE_FORMAT(o.created, '%d.%m.%Y %H:%i:%s') AS date,
			IF(
				u.organization_name <> '',
				CONCAT_WS (' ', ot.title, u.organization_name),
				CONCAT_WS (' ', u.name_1, u.name_2, u.name_3)
			) AS name,
			ov.user_id,
			ps.cipher
		FROM
			#orders_values ov
		LEFT JOIN
			#orders o ON o.id = ov.order_id
		LEFT JOIN
			#users u ON u.id = ov.user_id
		LEFT JOIN 
			#organizations_types ot ON ot.id=u.organization_type
		LEFT JOIN
			#provider_stores ps ON ps.id = ov.store_id
		WHERE
			ov.item_id = {$_GET['item_id']}
	", '');
	$status = "<a href='/admin'>Главная</a> > <a href='?view=items'>Номенклатура</a> > ";
	$status .= "<a href='/admin/?view=items&act=item&id={$_GET['item_id']}'>{$item['brend']} - {$item['article']}</a> > $page_title";
	?>
	<table id="itemHistory" class="t_table" cellspacing="1">
		<tr class="head">
			<td>Дата</td>
			<td>Действие</td>
		</tr>
		<tr>
			<td label="Дата"><?=$item['date']?></td>
			<td label="Действие">Добавлен <?=$item['source'] ? "c {$item['source']}" : ''?></td>
		</tr>
		<?if ($resOrderValues->num_rows){
			while($row = $resOrderValues->fetch_assoc()){?>
				<tr>
					<td label="Дата"><?=$row['date']?></td>
					<td label="Действие">
						Заказал <a target="_blank" href="/admin/?view=users&act=change&id=<?=$row['user_id']?>"><?=$row['name']?></a> c <?=$row['cipher']?> по цене <?=$row['price']?> руб. <a href="/admin/?view=orders&id=<?=$row['order_id']?>&act=change">Перейти в заказ</a>
					</td>
				</tr>
			<?}
		}?>
	</table>
	<a style="display: block;margin-top: 10px" href="<?=$_SERVER['HTTP_REFERER']?>">Назад</a>
<?}
function prices(){
	global $status, $db, $page_title;
	$id = $_GET['id'];
	$page_title = "Прайсы";
	$status = "<a href='/admin'>Главная</a> > <a href='?view=items'>Номенклатура</a> > ";
	$status .= "<a href='?view=items&id=$id&act=item'>".$db->getFieldOnID('items', $id, 'article')."</a> > $page_title";
	$res_store_items = $db->query("
		SELECT
			si.item_id,
			si.store_id,
			ps.cipher,
			ROUND(si.price * c.rate, 2) AS price,
			si.in_stock,
			si.packaging,
			c.title AS currency,
			p.title AS provider
		FROM
			#store_items si
		LEFT JOIN
			#provider_stores ps ON ps.id=si.store_id
		LEFT JOIN
			#currencies c ON c.id=ps.currency_id
		LEFT JOIN
			#providers p ON p.id=ps.provider_id
		WHERE
			si.item_id={$_GET['id']}
		ORDER BY price
	", '');?>
	<div id="total">Всего: <?=$res_store_items->num_rows?></div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Шифр</td>
			<td>Поставщик</td>
			<td>Цена</td>
			<td>Валюта</td>
			<td>В наличии</td>
			<td>Мин. заказ</td>
		</tr>
		<?if ($res_store_items->num_rows){
			while($row = $res_store_items->fetch_assoc()){?>
				<tr>
					<td label="Шифр">
						<a class="store" store_id="<?=$row['store_id']?>"><?=$row['cipher']?></a>
					</td>
					<td label="Поставщик"><?=$row['provider']?></td>
					<td label="Цена"><?=$row['price']?></td>
					<td label="Валюта"><?=$row['currency']?></td>
					<td label="В наличии"><?=$row['in_stock']?></td>
					<td label="Мин. заказ"><?=$row['packaging']?></td>
				</tr>
		<?}
		}
		else{?>
			<tr><td colspan="6">Поставщиков не найдено</td></tr>
		<?}?>
	</table>
	<a style="display: block;margin-top: 10px" href="<?=$_SERVER['HTTP_REFERER']?>">Назад</a>
<?}
function get_href_categories($ids, $titles){
	if (!count($ids)) return false;
	$str = '';
	$ids = explode(',', $ids);
	$titles = explode(',', $titles);
	foreach ($ids as $key => $value){
		$str .= "
			<a href='/admin/?view=category&id=$value'>".
				trim($titles[$key])
			."</a>, ";
	}
	return substr($str, 0, -2);
}
function related(){
	global $status, $db, $page_title;
	$item_id = $_GET['id'];
	$res_items = $db->query("
		SELECT
			i.id,
			i.title_full,
			i.barcode,
			i.brend_id,
			i.article,
			b.title AS brend,
			IF(i.id=$item_id, 1, 0) AS main_id
		FROM
			#items i
		LEFT JOIN #brends b ON i.brend_id=b.id
		WHERE
			i.id IN (
				SELECT item_diff FROM #articles WHERE item_id=$item_id
			) 
	", '');
	if ($res_items->num_rows){
		while($v = $res_items->fetch_assoc()){
			if ($v['main_id']) $item = $v;
			else $items[] = $v;
		}
	}
	$page_title = "Подобные товары";
	$status = "<a href='/admin'>Главная</a> > <a href='?view=items&act=item&id=$item_id'>{$item['title_full']}</a> > $page_title";?>
	<a href="?view=items&act=item&id=<?=$item_id?>" style="margin-bottom: 10px;display: block">Карточка товара</a>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Название</td>
			<td>Штрих-код</td>
		</tr>
		<tr>
			<td label="Бренд"><?=$db->getFieldOnID('brends', $item['brend_id'], 'title')?></td>
			<td label="Артикул"><a href="?items&id=<?=$item['id']?>&act=change"><?=$item['article']?></a></td>
			<td label="Название"><?=$item['title_full']?></td>
			<td label="Штрих-код"><?=$item['barcode']?></td>
		</tr>
	</table>
	<!-- <div id="total" style="margin-top: 20px">Всего: <?=$res_items->num_rows?></div> -->
	<div id="relatedActs" style="margin-top: 5px" class="actions">
		<form action="/admin/?view=items&act=related_search&id=<?=$item_id?>" method="post">
			<input style="width: 264px;" type="text" name="search" value="<?=$_POST['search']?>" placeholder="Поиск для добавления">
			<label>
				<input checked type="radio" name="type" value="artical">
				по артикулу
			</label>
			<label>
				<input type="radio" name="type" value="id">
				по id
			</label>
			<input type="submit" value="Искать">
		</form>
	</div>
	<table style="" class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Название</td>
			<td>Штрих-код</td>
			<td></td>
		</tr>
		<?if (count($items)){
			foreach($items as $value){?>
				<tr>
					<td label="Бренд"><?=$value['brend']?></td>
					<td label="Артикул"><a href="?view=items&act=item&id=<?=$value['id']?>"><?=$value['article']?></a></td>
					<td label="Название"><?=$value['title_full']?></td>
					<td label="Штрих-код"><?=$value['barcode']?></td>
					<td label=""><a class="delete_item" href="?view=items&act=delete_related&item_id=<?=$_GET['id']?>&item_diff=<?=$value['id']?>">Удалить</a></td>
				</tr>
			<?}	
		}
		else{?>
			<tr><td colspan="5">Подобных товаров не найдено</td></tr>
		<?}?>
	</table>
<?}
function related_search(){
	global $status, $db, $page_title;
	$where = "i.id!={$_GET['id']}"; ;
	switch($_POST['type']){
		case 'artical':
			$article = article_clear($_POST['search']);
			$where .= " AND i.article='$article'";
			break;
		case 'id':
			$where .= " AND i.id={$_POST['search']}";
			break;
	}
	$item_id = $_GET['id'];
	$q_items = "
		SELECT
			i.id,
			i.title_full,
			i.barcode,
			i.brend_id,
			i.article,
			b.title AS brend
		FROM
			#items i
		LEFT JOIN #brends b ON i.brend_id=b.id
		LEFT JOIN #categories_items ci ON ci.item_id=i.id
		LEFT JOIN #categories c ON c.id=ci.category_id
		WHERE 
			$where
	";
	$res_items = $db->query($q_items, '');
	if ($res_items->num_rows > 100){
		message('Слишком много совпадений! Уточните поиск.', false);
		header("Location: /admin/?view=items&act=related&id=$item_id");
	}
	$page_title = "Поиск для добавления";
	$status = "<a href='/admin'>Главная</a> > $page_title";?>
	<a href="?view=items&act=item&id=<?=$item_id?>" style="margin-bottom: 10px;display: block">Карточка товара</a>
	<div id="total" style="margin-top: 20px">Всего: <?=$res_items->num_rows?></div>
	<div style="margin-top: 5px" class="actions">
		<form action="/admin/?view=items&act=related_search&id=<?=$item_id?>" method="post">
			<input style="width: 264px;" type="text" name="search" value="<?=$search?>" placeholder="Поиск для добавления">
			<input type="submit" value="Искать">
		</form>
	</div>
	<table style="" class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Название</td>
			<td>Штрих-код</td>
			<td>Категории</td>
			<td></td>
		</tr>
		<?if ($res_items->num_rows){
			while($value = $res_items->fetch_assoc()){?>
				<tr>
					<td><?=$value['brend']?></td>
					<td><a href="?view=items&act=item&id=<?=$value['id']?>"><?=$value['article']?></a></td>
					<td><?=$value['title_full']?></td>
					<td><?=$value['barcode']?></td>
					<td><a href="?view=items&act=related_add&item_related=<?=$value['id']?>&item_id=<?=$item_id?>">Добавить</a></td>
				</tr>
			<?}	
		}
		else{?>
			<tr><td colspan="5">Замен не найдено</td></tr>
		<?}?>
	</table>
<?}
function item_search(){
	global $status, $db;
	$item_id = $_GET['item_id'];
	$item = $db->select('items', 'id,article,title_full,barcode,brend_id', "`id`=$item_id");
	$item = $item[0];
	$page_title = "Добавление замены для товара";
	$search = article_clear($_POST['search']);
	if (!$search) header ('Location: ?view=substitutes');
	$is_subtitutes = $db->select('substitutes', "item_sub", "`item_id`=$item_id");
	if (count($is_subtitutes)){
		$in = "";
		foreach ($is_subtitutes as $value) $in .= $value['item_sub'].',';
		$in = substr($in, 0, -1);
	} 
	if ($in) $where = "(`article`='$search' OR `barcode` LIKE '%$search%' OR `title_full` LIKE '%$search%') AND `id` NOT IN ($in)";
	else $where = "`article`='$search' OR `barcode` LIKE '%$search%' OR `title_full` LIKE '%$search%'";
	$items = $db->select('items', 'title_full,id,article,barcode,brend_id', $where);
	$categories = $db->select('categories', '*', '', '', '', '', true);
	$status = "<a href='/admin'>Главная</a> > <a href='?view=substitutes'>Замены</a> > $page_title";?>
	<b style="margin-bottom: 10px;display: block">Товар</b>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Название</td>
			<td>Штрих-код</td>
			<td>Категории</td>
			<td></td>
		</tr>
		<tr>
			<td><?=$db->getFieldOnID('brends', $item['brend_id'], 'title')?></td>
			<td><a href="?view=items&act=item&id=<?=$item['id']?>"><?=$item['article']?></a></td>
			<td><?=$item['title_full']?></td>
			<td><?=$item['barcode']?></td>
			<td>
				<?$categories_items = $db->select('categories_items', 'category_id', "`item_id`=".$item['id']);
				if (count($categories_items)){
					foreach ($categories_items as $category_item) {?>
						<a href="/admin/?view=category&id=<?=$category_item['category_id']?>"><?=$categories[$category_item['category_id']]['title']?></a>
					<?}
				}?>
			</td>
			<td></td>
		</tr>
	</table>
	<b style="display: block; margin: 10px 0">Найденные товары:</b>
	<div id="total" style="margin-top: 20px">Всего: <?=count($items)?></div>
	<div class="actions">
		<form style="margin-top: -3px;float: left;margin-bottom: 10px;" action="?view=substitutes&act=item_search&item_id=<?=$item_id?>" method="post">
			<input style="width: 264px;" type="text" name="search" value="<?=$_POST['search']?>" placeholder="Поиск">
			<input type="submit" value="Искать">
		</form>
	</div>
	<table style="" class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Название</td>
			<td>Штрих-код</td>
			<td>Категории</td>
			<td></td>
		</tr>
		<?if (count($items)){
			foreach($items as $item){?>
				<tr>
					<td><?=$db->getFieldOnID('brends', $item['brend_id'], 'title')?></td>
					<td><a href="?view=items&act=item&id=<?=$item['id']?>"><?=$item['article']?></a></td>
					<td><?=$item['title_full']?></td>
					<td><?=$item['barcode']?></td>
					<td>
						<?$categories_items = $db->select('categories_items', 'category_id', "`item_id`=".$item['id']);
						if (count($categories_items)){
							foreach ($categories_items as $category_item) {?>
								<a href="/admin/?view=category&id=<?=$category_item['category_id']?>"><?=$categories[$category_item['category_id']]['title']?></a>
							<?}
						}?>
					</td>
					<td><a href="?view=substitutes&act=add_item&item_id=<?=$item_id?>&id=<?=$item['id']?>">Добавить</a></td>
				</tr>
			<?}	
		}
		else{?>
			<tr><td colspan="5">Товары не найдены</td></tr>
		<?}?>
	</table>
<?}
function complect(){
	global $status, $db, $page_title;
	$complects = [];
	$item_id = $_GET['id'];
	$res_complects = $db->query("
		SELECT `item_diff` FROM #complects WHERE `item_id`=$item_id
	");
	if ($res_complects->num_rows){
		while($row = $res_complects->fetch_assoc()) $complects[] = $row['item_diff'];
		$whereComplects = " OR I.id IN (".implode(',', $complects).")";
	}
	$res_items = $db->query("
		SELECT
			i.id,
			i.title_full,
			i.barcode,
			i.brend_id,
			i.article,
			b.title AS brend,
			GROUP_CONCAT(
				c.id
				ORDER BY c.id
				SEPARATOR ','
			) as categories_ids,
			GROUP_CONCAT(
				c.title
				ORDER BY c.id
				SEPARATOR ','
			) as categories_titles,
			IF(i.id=$item_id, 1, 0) AS main_id
		FROM
			#items i
		LEFT JOIN #brends b ON i.brend_id=b.id
		LEFT JOIN #categories_items ci ON ci.item_id=i.id
		LEFT JOIN #categories c ON c.id=ci.category_id
		WHERE
			i.id=$item_id 
			$whereComplects
		GROUP BY i.id
	", '');
	if ($res_items->num_rows){
		while($v = $res_items->fetch_assoc()){
			if ($v['main_id']) $item = $v;
			else $items[] = $v;
		}
	}
	$page_title = "Комплектность";
	$status = "<a href='/admin'>Главная</a> > <a href='?view=items&act=item&id=$item_id'>{$item['title_full']}</a> > $page_title";?>
	<a href="?view=items&act=item&id=<?=$item_id?>" style="margin-bottom: 10px;display: block">Карточка товара</a>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Название</td>
			<td>Штрих-код</td>
			<td>Категории</td>
		</tr>
		<tr>
			<td label="Бренд"><?=$item['brend']?></td>
			<td label="Артикул"><a href="?view=items&act=item&id=<?=$item['id']?>"><?=$item['article']?></a></td>
			<td label="Название"><?=$item['title_full']?></td>
			<td label="Штрих-код"><?=$item['barcode']?></td>
			<td label="Категории"><?=get_href_categories($item['categories_ids'], $item['categories_titles'])?></td>
		</tr>
	</table>
	<div id="total" style="margin-top: 20px">Всего: <?=count($items)?></div>
	<div style="margin-top: 5px" class="actions">
		<form action="/admin/?view=items&act=complect_search&id=<?=$item_id?>" method="post">
			<input style="width: 264px;" type="text" name="search" value="<?=$_POST['search']?>" placeholder="Поиск для добавления">
			<input type="submit" value="Искать">
		</form>
	</div>
	<table style="" class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Название</td>
			<td>Штрих-код</td>
			<td>Категории</td>
			<td></td>
		</tr>
		<?if (count($items)){
			foreach($items as $value){?>
				<tr>
					<td label="Бренд"><?=$value['brend']?></td>
					<td label="Артикул"><a href="?view=items&act=item&id=<?=$value['id']?>"><?=$value['article']?></a></td>
					<td label="Название"><?=$value['title_full']?></td>
					<td label="Штрих-код"><?=$value['barcode']?></td>
					<td label="Категории"><?=get_href_categories($value['categories_ids'], $value['categories_titles'])?></td>
					<td label=""><a class="delete_item" href="?view=items&act=delete_complect&item_id=<?=$_GET['id']?>&item_diff=<?=$value['id']?>">Удалить</a></td>
				</tr>
			<?}	
		}
		else{?>
			<tr><td colspan="5">Товаров не найдено</td></tr>
		<?}?>
	</table>
<?}
function complect_search(){
	global $status, $db, $page_title;
	$search = trim($_POST['search']);
	$item_id = $_GET['id'];
	$q_items = "
		SELECT
			i.id,
			i.title_full,
			i.barcode,
			i.brend_id,
			i.article,
			b.title AS brend
		FROM
			#items i
		LEFT JOIN #brends b ON i.brend_id=b.id
		LEFT JOIN #categories_items ci ON ci.item_id=i.id
		LEFT JOIN #categories c ON c.id=ci.category_id
		WHERE 
			i.article='$search' OR
			i.id='$search' AND 
			i.id<>$item_id
	";
	$res_items = $db->query($q_items, '');
	if ($res_items->num_rows > 100){
		message('Слишком много совпадений! Уточните поиск.', false);
		header("Location: /admin/?view=items&act=complect&id=$item_id");
	}
	$page_title = "Поиск для добавления";
	$status = "<a href='/admin'>Главная</a> > $page_title";?>
	<a href="?view=items&act=item&id=<?=$item_id?>" style="margin-bottom: 10px;display: block">Карточка товара</a>
	<div id="total" style="margin-top: 20px">Всего: <?=$res_items->num_rows?></div>
	<div style="margin-top: 5px" class="actions">
		<form action="/admin/?view=items&act=complect_search&id=<?=$item_id?>" method="post">
			<input style="width: 264px;" type="text" name="search" value="<?=$search?>" placeholder="Поиск для добавления">
			<input type="submit" value="Искать">
		</form>
	</div>
	<table style="" class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Название</td>
			<td>Штрих-код</td>
			<td></td>
		</tr>
		<?if ($res_items->num_rows){
			while($value = $res_items->fetch_assoc()){?>
				<tr>
					<td label="Бренд"><?=$value['brend']?></td>
					<td label="Артикул"><a href="?view=items&act=item&id=<?=$value['id']?>"><?=$value['article']?></a></td>
					<td label="Название"><?=$value['title_full']?></td>
					<td label="Штрих-код"><?=$value['barcode']?></td>
					<td><a href="?view=items&act=complect_add&item_related=<?=$value['id']?>&item_id=<?=$item_id?>">Добавить</a></td>
				</tr>
			<?}	
		}
		else{?>
			<tr><td colspan="5">Комплектов не найдено</td></tr>
		<?}?>
	</table>
<?}
function analogies_substitutes($type){
	global $status, $db, $page_title;
	$item_id = $_GET['item_id'];
	switch($type){
		case 'analogies': $page_title = 'Аналоги'; break;
		case 'substitutes': $page_title = 'Замены'; break;
	}
	$item_temp = get_item();
	$item = $item_temp['item'];
	$status = "
		<a href='/admin'>Главная</a> > 
		<a href='/admin/?view=items'>Номенклатура</a> >
		<a href='/admin/?view=items&act=item&id={$_GET['item_id']}'>{$item['brend']} - {$item['article']}</a> >
		$page_title
	";
	$res_items = $db->query("
		SELECT
			diff.item_diff AS item_id,
			diff.hidden,
			i.article,
			i.title_full,
			i.barcode,
			b.title AS brend,
			ci.category_id,
			c.title AS category
		FROM
			#$type diff
		LEFT JOIN
			#items i ON i.id=diff.item_diff
		LEFT JOIN 
			#brends b ON b.id=i.brend_id
		LEFT JOIN #categories_items ci ON i.id=ci.item_id
		LEFT JOIN #categories c ON c.id=ci.category_id
		WHERE
			diff.item_id=$item_id
		ORDER BY
			b.title
	", '');
	if ($res_items->num_rows){
		while($r = $res_items->fetch_assoc()){
			$i = & $items[$r['item_id']];
			$i['article'] = $r['article'];
			$i['title_full'] = $r['title_full'];
			$i['barcode'] = $r['barcode'];
			$i['brend'] = $r['brend'];
			$i['hidden'] = $r['hidden'];
			if ($r['category_id']) $i['categories'][$r['category_id']] = $r['category'];
		};
	}
	?>
	<a id="goToItemCard" href="?view=items&id=<?=$item_id?>&act=item">Карточка товара</a>
	<?if($_GET['act'] == 'analogies'){?>
		<a class="clearAnalogies" href="?view=items&id=<?=$item_id?>&act=clearAnalogies">Очистить аналоги</a>
	<?}?>
	<?=$item_temp['html']?>
	<b style="display: block; margin: 10px 0"><?=$page_title?>:</b>
	<div id="total" style="margin-top: 20px">Всего: <?=count($items)?></div>
	<input type="hidden" name="item_id" value="<?=$_GET['item_id']?>">
	<div class="actions">
		<form style="margin-top: -3px;float: left;margin-bottom: 10px;" action="?view=items&act=<?=$type?>_search&item_id=<?=$item_id?>" method="post">
			<input style="width: 264px;" type="text" name="search" value="<?=$_POST['search']?>" placeholder="Поиск для добавления">
			<input type="submit" value="Искать">
		</form>
	</div>
	<table style="" class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Название</td>
			<td>Штрих-код</td>
			<td>Категории</td>
			<?if ($type == 'analogies'){?>
				<td>Скрыть</td>
			<?}?>
			<td></td>
		</tr>
		<?if (!empty($items)){
			foreach($items as $id => $item){?>
				<tr>
					<td label="Бренд"><?=$item['brend']?></td>
					<td label="Артикул"><a href="?view=items&act=item&id=<?=$id?>"><?=$item['article']?></a></td>
					<td label="Название"><?=$item['title_full']?></td>
					<td label="Штрих-код"><?=$item['barcode']?></td>
					<td label="Категории">
						<?if (!empty($item['categories'])){
							foreach ($item['categories'] as $key => $value) {?>
								<a href="/admin/?view=category&id=<?=$key?>"><?=$value?></a>
							<?}
						}?>
					</td>
					<?if ($type == 'analogies'){?>
						<td label="Скрыть"><input <?=$item['hidden'] ? 'checked' : ''?> name="hidden" type="checkbox" value="<?=$id?>"></td>
					<?}?>
					<td label=""><a class="<?=$type?>_delete delete_item" href="?view=items&act=<?=$type?>_delete&item_id=<?=$item_id?>&delete_item=<?=$id?>">Удалить</a></td>
				</tr>
			<?}	
		}
		else{?>
			<tr><td colspan="6">Ничего не найдено</td></tr>
		<?}?>
	</table>
<?}
function analogies_substitutes_search($type){
	global $status, $db, $page_title;
	$item_id = $_GET['item_id'];
	switch($type){
		case 'analogies': $page_title = 'Поиск аналогов'; $status_title = 'Аналоги'; break;
		case 'substitutes': $page_title = 'Поиск замен'; $status_title = 'Замены'; break;
	}
	$article = article_clear($_POST['search']);
	$item_temp = get_item();
	$item = $item_temp['item'];
	$status = "
		<a href='/admin'>Главная</a> > 
		<a href='/admin/?view=items'>Номенклатура</a> >
		<a href='/admin/?view=items&act=item&id={$_GET['item_id']}'>{$item['brend']} - {$item['article']}</a> >
		<a href='/admin/?view=items&act=$type&item_id={$_GET['item_id']}'>$status_title</a> >
		$page_title
	";
	$where = "i.id!={$_GET['item_id']} AND (i.article='$article'";
	if (is_numeric($article)) $where .= " OR i.id=$article";
	$where .= ')';
	$res_items = $db->query("
		SELECT
			i.id,
			i.article,
			i.title_full,
			i.barcode,
			b.title AS brend,
			ci.category_id,
			c.title AS category,
			diff.item_diff,
			diff.item_id
		FROM
			#items i
		LEFT JOIN 
			#brends b ON b.id=i.brend_id
		LEFT JOIN #categories_items ci ON i.id=ci.item_id
		LEFT JOIN #categories c ON c.id=ci.category_id
		LEFT JOIN #$type diff ON diff.item_diff=i.id AND diff.item_id={$_GET['item_id']}
		WHERE
			$where AND diff.item_id IS NULL
	", '');
	if ($res_items->num_rows){
		while($r = $res_items->fetch_assoc()){
			$i = & $items[$r['id']];
			$i['article'] = $r['article'];
			$i['title_full'] = $r['title_full'];
			$i['barcode'] = $r['barcode'];
			$i['brend'] = $r['brend'];
			if ($r['category_id']) $i['categories'][$r['category_id']] = $r['category'];
		};
	}?>
	<b style="margin-bottom: 10px;display: block">Товар</b>
	<?=$item_temp['html'];?>
	<b style="display: block; margin: 10px 0">Найденные товары:</b>
	<div id="total">Всего: <?=count($items)?></div>
	<div class="actions" style="width: 100%">
		<form style="margin-top: -3px;float: left;margin-bottom: 10px;" action="?view=items&act=<?=$type?>_search&item_id=<?=$_GET['item_id']?>" method="post">
			<input style="width: 264px;" type="text" name="search" value="<?=$_POST['search']?>" placeholder="Поиск">
			<input type="submit" value="Искать">
		</form>
	</div>
	<table style="" class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Название</td>
			<td>Штрих-код</td>
			<td>Категории</td>
			<td></td>
		</tr>
		<?if (count($items)){
			foreach($items as $id => $item){?>
				<tr>
					<td label="Бренд"><?=$item['brend']?></td>
					<td label="Артикул"><a href="?view=items&act=item&id=<?=$id?>"><?=$item['article']?></a></td>
					<td label="Название"><?=$item['title_full']?></td>
					<td label="Штрих-код"><?=$item['barcode']?></td>
					<td label="Категории">
						<?if (!empty($item['categories'])){
							foreach ($item['categories'] as $key => $value) {?>
								<a href="/admin/?view=category&id=<?=$key?>"><?=$value?></a> 
							<?}
						}?>
					</td>
					<td><a class="<?=$type?>_add" href="?view=items&act=<?=$type?>_add&item_id=<?=$_GET['item_id']?>&added=<?=$id?>">Добавить</a></td>
				</tr>
			<?}	
		}
		else{?>
			<tr><td colspan="5">Товары не найдены</td></tr>
		<?}?>
	</table>
<?}
function analogies_substitutes_add($type){
	global $db;
	if ($_GET['all'] == 1) {
		$res = $db->query("
			SELECT * FROM #analogies WHERE item_id={$_GET['item_id']}
		", '');
		if ($res->num_rows){
			while($row = $res->fetch_assoc()){
				$db->insert($type, ['item_id' => $_GET['added'], 'item_diff' => $row['item_diff']]);
				$db->insert($type, ['item_id' => $row['item_diff'], 'item_diff' => $_GET['added']]);
			} 
		}
	}
	$db->insert($type, ['item_id' => $_GET['item_id'], 'item_diff' => $_GET['added']]);
	$db->insert($type, ['item_id' => $_GET['added'], 'item_diff' => $_GET['item_id']]);
	message('Успешно добавлено!');
	header("Location: ?view=items&act=$type&item_id={$_GET['item_id']}");
}
function analogies_substitutes_delete($type){
	global $db;
	if ($_GET['all']){
		$res = $db->query("
			SELECT item_diff FROM #analogies WHERE `item_id`={$_GET['item_id']}
		", '');
		if ($res->num_rows){
			while($row = $res->fetch_assoc()){
				$db->delete('analogies', "
					(`item_id`={$_GET['delete_item']} AND `item_diff`={$row['item_diff']}) OR
					(`item_id`={$row['item_diff']} AND `item_diff`={$_GET['delete_item']})
				");
			}
		}
	}
	$db->delete($type, "`item_id`={$_GET['item_id']} AND `item_diff`={$_GET['delete_item']}");
	$db->delete($type, "`item_id`={$_GET['delete_item']} AND `item_diff`={$_GET['item_id']}");
	message('Успешно удалено!');
	header("Location: ?view=items&act=$type&item_id={$_GET['item_id']}");
}
function get_item(){
	global $db;
	$res_item = $db->query("
		SELECT
			i.id,
			i.article,
			i.title_full,
			i.barcode,
			b.title AS brend,
			ci.category_id,
			c.title AS category
		FROM
			#items i
		LEFT JOIN 
			#brends b ON b.id=i.brend_id
		LEFT JOIN #categories_items ci ON i.id=ci.item_id
		LEFT JOIN #categories c ON c.id=ci.category_id
		WHERE
			i.id={$_GET['item_id']}
	", '');
	while($r = $res_item->fetch_assoc()){
		$item['id'] = $r['id'];
		$item['article'] = $r['article'];
		$item['title_full'] = $r['title_full'];
		$item['barcode'] = $r['barcode'];
		$item['brend'] = $r['brend'];
		$item['categories'][$r['category_id']] = $r['category'];
	}
	ob_start();?>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Название</td>
			<td>Штрих-код</td>
			<td>Категории</td>
		</tr>
		<tr>
			<td label="Бренд"><?=$item['brend']?></td>
			<td label="Артикул"><a href="/admin/?view=items&act=item&id=<?=$item['id']?>"><?=$item['article']?></a></td>
			<td label="Название"><?=$item['title_full']?></td>
			<td label="Штрих-код"><?=$item['barcode']?></td>
			<td label="Категории">
				<?if (!empty($item['categories'])){
					foreach($item['categories'] as $key => $value){?>
						<a href="?view=category&act=items&id=<?=$key?>"><?=$value?></a> 
					<?}
				}
				?>
			</td>
		</tr>
	</table>
	<?
	$html = ob_get_contents();
	ob_clean();
	return[
		'item' => $item,
		'html' => $html
	];
}
function items(){
	global $status, $db, $page_title, $settings;
	require_once('templates/pagination.php');
	$all = $settings['countItems'];
	$perPage = 30;
	$linkLimit = 10;
	$page = $_GET['page'] ? $_GET['page'] : 1;
	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ? $chank[$page] : 0;
	$items = $db->select('items', 'title_full,id,article,article_cat,barcode,brend_id', '', 'id', false, "$start,$perPage");
	$page_title = "Номенклатура";
	$status = "<a href='/admin'>Главная</a> > $page_title"?>
	<div id="total" style="margin-top: 10px;">Всего: <?=$all?></div>
	<div class="actions items" class="" style="">
		<form action="?view=items&act=search" method="post">
			<input style="width: 264px;" type="text" name="search" value="" placeholder="Поиск по артикулу, vid и названию" required>
			<input type="submit" value="Искать">
		</form>
		<a href="?view=items&act=add">Добавить</a>
		<a href="?view=items&act=block_item">Заблокировать товар</a>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Каталожный номер</td>
			<td>Название</td>
			<td>Штрих-код</td>
			<td>Категории</td>
		</tr>
		<?if (count($items)){
			$db->isProfiling = false;
			foreach($items as $item){?>
				<tr class="items_box" item_id="<?=$item['id']?>">
					<td label="Бренд"><?=$db->getFieldOnID('brends', $item['brend_id'], 'title')?></td>
					<td label="Артикул"><?=$item['article']?></td>
					<td label="Каталожный номер"><?=$item['article_cat']?></td>
					<td label="Название"><?=$item['title_full']?></td>
					<td label="Штрих-код"><?=$item['barcode']?></td>
					<td label="Категории">
						<?$categories_items = $db->select('categories_items', 'category_id', "`item_id`=".$item['id']);
						if (!empty($categories_items)){
							foreach ($categories_items as $category_item) {?>
								<a href="?view=category&act=items&id=<?=$category_item['category_id']?>"><?=$db->getFieldOnID('categories', $category_item['category_id'], 'title')?></a>
							<?}
							
						}?>
					</td>
				</tr>
			<?}
			$db->isProfiling = true;
		}
		else{?>
			<tr>
				<td colspan="5">Номенклатура пуста</td>
			</tr>
		<?}?>
	</table>
	<?pagination($chank, $page, ceil($all / $perPage), $href = "?view=items&page=");
}
function search(){
	global $status, $db;
	$search = article_clear($_POST['search']);
	if (!$search) header ('Location: ?view=items');
	$where = "
		`article`='$search' OR 
		`barcode`='$search' OR 
		`id`='$search'
	";
	$all = $db->getCount('items', $where);
	$items = $db->select('items', 'title_full,id,article,barcode,brend_id', $where);
	$page_title = "Поиск по номенклатуре";
	$categories = $db->select('categories', '*', '', '', '', '', true);
	$status = "<a href='/admin'>Главная</a> > <a href='?view=items'>Номенклатура</a> > $page_title"?>
	<div id="total">Всего: <?=$all?></div>
	<div class="actions" style="width: 100%">
		<form style="margin-top: -3px;float: left;margin-bottom: 10px;" action="?view=items&act=search" method="post">
			<input style="width: 264px;" type="text" name="search" value="<?=$_POST['search']?>" placeholder="Поиск">
			<input type="submit" value="Искать">
		</form>
		<a style="margin-left: 10px;" href="?view=items&act=add">Добавить</a>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Название</td>
			<td>Штрих-код</td>
			<td>Категории</td>
		</tr>
		<?if (count($items)){
			foreach($items as $item){?>
			<tr class="items_box" item_id="<?=$item['id']?>">
				<td label="Бренд"><?=$db->getFieldOnID('brends', $item['brend_id'], 'title')?></td>
				<td label="Артикул"><?=$item['article']?></td>
				<td label="Название"><?=$item['title_full']?></td>
				<td label="Штрих-код"><?=$item['barcode']?></td>
				<td label="Категории">
					<?$categories_items = $db->select('categories_items', 'category_id', "`item_id`=".$item['id']);
					if (count($categories_items)){
						foreach ($categories_items as $category_item) {?>
							<a href="?view=category&act=items&id=<?=$category_item['category_id']?>"><?=$db->getFieldOnID('categories', $category_item['category_id'], 'title')?></a>
						<?}
					}?>
				</td>
			</tr>
		<?}
		}
		else{?>
			<tr><td colspan="5">Товаров не найдено</td></tr>
		<?}?>
	</table>
<?}?>