<?
if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'){
	$coincidences = array();

	$mikado = new core\Provider\Mikado($db);
	setCoincidences($mikado->getCoincidences($_GET['search']));

	$armtek = new core\Provider\Armtek($db);
	setCoincidences($armtek->getSearch($_GET['search']));

	setCoincidences(core\Provider\FavoriteParts::getSearch($_GET['search']));

	$rossko = new core\Provider\Rossko($db);
	setCoincidences($rossko->getSearch($_GET['search']));

	setCoincidences(core\Provider\Autoeuro::getSearch($_GET['search']));

	$abcp = new core\Provider\Abcp(NULL, $db);
	setCoincidences($abcp->getSearch($_GET['search']));

	core\Provider\Autokontinent::getCoincidences($_GET['search']);
	
	if (empty($coincidences)) exit();
	echo json_encode($coincidences);
	exit();
}
if ($connection->denyAccess) die('Доступ запрещен!');
if ($_GET['type'] == 'armtek') get_items_armtek($_GET['item_id']);
// debug($_GET); exit();
// 56992-PDE-505
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
	if (!empty($items) && $_SESSION['user']){
		foreach($items as $id => $item) break;
		save_search($item['title_full']);
	} 
	if (!empty($items) && count($items) == 1){
		foreach($items as $id => $item) break;
		// debug($item, $id); exit();
		if ($item['is_armtek']) header("Location: /search/armtek/$id");
		else header("Location: /article/$id-{$item['article']}");
	}?>
	<input type="hidden" name="search" value="<?=$_GET['search']?>">
	<input type="hidden" name="user_id" value="<?=$user['id']?>">
	<div class="hit-list">
		<h1>Список совпадений</h1>
		<table class="hit-list-table">
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
			<?if ($items){
					foreach($items as $id => $item){?>
						<tr is_armtek="<?=$item['is_armtek']?>" item_id="<?=$id?>" article="<?=$item['article']?>">
							<td><?=$item['brend']?></td>
							<td><a class="articul" href="/article/<?=$id?>-<?=$item['article']?>"><?=$item['article']?></a></td>
							<td style="text-align: left"><?=$item['title_full']?></td>
							<td>
								<?=$item['price'] ? 'от '.get_user_price($item['price'], $user).$user['designation'] : 'Нет данных'?>
							</td>
							<td>
								<?=$item['delivery'] ? 'от '.$item['delivery'].' дн.' : 'Нет данных'?>
							</td>
							<?if ($user['allow_request_delete_item']){
								if (!$item['is_blocked']){?>
									<td><span title="Запрос на удаление" class="icon-bin"></span></td>
								<?}
								?>
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
		</table>
	</div>
<?}
function save_search($title){
	global $db, $search_count_user;
	if (!$_SESSION['user']) return false;
	switch($_GET['type']){
		case 'article': $search_type = 1; break;
		case 'barcode': $search_type = 2; break;
		case 'vin': $search_type = 3; break;
	}
	$db->query("
		UPDATE #search SET `date`=CURRENT_TIMESTAMP
		WHERE
		`user_id`={$_SESSION['user']} AND
		`type`=$search_type AND
		`text`='{$_GET['search']}'
	", '');
	if (!$db->rows_affected()){
		$userSearch = $db->select(
			'search',
			'*',
			"`user_id` = {$_SESSION['user']} AND `type` = $search_type",
			'date',
			true,
			"0, $search_count_user"
		);
		if (count($userSearch) == $search_count_user){
			$db->query("
				UPDATE
					#search
				SET
					`text`='{$_GET['search']}',
					`title`='$title',
					`date` = CURRENT_TIMESTAMP
				WHERE
					`user_id`={$_SESSION['user']} AND
					`type`= $search_type AND
					`text` = '{$userSearch[0]['text']}'
			", '');
		}
		else $db->insert(
			'search',
			[
				'user_id' => $_SESSION['user'],
				'type' => $search_type,
				'text' => $_GET['search'],
				'title' => $title,
			]
		);
	}
}
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
	$for_search = article_clear($_GET['search']);
	$ucs = 10; //user_count_search
	// print_r($_GET);
	switch ($_GET['type']){
		case 'article': 
			if (mb_strlen($for_search) == 13) $where = "(i.`article`='$for_search' OR i.`barcode`='$for_search')";
			else $where = "i.`article`='$for_search'";
			$type_search = 1;
			break;
		case 'barcode':
			$where =  "i.`barcode`='$for_search'";
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
						i.barcode
					)
			) AS article,
			IF (i.title_full!='', i.title_full, i.title) AS title_full,
			FLOOR(si.price * c.rate + si.price * c.rate * (ps.percent/100)) AS price,
			si.store_id,
			IF (si.in_stock, ps.delivery, ps.under_order) AS delivery,
			ps.cipher AS cipher,
			i.is_blocked,
			IF (
				i.applicability !='' || i.characteristics !=''  || i.full_desc !='' || i.foto != '',
				1,
				0
			) as is_desc
		FROM #items i
		LEFT JOIN #brends b ON b.id=i.brend_id
		LEFT JOIN #store_items si ON si.item_id=i.id
		LEFT JOIN #provider_stores ps ON ps.id=si.store_id
		LEFT JOIN #currencies c ON ps.currency_id=c.id
		WHERE $where
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
