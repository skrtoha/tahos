<?php
/* @var $db \core\Database */

use core\Config;
use core\Provider;

$act = $_GET['act'];
switch ($act) {
    case 'clearAllPrices':
        Provider::clearStoresItems();
        break;
	case 'delete':
		if ($db->delete('providers', "`id`=".$_GET['id'])){
			message('Поставщик успешно удален!');
			header("Location: ?view=prices");
		}
		break;
	case 'items': items(); break;
	case 'add': show_form('s_add'); break;
	case 'change': show_form('s_change'); break;
	case 'search_add': search_add(); break;
	case 'delete_item':
        $where = "`item_id`=".$_GET['item_id']." AND `store_id`=".$_GET['store_id'];
		if ($db->delete('store_items', $where)){
            $db->delete('main_store_item', "`item_id` = {$_GET['item_id']}");
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
    case 'updatePrices':
        $query = "
            SELECT
                si2.store_id AS main_store_item,
                si2.price AS main_store_item_price,
				si.item_id,
				si.store_id,
                si.price,
                msi.min_price    
			FROM
				#store_items si
			RIGHT JOIN
                #main_store_item msi ON msi.item_id = si.item_id
            LEFT JOIN
			    #store_items si2 ON si2.store_id = msi.store_id AND si2.item_id = si.item_id     
            WHERE
                si.store_id = ".Config::MAIN_STORE_ID." AND si.price != si2.price";
        $result = $db->query($query, '');
        foreach($result as $row){
            if ($row['main_store_item_price'] < $row['min_price']) continue;
            $where = "`store_id` = ".Config::MAIN_STORE_ID." AND item_id = {$row['item_id']}";
            $db->update(
                'store_items',
                ['price' => $row['main_store_item_price']],
                $where
            );
            $db->update(
                'main_store_item',
                ['updated' => (new DateTime())->format('Y-m-d H:i:s')],
                "`item_id` = {$row['item_id']}"
            );
        }
        header("Location: /admin/?view=prices&act=items&id=".Config::MAIN_STORE_ID);
        die;
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
	?>
	<div id="total" style="margin-top: 10px;">Всего: <?=$all?></div>
	<div class="actions" style="">
		<form style="float: left;margin-bottom: 10px;" method="post">
			<input style="width: 264px;" type="text" name="search" value="<?=$search?>" placeholder="Поиск">
			<input type="submit" value="Искать">
		</form>
        <a href="/cron.php?param1=clearStores">Очистить все прайсы</a>
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
    $isSelf = isset($_GET['self']) && $_GET['self'];
	$id = $_GET['id'];
	require_once('templates/pagination.php');
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
			case 'summ': $orderBy = 'si.price * si.in_stock'; break;
            case 'updated': $orderBy = 'msi.updated'; break;
            case 'min_price': $orderBy = 'msi.min_price'; break;
		}
		if (isset($_GET['direction']) && $_GET['direction']) $orderBy .= " {$_GET['direction']}";
	}
    if ($isSelf){
        $selfStores = Provider\Tahos::getSelfStores();
        if (!isset($_GET['id'])){
            $id = $selfStores[0]['id'];
        }
        else $id = $_GET['id'];
    }

	$where = "si.store_id = $id AND ";

	if (isset($_GET['article'])){
        switch($_GET['type_search']){
            case 'article':
                $article = \core\Item::articleClear($_GET['article']);
                $where .= "(i.article='$article') AND ";
                break;
            case 'brend':
                $where .= "b.title LIKE '{$_GET['article']}%' AND ";
                break;
            case 'title':
                $where .= "i.title_full LIKE '{$_GET['article']}%' AND ";
                break;
        }
    }
    $where = substr($where, 0, -5);
	$query = "
		SELECT 
			si.item_id,
			si.price,
			si.in_stock,
			si.packaging,
			b.title as brend, 
			rr.requiredRemain,
			IF(i.article_cat != '', i.article_cat, i.article) AS article, 
			IF (i.title_full<>'', i.title_full, i.title) AS title_full,  
		    CONCAT(p.title, '-', ps.cipher, '-', ps.title) AS main_store_item,
            msi.min_price,
            ps.provider_id,
            msi.updated
		FROM
			#store_items si
		LEFT JOIN #items i ON si.item_id=i.id
		LEFT JOIN #brends b ON b.id=i.brend_id
		LEFT JOIN #required_remains rr ON rr.item_id = si.item_id AND self_store_id = {$id}
		LEFT JOIN #main_store_item msi ON msi.item_id = si.item_id    
		LEFT JOIN #provider_stores ps ON ps.id = si.store_id 
		LEFT JOIN #providers p ON p.id = ps.provider_id
		WHERE 
			$where
		ORDER BY
			$orderBy
	";
	// echo $query; exit();
    $groupQuery = $db->query("
		SELECT
			COUNT(*) AS count,
            SUM(si.price * si.in_stock) AS summ
		FROM
			#store_items si
		LEFT JOIN #items i ON si.item_id=i.id
		LEFT JOIN #brends b ON b.id=i.brend_id
		WHERE 
			$where
	", '');
    $result = $groupQuery->fetch_assoc();
	$all = $result['count'];
    $commonSumm = $result['summ'];
	$page_title = "Прайс $title_store";
	$status = "<a href='/admin'>Главная</a> > <a href='?view=prices'>Прайсы</a> > $page_title";
	$perPage = 30;
	$linkLimit = 10;
	$page = $_GET['page'] ?? 1;
	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ?? 0;
	$query .= " LIMIT $start,$perPage";
	$linkHref = "/admin/?view=prices&act=items&id={$_GET['id']}";
	$menu = [
		'brend' => 'Бренд',
		'article' => 'Артикул',
		'title_full' => 'Название',
		'packaging' => 'Мин. заказ',
		'in_stock' => 'В наличии',
		'price' => 'Цена'
	];
	$res_items = $db->query($query, '');
    if ($isSelf){
        $menu['summ'] = 'Сумма';
        $menu['requiredRemain'] = 'Мин. наличие';
        $menu['main_store_item'] = 'Поставщик';
        $menu['min_price'] = 'Закупка';
        $menu['updated'] = 'Обновлен';
    }
    $provider_id = 0;
    foreach($res_items as $item){
        $provider_id = $item['provider_id'];
        break;
    }
    if ($isSelf && !$provider_id){
        $result = $db->select_one('provider_stores', ['provider_id'], "`id` = $id");
        $provider_id = $result['provider_id'];
    }

    ?>
	<div id="total" style="margin-top: 10px;">
        Всего: <?=$all?>
        <?if ($_GET['id'] == Config::MAIN_STORE_ID){?>
            на сумму <b><?=$commonSumm?></b> р.
        <?}?>
    </div>
    <input type="hidden" name="provider_id" value="<?=$provider_id?>">
	<div class="actions" style="">
        <?if ($isSelf){
            ?>
            <div class="self">
                <select name="self_id">
                    <?foreach($selfStores as $row){
                        $selected = $row['id'] == $id ? 'selected' : '';
                        ?>
                        <option <?=$selected?> value="<?=$row['id']?>"><?=$row['title']?></option>
                    <?}?>
                </select>
            </div>
        <?}?>
        <form>
            <input type="hidden" name="view" value="prices">
            <input type="hidden" name="act" value="items">
            <input type="hidden" name="id" value="<?=$_GET['id']?>">
            <div class="type_search">
                <div>
                    <label>
                        <?$checked = !$_GET['type_search'] || $_GET['type_search'] == 'article' ? 'checked' : '';?>
                        <input <?=$checked?> type="radio" name="type_search" value="article">
                        <span>артикулу</span>
                    </label>
                    <label>
                        <?$checked = $_GET['type_search'] == 'title' ? 'checked' : '';?>
                        <input <?=$checked?> type="radio" name="type_search" value="title">
                        <span>наименованию</span>
                    </label>
                    <label>
                        <?$checked = $_GET['type_search'] == 'brend' ? 'checked' : '';?>
                        <input <?=$checked?> type="radio" name="type_search" value="brend">
                        <span>бренду</span>
                    </label>
                </div>
            </div>
            <input class="intuitive_search" style="width: 264px; margin-top: 5px" type="text" name="article" value="<?=$_GET['article']?>" placeholder="Поиск по артикулу" required>
            <input type="submit" value="Искать">
        </form>
        <input style="width: 264px;" type="text" name="storeItemsForAdding" value="" class="intuitive_search" placeholder="Поиск для добавления">
        <?if($_GET['id'] == Config::MAIN_STORE_ID){?>
            <a href="/admin/?view=prices&act=updatePrices">Обновить цены</a>
        <?}?>
        <div class="clear"></div>
	</div>
	<table class="t_table" cellspacing="1" store_id="<?=$id?>" data-provider-id="<?=$provider_id?>">
        <thead>
        <tr class="head sort">
            <?foreach($menu as $alias => $title){
                $href = "$linkHref&sort=$alias";
                if (isset($_GET['sort']) && $_GET['sort'] == $alias && !$_GET['direction']){
                    $href .= "&direction=desc";
                }?>
                <td>
                    <a class="<?= $_GET['direction'] ?? '' ?>" href="<?=$href?>"><?=$title?></a>
                    <?if (isset($_GET['sort']) && $_GET['sort'] == $alias){?>
                        <span class="icon-arrow-down2"></span>
                    <?}?>
                </td>
            <?}?>
            <td>
        </tr>
        </thead>
		<tbody>
            <?if ($res_items->num_rows){
                foreach($res_items as $pi){?>
                    <tr>
                        <td><?=$pi['brend']?></td>
                        <td><a class="item" href="?view=items&id=<?=$pi['item_id']?>&act=item"><?=$pi['article']?></a></td>
                        <td><?=$pi['title_full']?></td>
                        <td><input type="text" class="store_item" value="<?=$pi['packaging']?>" column="packaging" item_id="<?=$pi['item_id']?>"></td>
                        <td><input type="text" class="store_item" value="<?=$pi['in_stock']?>" column="in_stock" item_id="<?=$pi['item_id']?>"></td>
                        <td><input type="text" class="store_item" value="<?=$pi['price']?>" column="price" item_id="<?=$pi['item_id']?>"></td>
                        <?if ($isSelf){?>
                            <td><?=$pi['price'] * $pi['in_stock']?></td>
                            <td>
                                <input type="text" class="store_item" value="<?=$pi['requiredRemain']?>" column="requiredRemain" item_id="<?=$pi['item_id']?>">
                            </td>
                            <td><?=$pi['main_store_item']?></td>
                            <td><input type="text" class="store_item" value="<?=$pi['min_price']?>" column="min_price" item_id="<?=$pi['item_id']?>"></td>
                            <td column="updated" item_id="<?=$pi['item_id']?>">
                                <?if ($pi['updated']){?>
                                    <?=DateTime::createFromFormat('Y-m-d H:i:s', $pi['updated'])->format('d.m.Y')?>
                                <?}?>
                            </td>
                        <?}?>
                        <td><a title="Удалить" item_id="<?=$pi['item_id']?>" class="deleteStoreItem" href="#"><span class="icon-cancel-circle1"></span></a></td>
                    </tr>
                <?}
            }
            else{?>
                <tr class="empty"><td colspan="7">Товаров не найдено</td></tr>
            <?}?>
        </tbody>
	</table>
	<a style="display: block;margin-top: 10px" href="<?=$_SERVER['HTTP_REFERER']?>">Назад</a>
	<?$href = "?view=prices&act=items&id=$id&article={$_GET['article']}&sort={$_GET['sort']}&direction={$_GET['direction']}&type_search={$_GET['type_search']}";
    if (isset($_GET['self'])) $href .= "&self={$_GET['self']}";
    $href .= "&page=";
    pagination($chank, $page, ceil($all / $perPage), $href);
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