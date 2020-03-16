<?php
if ($_FILES['model_image']){
	error_reporting(E_ERROR);
	$res = model_set_image($_FILES['model_image'], $_POST['model_id']);
	if ($res){?>
		<img src="/images/models/<?=$_POST['model_id']?>.jpg">
		<a href="#" class="model_image_delete" model_id="<?=$_POST['model_id']?>">Удалить</a>
	<?}
	exit();
}
if ($_FILES['vehicle_image']){
	error_reporting(E_ERROR);
	// debug($_POST);
	// debug($_FILES);
	// exit();
	$res = vehicle_set_image($_FILES['vehicle_image'], $_POST['vehicle_id']);
	if ($res){?>
		<img src="/images/vehicles/<?=$_POST['vehicle_id']?>.jpg">
		<a href="#" class="vehicle_image_delete" vehicle_id="<?=$_POST['model_id']?>">Удалить</a>
	<?}
	exit();
}
$act = $_GET['act'];
error_reporting(E_ERROR);
if ($_GET['brend_id'] && $_GET['vehicle_id'] && !$_GET['model_id']) $act = 'models';
if ($_GET['brend_id'] && $_GET['vehicle_id'] && $_GET['model_id']) $act = 'modifications';
if ($_GET['brend_id'] && $_GET['vehicle_id'] && $_GET['model_id'] && $_GET['modification_id']) $act = 'nodes';
if ($_GET['node_id'] && $_GET['act'] != 'node_image') $act = 'node';
// debug($_GET);
switch ($act) {
	case 'vehicle_brends': vehicle_brends(); break;
	case 'models': models(); break;
	case 'modifications': modifications(); break;
	case 'nodes': nodes(); break;
	case 'node_create':
		$db->insert(
			'nodes', 
			[
				'title' => $_POST['title'], 
				'parent_id' => $_POST['parent_id'], 
				'modification_id' => $_GET['modification_id']
			],
			['print_query' => false]
		);
		header("Location: {$_SERVER['HTTP_REFERER']}");
		break;
	case 'node': node(); break;
	case 'node_image':
		$res = node_set_image($_FILES['image'], $_GET['node_id']);
		if (!$res['error']){
			message('Изображение успешно добавлено!');
			header("Location: {$_SERVER['HTTP_REFERER']}");
		}
		else message($res['error'], false);
		break;
	case 'image_delete':
		unlink($_GET['src']);
		unlink(str_replace('big', 'small', $_GET['src']));
		message('Изображение успешно удалено!');
		header("Location: {$_SERVER['HTTP_REFERER']}");
		break;
	case 'recalculate_subgroups':
		set_time_limit(0);
		$seconds = 28800;
		$start = time();
		$countChecked = 0;
		do{
			$res_node = $db->query("
				SELECT
					n.id
				FROM 
					#nodes n
				WHERE
					n.isChecked = 0
				LIMIT
					0, 1
			", '');
			if (!$res_node->num_rows){
				message('Успешно обновлено!');
				header("Location: /admin/?view=original-catalogs");	
			};
			$node = $res_node->fetch_assoc();
			$res_nodes = $db->query("
				SELECT
					n.id
				FROM 
					#nodes n
				WHERE
					n.parent_id={$node['id']}
			", '');
			if (!$res_nodes->num_rows) $db->update('nodes', ['isChecked' => 1, 'subgroups_exist' => 0], "`id`={$node['id']}");
			else $db->update('nodes', ['isChecked' => 1], "`id`={$node['id']}");
			$countChecked++;
			$end = time();
			if ($end - $start >= $seconds) die("Время вышло, проверено $countChecked");
		} while ($res_node->num_rows);
		message('Успешно обновлено!');
		header("Location: /admin/?view=original-catalogs");	
		break;
	default:
		vehicles();
}
function vehicles(){
	global $status, $db, $page_title;
	$res_vehicles = $db->query("
		SELECT
			v.id,
			v.title,
			v.pos,
			v.is_mosaic,
			vc.title AS category
		FROM
			#vehicles v
		LEFT JOIN #vehicle_categories vc ON vc.id=v.category_id
	", '');
	$page_title = "Оригинальные каталоги";
	$status = "<a href='/admin'>Главная</a> > $page_title"?>
	<div id="total" style="margin-top: 10px;">Всего: <span><?=$res_vehicles->num_rows?></span></div>
	<div id="action">
		<a class="vehicle_add" href="#">Добавить</a>
		<a id="vehicle_categories" href="#">Категории</a>
		<a href="/admin/?view=original-catalogs&act=recalculate_subgroups">Пересчитать подгруппы</a>
	</div>
	<table class="t_table vehicles" cellspacing="1">
		<tr class="head">
			<td>Вид транспортного средства</td>
			<td>Позиция</td>
			<td>Плитка</td>
			<td>Категория</td>
			<td></td>
		</tr>
		<?if ($res_vehicles->num_rows){
			while($value = $res_vehicles->fetch_assoc()){?>
				<tr class="vehicle clickable" vehicle_id="<?=$value['id']?>">
					<td>
						<a href="?view=original-catalogs&act=vehicle_brends&vehicle_id=<?=$value['id']?>">
							<?=$value['title']?>
						</a>
					</td>
					<td><?=$value['pos']?></td>
					<td><?=$value['is_mosaic'] ? 'да' : 'нет'?></td>
					<td><?=$value['category']?></td>
					<td>
						<a href="#" class="vehicle_change not_clickable">Изменить</a>
						<a href="#" class="vehicle_remove not_clickable">Удалить</a>
						<?$is_image = file_exists("{$_SERVER['DOCUMENT_ROOT']}/images/vehicles/{$value['id']}.jpg") ? 1 : 0?>
						<input type="hidden" name="is_image" value="<?=$is_image?>">
					</td>
				</tr>
			<?}
		}
		else{?>
			<tr class="removable">
				<td colspan="5">Категории траспортных средств не найдено</td>
			</tr>
		<?}?>
	</table>
	<form action="?view=original-catalogs" class="vehicle_image" enctype="multipart/form-data" method="post">
		<input type="text" name="vehicle_id" value="">
		<input type="file" name="vehicle_image">
	</form>
<?}
function vehicle_brends(){
	global $status, $db, $page_title;
	// debug($_SERVER);
	$page_title = $db->getFieldOnID('vehicles', $_GET['vehicle_id'], 'title');
	$status = "
		<a href='/admin'>Главная</a> > 
		<a href='/admin/?view=original-catalogs'>Оригинальные каталоги</a> > 
		$page_title
	";
	$res_brends = $db->query("
		SELECT
			b.id,
			b.title
		FROM
			#vehicle_filters vf
		LEFT JOIN #brends b ON b.id=vf.brend_id
		WHERE
			vf.vehicle_id={$_GET['vehicle_id']}
		GROUP BY vf.brend_id
		ORDER BY b.title
	", '');?>
	<div id="total" style="margin-top: 10px;">Всего: <span><?=$res_brends->num_rows?></span></div>
	<div id="action"><a class="brend_add" href="#">Добавить</a></div>
	<table vehicle_id=<?=$_GET['vehicle_id']?> class="t_table brends" cellspacing="1">
		<tr class="head">
			<td>Бренд</td>
			<td></td>
		</tr>
		<?if ($res_brends->num_rows){
			while($row = $res_brends->fetch_assoc()){?>
				<tr class="brend clickable" brend_id="<?=$row['id']?>">
					<td>
						<a href="<?=$_SERVER['REQUEST_URI']?>&brend_id=<?=$row['id']?>"><?=$row['title']?></a>
					</td>
					<td>
						<a href="/admin/?view=brends&id=<?=$row['id']?>&act=change&from=<?=get_from_uri()?>">Изменить</a>
						<a href="#" class="brend_remove not_clickable">Удалить</a>
					</td>
				</tr>
			<?}
		}
		else{?>
			<tr class="removable">
				<td colspan="5">Брендов не найдено</td>
			</tr>
		<?}?>
	</table>
<?}
function models(){
	global $status, $db, $page_title;
	$vehicle_title = $db->getFieldOnID('vehicles', $_GET['vehicle_id'], 'title');
	$page_title = $db->getFieldOnID('brends', $_GET['brend_id'], 'title');
	$status = "
		<a href='/admin'>Главная</a> > 
		<a href='/admin/?view=original-catalogs'>Оригинальные каталоги</a> > 
		<a href='/admin/?view=original-catalogs&act=vehicle_brends&vehicle_id={$_GET['vehicle_id']}'>$vehicle_title</a> > 
		$page_title
	";
	$models = $db->select('models', '*', "`brend_id`={$_GET['brend_id']} AND `vehicle_id`={$_GET['vehicle_id']}", 'title', true)?>
	<div id="total" style="margin-top: 10px;">Всего: <span><?=count($models)?></span></div>
	<div id="action"><a class="model_add" href="">Добавить</a> <a href="#" class="filters">Фильтры</a></div>
	<table vehicle_id="<?=$_GET['vehicle_id']?>" brend_id="<?=$_GET['brend_id']?>" class="t_table models" cellspacing="1">
		<tr class="head">
			<td>Модель</td>
			<td>VIN</td>
			<td></td>
		</tr>
		<?if (count($models)){
			foreach($models as $key => $value){?>
				<tr class="model clickable" model_id="<?=$value['id']?>" href="<?=$value['href']?>">
					<td>
						<a href="<?=$_SERVER['REQUEST_URI']?>&model_id=<?=$value['id']?>"><?=$value['title']?></a>
					</td>
					<td><?=$value['vin']?></td>
					<td>
						<?$img_path = "/images/models/{$value['id']}.jpg";
						$model_image_exists = file_exists($_SERVER['DOCUMENT_ROOT'].$img_path) ? 1 : 0;?>
						<input type="hidden" name="model_image_exists" value="<?=$model_image_exists?>">
						<a class="model_change not_clickable" href="">Изменить</a>
						<a href="#" class="model_remove not_clickable">Удалить</a>
					</td>
				</tr>
			<?}
		}
		else{?>
			<tr class="removable">
				<td colspan="5">Моделей не найдено</td>
			</tr>
		<?}?>
	</table>
		<form action="?view=original-catalogs" id="model_image" enctype="multipart/form-data" method="post">
			<input type="text" name="model_id" value="">
			<input type="file" name="model_image">
		</form>
<?}
function modifications(){
	global $status, $db, $page_title;
	$page_title = $db->getFieldOnID('models', $_GET['model_id'], 'title');
	$vehicle_title = $db->getFieldOnID('vehicles', $_GET['vehicle_id'], 'title');
	$brend_title = $db->getFieldOnID('brends', $_GET['brend_id'], 'title');
	$status = "
		<a href='/admin'>Главная</a> > 
		<a href='/admin/?view=original-catalogs'>Оригинальные каталоги</a> > 
		<a href='/admin/?view=original-catalogs&act=vehicle_brends&vehicle_id={$_GET['vehicle_id']}'>$vehicle_title</a> > 
		<a href='/admin/?view=original-catalogs&act=vehicle_brends&vehicle_id={$_GET['vehicle_id']}&brend_id={$_GET['brend_id']}'>$brend_title</a> > 
		$page_title
	";
	$res_filters = $db->query("
		SELECT 
			f.id,
			f.title
		FROM
			#vehicle_filters f
		WHERE
			`vehicle_id`={$_GET['vehicle_id']} AND `brend_id`={$_GET['brend_id']}
	", '');
	if ($res_filters->num_rows){
		while($row = $res_filters->fetch_assoc()) $filters[$row['id']] = $row['title'];
	}
	$res_modifications = $db->query("
		SELECT
			m.id,
			m.title,
			GROUP_CONCAT(CONCAT(f.id, ':', fv.title)) AS fvs
		FROM
			#modifications m
		LEFT JOIN #vehicle_model_fvs fvs ON fvs.modification_id=m.id
		LEFT JOIN #vehicle_filter_values fv ON fv.id=fvs.fv_id
		LEFT JOIN #vehicle_filters f ON f.id=fv.filter_id
		WHERE
			m.model_id={$_GET['model_id']}
		GROUP BY m.id
		ORDER BY m.title
	", '')?>
	<div id="total" style="margin-top: 10px;">Всего: <span><?=$res_modifications->num_rows?></span></div>
	<div id="action">
		<a class="modification_add" href="">Добавить</a>
	</div>
	<table model_id="<?=$_GET['model_id']?>" brend_id="<?=$_GET['brend_id']?>" vehicle_id="<?=$_GET['vehicle_id']?>" class="t_table modifications" cellspacing="1">
		<tr class="head">
			<td>Название</td>
			<?if (!empty($filters)){
				foreach($filters as $key => $value){?>
					<td filter_id="<?=$key?>"><?=$value?></td>
				<?}
			}?>
			<td></td>
		</tr>
		<?if ($res_modifications->num_rows){
			while($row = $res_modifications->fetch_assoc()){?>
				<tr class="modification clickable" modification_id="<?=$row['id']?>">
					<td>
						<a href="<?=$_SERVER['REQUEST_URI']?>&modification_id=<?=$row['id']?>"><?=$row['title']?></a>
					</td>
					<?if (!empty($filters)){
						$fvs = array();
						if ($row['fvs']){
							$a = explode(',', $row['fvs']);
							foreach($a as $k => $v){
								$fv = explode(':', $v);
								$fvs[$fv[0]] = $fv[1];
							}
						}
						foreach($filters as $key => $value){?>
							<td filter_id="<?=$key?>"><?=$fvs[$key]?></td>
						<?}
					}?>
					<td>
						<a class="modification_change not_clickable" href="">Изменить</a>
						<a href="#" class="modification_remove not_clickable">Удалить</a>
					</td>
				</tr>
			<?}
		}
		else{?>
			<tr class="removable">
				<td colspan="5">Модификаций не найдено</td>
			</tr>
		<?}?>
	</table>
<?}
function nodes(){
	global $status, $db, $page_title;
	$model_title = $db->getFieldOnID('models', $_GET['model_id'], 'title');
	$vehicle_title = $db->getFieldOnID('vehicles', $_GET['vehicle_id'], 'title');
	$brend_title = $db->getFieldOnID('brends', $_GET['brend_id'], 'title');
	$page_title = $db->getFieldOnID('modifications', $_GET['modification_id'], 'title');
	$status = "
		<a href='/admin'>Главная</a> > 
		<a href='/admin/?view=original-catalogs'>Оригинальные каталоги</a> > 
		<a href='/admin/?view=original-catalogs&act=vehicle_brends&vehicle_id={$_GET['vehicle_id']}'>$vehicle_title</a> > 
		<a href='/admin/?view=original-catalogs&act=vehicle_brends&vehicle_id={$_GET['vehicle_id']}&brend_id={$_GET['brend_id']}'>$brend_title</a> > 
		<a href='/admin/?view=original-catalogs&act=vehicle_brends&vehicle_id={$_GET['vehicle_id']}&brend_id={$_GET['brend_id']}&model_id={$_GET['model_id']}'>$model_title</a> > 
		$page_title
	";
	$res_nodes = $db->query("
		SELECT
			n.id,
			n.title,
			n.parent_id
		FROM
			#nodes n
		WHERE
			n.modification_id={$_GET['modification_id']}
	", '');
	if ($res_nodes->num_rows){
		while ($row = $res_nodes->fetch_assoc()) {
			if (!$row['subgroups_exist']) $nodes_all[$row['id']] = $row;
			$nodes[$row['parent_id']][] = $row;
		}
	}
	// for($i = 7; $i <= 24; $i++ ) $db->insert('node_modifications', ['modification_id' => 16, 'node_id' => $i]);
	?>
	<div id="total" style="margin-top: 10px;">Всего: <span><?=$res_nodes->num_rows?></span></div>
	<div id="action">
		<a id="node_create" href="">Создать</a>
		<a id="node_forward" href="">Перейти</a>
		<a id="node_change" href="">Редактировать</a>
	</div>
	<input type="hidden" id="parent_id" value="0">
	<input type="hidden" id="modification_id" value="<?=$_GET['modification_id']?>">
	<?if (empty($nodes)){?>
		<p>Узлов не найдено</p>
	<?}
	else{?>
		<div class="tree-structure">
			<?view_cat($nodes);?>
		</div>
	<?}
}
function view_cat($arr, $parent_id = 0) {
	$a = & $arr[$parent_id];
	if(empty($a)) return;?>
	<ul>
		<?for($i = 0; $i < count($a);$i++) {
			$nodes[$a[$i]['id']]['title'] = $a[$i]['title'];
			?>
			<li node_id="<?=$a[$i]['id']?>" id="node_<?=$a[$i]['id']?>">
				<a href="<?=$_SERVER['REQUEST_URI']?>&node_id=<?=$a[$i]['id']?>">
					<?=$a[$i]['title']?>
				</a>
				<?view_cat($arr,$a[$i]['id'])?>
			</li>
		<?}?>
	</ul>
<?}
function node(){
	global $status, $db, $page_title;
	$model_title = $db->getFieldOnID('models', $_GET['model_id'], 'title');
	$vehicle_title = $db->getFieldOnID('vehicles', $_GET['vehicle_id'], 'title');
	$brend_title = $db->getFieldOnID('brends', $_GET['brend_id'], 'title');
	$modification_title = $db->getFieldOnID('modifications', $_GET['modification_id'], 'title');
	$page_title = $db->getFieldOnID('nodes', $_GET['node_id'], 'title');
	$status = "
		<a href='/admin'>Главная</a> > 
		<a href='/admin/?view=original-catalogs'>Оригинальные каталоги</a> > 
		<a href='/admin/?view=original-catalogs&act=vehicle_brends&vehicle_id={$_GET['vehicle_id']}'>$vehicle_title</a> > 
		<a href='/admin/?view=original-catalogs&act=vehicle_brends&vehicle_id={$_GET['vehicle_id']}&brend_id={$_GET['brend_id']}'>$brend_title</a> > 
		<a href='/admin/?view=original-catalogs&act=vehicle_brends&vehicle_id={$_GET['vehicle_id']}&brend_id={$_GET['brend_id']}&model_id={$_GET['model_id']}'>$model_title</a> > 
		<a href='/admin/?view=original-catalogs&act=vehicle_brends&vehicle_id={$_GET['vehicle_id']}&brend_id={$_GET['brend_id']}&model_id={$_GET['model_id']}&modification_id={$_GET['modification_id']}'>$modification_title</a> > 
		$page_title
	";
	$res_node_items = $db->query("
		SELECT
			ni.pos,
			ni.item_id,
			b.title AS brend,
			b.id AS brend_id,
			i.title_full,
			i.article,
			ni.quan,
			ni.comment
		FROM
			#node_items ni 
		LEFT JOIN #items i ON i.id=ni.item_id
		LEFT JOIN #brends b ON b.id=i.brend_id
		WHERE
			ni.node_id={$_GET['node_id']}
	", '');
	?>
	<div id="total" style="margin-top: 10px;">Всего: <span><?=$res_node_items->num_rows?></span></div>
	<?$img = "$brend_title/{$_GET['node_id']}.jpg";
	$img_path = "/images/nodes/big/$img";
	$src = array_shift(glob("../images/nodes/big/$brend_title/{$_GET['node_id']}.*"));
	if ($src){?>
		<img class="zoom" src="/<?=$src?>" alt="<?=$page_title?>" data-zoom-image="/<?=$src?>">
		<a class="delete_item" href="?view=original-catalogs&act=image_delete&src=<?=$src?>">Удалить изображение</a>
	<?}
	else{?>
		<form method="post" action="?view=original-catalogs&brend_id=<?=$_GET['brend_id']?>&act=node_image&node_id=<?=$_GET['node_id']?>" enctype="multipart/form-data">
			<input type="file" name="image">
			<input type="submit" value="Сохранить">
		</form>
	<?}?>
	<a id="item_add" href="">Добавить деталь</a>
	<!-- <a id="item_create" href="">Создать деталь</a> -->
	<table style="width: auto" node_id="<?=$_GET['node_id']?>" class="t_table nodes" cellspacing="1">
		<tr class="head">
			<td>Позиция</td>
			<td>Название</td>
			<td>Артикул</td>
			<td>Кол-во</td>
			<td>Комментарий</td>
		</tr>
		<?if($res_node_items->num_rows){
			while($row = $res_node_items->fetch_assoc()){?>
				<tr class="node_item" item_id="<?=$row['item_id']?>">
					<td><?=$row['pos']?></td>
					<td><?=$row['title_full']?></td>
					<td><?=$row['article']?></td>
					<td><?=$row['quan']?></td>
					<td><?=$row['comment']?></td>
				</tr>
			<?}
		}
		else{?>
			<tr class="removable">
				<td colspan="5">Деталей не найдено</td>
			</tr>
		<?}?>
	</table>
<?}
function node_set_image($file, $id){
	global $db;
	$array = [];
	$name = $file['name'];
	if (!$name) {
		$array['error'] = '';
		return $array;
	}
	$brend = $db->getFieldOnID('brends', $_GET['brend_id'], 'title');
	$dir_big = "../images/nodes/big/$brend";
	$dir_small = "../images/nodes/small/$brend";
	require_once('../vendor/class.upload.php');
	if (!file_exists($dir_big)) mkdir($dir_big);
	if (!file_exists($dir_small)) mkdir($dir_small);
	$handle = new upload($file);
	$handle_big = new upload($file);
	if (!$handle->file_is_image){
		$array['error'] = 'Запрещенный вид файла!';
		return $array;
	}
	$need_ratio = [
		'x' => 304,
		'y' => 418
	];
	if ($handle->uploaded){
		$handle->file_new_name_body = $id;
		$handle_big->file_new_name_body = $handle->file_new_name_body;
		$handle->file_new_name_ext = 'jpg';
		$handle_big->file_new_name_ext = 'jpg';
		$handle->image_resize = true;
		$src_x = $handle->image_src_x;
		$src_y = $handle->image_src_y;
		if (($need_ratio['x'] / $need_ratio['y']) >= ($src_x / $src_y)){
			$handle->image_x = $need_ratio['x'];
			$handle->image_y = floor($need_ratio['x'] * $src_y / $src_x);
			$t = floor($handle->image_y / 2 - $need_ratio['y'] / 2);
			$handle->image_crop = "$t 0";
		}
		else{
			$handle->image_y = $need_ratio['y'];
			$handle->image_x = floor($need_ratio['y'] * $src_x / $src_y);
			$t = floor($handle->image_x / 2 - $need_ratio['x'] / 2);
			$handle->image_crop = "0 $t";
		}
		$handle->process($dir_small);
		$handle_big->process($dir_big);
		if ($handle->processed) $array['name'] = $handle->file_dst_name;
		$handle->clean();
		$handle_big->clean();
		$array['error'] = '';
		return $array;
	}
	else{
		$array['error'] = 'Произошла ошибка';
		return $array;
	}
}
function model_set_image($file, $id){
	global $db;
	$array = [];
	$name = $file['name'];
	if (!$name) {
		$array['error'] = '';
		return $array;
	}
	$dir = "../images/models/";
	require_once('../vendor/class.upload.php');
	if (!file_exists($dir)) mkdir($dir);
	$handle = new upload($file);
	if (!$handle->file_is_image){
		$array['error'] = 'Запрещенный вид файла!';
		return $array;
	}
	$need_ratio = [
		'x' => 260,
		'y' => 160
	];
	if ($handle->uploaded){
		$handle->file_new_name_body = $id;
		$handle->file_new_name_ext = 'jpg';
		$handle->image_resize = true;
		$src_x = $handle->image_src_x;
		$src_y = $handle->image_src_y;
		if (($need_ratio['x'] / $need_ratio['y']) >= ($src_x / $src_y)){
			$handle->image_x = $need_ratio['x'];
			$handle->image_y = floor($need_ratio['x'] * $src_y / $src_x);
			$t = floor($handle->image_y / 2 - $need_ratio['y'] / 2);
			$handle->image_crop = "$t 0";
		}
		else{
			$handle->image_y = $need_ratio['y'];
			$handle->image_x = floor($need_ratio['y'] * $src_x / $src_y);
			$t = floor($handle->image_x / 2 - $need_ratio['x'] / 2);
			$handle->image_crop = "0 $t";
		}
		$handle->process($dir);
		if ($handle->processed){
			$handle->clean();
			return true;
		}
		else return false;
	}
	else return false;
}
function vehicle_set_image($file, $id){
	global $db;
	$array = [];
	$name = $file['name'];
	if (!$name) {
		$array['error'] = '';
		return $array;
	}
	$dir = "../images/vehicles/";
	require_once('../vendor/class.upload.php');
	if (!file_exists($dir)) mkdir($dir);
	$handle = new upload($file);
	if (!$handle->file_is_image){
		$array['error'] = 'Запрещенный вид файла!';
		return $array;
	}
	$need_ratio = [
		'x' => 205,
		'y' => 100
	];
	if ($handle->uploaded){
		$handle->file_new_name_body = $id;
		$handle->file_new_name_ext = 'jpg';
		$handle->image_resize = true;
		$src_x = $handle->image_src_x;
		$src_y = $handle->image_src_y;
		if (($need_ratio['x'] / $need_ratio['y']) >= ($src_x / $src_y)){
			$handle->image_x = $need_ratio['x'];
			$handle->image_y = floor($need_ratio['x'] * $src_y / $src_x);
			$t = floor($handle->image_y / 2 - $need_ratio['y'] / 2);
			$handle->image_crop = "$t 0";
		}
		else{
			$handle->image_y = $need_ratio['y'];
			$handle->image_x = floor($need_ratio['y'] * $src_x / $src_y);
			$t = floor($handle->image_x / 2 - $need_ratio['x'] / 2);
			$handle->image_crop = "0 $t";
		}
		$handle->process($dir);
		if ($handle->processed){
			$handle->clean();
			return true;
		}
		else return false;
	}
	else return false;
}
?>