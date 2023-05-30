<?
use core\Managers;
use core\Item;
use core\Marketplaces\Marketplaces;

/** @global \core\Database $db */

if (isset($_FILES['photo'])){
	$name = preg_replace('/[а-яА-Я]+/u', "", $_FILES['photo']['name']);
	copy($_FILES['photo']['tmp_name'], core\Config::$tmpFolderPath . '/' . $name);?>
		<img id="uploadedPhoto" src="<?=core\Config::$tmpFolderUrl?>/<?=$name?>" alt="">
		<button id="savePhoto">Сохранить</button>
	<?
	exit();
}
$act = $_GET['act'];
if ($_POST['form_submit']){
	//если товар заблокирован и пользователь не является администртором
	if (
        $_SESSION['manager']['group_id'] != Managers::$administratorGroupID &&
        $db->getFieldOnID('items', $_GET['id'], 'is_blocked') == 1
	){
		Managers::handlerAccessNotAllowed();
	}

	//если доступ не разрешен
	if (Managers::isActionForbidden('Номенклатура', 'Изменение')){
		Managers::handlerAccessNotAllowed();
	} 

	//удаляем отсутствующие фото
	$filesBig = glob(core\Config::$imgPath . '/items/big/' . $_GET['id'] . '/*');
	$filesSmall = glob(core\Config::$imgPath . '/items/small/' . $_GET['id'] . '/*');

	core\Item::deleteMissingPhoto($filesBig, $_POST['photos'], 'big');
	core\Item::deleteMissingPhoto($filesSmall, $_POST['photos'], 'small');

	$db->update('items', ['photo' => NULL], "'id' = {$_GET['id']}");

    if ($_POST['marketplace_description']){
        Marketplaces::setItemDescription($_GET['id'], $_POST['marketplace_description']);
    }
    else $db->delete('item_marketplace_description', "`item_id` = {$_GET['id']}");

	$db->delete('items_values', "`item_id` = {$_GET['id']}");
	
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
		if ($key == 'photos') continue;
		if ($key == 'category_id') continue;
		if ($key == 'marketplace_description') continue;
		$array[$key] = $value;
	}
	if ($array['article_cat'] && !$array['article']) $array['article'] = core\Item::articleClear($array['article_cat']);
	if (!$array['article_cat'] && !$array['article'] && $array['barcode']) $array['article'] = $array['barcode'];
	if (isset($_GET['id'])){
		$db->delete('items_titles', "`item_id`={$_GET['id']}");
		$res = core\Item::update($array, ['id' => $_GET['id']]);
		$last_id = $_GET['id'];
	} 
	else{
		$res = core\Item::insert($array);
		$last_id = core\Item::$lastInsertedItemID;
	} 

	if ($res === true) {
		if (isset($_POST['photos']) && !empty($_POST['photos'])){
			$dir_big = core\Config::$imgPath . "/items/big/$last_id";
			$dir_small = core\Config::$imgPath . "/items/small/$last_id";
			if (!file_exists($dir_big)) mkdir($dir_big);
			if (!file_exists($dir_small)) mkdir($dir_small);
			$i = 0;
			$time = time();

			foreach($_POST['photos'] as $photo){
				

				//если файл не является новым
				if (!preg_match('/tmp/', $photo['big'])){
					if ($photo['is_main']){
						$fileName = preg_replace('/.*\//', '', $photo['big']);
						Item::update(['photo' => $fileName], ['id' => $last_id]);
					} 
					continue;
				} 

				$nameBody = $time . $i;
				copy($_SERVER['DOCUMENT_ROOT'].$photo['big'], "$dir_big/$nameBody.jpg");
				copy($_SERVER['DOCUMENT_ROOT'].$photo['small'], "$dir_small/$nameBody.jpg");
				if ($photo['is_main']) Item::update(['photo' => "$nameBody.jpg"], ['id' => $last_id]);
				$i++;
			}
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

        $db->delete('categories_items', "`item_id` = $last_id");
        if (!empty($_POST['category_id'])){
            foreach($_POST['category_id'] as $value){
                $db->insert('categories_items', [
                    'item_id' => $last_id,
                    'category_id' => $value
                ]);
            }
        }
		message('Успешно сохранено!');
		if ($_POST['is_stay']) header("Location: /admin/?view=items&act=item&id=$last_id");
		else header("Location: /admin/?view=items");
	}
	else message($res, false);
}
switch ($act) {
	case 'articles':
	case 'complects': 
	case 'analogies': 
	case 'substitutes': 
		if (isset($_GET['status'])){
			$db->update('item_'.$act, ['status' => $_GET['status']], "`item_id` = {$_GET['item_id']} AND `item_diff` = {$_GET['item_diff']}");
			$db->update('item_'.$act, ['status' => $_GET['status']], "`item_id` = {$_GET['item_diff']} AND `item_diff` = {$_GET['item_id']}");
			die();
		}
		itemDiff($act); 
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
	case 'deleteItemDiff':
		debug($_GET);
		break;
	case 'items': items(); break;
	case 'item': 
		item('s_change'); 
		break;
	default: items();
}
function item($act){
	global $db, $page_title, $status;
	$languages = $db->select('languages', "*", '', 'title');
	switch($act){
		case 's_add': 
			$page_title = 'Добавление товара';
			break;
		case 's_change':
			$id = $_GET['id'];
			$item = core\Item::getByID($id, ['marketplace_description', 'additional_options']);
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
						it.item_id=$id
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
			<?$count = $db->getCount('item_complects', "`item_id`={$item['id']} AND `item_diff`<>{$item['id']}");
			$class = $count ? 'red' : '';?>
			<a class="<?=$class?>" href="?view=items&act=complects&id=<?=$item['id']?>">Комплектность(<?=$count?>)</a>
			<?$count = $db->getCount('item_articles', "`item_id`={$item['id']} AND `item_diff`<>{$item['id']}");
			$class = $count ? 'red' : '';?>
			<a class="<?=$class?>" href="?view=items&act=articles&id=<?=$item['id']?>">Подобные(<?=$count?>)</a>
			<?$count = $db->getCount('store_items', "`item_id`=".$item['id']);
			$class = $count ? 'red' : '';?>
			<a class="<?=$class?>" href="?view=items&act=prices&id=<?=$item['id']?>">Прайсы(<?=$count?>)</a>
			<?$count = $db->getCount('item_substitutes', "`item_id`=".$item['id']);
			$class = $count ? 'red' : '';?>
			<a class="<?=$class?>" href="?view=items&act=substitutes&id=<?=$item['id']?>">Замены(<?=$count?>)</a>
			<?$count = $db->getCount('item_analogies', "`item_id`=".$item['id']);
			$class = $count ? 'red' : '';?>
			<a class="<?=$class?>" href="?view=items&act=analogies&id=<?=$item['id']?>">Аналоги(<?=$count?>)</a>
			<a href="?view=items&act=history&item_id=<?=$item['id']?>">История</a>
			<?if (!Managers::isActionForbidden('Номенклатура', 'Удаление')){?>
				<a href="/admin/?view=test_api_providers&item_id=<?=$_GET['id']?>">
					Тестировать на API
				</a>
				<a style="float: right" href="?view=items&id=<?=$item['id']?>&act=delete" class="delete_item" item_id="<?=$item['id']?>">Удалить</a>
			<?}?>
		</div>
	<?}?>
	<div class="t_form">
		<div class="bg">
			<form class="defaultSubmit" method="post" enctype="multipart/form-data">
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
                    <div class="title">Дополнительно</div>
                    <div class="value">
                        <a href="#" class="hide">Показать</a>
                        <div style="margin-top: 10px;display: none">
                            <table>
                                <tr>
                                    <td>Глубина, мм</td>
                                    <td><input type="text" name="depth" value="<?= $_POST['depth'] ?? $item['depth'] ?>"></td>
                                </tr>
                                <tr>
                                    <td>Ширина, мм</td>
                                    <td><input type="text" name="width" value="<?= $_POST['width'] ?? $item['width'] ?>"></td>
                                </tr>
                                <tr>
                                    <td>Высота, мм</td>
                                    <td><input type="text" name="height" value="<?= $_POST['height'] ?? $item['height'] ?>"></td>
                                </tr>
                                <tr>
                                    <td>Вес, кг</td>
                                    <td><input type="text" name="weight" value="<?= $_POST['weight'] ?? $item['weight'] ?>"></td>
                                </tr>
                                <tr>
                                    <td>Единица измерения</td>
                                    <td>
                                        <?$measures = $db->select('measures', '*');?>
                                        <select name="measure_id">
                                            <option value="0">ничего не выбрано</option>
                                            <?foreach($measures as $measure){
                                                if ($_POST['form_submit']) $selected = $_POST['measure_id'] == $measure['id'] ? 'selected' : '';
                                                else $selected = $item['measure_id'] == $measure['id'] ? 'selected' : ''?>
                                                <option <?=$selected?> value="<?=$measure['id']?>"><?=$measure['title']?></option>
                                            <?}?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Количество в упаковке</td>
                                    <td><input type="text" name="amount_package" value="<?= $_POST['amount_package'] ?? $item['amount_package'] ?>"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
				<div class="field">
					<div class="title">Описание</div>
					<div class="value">
						<?$full_desc = $_POST['full_desc'] ?: $item['full_desc'];
						$active = $full_desc ? 'active' : ''?>
						<a href="#" class="hide <?=$active?>">Показать</a>
						<div style="margin-top: 10px;display: none">
							<textarea  class="need" name="full_desc" class="htmlarea" style=""><?=$full_desc?></textarea>
						</div>
					</div>
				</div>
                <div class="field">
                    <div class="title">Маркетплейсы</div>
                    <div class="value">
                        <?$marketplace_description = $_POST['marketplace_description'] ?: $item['marketplace_description'];
                        $active = $marketplace_description ? 'active' : ''?>
                        <a href="#" class="hide <?=$active?>">Показать</a>
                        <div style="margin-top: 10px;display: none">
                            <textarea  class="need" name="marketplace_description" class="htmlarea" style=""><?=$marketplace_description?></textarea>
                        </div>
                    </div>
                </div>
				<div class="field">
					<div class="title">Характеристики</div>
					<div class="value">
						<?$characteristics = $_POST['characteristics'] ? $_POST['characteristics'] : $item['characteristics'];
						$active = $characteristics ? 'active' : ''?>
						<a href="" class="hide <?=$active?>">Показать</a>
						<div style="margin-top: 10px;display: none">
							<textarea class="need" name="characteristics" class="htmlarea" style=""><?=$characteristics?></textarea>
						</div>
					</div>
				</div>
				<div class="field">
					<div class="title">Применяемость</div>
					<div class="value">
						<?$applicability = $_POST['applicability'] ? $_POST['applicability'] : $item['applicability'];
						$active = $applicability ? 'active' : ''?>
						<a href="" class="hide <?=$active?>">Показать</a>
						<div style="margin-top: 10px;display: none">
							<textarea class="need" name="applicability" class="htmlarea" style=""><?=$applicability?></textarea>
						</div>
					</div>
				</div>
				<div class="field">
					<div class="title">Фото</div>
					<div class="value">
						<a href="" class="hide">Скрыть</a>
						<div style="display: block; margin-top: 10px">
							<ul class="photo" id="photos">
								<?$photoNames = scandir(core\Config::$imgPath . "/items/small/{$_GET['id']}/");
								$i = -1;
								foreach($photoNames as $name){
									if (!preg_match('/.+\.jpg/', $name)) continue;
									$i++;?>
									<li class="<?=$name == $item['photo'] ? 'main-photo' : ''?>" big="<?=core\Config::$imgUrl?>/items/big/<?=$item['id']?>/<?=$name?>">
										<div>
											<a class="loop" href="#">Увеличить</a>
											<a class="removePhoto">Удалить</a>
											<span class="main-photo <?=$name == $item['photo'] ? 'icon-lock' : 'icon-unlocked'?>"></span>
										</div>
										<img src="<?=core\Config::$imgUrl?>/items/small/<?=$item['id']?>/<?=$name?>" alt="">
										<input type="hidden" name="photos[<?=$i?>][small]" value="<?=core\Config::$imgPath?>/items/small/<?=$item['id']?>/<?=$name?>">
										<input type="hidden" name="photos[<?=$i?>][big]" value="<?=core\Config::$imgPath?>/items/big/<?=$item['id']?>/<?=$name?>">
										<input type="hidden" name="photos[<?=$i?>][is_main]" value="<?=$name == $item['photo'] ? '1' : '0'?>">
									</li>
								<?}?>
							</ul>
							<input type="button" accept="image/*" value="Загрузить фото" id="buttonLoadPhoto">
						</div>
					</div>
				</div>
				<div class="field">
					<div class="title">Метаданные</div>
					<div class="value"><input type=text name="meta_desc" value="<?=$_POST['meta_desc'] ?: $item['meta_desc']?>"></div>
				</div>
				<div class="field">
					<div class="title">Ключевые слова</div>
					<div class="value"><input type=text name="meta_key" value="<?=$_POST['meta_key'] ?: $item['meta_key']?>"></div>
				</div>
				<div class="field">
					<div class="title">Рейтинг</div>
					<div class="value"><input type=text name="rating" value="<?=$_POST['rating'] ?: $item['rating']?>"></div>
				</div>
				<div class="field">
					<div class="title">Примечание</div>
					<div class="value"><input type=text name="comment" value="<?=$_POST['comment'] ?: $item['comment']?>"></div>
				</div>
				<? if ($act != 's_add'){?>
				<div class="field">
					<div class="title">Категории</div>
					<div class="value">
						<a href="" id="add_category">Добавить</a>
                        <div id="categories">
                            <?$category_items = $db->select('categories_items', 'category_id', "`item_id`=".$item['id']);
                            $filterList = Item::getFiltersByItemID($_GET['id']);
                            if (count($category_items)){
                                foreach ($category_items as $category_item) {
                                    $parentMainCategory = Item::getParentMainCategory($category_item['category_id']); ?>
                                    <div class="category">
                                        <?=Item::getMainCategory($parentMainCategory);?>
                                        <?=Item::getSubCategory($parentMainCategory, $category_item['category_id'])?>
                                        <?$category = & $filterList[$category_item['category_id']];
                                        if (!is_null($category)){?>
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
                                <?}?>
                            <?}?>
                        </div>
					</div>
				</div>
				<?}?>
				<? if ($act != 's_add'){?>
					<div class="field">
						<div class="title">Заблокировано</div>
						<div class="value">
							<?if (!empty($_POST)){
								$selected = $_POST['is_blocked'];
							}
							else{
								$selected = $item['is_blocked'];
							}?>
							<select name="is_blocked">
								<option <?=$selected == 0 ? 'selected' : ''?> value="0">Нет</option>
								<option <?=$selected == 1 ? 'selected' : ''?> value="1">Да</option>
							</select>
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
	<form style="display: none" action="/admin/?view=items&act=item&id=<?=$_GET['id']?>" enctype="multipart/form-data" method="post">
		<input id="loadPhoto" name="photo" type="file">
	</form>
	<div class="actions"><a href="<?=$_SERVER['HTTP_REFERER']?>">Назад</a></div>
	<div class="popup-gallery"></div>
<?}
function history(){
	global $status, $db, $page_title;
	$page_title = "История товара";
	$res_item = $db->query("
		SELECT
            DATE_FORMAT(i.created, '%d.%m.%Y %H:%i:%s') AS date,
            IF (
                i.source is not null,
                concat_ws(' ', 'Добавлен с', i.source),
                'Добавлен'
            ) as comment
        from
            #items i
        where i.id = {$_GET['item_id']}
        
        UNION
        
        SELECT
            DATE_FORMAT(r.created, '%d.%m.%Y %H:%i:%s') AS date,
            concat_ws(
                ' ', 
                'Возврат по заказу №', 
                concat(
                    '<a target=\"_blank\" href=\"/admin/?view=orders&id=',
                    r.order_id,
                    '&act=change\">',
                    r.order_id,
                    '</a>'
                )
                ) as comment
        from
            #returns r
        where r.item_id = {$_GET['item_id']}
        
        UNION
        
        SELECT
            DATE_FORMAT(o.created, '%d.%m.%Y %H:%i:%s') AS date,
            concat_ws(
                ' ',
                'Заказал',
                concat(
                    '<a target=\'_blank\' href=\"/admin/?view=users&act=change&id=',
                    u.id,
                    '\">',
                    ".\core\User::getUserFullNameForQuery().",
                    '</a>'
                ),
                'в заказе №',
                concat(
                    '<a target=\"_blank\" href=\"/admin/?view=orders&id=',
                    ov.order_id,
                    '&act=change\">',
                    ov.order_id,
                    '</a>'
                )
            ) as comment
        FROM
            #orders_values ov
            LEFT JOIN
            #orders o ON o.id = ov.order_id
                LEFT JOIN
            #users u ON u.id = o.user_id
                LEFT JOIN
            #organizations_types ot ON ot.id=u.organization_type
        WHERE
            ov.item_id = {$_GET['item_id']}
            
        UNION
        
        SELECT
            DATE_FORMAT(oi.created, '%d.%m.%Y %H:%i:%s') AS date,
            concat(
                'Реализация №',
                '<a target=\"_blank\" href=\"/admin/?view=order_issues&issue_id=',
                iv.issue_id,
                '\">',
                iv.issue_id,
                '</a>'
            )
        from
            #order_issue_values iv
        left join
            #order_issues oi on iv.issue_id = oi.id
        where
            iv.item_id = {$_GET['item_id']}
        
        order by str_to_date(date, '%d.%m.%Y %H:%i:%s') desc
	", '');

	$query = Item::getQueryItemInfo(['item_id' => $_GET['item_id']]);
    $query .= " WHERE i.id = {$_GET['item_id']}";
    $itemInfo = $db->query($query)->fetch_assoc();
	$status = "<a href='/admin'>Главная</a> > <a href='?view=items'>Номенклатура</a> > ";
	$status .= "<a href='/admin/?view=items&act=item&id={$_GET['item_id']}'>{$itemInfo['brend']} - {$itemInfo['article']}</a> > $page_title";
	?>
	<table id="itemHistory" class="t_table" cellspacing="1">
		<tr class="head">
			<td>Дата</td>
			<td>Действие</td>
		</tr>
		<?if ($res_item->num_rows){
			while($row = $res_item->fetch_assoc()){?>
				<tr>
					<td label="Дата"><?=$row['date']?></td>
					<td label="Действие"><?=$row['comment']?></td>
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
					<td class="storeInfo" label="Шифр">
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
function itemDiff($type){
	global $status, $page_title;
	$itemMain = core\Item::getByID($_GET['id']);
	$item_id = $_GET['id'];
	$res_items = core\Item::getResItemDiff($type, $item_id, '');
	switch($type){
		case 'complects': $page_title = "Комплектность"; break;
		case 'articles': $page_title = "Подобные"; break;
		case 'substitutes': $page_title = "Замены"; break;
		case 'analogies': $page_title = "Аналоги"; break;
	}
	$status = "
		<a href='/admin'>Главная</a> > 
		<a href='?view=items&act=item&id=$item_id'>{$itemMain['brend']} - {$itemMain['article']}</a> > 
		$page_title
	";?>
	<a href="?view=items&act=item&id=<?=$item_id?>">Карточка товара</a>
	<a href="#" id="clearItemDiff" item_id="<?=$item_id?>" type="<?=$_GET['act']?>">Очистить "<?=$page_title?>"</a>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Название</td>
			<td>Штрих-код</td>
			<td>Категории</td>
		</tr>
		<tr>
			<td label="Бренд"><?=$itemMain['brend']?></td>
			<td label="Артикул">
				<a href="?view=items&act=item&id=<?=$itemMain['id']?>"><?=$itemMain['article']?></a>
			</td>
			<td label="Название"><?=$itemMain['title_full']?></td>
			<td label="Штрих-код"><?=$itemMain['barcode']?></td>
			<td label="Категории"><?=$itemMain['categories']?></td>
		</tr>
	</table>
	<div id="total" style="margin-top: 20px">Всего: <?=$res_items->num_rows?></div>
	<div style="margin-top: 10px" class="actions">
		<input style="width: 264px;" type="text" name="<?=$type?>" class="intuitive_search" placeholder="Поиск для добавления">
	</div>
	<table id="itemDiff" style="" class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
            <td>Кат. номер</td>
			<td>Название</td>
			<td>Штрих-код</td>
			<?if ($type == 'analogies'){?>
				<td>Статус</td>
			<?}?>
			<td>Категории</td>
			<td></td>
		</tr>
		<?if ($res_items->num_rows){
			foreach($res_items as $value){?>
				<tr class="analogyStatus_<?=$value['status']?>">
					<td label="Бренд"><?=$value['brend']?></td>
					<td label="Артикул">
						<a target="blank" href="?view=items&act=item&id=<?=$value['item_id']?>"><?=$value['article']?></a>
					</td>
                    <td label="Кат. номер">
                        <a target="blank" href="?view=items&act=item&id=<?=$value['item_id']?>"><?=$value['article_cat']?></a>
                    </td>
					<td label="Название"><?=$value['title_full']?></td>
					<td label="Штрих-код"><?=$value['barcode']?></td>
					<?if ($type == 'analogies'){?>
						<td label="Статус">
							<form class="status">
								<input type="hidden" name="act" value="analogies">
								<input type="hidden" name="view" value="items">
								<input type="hidden" name="item_id" value="<?=$_GET['id']?>">
								<input type="hidden" name="item_diff" value="<?=$value['item_id']?>">
								<select name="status">
									<option <?=$value['status'] == '0' ? 'selected' : ''?> value="0">не выбрано</option>
									<option <?=$value['status'] == '1' ? 'selected' : ''?> value="1">проверен</option>
									<option <?=$value['status'] == '2' ? 'selected' : ''?> value="2">скрыт</option>
								</select>
							</form>
							<!-- <?$checked = $value['checked'] ? 'checked' : ''?>
							<input <?=$checked?> name="checked" type="checkbox" value="<?=$value['item_id']?>"> -->
						</td>
					<?}?>
					<td label="Категории"><?=$value['categories']?></td>
					<td label="">
						<a class="deleteItemDiff" href="act=deleteItemDiff&type=<?=$type?>&item_id=<?=$_GET['id']?>&item_diff=<?=$value['item_id']?>">Удалить</a>
					</td>
				</tr>
			<?}	
		}
		else{?>
			<tr class="empty"><td colspan="5">Товаров не найдено</td></tr>
		<?}?>
	</table>
<?}
function items(){
	global $status, $db, $page_title, $settings;
	require_once('templates/pagination.php');
	$all = core\Setting::get('commonCount');
	$perPage = core\Setting::get('perPage');
	$linkLimit = core\Setting::get('linkLimit');
	$page = $_GET['page'] ?: 1;
	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ?: 0;
	$query = core\Item::getQueryItemInfo();
	$article = core\Item::articleClear($_GET['items']);
	if (isset($_GET['items'])) $query .= "
		WHERE
			i.article = '$article'
	";
	$query .= "
		ORDER BY i.id DESC
		LIMIT
			$start,$perPage
	";
	$res_items = $db->query($query, '');
	if (isset($_GET['items'])) $all = $res_items->num_rows;
	$page_title = "Номенклатура";
	$status = "<a href='/admin'>Главная</a> > $page_title"?>
	<div id="total" style="margin-top: 10px;">Всего: <?=$all?></div>
	<div class="actions items" class="" style="">
	<form>
		<input type="hidden" name="view" value="items">
		<input class="intuitive_search" style="width: 264px;" type="text" name="items" value="<?=$_GET['items']?>" placeholder="Поиск по артикулу, vid и названию" required>
		<input type="submit" value="Искать">
	</form>
		<a href="?view=items&act=add">Добавить</a>
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
		<?if ($res_items->num_rows){
			$db->isProfiling = false;
			foreach($res_items as $item){?>
				<tr class="items_box" item_id="<?=$item['id']?>">
					<td label="Бренд"><?=$item['brend']?></td>
					<td label="Артикул">
						<a href="/admin/?view=items&act=item&id=<?=$item['id']?>"><?=$item['article']?></a>
					</td>
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
	<?if (!isset($_GET['items'])){
		pagination($chank, $page, ceil($all / $perPage), $href = "?view=items&page=");
	}
}?>
<input type="hidden" name="item_id" value="<?=$_GET['id']?>">
