<?php
$act = $_GET['act'];
switch ($act) {
	case 'delete':
		if (Managers::isActionForbidden('Подкатегории', 'Удаление')){
			Managers::handlerAccessNotAllowed();
		} 
		$id = $_GET['id'];
		$db->delete('categories', "`id`=$id");
		debug($db->get_mysqli());
		message('Успешно удалено!');
		// header("Location: ?view=category&id={$_GET['parent_id']}");
		break;
	case 'items_search':items_search();break;
	case 'add_item':
		if (Managers::isActionForbidden('Подкатегории', 'Изменение')){
			Managers::handlerAccessNotAllowed();
		} 
		$item_id = $_GET['item_id'];
		$category_id = $_GET['id'];
		if ($db->getCount('categories_items', "`item_id`=$item_id AND `category_id`=$category_id")){
			message('Такой товар уже присутствует в данной категории!', false);
		}
		else{
			$db->insert('categories_items', array('item_id' => $item_id, 'category_id' => $category_id));
			message('Товар успешно добавлен!');
		}
		header("Location: /admin/?view=category&id=$category_id");
		break;
	case 'items': items(); break;
	case 'filters': filters(); break;
	case 'filters_values': filters_values(); break;
	case 'items_filters_values': items_filters_values(); break;
	case 'delete_filter':
		if (Managers::isActionForbidden('Подкатегории', 'Изменение')){
			Managers::handlerAccessNotAllowed();
		} 
		$filters_values = $db->select('filters_values', 'id', "`filter_id`={$_GET['id']}");
		foreach ($filters_values as $value) $in[] = $value['id'];
		$db->delete('filters', "`id`={$_GET['id']}");
		$db->delete('filters_values', '`id` IN ('.implode(',', $in).')');
		$db->delete('items_values', '`value_id` IN ('.implode(',', $in).')');
		message('Успешно удалено!');
		header("Location: ?view=category&act=filters&id={$_GET['category_id']}");
		// debug($filters_values);
		break;
	case 'add_slider':
		if (Managers::isActionForbidden('Подкатегории', 'Изменение')){
			Managers::handlerAccessNotAllowed();
		} 
		$bool = true;
		$filters_values = $db->select('filters_values', 'title', "`filter_id`={$_GET['id']}");
		if (!count($filters_values)){
			message('Отсутствуют значения фильтра!', false);
			$bool = false;
		}
		foreach ($filters_values as $value){
			if (!is_numeric($value['title'])){
				message('Значения фильтра не являются числами!', false);
				$bool = false;
			}
		}
		if ($bool){
			$db->update('filters', ['slider' => 1], "`id`={$_GET['id']}");
			message('Успешно изменено!');
		}
		header("Location: ?view=category&act=filters&id={$_GET['from']}");
		break;
	case 'delete_slider':
		if (Managers::isActionForbidden('Подкатегории', 'Изменение')){
			Managers::handlerAccessNotAllowed();
		} 
		$db->update('filters', ['slider' => 0], "`id`={$_GET['id']}");
		message('Успешно изменено!');
		header("Location: ?view=category&act=filters&id={$_GET['from']}");
	default:
		view();
}
function view(){
	global $db, $page_title, $status;
	$id = $_GET['id'];
	$subcategories = $db->select('categories', '*', "`parent_id`=$id", 'pos', true);
	$page_title = $db->getFieldOnID('categories', $id, 'title');
	$status = "<a href='/admin'>Главная</a> > <a href='?view=categories'>Категории товаров</a> > $page_title";?>
	<div id="total" style="margin: 0">Всего: <?=count($subcategories)?></div>
	<div class="actions"><a id="add_subcategory" category_id=<?=$id?> href="">Добавить</a></div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Заголовок</td>
			<td>Позиция</td>
			<td>Ссылка</td>
			<td></td>
			<td></td>
		</tr>
		<?if (count($subcategories)){
			foreach ($subcategories as $category) {?>
			<tr class="subcategory">
				<td title="Нажмите, чтобы изменить" class="category" data-id="<?=$category['id']?>">
					<?=$category['title']?>
				</td>
				<td class="pos" data-id="<?=$category['id']?>"><?=$category['pos']?></td>
				<td title="Нажмите, чтобы изменить" class="href" >
					<?if ($category['href']){?>
						<?=$category['href']?>
					<?}
					else{?>
						Ссылка не задана
					<?}?>
				</td>
				<td>
					<?$items = $db->select('categories_items', 'item_id', "`category_id`=".$category['id']." GROUP BY `item_id`")?>
					<a href="?view=category&act=items&id=<?=$category['id']?>">Товаров (<?=count($items)?>)</a> 
					<?$filters = $db->getCount('filters', "`category_id`=".$category['id'])?>
					<a href="?view=category&act=filters&id=<?=$category['id']?>">Фильтров(<?=$filters?>)</a>
				</td>
				<td>
					<a class="delete_item" href="?view=category&act=delete&id=<?=$category['id']?>&parent_id=<?=$_GET['id']?>">Удалить</a>
				</td>
			</tr>
		<?}
		}
		else{?>
			<tr><td colspan="4">Для данной категории подкатегорий не найдено</td></tr>
		<?}?>
	</table>
<?}
function items(){
	global $status, $db, $page_title;
	$id = $_GET['id'];
	require_once('templates/pagination.php');
	$category_items = $db->select('categories_items', 'item_id', "`category_id`=$id");
	$perPage = 30;
	$linkLimit = 10;
	$str_filters = $_GET['filters'];
	if ($str_filters){
		$array = getStrFilters($str_filters);
		$filters_values_table = $array['filters_values_table'];
		$filters = $array['filters'];
		$filters_in = explode(',', $str_filters);
	} 
	if (count($category_items) and $_POST['form_submit']){
		// debug($_POST);
		$filters_in = array();
		foreach ($_POST as $key => $value){
			if ($key != 'form_submit' and $key != 'search') $filters_in[]= $value;
		}
		if (!count($filters_in)) header("Location: ?view=category&act=items&id=".$_GET['id']);
		$str_filters = implode(',', $filters_in);
		$array = getStrFilters($str_filters);
		$filters_values_table = $array['filters_values_table'];
		$filters = $array['filters'];
		// debug($filters_values_table);
		// $i = 0;
		// $array = [];
		// foreach ($filters_values_table as $key => $value){
		// 	// debug($value, $key);
		// 	foreach ($value as $k => $val){
		// 		$array[$i][] = $k;
		// 	}
		// 	$i++;
		// }
		// $items = $db->select('items', 'id');
		// foreach ($items as $item){
		// 	foreach ($array as $value){
		// 		$count = count($value);
		// 		$rand = rand(0, $count - 1);
		// 		$db->insert('items_values', ['item_id' => $item['id'], 'value_id' => $value[$rand]]);
		// 	}
		// }
		// debug($array);
	}
	$in = "";
	if (count($category_items)) foreach ($category_items as $category_item) $in .= $category_item['item_id'].",";
	$in = substr($in, 0, -1);
	$search = $_POST['search'] ? $_POST['search'] : $_GET['search'];
	if ($search) $where = "`id` IN ($in) AND (`article`='$search' OR `barcode` LIKE '%$search%' OR `title_full` LIKE '%$search%')";
	else $where = "`id` IN ($in)";
	$all = $db->getCount('items', $where);
	$page = $_GET['page'] ? $_GET['page'] : 1;
	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ? $chank[$page] : 0;
	$items = $db->select('items', 'title_full,id,article,barcode,brend_id', $where, 'brend_id', '', "$start,$perPage");
	$page_title = "Товары подкатегории <b>".$db->getFieldOnID('categories', $id, 'title')."</b>";
	$category = $db->select('categories', '*', "`id`=".$_GET['id']);
	$status = "<a href='/admin'>Главная</a> > <a href='?view=categories'>Категории товаров</a> > ";
	$status .= "<a href='?view=category&id=".$category[0]['parent_id']."'>".$db->getFieldOnID('categories', $category[0]['id'], 'title')."</a> > $page_title";?>
	<div id="total" style="position: absolute;right: 10px;">Всего: <?=$all?></div>
	<div class="actions">
		<form action="?view=category&act=items&id=<?=$id?>" style="margin-top: -3px;float: left;margin-bottom: 10px;"  method="post">
			<input type="hidden" name="form_submit" value="1">
			<input style="width: 264px;" type="text" name="search" value="<?=$search?>" placeholder="Поиск по артикулу, vid и названию">
			<input type="submit" value="Искать">
			<?$filters_all = $db->select('filters', 'id,title', "`category_id`=".$_GET['id'], '', '', '', true);
				if (count($filters_all)){?>
					<div id="filters">
						<?foreach($filters_all as $id => $filter){
							if (count($filters_in)) $checked = in_array($id, $filters_in) ? "checked" : "";?>
							<input <?=$checked?> type="checkbox" name="filter_<?=$id?>" value="<?=$id?>" id="filter_<?=$id?>">
							<label for="filter_<?=$id?>"><?=$filter['title']?></label>
						<?}?>
						<input type="submit" value="Применить">
					</div>
				<?}?>
		</form>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Название</td>
			<?if (count($filters)){
				foreach($filters as $filter){?>
					<td><?=$filter['title']?></td>
				<?}
			}?>
		</tr>
		<?if (count($items)){
			foreach($items as $item){?>
				<tr>
					<td><?=$db->getFieldOnID('brends', $item['brend_id'], 'title')?></td>
					<td><a href="?view=items&act=item&id=<?=$item['id']?>"><?=$item['article']?></a></td>
					<td><?=$item['title_full']?></td>
					<?if (count($filters_values_table)){
						foreach($filters_values_table as $filter_id => $filter_value_table){
							$item_values_temp = $db->select('items_values', 'value_id', "`item_id`=".$item['id']);
							$item_values = array();
							if (count($item_values_temp)) foreach($item_values_temp as $value) $item_values[] = $value['value_id'];?>
							<td>
								<select class="value_apply" item_id="<?=$item['id']?>">
									<option value="">ничего не выбрано</option>
									<?if (count($filter_value_table)){
										foreach ($filter_value_table as $k => $v){?>
											<option <?=in_array($k, $item_values) ? "selected" : ""?> value="<?=$k?>"><?=$v?></option>
										<?}
									}?>
								</select>
							</td>
						<?}
					}?>
				</tr>
			<?}
		}
		else{?>
			<tr><td colspan="5">Товаров данной подкатегории не найдено</td></tr>
		<?}?>
	</table>
	<?$href = "?view=category&act=items&id={$_GET['id']}&search=$search&filters=$str_filters&page=";
	pagination($chank, $page, ceil($all / $perPage), $href);
}
function items_search(){
	global $status, $db;
	$id = $_GET['id'];
	$search = $_POST['search'] ? $_POST['search'] : $_GET['search'];
	$category_items = $db->select('categories_items', 'item_id', "`category_id`=$id");
	if (count($category_items)){
		require_once('templates/pagination.php');
		$in = "";
		foreach ($category_items as $category_item) $in .= $category_item['item_id'].",";
		$in = substr($in, 0, -1);
		$where = "(`article`='$search' OR `barcode` LIKE '%$search%' OR `title` LIKE '%$search%') AND `id` IN ($in)";
		$all = $db->getCount('items', $where);
		$perPage = 3;
		$linkLimit = 4;
		$page = $_GET['page'] ? $_GET['page'] : 1;
		$chank = getChank($all, $perPage, $linkLimit, $page);
		$start = $chank[$page] ? $chank[$page] : 0;
		$items = $db->select('items', 'title,id,article,barcode,brend_id', $where, '', '', "$start,$perPage");
	}
	$categories = $db->select('categories', '*', '', '', '', '', true);
	$page_title = "Поск товаров подкатегории <b>".$db->getFieldOnID('categories', $id, 'title')."</b>";
	$category = $db->select('categories', '*', "`id`=$id");
	$status = "<a href='/admin'>Главная</a> > <a href='?view=categories'>Категории товаров</a> > ";
	$status .= "<a href='?view=category&id=".$category[0]['parent_id']."'>".$db->getFieldOnID('categories', $category[0]['parent_id'], 'title')."</a> > $page_title";
	?>
	<div id="total" style="margin: 0">Всего: <?=$all?></div>
	<div class="actions" style="width: 100%">
		<form style="margin-top: -3px;float: left;margin-bottom: 10px;" action="?view=category&id=<?=$id?>&act=items_search&search=<?=$search?>" method="post">
			<input style="width: 264px;" required type="text" name="search" value="<?=$search?>" placeholder="Поиск по артикулу, vid и названию">
			<input type="submit" value="Искать">
		</form>
	</div>
	<table class="t_table" cellspacing="1">
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
				<td><?=$item['article']?></td>
				<td><?=$item['title']?></td>
				<td><?=$item['barcode']?></td>
				<td>
					<?$categories_items = $db->select('categories_items', 'category_id', "`item_id`=".$item['id']);
					if (count($categories_items)){
						foreach ($categories_items as $category_item) {?>
							<a href="/admin/?view=category&id=<?=$category_item['category_id']?>"><?=$categories[$category_item['category_id']]['title']?></a>
						<?}
					}?>
				</td>
				<td>
					<a href="?view=items&id=<?=$item['id']?>&act=item">Изменить</a>
					<a href="?view=items&id=<?=$item['id']?>&act=delete" class="delete_item" item_id="<?=$item['id']?>">Удалить</a>
				</td>
			</tr>
		<?}
		}
		else{?>
			<tr><td colspan="5">Товаров не найдено</td></tr>
		<?}?>
	</table>
	<?pagination($chank, $page, ceil($all / $perPage), $href = "?view=category&search=$search&act=items_search&id=$id&page=");
}
function filters(){
	global $db, $page_title, $status;
	$id = $_GET['id'];
	$filters = $db->select('filters', '*', "`category_id`=$id", 'pos', true);
	$category = $db->select('categories', '*', "`id`=$id");
	$page_title = $db->getFieldOnID('categories', $id, 'title')." - фильтры";
	$status = "<a href='/admin'>Главная</a> > <a href='?view=categories'>Категории товаров</a> > ";
	$status .= "<a href='?view=category&id=".$category[0]['parent_id']."'>".$db->getFieldOnID('categories', $category[0]['parent_id'], 'title')."</a> > $page_title";?>
	<div id="total" style="margin: 0">Всего: <?=count($filters)?></div>
	<div class="actions"><a id="add_filter" category_id=<?=$id?> href="">Добавить</a></div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Заголовок</td>
			<td>Позиция</td>
			<td></td>
		</tr>
		<?if (count($filters)){
			foreach ($filters as $filter) {?>
			<tr class="filter_category" filter_id="<?=$filter['id']?>">
				<td class="filter_title"><?=$filter['title']?></td>
				<td class="filter_pos"><?=$filter['pos']?></td>
				<td>
					<?$count_filters_values = $db->getCount('filters_values', "`filter_id`=".$filter['id'])?>
					<a href="?view=category&act=filters_values&id=<?=$filter['id']?>">Свойства фильров(<?=$count_filters_values?>)</a>
					<?if (!$filter['slider']){?>
						<a href="?view=category&from=<?=$_GET['id']?>&act=add_slider&id=<?=$filter['id']?>">Отображать слайдером</a>
					<?}
					else{?>
						<a href="?view=category&from=<?=$_GET['id']?>&act=delete_slider&id=<?=$filter['id']?>">Удалить слайдер</a>
					<?}?>
					<a class="delete_item" href="?view=category&act=delete_filter&id=<?=$filter['id']?>&category_id=<?=$_GET['id']?>" filter_id="<?=$filter['id']?>">Удалить</a>
				</td>
			</tr>
		<?}
		}
		else{?>
			<tr><td colspan="2">Фильтров для данной категории не найдено</td></tr>
		<?}?>
	</table>
<?}
function filters_values(){
	global $db, $page_title, $status;
	$id = $_GET['id'];
	$res_filters_values = $db->query("
		SELECT
			fv.id,
			fv.title,
			CAST(fv.title AS UNSIGNED) as title_2
		FROM
			#filters_values fv
		WHERE
			fv.filter_id=$id
		ORDER BY
			title_2, fv.title 
	", '');
	$filter = $db->select('filters', '*', "`id`=$id");
	$category = $db->select('categories', '*', "`id`=".$filter[0]['category_id']);
	$page_title = "Свойства фильтра";
	$status = "<a href='/admin'>Главная</a> > <a href='?view=categories'>Категории товаров</a> > ";
	$status .= "<a href='?view=category&id=".$category[0]['parent_id']."'>".$db->getFieldOnID('categories', $category[0]['parent_id'], 'title')."</a> > ";
	$status .= "<a href='?view=category&act=filters&id=".$category[0]['id']."'>".$db->getFieldOnID('filters', $filter[0]['id'], 'title')." - фильтры</a> > $page_title";?>
	<div id="total" style="margin: 0">Всего: <?=count($filters_values)?></div>
	<div class="actions"><a id="add_filter_value" filter_id=<?=$id?> href="">Добавить</a></div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Заголовок</td>
			<td></td>
		</tr>
		<?if ($res_filters_values->num_rows){
			while ($filter_value = $res_filters_values->fetch_assoc()) {?>
			<tr>
				<td><?=$filter_value['title']?></td>
				<td>
					<?$count_filter_value = $db->getCount('items_values', "`value_id`=".$filter_value['id'])?>
					<a href="?view=category&act=items_filters_values&id=<?=$filter_value['id']?>">Товаров(<?=$count_filter_value?>)</a>
					<a class="change_filter_value" href="" filter_value_id="<?=$filter_value['id']?>">Изменить</a>
					<a class="delete_filter_value" href="" filter_value_id="<?=$filter_value['id']?>">Удалить</a>
				</td>
			</tr>
		<?}
		}
		else{?>
			<tr><td colspan="2">Фильтров для данной категории не найдено</td></tr>
		<?}?>
	</table>
<?}
function items_filters_values(){
	global $status, $db, $page_title;
	$id = $_GET['id'];
	$items_values = $db->select('items_values', '*', "`value_id`=$id");
	if (count($items_values)) foreach($items_values as $item_value) $items_ids[] = $item_value['item_id'];
	$where = "`id` IN (".implode(',', $items_ids).")";
	$all = $db->getCount('items', $where);
	$items = $db->select('items', 'title,id,article,barcode,brend_id', $where, 'brend_id');
	$page_title = "Товары со свойством <b>".$db->getFieldOnID('filters_values', $id, 'title')."</b>";
	$filter_value = $db->select('filters_values', '*', "`id`=".$items_values[0]['value_id']);
	$filter = $db->select('filters', '*', "`id`=".$filter_value[0]['filter_id']);
	$category = $db->select('categories', '*', "`id`=".$filter[0]['category_id']);
	$status = "<a href='/admin'>Главная</a> > <a href='?view=categories'>Категории товаров</a> > ";
	$status .= "<a href='?view=category&id=".$category[0]['parent_id']."'>".$db->getFieldOnID('categories', $category[0]['parent_id'], 'title')."</a> > ";
	$status .= "<a href='?view=category&act=filters&id=".$category[0]['id']."'>".$db->getFieldOnID('filters', $filter[0]['id'], 'title')." - фильтры</a> > $page_title";?>
	<div id="total">Всего: <?=$all?></div>
	<div class="actions">
		<form style="margin-top: -3px;float: left;margin-bottom: 10px;"  method="post">
			<input type="hidden" name="form_submit" value="1">
			<input style="width: 264px;" required type="text" name="search" value="<?=$_POST['search']?>" placeholder="Поиск по артикулу, vid и названию">
			<input type="submit" value="Искать">
		</form>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Название</td>
			<td>Штрих-код</td>
			<td>Категории</td>
		</tr>
		<?foreach($items as $item){?>
			<tr>
				<td><?=$db->getFieldOnID('brends', $item['brend_id'], 'title')?></td>
				<td><a href="?view=items&act=item&id=<?=$item['id']?>"><?=$item['article']?></a></td>
				<td><?=$item['title']?></td>
				<td><?=$item['barcode']?></td>
				<td>
					<?$categories_items = $db->select('categories_items', 'category_id', "`item_id`=".$item['id']);
					if (count($categories_items)){
						foreach ($categories_items as $category_item) {?>
							<a href="?view=category&act=items&id=<?=$category_item['category_id']?>"><?=$db->getFieldOnID('categories', $category_item['category_id'], 'title')?></a>
						<?}
					}?>
				</td>
			</tr>
		<?}?>
	</table>
<?}
?>