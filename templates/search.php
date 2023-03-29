<?

use core\Breadcrumb;
use core\Search;

if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'){
    $coincidences = Search::searchItemProviders($_GET['search']);
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
	$items = Search::searchItemDatabase($_GET['search'], $_GET['type']);
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