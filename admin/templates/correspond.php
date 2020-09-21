<?
if ($_POST['image_submit']){
	$bool = true;
	$name = $_FILES['image']['name'];
	$type = $_FILES['image']['type'];
	$tmp_name = $_FILES['image']['tmp_name'];
	if ($type != 'image/jpeg' && $type != 'image/png' && $type != 'image/gif'){
		message('Недопустимый формат файла!', false);
		$bool = false;
	}
	if ($bool){
		$target_dir = core\Config::$imgPath . "/temp/";
		$image_name = microtime().".jpg";
		if (move_uploaded_file($tmp_name, $target_dir.$image_name)){
			message('Фото успешно загружено!');?>
			<li style="padding: 0" foto_name="<?=$image_name?>"><img src="/<?=$target_dir.$image_name?>" alt=""></li>
		<?}
	}
	exit();
}
if ($_GET['act'] == 'to_archive'){
	$db->update('corresponds', ['is_archive' => 1], "`id`={$_GET['id']}");
	message('Переписка успешно отправлена в архив!');
	header("Location: ?view=messages");
}
if ($_GET['act'] == 'from_archive'){
	$db->update('corresponds', ['is_archive' => NULL], "`id`={$_GET['id']}");
	message('Переписка успешно удалена из архива!');
	header("Location: ?view=messages");
}
$db->query("
	UPDATE	
		#messages m
	SET	
		m.is_read=1
	WHERE 
		m.correspond_id={$_GET['id']} AND
		m.sender=1
", '');
if ($_GET['order_id']){
	$page_title = 'Переписка в товаре заказа';
	$user_id = $_GET['user_id'];
	$order_id = $_GET['order_id'];
	$store_id = $_GET['store_id'];
	$item_id = $_GET['item_id'];
} 
if ($_GET['id']){
	$messages = $db->select_unique("
		SELECT
			m.id,
			c.id as correspond_id,
			c.user_id,
			c.order_id,
			c.store_id,
			c.item_id,
			m.text,
			c.theme_id,
			IF (c.theme_id, mth.title, 'Переписка в товаре заказа') AS theme,
			IF(
				m.sender IS NULL OR m.sender=0,
				'Администрация',
				CONCAT_WS(' ', u.name_1, u.name_2, u.name_3)
			) as sender,
			GROUP_CONCAT(
				mf.title
				ORDER BY mf.title
				SEPARATOR ','
				) as fotos,
			DATE_FORMAT(m.created, '%d.%m.%Y %H:%i') as date,
			c.is_archive
		FROM
			#messages m
		LEFT JOIN #corresponds c ON c.id=m.correspond_id
		LEFT JOIN #messages_themes mth ON c.theme_id=mth.id
		LEFT JOIN #users u ON u.id=c.user_id
		LEFT JOIN #msg_fotos mf ON mf.message_id=m.id
		WHERE 
			m.correspond_id={$_GET['id']}
		GROUP BY m.id
		ORDER BY m.created DESC
	", '');
	$page_title = $messages[0]['theme'];
	$theme_id = $messages[0]['theme_id'];
	$user_id = $messages[0]['user_id'];
	$order_id = $messages[0]['order_id'];
	$store_id = $messages[0]['store_id'];
	$item_id = $messages[0]['item_id'];
} 
// debug($messages);
if (!$user_id) $user_id = $_GET['user_id'];
$status = "<a href='/admin'>Главная</a> > <a href='/admin/?view=messages'>Сообщения</a> > $page_title";
$is_archive = $messages[0]['is_archive'];
if ($order_id){
	$res_order_values = $db->query("
		SELECT
			ps.cipher,
			b.title AS brend,
			IF (i.title_full, i.title_full, i.title) AS title_full,
			IF (
				i.article_cat != '', 
				i.article_cat, 
				IF (
					i.article !='',
					i.article,
					ib.barcode
				)
			) AS article,
			ov.store_id,
			ov.item_id,
			ov.price,
			ov.quan,
			(ov.price * ov.quan) AS sum,
			ov.comment,
			os.title AS status,
			os.id AS status_id,
			o.user_id,
			u.bill,
			u.reserved_funds
		FROM
			#orders_values ov
		LEFT JOIN #provider_stores ps ON ps.id=ov.store_id
		LEFT JOIN #items i ON i.id=ov.item_id
		LEFT JOIN #brends b ON b.id=i.brend_id
		LEFT JOIN #item_barcodes ib ON ib.item_id = i.item_id
		LEFT JOIN #orders_statuses os ON os.id=ov.status_id
		LEFT JOIN #orders o ON ov.order_id=o.id
		LEFT JOIN #users u ON u.id=o.user_id
		WHERE
			ov.order_id=$order_id AND
			ov.store_id=$store_id AND
			ov.item_id=$item_id
	", '');?>
	<a href="?view=orders&id=<?=$order_id?>&act=change">Карточка заказа</a>
	<table style="margin-top: 10px;" class="t_table" cellspacing="1">
		<tr class="head">
			<td>Поставщик</td>
			<td>Бренд</td>
			<td>Артикул</td>
			<td>Наименование</td>
			<td>Цена</td>
			<td>Кол-во</td>
			<td>Сумма</td>
			<td>Комментарий</td>
			<td>Статус</td>
		</tr>
		<?if (!$res_order_values->num_rows){?>
			<td colspan="12">Произошла ошибка</td>
		<?}
		else{
			while ($ov = $res_order_values->fetch_assoc()){
				$selector = "store_id='{$ov['store_id']}' item_id='{$ov['item_id']}'";?>
				<tr <?=$selector?> class="status_<?=$ov['status_id']?>">
					<td label="Поставщик"><?=$ov['cipher']?></td>
					<td label="Бренд"><?=$ov['brend']?></td>
					<td label="Артикул"><?=$ov['article']?></td>
					<td label="Наименование"><?=$ov['title_full']?></td>
					<td label="Цена" class="price_format"><?=$ov['price']?> руб.</td>
					<td label="Кол-во"><?=$ov['quan']?></td>
					<td label="Сумма" class="price_format"><?=$ov['sum']?></td>
					<td label="Комментарий"><?=$ov['comment']?></td>
					<td label="Статус" class="change_status">
						<b><?=$ov['status']?></b>
					</td>
				</tr>
			<?}
		}?>
	</table>
<?}
if (!empty($messages)){
	if (!$is_archive){?>
		<a style="margin-top: 10px;display: block" href="/admin/?view=correspond&act=to_archive&id=<?=$_GET['id']?>">Отправить в архив</a>
	<?}
	else{?>
		<a  style="margin-top: 10px;display: block" href="/admin/?view=correspond&act=from_archive&id=<?=$_GET['id']?>">Удалить из архива</a>
	<?}?>
	<div class="dialog" style="margin-top: 10px">
		<div class="dialog-box">
		<?if (!empty($messages)){
			foreach ($messages as $message) {?>
			<div class="message-box">
				<span class="sender"><?=$message['sender']?></span>
				<span class="send-time"><?=$message['date']?></span>
				<p class="message"><?=$message['text']?></p>
				<?$msg_fotos = $db->select('msg_fotos', 'title', "`message_id`=".$message['id']);
				if (count($msg_fotos)){?>
					<div class="attachment">
					<?foreach ($msg_fotos as $msg_foto){?>
						<div class="img-wrap"><a href="<?=core\Config::$imgUrl?>/temp/<?=$msg_foto['title']?>"><img style="width: 200px" src="/images/temp/<?=$msg_foto['title']?>" alt=""></a></div>
					<?}?>
					</div>
				<?}?>
			</div>
		<?}
		}?>
		</div>
	</div>
<?}?>
<form action="/core/send_message.php" id="send-message" method="post">
	<input type="hidden" name="message_send" value="1">
	<input type="hidden" name="json_fotos">
	<input type="hidden" name="sender" value="admin">
	<input type="hidden" name="user_id" value="<?=$user_id?>">
	<input type="hidden" name="order_id" value="<?=$order_id?>">
	<input type="hidden" name="store_id" value="<?=$store_id?>">
	<input type="hidden" name="item_id" value="<?=$item_id?>">
	<input type="hidden" name="correspond_id" value="<?=$_GET['id']?>">
	<?if (empty($messages) && !$_GET['order_id']){
		$messages_themes = $db->select('messages_themes', '*', '', '', '', '', true)?>
		<div class="input-wrap">
			<label for="department">Тема сообщения: </label>
			<select data-placeholder="Выберите тему" name="theme_id" id="department">
				<option value=""></option>
				<?foreach ($messages_themes as $id => $theme_message) {?>
					<option value="<?=$id?>"><?=$theme_message['title']?></option>
				<?}?>
			</select>
			<a href="#" id="add_theme">Создать тему</a>
		</div>
	<?}
	else{?>
		<input type="hidden" id="department" name="theme_id" value="<?=$messages[0]['theme_id']?>">
	<?}?>
	<p style="margin-top: 20px">Текст сообщения: </p>
	<textarea style="padding: 20px;box-sizing: border-box" name="text" id="send-order-text" cols="30" rows="10" required></textarea>
	<div style="position: relative;">
		<ul style="padding-left: 0" id="fotos"></ul>
		<div id="temp_foto"></div>
		<input type="button" accept="image/*" value="Загрузить фото" id="click_image">
		<span class="info_btn"></span>
		<div id="upload-info" class="info" style="display: none;">
			<p>Размер фотографии не должен превышать 2 мб. Допускаются файлы с расширением *.jpg, *.jpeg, *.gif, *.png</p>
		</div>
	</div>
	<button style="margin-top: 10px">Отправить</button>
</form>
<form style="display: none" id="upload_image" action="/admin/?view=correspond&user_id=<?=$_GET['user_id']?>" enctype="multipart/form-data" method="post">
	<input id="image" name="image" type="file">
	<input type="hidden" name="image_submit" value="1" style="display: none">
</form>