<?php
$act = $_GET['act'];
switch ($act) {
    case 'clearAllPrices':
        \core\Provider::clearStoresItems(false);
        break;
	case 'delete':
		if ($db->delete('providers', "`id`=".$_GET['id'])){
			message('Поставщик успешно удален!');
			header("Location: ?view=prices");
		}
		break;
	case 'items': items(); break;
	case 'item': item(); break;
	case 'add': show_form('s_add'); break;
	case 'change': show_form('s_change'); break;
	case 'search_add': search_add(); break;
	case 'delete_item':
		if ($db->delete('store_items', "`item_id`=".$_GET['item_id']." AND `store_id`=".$_GET['store_id'])){
			exit();
		}
		break;
	case 's_add':
		if ($db->insert('providers', $_POST)){
			message('Поставщик успешно добавлен!');
			header("Location: ?view=prices");
		}
		break;
	case 's_change':
		if ($db->update('providers', $_POST, "`id`=".$_GET['id'])){
			message('Поставщик успешно изменен!');
			header("Location: ?view=prices");
		}
		break;
	default:
		view();
}
function view(){
	global $status, $db, $page_title;
	require_once('templates/pagination.php');
	$search = $_GET['search'] ? $_GET['search'] : $_POST['search'];
	if ($search){
		$where = "`cipher` LIKE '%$search%'";
		$page_title = "Поиск по шифру";
		$status = "<a href='/admin'>Главная</a> > <a href='?view=prices'>Прайсы</a> > $page_title";
	} 
	else{
		$where = "";
		$page_title = "Прайсы";
		$status = "<a href='/admin'>Главная</a> > $page_title";
	} 
	$all = $db->getCount('provider_stores', $where);
	if ($where) $where = "WHERE $where";
	$perPage = 30;
	$linkLimit = 10;
	$page = $_GET['page'] ? $_GET['page'] : 1;
	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ? $chank[$page] : 0;
	$res_stores = $db->query("
		SELECT
			ps.id AS store_id,
			ps.cipher,
			ps.title,
			ps.price_updated,
			p.title AS provider_title,
			p.id AS provider_id
		FROM
			#provider_stores ps
		LEFT JOIN
			#providers p ON p.id=ps.provider_id
		$where
		ORDER BY
			ps.price_updated DESC
		LIMIT
			$start,$perPage
	", '');
	// $providers = $db->select_query('providers', 'id,title,cipher,price_updated', $where, 'price_updated', false, "$start,$perPage");
	?>
	<div id="total" style="margin-top: 10px;">Всего: <?=$all?></div>
	<div class="actions" style="">
		<form style="float: left;margin-bottom: 10px;" method="post">
			<input style="width: 264px;" type="text" name="search" value="<?=$search?>" placeholder="Поиск">
			<input type="submit" value="Искать">
		</form>
        <a href="?view=prices&act=clearAllPrices">Очистить все прайсы</a>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Шифр</td>
			<td>Название</td>
			<td>Поставщик</td>
			<td>Дата обновления</td>
		</tr>
		<?if ($res_stores->num_rows){
			while($row = $res_stores->fetch_assoc()){?>
			<tr store_id="<?=$row['store_id']?>">
				<td><?=$row['cipher']?></td>
				<td><?=$row['title']?></td>
				<td><a href="?view=providers&act=provider&id=<?=$row['provider_id']?>"><?=$row['provider_title']?></a></td>
				<td><?=date('d.m.Y H:i:s', strtotime($row['price_updated']))?></td>
			</tr>
		<?}
		}
		else{?>
			<tr><td colspan="3">Поставщиков не найдено</td></tr>
		<?}?>
	</table>
	<?pagination($chank, $page, ceil($all / $perPage), $href = "?view=prices&search=$search&page=");
}
function show_form($act){
	global $status, $db, $page_title;
	switch ($act){
		case 's_add':
			$page_title = "Добавление поставщика";
			break;
		case 's_change':
			$id = $_GET['id'];
			$page_title = "Изменение поставщика";
			$provider = $db->select('providers', '*', "`id`=$id");
			$provider = $provider[0];
			break;
	}
	$status = "<a href='/admin'>Главная</a> > <a href='?view=prices'>Прайсы</a> > $page_title";
	?>
	<div class="t_form">
		<div class="bg">
			<form action="?view=prices&id=<?=$id?>&act=<?=$act?>" method="post" enctype="multipart/form-data">
				<div class="field">
					<div class="title">Название</div>
					<div class="value">
						<input type="text" name="title" value="<?=$provider['title']?>">
					</div>
				</div>
				<div class="field">
					<div class="title">Шифр</div>
					<div class="value">
						<span style="color: grey;display: block; margin-bottom: 5px;font-size: 12px">Введите 4 заглавные буквы латинского алфавита</span>
						<input type="text" pattern="^[A-Z]{4}$" required name="cipher" value="<?=$provider['cipher']?>">
					</div>
				</div>
				<div class="field">
					<div class="title">Ожидаемый срок</div>
					<div class="value">
						<input type="text" name="delivery" value="<?=$provider['delivery']?>">
					</div>
				</div>
				<div class="field">
					<div class="title">Гарантированный</div>
					<div class="value">
						<input type="text" name="delivery_max" value="<?=$provider['delivery_max']?>">
					</div>
				</div>
				<div class="field">
					<div class="title">Под заказ</div>
					<div class="value">
						<input type="text" name="under_order" value="<?=$provider['under_order']?>">
					</div>
				</div>
				<div class="field">
					<div class="title"></div>
					<div class="value"><input type="submit" class="button" value="Сохранить"></div>
				</div>
			</form>
		</div>
	</div>
<?}
function items(){
	global $status, $db, $page_title;
	$id = $_GET['id'];
	require_once('templates/pagination.php');
	$search = $_GET['search'] ? $_GET['search'] : $_POST['search'];
	$search = core\Item::articleClear($search);
	$title_store = $db->getFieldOnID('provider_stores', $id, 'cipher');
	$orderBy = "si.created DESC";
	if (isset($_GET['sort'])){
		switch ($_GET['sort']){
			case 'brend': $orderBy = "b.title"; break;
			case 'article': $orderBy = "i.article"; break;
			case 'title_full': $orderBy = 'i.title_full'; break;
			case 'packaging': $orderBy = 'si.packaging'; break;
			case 'in_stock': $orderBy = 'si.in_stock'; break;
			case 'price': $orderBy = 'si.price'; break;
			case 'requiredRemain': $orderBy = 'rr.requiredRemain'; break;
			case 'summ': $orderBy = 'si.price * si.in_stock';
		}
		if (isset($_GET['direction']) && $_GET['direction']) $orderBy .= " {$_GET['direction']}";
	}
	$where = '';
	if ($search) $where = "
		(
			i.article='$search'
		) 
		AND 
	";
	$where .= "si.store_id=$id";
	$query = "
		SELECT 
			si.item_id,
			si.price,
			si.in_stock,
			si.packaging,
			b.title as brend, 
			rr.requiredRemain,
			IF(i.article_cat != '', i.article_cat, i.article) AS article, 
			IF (i.title_full<>'', i.title_full, i.title) AS title_full
		FROM
			#store_items si
		LEFT JOIN #items i ON si.item_id=i.id
		LEFT JOIN #brends b ON b.id=i.brend_id
		LEFT JOIN #required_remains rr ON rr.item_id = si.item_id
		WHERE 
			$where
		ORDER BY
			$orderBy
	";
	// echo $query; exit();
	$all = $db->query("
		SELECT SQL_CALC_FOUND_ROWS
			si.item_id
		FROM
			#store_items si
		WHERE 
			$where
	", '');
	$all = $db->found_rows();
	$page_title = "Прайс $title_store";
	$status = "<a href='/admin'>Главная</a> > <a href='?view=prices'>Прайсы</a> > $page_title";
	$perPage = 30;
	$linkLimit = 10;
	$page = $_GET['page'] ? $_GET['page'] : 1;
	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ? $chank[$page] : 0;
	$query .= " LIMIT $start,$perPage";
	$linkHref = "/admin/?view=prices&act=items&id={$_GET['id']}";
	$menu = [
		'brend' => 'Бренд',
		'article' => 'Артикул',
		'title_full' => 'Название',
		'packaging' => 'Мин. заказ',
		'in_stock' => 'В наличии',
		'price' => 'Цена',
	];
	if ($id == 23){
		$menu['summ'] = 'Сумма';
		$menu['requiredRemain'] = 'Мин. наличие';
	}
	$res_items = $db->query($query, '');?>
	<div id="total" style="margin-top: 10px;">Всего: <?=$all?></div>
	<div class="actions" style="">
		<input style="width: 264px;" type="text" name="searchArticle" value="" placeholder="Поиск по артикулу" class="intuitive_search">
			<input style="width: 264px;" type="text" name="storeItemsForAdding" value="" class="intuitive_search" placeholder="Поиск для добавления">
	</div>
	<table class="t_table" cellspacing="1" store_id="<?=$_GET['id']?>">
		<tr class="head sort">
			<?foreach($menu as $alias => $title){
				$href = "$linkHref&sort=$alias";
				if (isset($_GET['sort']) && $_GET['sort'] == $alias && !$_GET['direction']){
					$href .= "&direction=desc";
				}?>
				<td>
					<a class="<?=isset($_GET['direction']) ? $_GET['direction'] : ''?>" href="<?=$href?>"><?=$title?></a>
					<?if (isset($_GET['sort']) && $_GET['sort'] == $alias){?>
						<span class="icon-arrow-down2"></span>
					<?}?>
				</td>
			<?}?>
			<td>
		</tr>
		<?if ($res_items->num_rows){
			while($pi = $res_items->fetch_assoc()){?>
				<tr>
					<td><?=$pi['brend']?></td>
					<td><a class="item" href="?view=items&id=<?=$pi['item_id']?>&act=item"><?=$pi['article']?></a></td>
					<td><?=$pi['title_full']?></td>
					<td><input type="text" class="store_item" value="<?=$pi['packaging']?>" column="packaging" item_id="<?=$pi['item_id']?>"></td>
					<td><input type="text" class="store_item" value="<?=$pi['in_stock']?>" column="in_stock" item_id="<?=$pi['item_id']?>"></td>
					<td><input type="text" class="store_item" value="<?=$pi['price']?>" column="price" item_id="<?=$pi['item_id']?>"></td>
					<?if ($id == 23){?>
						<td><?=$pi['price'] * $pi['in_stock']?></td>
						<td>
							<input type="text" class="store_item" value="<?=$pi['requiredRemain']?>" column="requiredRemain" item_id="<?=$pi['item_id']?>">
							</td>
					<?}?>
					<td><a title="Удалить" item_id="<?=$pi['item_id']?>" class="deleteStoreItem" href="#"><span class="icon-cancel-circle1"></span></a></td>
				</tr>
			<?}
		}
		else{?>
			<tr class="empty"><td colspan="7">Товаров не найдено</td></tr>
		<?}?>
	</table>
	<a style="display: block;margin-top: 10px" href="<?=$_SERVER['HTTP_REFERER']?>">Назад</a>
	<?pagination($chank, $page, ceil($all / $perPage), $href = "?view=prices&act=items&id=$id&search=$search&sort={$_GET['sort']}&direction={$_GET['direction']}&page=");
}
function search_add(){
	global $status, $db, $page_title;
	$id = $_GET['id'];
	$search = $_GET['search'] ? $_GET['search'] : $_POST['search'];
	$search = core\Item::articleClear($search);
	$title_provider = $db->getFieldOnID('providers', $id, 'title');
	$page_title = "Добавление товара";
	$status = "<a href='/admin'>Главная</a> > <a href='?view=prices'>Прайсы</a> > ";
	$status .= "<a href='?view=prices&act=items&id=$id'>".$db->getFieldOnID('providers', $id, 'title')."</a> > $page_title";
	$res_items = $db->query("
		SELECT 
			i.id,
			b.title as brend, 
			IF(i.article_cat != '', i.article_cat, i.article) as article, 
			if (i.title_full<>'', i.title_full, i.title) as title_full
		FROM
			#items i
		LEFT JOIN #store_items si ON si.item_id=i.id AND si.store_id=$id
		LEFT JOIN tahos_brends b ON b.id=i.brend_id
		WHERE 
			i.article='$search' AND
			si.item_id IS NULL
	", '');
	if ($res_items->num_rows > 200){
		message('Результат поиска вернул более 200 совпадений! Уточните поиск!', false);
		header("Location: /admin/?view=prices&act=items&id=$id");
		exit();
	}?>
	<div id="total" style="margin-top: 10px;">Всего: <?=$res_items->num_rows?></div>
	<div class="actions" style="">
		<form style="margin: 0px 0 5px 0px;;" action="?view=prices&act=search_add&id=<?=$id?>" method="post">
			<input style="width: 264px;" required type="text" name="search" value="<?=$search?>" placeholder="Поиск для добавления">
			<input type="submit" value="Искать">
		</form>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Название</td>
			<td></td>
		</tr>
		<?if ($res_items->num_rows){
			while($item = $res_items->fetch_assoc()){?>
				<tr>
					<td><?=$item['brend']?></td>
					<td><?=$item['article']?></td>
					<td><?=$item['title_full']?></td>
					<td><a class="add_item_to_store" store_id="<?=$id?>" item_id="<?=$item['id']?>" href="">Добавить</a></td>
				</tr>
			<?}
		}
		else{?>
			<tr><td colspan="4">Товаров не найдено</td></tr>
		<?}?>
	</table>
	<a style="display: block;margin-top: 10px" href="<?=$_SERVER['HTTP_REFERER']?>">Назад</a>
<?}?>