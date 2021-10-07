<?
$query = "
	SELECT
		mf.id,
		mf.title,
		mf.model_id,
		md.title AS model_title,
		md.vin,
		md.vehicle_id,
		b.title AS brend,
		f.title AS filter,
		fv.title AS filter_value
";
if ($user['id']) $query .= ",IF(g.modification_id IS NULL, '', 'is_garaged') as garage";
$query .= "
	FROM
		#modifications mf
	LEFT JOIN
		#models md ON md.id=mf.model_id
	LEFT JOIN 
		#brends b ON b.id=md.brend_id
	LEFT JOIN
		#vehicle_model_fvs fvs ON fvs.modification_id=mf.id
	LEFT JOIN
		#vehicle_filter_values fv ON fv.id=fvs.fv_id
	LEFT JOIN
		#vehicle_filters f ON f.id=fv.filter_id
";
if ($user['id']) $query .= "LEFT JOIN #garage g ON g.user_id={$user['id']} AND g.modification_id=mf.id AND g.is_active=1";
$query .= " WHERE mf.id={$_GET['modification_id']}";
$res_modification = $db->query($query, '');
if ($res_modification->num_rows){
	while($row = $res_modification->fetch_assoc()){
		$modification['id'] = $row['id'];
		$modification['title'] = $row['title'];
		$modification['vin'] = $row['vin'];
		$modification['brend'] = $row['brend'];
		$modification['garage'] = $row['garage'];
		$modification['vehicle_id'] = $row['vehicle_id'];
		$modification['model_title'] = $row['model_title'];
		if ($row['filter']) $modification['filter_values'][$row['filter']] = $row['filter_value'];
	}
}
// debug($modification);
$res_nodes = $db->query("
	SELECT
		n.id AS id,
		n.title,
		n.parent_id,
		n.subgroups_exist
	FROM
		#nodes n
	WHERE
		n.modification_id={$_GET['modification_id']}
	ORDER BY n.title
", '');
$title = $db->getFieldOnID('modifications', $_GET['modification_id'], 'title');
if ($res_nodes->num_rows){
	while ($row = $res_nodes->fetch_assoc()) {
		if (!$row['subgroups_exist']) $nodes_all[$row['id']] = $row;
		// $nodes_all[$row['id']] = $row;
		$nodes[$row['parent_id']][] = $row;
	}
}
?>
<div class="catalogue-original">
    <input type="hidden" name="full_name" value="<?=$user['full_name']?>">
	<div class="clearfix"></div>
	<div class="item-info-block">
		<?if (file_exists(core\Config::$imgPath . "/models/{$_GET['model_id']}.jpg")){?>
			<div class="img">
				<img src="<?=core\Config::$imgUrl?>/models/<?=$_GET['model_id']?>.jpg" alt="<?=$modification['title']?>">
			</div>
			<?}
		if (file_exists("{$_SERVER['DOCUMENT_ROOT']}/images/vehicles/{$modification['vehicle_id']}.jpg")){?>
			<div class="img">
				<img src="<?=core\Config::$imgUrl?>/vehicles/<?=$modification['vehicle_id']?>.jpg" alt="<?=$modification['title']?>">
			</div>
		<?}
		if (!empty($modification['filter_values']) || ($_GET['vin'] != 'vin' && $_GET['vin'])){?>
			<div class="description">
				<dl>
					<?if (!empty($modification['filter_values'])) foreach($modification['filter_values'] as $key => $value){?>
						<dt><?=$key?>: </dt> <dd><?=$value?></dd>
					<?}
					if ($_GET['vin'] != 'vin' && $_GET['vin']){?>
						<dt>VIN: </dt> <dd><?=$_GET['vin']?></dd>
					<?}?>
				</dl>
			</div>
		<?}?>
	</div>
	<div class="items">
		<div class="option-panel">
			<?if ($user['id']){?>
				<div id="to_garage">
					<?$button_title = $modification['garage'] == 'is_garaged' ? 'Убрать из гаража' : 'Добавить в гараж'?>
					<button user_id="<?=$user['id']?>" modification_id="<?=$modification['id']?>" class="<?=$modification['garage']?>" title="<?=$button_title?>"></button>
				</div>
			<?}?>
			<div class="breadcrumbs">
				<a href="/original-catalogs/<?=$_GET['vehicle']?>/<?=$_GET['brend']?>">
					<?=$modification['brend']?>
				</a>
					<a href="/original-catalogs/<?=$_GET['vehicle']?>/<?=$_GET['brend']?>/<?=$_GET['model_id']?>/<?=$_GET['href']?>/<?=$_GET['vin']?>">
					<?=$modification['model_title']?>
				</a>
				<span><?=$title?></span>
			</div>
			<div class="view-switchs">
				<div class="view-switch mosaic-view-switch active" id="mosaic-view-switch">
					<img src="/img/icons/option-panel_mosaic_view.png" alt="Мозайкой">
				</div>
				<div class="view-switch list-view-switch " id="list-view-switch">
					<img src="/img/icons/option-panel_list-view.png" alt="Списком">
				</div>
			</div>
		</div>
		<div class="content" brend="<?=$model['brend']?>">
			<div class="mosaic-view clearfix">
				<?//debug($nodes_all);
				if (!empty($nodes_all)) foreach($nodes_all as $k => $v) get_node_item($v['id'], $v['title'])?>
			</div>
			<div class="list-view">
				<?if (!empty($nodes)){?>
					<div class="tree-structure">
						<?view_cat($nodes);?>
					</div>
				<?}?>
				<div class="items clearfix"></div>
			</div>
		</div>
	</div>
</div>
<div class="hidden"></div>
<?function get_node_item($k, $v){
	global $model;?>
	<div class="item">
		<a href="<?=$_SERVER['REQUEST_URI']?>/<?=$k?>"></a>
		<p><?=$v?></p>
		<?$imgPath = array_shift(glob(core\Config::$imgPath . "/nodes/small/{$_GET['brend']}/$k.*"));
		$pathinfo = pathinfo($imgPath);
		$src = core\Config::$imgUrl . "/nodes/small/{$_GET['brend']}/{$pathinfo['basename']}";
		if ($src){?>
			<div class="img">
				<img src="<?=$src?>" alt="<?=$v?>">
			</div>
		<?}?>
	</div>
<?}function view_cat($arr, $parent_id = 0) {
	$a = & $arr[$parent_id];
	if(empty($a)) return;?>
	<ul>
		<?for($i = 0; $i < count($a);$i++) {
			$nodes[$a[$i]['id']]['title'] = $a[$i]['title'];
			?>
			<li node_id="<?=$a[$i]['id']?>" id="node_<?=$a[$i]['id']?>">
				<a href="<?=$_SERVER['REQUEST_URI']?>/<?=$a[$i]['id']?>">
					<?=$a[$i]['title']?>
				</a>
				<?view_cat($arr,$a[$i]['id'])?>
			</li>
		<?}?>
	</ul>
<?}?>