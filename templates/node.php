<?
use core\OriginalCatalog\PartsCatalogs;

//partsCatalogs
if (preg_match('/pc_/', $_GET['model_id'])){
	$brend = $_GET['brend'];
	$carId = $_GET['modification_id'];
	$groupId = $_GET['node_id'];
	$nodePartsCatalogs = PartsCatalogs::getNode($brend, $carId, $groupId);
	$modelInfoPartsCatalogs = PartsCatalogs::getModelInfoByModelID($brend, $_GET['href']);
}
else{
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
}
	
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
		<?if (!empty($modelInfoPartsCatalogs)){
			if ($modelInfoPartsCatalogs->img){?>
				<div class="img">
					<img src="<?=$modelInfoPartsCatalogs->img?>" alt="<?=$modelInfoPartsCatalogs->name?>">
				</div>
			<?}?>
			<div class="description">
				<dl>
					<dt>Наименование: </dt> <dd><?=$modelInfoPartsCatalogs->name?></dd>
				</dl>
			</div>
		<?}?>
	</div>
	<div class="content">
		<div class="breadcrumbs unit-breadcrumbs">
			<?if (isset($modelInfoPartsCatalogs)){?>
					<a href="/original-catalogs/<?=$_GET['vehicle']?>/<?=$_GET['brend']?>">
						<?=$_GET['brend']?>
					</a>
					<a href="/original-catalogs/<?=$_GET['vehicle']?>/<?=$_GET['brend']?>/<?=$_GET['model_id']?>/<?=$_GET['href']?>/<?=$_GET['vin']?>">
						<?=$modelInfoPartsCatalogs->name?>
					</a>
					<span>Узлы</span>
				<?}
				else{?>
					<a href="/original-catalogs/<?=$_GET['vehicle']?>/<?=$_GET['brend']?>">
						<?=$modification['brend']?>
					</a>
					<a href="/original-catalogs/<?=$_GET['vehicle']?>/<?=$_GET['brend']?>/<?=$_GET['model_id']?>/<?=$_GET['href']?>/<?=$_GET['vin']?>">
						<?=$modification['model_title']?>
					</a>
					<span><?=$title?></span>
				<?}?>
		</div>
		<?if (isset($nodePartsCatalogs)){?>
			<div class="unit-pic">
				<a href="<?=$nodePartsCatalogs->img?>">
					<img src="<?=$nodePartsCatalogs->img?>" data-zoom-image="<?=$nodePartsCatalogs->img?>" alt="<?=$nodePartsCatalogs->imgDescription?>">
				</a>
			</div>
		<?}
		else{
			$files = array_shift(glob(core\Config::$imgPath . "/nodes/big/{$model['brend']}/{$_GET['node_id']}.*"));?>
			<?if ($files){
				$pathinfo = pathinfo($files);
				$src = "/nodes/big/{$model['brend']}/{$pathinfo['basename']}";?>
				<div class="unit-pic">
					<a href="<?=core\Config::$imgUrl . $src?>">
						<img src="<?=core\Config::$imgUrl . $src?>" data-zoom-image="<?=core\Config::$imgUrl . $src?>?>" alt="<?=$title?>">
					</a>
				</div>
			<?}
		}?>
		<table <?=!$src && !$nodePartsCatalogs->img ? 'style="width: 100%"' : ''?>>
			<tr>
				<th>№</th>
				<th>Наименование</th>
				<th>Примечание</th>
				<th>Кол-во</th>
			</tr>
			<?if (isset($nodePartsCatalogs->partGroups)){
				foreach($nodePartsCatalogs->partGroups[0]->parts as $group){?>
					<tr>
						<td><?=$group->positionNumber?></td>
						<td>
							<a target="_blank" href="/search/article/<?=$group->number?>">
								<?=$group->name ? $group->name : $group->number?>
							</a>
						</td>
						<td><?=$group->description?></td>
						<td>1</td>
					</tr>
				<?}
				?>
			<?}
			elseif ($res_items->num_rows) while($row = $res_items->fetch_assoc()){?>
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