<? use core\Breadcrumb;

if (!$_SESSION['user']) header('Location: /');
$title = "Избранное";
$user_id = $_SESSION['user'];
$favorites = $db->select_unique("
	SELECT
		f.item_id,
		f.remark,
		b.title as brend,
		i.brend_id,
		IF (i.title_full, i.title_full, i.title) as title_full,
		IF (
				i.article_cat != '', 
				i.article_cat, 
				IF (
					i.article !='',
					i.article,
					ib.barcode
				)
			) as article,
			b.title as brend,
			IF (
				i.applicability !='' || i.characteristics !=''  || i.full_desc !='',
				1,
				0
			) AS is_desc
	FROM
		#favorites f
	LEFT JOIN #items i ON i.id=f.item_id
	LEFT JOIN #brends b ON i.brend_id=b.id
	LEFT JOIN #item_barcodes ib ON ib.item_id = i.id
	WHERE 
		f.user_id={$_SESSION['user']}
", '');
Breadcrumb::add('/favorites', 'Избранное');
Breadcrumb::out();
?>
<div class="favorites">
	<h1>Избранное</h1>
	<table class="favorites-table">
		<tr>
			<th>Бренд</th>
			<th>Наименование</th>
			<th>Пометки</th>
			<th><img id="favorite_clear" src="img/icons/icon_trash.png" alt="Удалить"></th>
		</tr>
		<?if (!empty($favorites)){
			foreach ($favorites as $value) {?>
				<tr class="item_id" item_id="<?=$value['item_id']?>">
					<td>
						<b class="brend_info" brend_id="<?=$value['brend_id']?>"><?=$value['brend']?></b> 
						<a href="<?=core\Item::getHrefArticle($value['article'])?>" class="articul"><?=$value['article']?></a>
					</td>
					<td class="name-col">
						<?if ($value['is_desc']){?>
							<a href="#"><i class="fa fa-camera product-popup-link" aria-hidden="true"></i></a>
						<?}?>
						<?=$value['title_full']?>
					</td>
					<td><textarea class="remarks" placeholder="Начните свою запись"><?=$value['remark']?></textarea></td>
					<td><span type_delete="favorite" class="delete-btn"><i class="fa fa-times" aria-hidden="true"></i></span></td>
				</tr>
			<?}
		}
		else{?>
		<tr>
			<td colspan="4">Избранного не найдено</td>
		</tr>
		<?}?>
		
	</table>
	<div class="mobile-layout">
		<?if (!empty($favorites)){
			foreach ($favorites as $value) {?>
				<div class="good item_id" item_id="<?=$value['item_id']?>">
					<div class="goods-header">
						<p>
							<b class="brend_info" brend_id="<?=$value['brend_id']?>"><?=$value['brend']?></b>  
							<a href="<?=core\Item::getHrefArticle($value['article'])?>" class="articul"><?=$value['article']?></a>
						</p>
						<p><a href="#"></a><?=$value['title_full']?></p>
						<i class="fa fa-camera product-popup-link" aria-hidden="true"></i>
					</div>
					<div class="goods-footer">
						<textarea class="remarks" placeholder="Начните свою запись"><?=$value['remark']?></textarea>
						<span class="delete-btn" type_delete="favorite"><i class="fa fa-times" aria-hidden="true"></i></span>
					</div>
				</div>
			<?}
		}
		else{?>
		<div class="good">Избранного не найдено</div>
		<?}?>
	</div>
</div>
<div id="mgn_popup" class="product-popup mfp-hide"></div>
<div class="popup-gallery"></div>
