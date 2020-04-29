<?
$res_model = $db->query("
	SELECT
		m.id,
		m.title,
		b.title AS brend
	FROM
		#models m
	LEFT JOIN #brends b ON b.id=m.brend_id
	WHERE 
		m.id={$_GET['model_id']}
", '');
if ($res_model->num_rows){
	while($row = $res_model->fetch_assoc()){
		$r = & $model;
		$r['id'] = $row['id'];
		$r['title'] = $row['title'];
		$r['brend'] = $row['brend'];
		if ($row['filter']) $r['filter_values'][$row['filter']] = $row['filter_value'];
	}
}
$title = $db->getFieldOnID('nodes', $_GET['node_id'], 'title');
$res_items = $db->query("
	SELECT
		ni.pos,
		CAST(ni.pos AS UNSIGNED) as pos2,
		ni.item_id,
		IF (i.title_full<>'', i.title_full, i.title) AS title,
		i.article,
		b.title AS brend,
		ni.comment,
		IF (ni.comment<>'', ni.comment, i.comment) AS comment,
		ni.quan
	FROM
		#node_items ni
	LEFT JOIN #items i ON i.id=ni.item_id
	LEFT JOIN #brends b ON b.id=i.brend_id
	WHERE
		ni.node_id={$_GET['node_id']}
	ORDER BY pos2, pos
", '');
// debug($_GET);
// debug($model);
?>
<div class="catalogue-original catalogue-original-unit">
	<div class="item-info-block">
		<?if (file_exists(core\Config::$imgPath . "/models/{$_GET['model_id']}.jpg")){?>
			<div class="img">
				<img src="<?=core\Config::$imgUrl?>/models/<?=$_GET['model_id']?>.jpg" alt="<?=$model['title']?>">
			</div>
		<?}?>
		<?if (!empty($model['filter_values']) || ($_GET['vin'] != 'vin' && $_GET['vin'])){?>
			<div class="description">
				<dl>
					<?if (!empty($model['filter_values'])) foreach($model['filter_values'] as $key => $value){?>
						<dt><?=$key?>: </dt> <dd><?=$value?></dd>
					<?}
					if ($_GET['vin'] != 'vin' && $_GET['vin']){?>
						<dt>VIN: </dt> <dd><?=$_GET['vin']?></dd>
					<?}?>
				</dl>
			</div>
		<?}?>
	</div>
	<div class="content">
		<div class="breadcrumbs unit-breadcrumbs">
			<a href="/original-catalogs/<?=$_GET['vehicle']?>/<?=$_GET['brend']?>">
				<?=$model['brend']?>
			</a>
			<a href="/original-catalogs/<?=$_GET['vehicle']?>/<?=$_GET['brend']?>/<?=$_GET['model_id']?>/<?=$_GET['href']?>/<?=$_GET['vin']?>">
				<?=$model['title']?>
			</a>
			<a href="/original-catalogs/<?=$_GET['vehicle']?>/<?=$_GET['brend']?>/<?=$_GET['model_id']?>/<?=$_GET['href']?>/<?=$_GET['vin']?>/<?=$_GET['modification_id']?>">
				<?=$db->getFieldOnID('modifications', $_GET['modification_id'], 'title')?>
			</a>
			<span><?=$title?></span>
		</div>
		<?$files = array_shift(glob(core\Config::$imgPath . "/nodes/big/{$model['brend']}/{$_GET['node_id']}.*"));?>
		<?if ($files){
			$pathinfo = pathinfo($files);
			$src = "/nodes/big/{$model['brend']}/{$pathinfo['basename']}";?>
			<div class="unit-pic">
				<a href="<?=core\Config::$imgUrl . $src?>">
					<img src="<?=core\Config::$imgUrl . $src?>" data-zoom-image="<?=core\Config::$imgUrl . $src?>?>" alt="<?=$title?>">
				</a>
			</div>
		<?}?>
		<table <?=!$src ? 'style="width: 100%"' : ''?>>
			<tr>
				<th>№</th>
				<th>Наименование</th>
				<th>Примечание</th>
				<th>Кол-во</th>
			</tr>
			<?if ($res_items->num_rows) while($row = $res_items->fetch_assoc()){?>
				<tr>
					<td><?=$row['pos']?></td>
					<td>
						<a target="_blank" href="/search/article/<?=$row['article']?>/<?=$row['brend']?>">
							<?=$row['title']?>
						</a>
					</td>
					<td><?=$row['comment']?></td>
					<td><?=$row['quan']?></td>
				</tr>
			<?}
			else{?>
				<tr><td style="text-align: left" colspan="4">Деталей не найдено</td></tr>
			<?}?>
		</table>
	</div>
	<div class="hidden">
	</div>
</div>