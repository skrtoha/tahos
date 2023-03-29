<?
/** @global \core\Database $db */
/** @global stdClass $connection */

use core\Breadcrumb;
use core\Provider\Emex;

if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'){
	if (!core\Config::$isUseApiProviders){
		echo json_encode([]);
		exit();
	}
	$coincidences = array();

	$mikado = new core\Provider\Mikado($db);
	setCoincidences($mikado->getCoincidences($_GET['search']));

	$armtek = new core\Provider\Armtek($db);
	setCoincidences($armtek->getSearch($_GET['search']));

	setCoincidences(core\Provider\FavoriteParts::getSearch($_GET['search']));

	$rossko = new core\Provider\Rossko($db);
	setCoincidences($rossko->getSearch($_GET['search']));

    //из-за того, что изменилось АПИ поставщика, теперь для поска товара
    //стало обязательно передавать бренд
//	setCoincidences(core\Provider\Autoeuro::getSearch($_GET['search']));

	$abcp = new core\Provider\Abcp(NULL, $db);
	setCoincidences($abcp->getSearch($_GET['search']));

	setCoincidences(core\Provider\Autokontinent::getCoincidences($_GET['search']));

	setCoincidences(core\Provider\ForumAuto::getCoincidences($_GET['search']));

	setCoincidences(core\Provider\Autopiter::getCoincidences($_GET['search']));
	
	setCoincidences(core\Provider\Berg::getCoincidences($_GET['search']));

    setCoincidences(Emex::getCoincidences($_GET['search']));
	
	echo json_encode($coincidences);
	exit();
}
Breadcrumb::add("/search/{$_GET['type']}/{$_GET['search']}", 'Список совпадений');
if ($connection->denyAccess) die('Доступ запрещен!');
$title = "Список совпадений";
$search_count_user = 10;
if ($_GET['type'] == 'vin'){
	$search = substr($_GET['search'], 0, 9);
	// echo "$search<br>";
	$res_models = $db->query("
		SELECT
			m.id,
			m.title,
			m.href,
			m.vin,
			v.title AS vehicle,
			v.href AS vehicle_href,
			b.title AS brend,
			b.href AS brend_href
		FROM
			#models m
		LEFT JOIN #brends b ON m.brend_id=b.id
		LEFT JOIN #vehicles v ON v.id=m.vehicle_id
		WHERE
			m.vin='$search'
	", '');
	if ($res_models->num_rows){
		$m = $res_models->fetch_assoc();
		// debug($m);
		save_search($m['title']);
		$href = "/original-catalogs/{$m['vehicle_href']}/{$m['brend_href']}/{$m['id']}/{$m['href']}/{$_GET['search']}";
		header("Location: $href");
		exit();
	}
	else{?>
		<p>VIN <b style="font-weight: 700"><?=$_GET['search']?></b> не найден.</p>
	<?}
}
else{
	core\Provider\Impex::setSearch($_GET);
	$items = search_items('');
	if (!empty($items) && count($items) == 1){
		foreach($items as $id => $item) break;
		// debug($item, $id); exit();
		if ($item['is_armtek']) header("Location: /search/armtek/$id");
		else{
			if ($_SESSION['user']){
				core\User::saveUserSearch([
					'user_id' => $_SESSION['user'],
					'item_id' => $id
				]);
			}
			header("Location: /article/$id-{$item['article']}");
			exit();
		} 
	}
    Breadcrumb::out();
    ?>
	<input type="hidden" name="search" value="<?=$_GET['search']?>">
	<div class="hit-list">
		<h1>Список совпадений</h1>
		<table class="hit-list-table mobile_view">
            <thead>
                <tr>
                    <th>Бренд</th>
                    <th>Артикул</th>
                    <th>Наименование</th>
                    <th>Цена</th>
                    <th>Срок</th>
                    <?if ($user['allow_request_delete_item']){?>
                        <th></th>
                    <?}?>
                </tr>
            </thead>
			<tbody>
                <?if ($items){
                    foreach($items as $id => $item){?>
                        <tr is_armtek="<?=$item['is_armtek']?>" item_id="<?=$id?>" article="<?=$item['article']?>">
                            <td label="Бренд"><?=$item['brend']?></td>
                            <td label="Артикул"><a class="articul" href="/article/<?=$id?>-<?=$item['article']?>"><?=$item['article']?></a></td>
                            <td label="Наименование" style="text-align: left">
                                <?=trimStr($item['title_full'], 50)?>
                            </td>
                            <td label="Цена">
                                <?=$item['price'] ? 'от ' . core\User::getHtmlUserPrice($item['price'], $user['designation']) : 'Нет данных'?>
                            </td>
                            <td label="Срок">
                                <?=$item['delivery'] ? 'от '.$item['delivery'].' дн.' : 'Нет данных'?>
                            </td>
                            <?if ($user['allow_request_delete_item']){
                                if (!$item['is_blocked']){?>
                                    <td><span title="Запрос на удаление" class="icon-bin"></span></td>
                                <?}?>
                            <?}?>
                        </tr>
                    <?}
                }
                else{
                    switch($_GET['type']){
                        case 'article': $name = 'Артикул'; break;
                        case 'barcode': $name = 'Штрих-код'; break;
                        case 'vin': $name = 'VIN-номер';
                    }?>
                    <tr class="notFound">
                        <td colspan="5"><?=$name?> <b style="font-weight: 700"><?=$_GET['search']?></b> не найден</td>
                    </tr>
                    <tr class="notFound removable">
                        <td colspan="5">Идёт поиск по поставщикам<img src="/img/gif.gif" alt=""></td>
                    </tr>
                <?}?>
            </tbody>
		</table>
	</div>
<?}
function get_brend($title){
	global $db;
	$brend = $db->select_one('brends', 'id,parent_id', "`title`='$title'");
	if (empty($brend)){
		$db->insert('brends', ['title' => $title, 'href' => translite($title)]);
		return $db->last_id();
	}
	else return $brend['parent_id'] ? $brend['parent_id'] : $brend['id'];
}
function setCoincidences($c){
	global $coincidences;
	if (empty($c)) return false;
	foreach($c as $key => $value){
		if (!$key || !$value) continue;
		$coincidences[$key] = $value;
	} 
}
function search_items($flag = ''){
	global $db, $res_items;
	$for_search = core\Item::articleClear($_GET['search']);
	$ucs = 100; //user_count_search
	// print_r($_GET);
	switch ($_GET['type']){
		case 'article': 
			$where = "i.`article`='$for_search'";
            $where .= " OR (i.title_full LIKE '{$_GET['search']}%' AND si.price IS NOT NULL)";
			$type_search = 1;
			break;
		case 'barcode':
			$where =  "ib.`barcode`='$for_search'";
			$type_search = 2;
			break;
	}
	$res_items = $db->query("
		SELECT
			i.id,
			b.title as brend,
			i.brend_id AS brend_id,
			IF (
                i.article_cat != '', 
                i.article_cat, 
                IF (
                    i.article !='',
                    i.article,
                    ib.barcode
                )
			) AS article,
			IF (i.title_full!='', i.title_full, i.title) AS title_full,
			FLOOR(si.price * c.rate + si.price * c.rate * (ps.percent/100)) AS price,
			si.store_id,
			IF (si.in_stock, ps.delivery, ps.under_order) AS delivery,
			ps.cipher AS cipher,
			i.is_blocked,
			IF (
				i.applicability !='' || i.characteristics !=''  || i.full_desc !='' || i.photo != '',
				1,
				0
			) as is_desc
		FROM #items i
		LEFT JOIN #brends b ON b.id=i.brend_id
		LEFT JOIN #item_barcodes ib ON ib.item_id = i.id
		LEFT JOIN #store_items si ON si.item_id=i.id
		LEFT JOIN #provider_stores ps ON ps.id=si.store_id
		LEFT JOIN #currencies c ON ps.currency_id=c.id
		WHERE $where
        LIMIT 0, $ucs
	", $flag);
	if (!$res_items->num_rows) return false;
	$price_min = 0;
	$delivery_min = 0;
	while($item = $res_items->fetch_assoc()){
		$i = & $items[$item['id']];
		$i['brend'] = $item['brend'];
		$i['brend_id'] = $item['brend_id'];
		$i['article'] = $item['article'];
		$i['title_full'] = $item['title_full'];
		$i['is_blocked'] = $item['is_blocked'];
		//for displaying coincided items
		if (in_array($item['provider_id'], [11, 12, 18, 20, 21])) $i['is_armtek'] = 1;
		if (isset($i['price'])){
			if ($item['price'] < $i['price']) $i['price'] = $item['price'];
		}
		else $i['price'] = $item['price'];
		if (isset($i['delivery'])){
			if ($item['delivery'] < $i['delivery']) $i['delivery'] = $item['delivery'];
		}
		else $i['delivery'] = $item['delivery'];
	}
	return $items;
}?>
