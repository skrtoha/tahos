<?if ($_POST['image_submit']){
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
			<li foto_name="<?=$image_name?>"><img src="/<?=$target_dir.$image_name?>" alt=""></li>
		<?}
	}
	exit();
}
// debug($_GET);
$db->query("
	UPDATE	
		#messages m
	SET	
		m.is_read=1
	WHERE 
		m.correspond_id={$_GET['id']} AND
		m.sender=0
", '');
if ($_GET['id']){
	$messages = $db->select_unique("
		SELECT
			m.id,
			c.id as correspond_id,
			c.user_id,
			c.order_id,
			c.theme_id,
			c.store_id,
			c.item_id,
			m.text,
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
			DATE_FORMAT(m.created, '%d.%m.%Y %H:%i') AS date
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
	// debug($messages);
	$order_id = $messages[0]['order_id'];
	$store_id = $messages[0]['store_id'];
	$item_id = $messages[0]['item_id'];
}
if($_GET['order_id']){
	$title = 'Переписка в товаре заказа';
	$order_id = $_GET['order_id'];
	$store_id = $_GET['store_id'];
	$item_id = $_GET['item_id'];
}
$title = $messages[0]['theme'];
?>
<div class="orders-message">
	<h1><?=$title?></h1>
	<?if ($order_id){?>
		<table class="orders-table">
		<tr>
			<th>Наименование</th>
			<th>Поставщик</th>
			<th>Дата</th>
			<th>Срок</th>
			<th>Кол-во</th>
			<th>Статус</th>
			<th>Цена</th>
		</tr>
		<?$orders = $db->select_unique("
			SELECT 
				IF (i.title_full, i.title_full, i.title) AS title,
				IF (
					i.article_cat != '', 
					i.article_cat, 
					IF (
						i.article !='',
						i.article,
						ib.barcode
					)
				) AS article,
				i.brend_id,
				b.title AS brend,
				ps.cipher,
				ov.price * ov.quan AS price,
				ov.quan,
				os.title AS status,
				os.class,
				os.class AS status_class,
				@delivery:=IF(si.in_stock = 0, ps.under_order, ps.delivery) AS delivery,
				'disable' AS message,
				DATE_FORMAT(o.created, '%d.%m.%Y') as date_from,
				DATE_FORMAT(DATE_ADD(o.created, Interval @delivery DAY), '%d.%m.%Y') AS date_to
			FROM 
				#orders_values ov
			JOIN #items i ON i.id=ov.item_id
			JOIN #provider_stores ps ON ps.id=ov.store_id
			JOIN #orders_statuses os ON ov.status_id=os.id
			LEFT JOIN #store_items si ON si.store_id=ov.store_id AND si.item_id=ov.item_id
			JOIN #brends b ON b.id=i.brend_id
			LEFT JOIN
				#item_barcodes ib ON ib.item_id = i.id
			JOIN #orders o ON o.id=ov.order_id
			WHERE 
				ov.order_id={$order_id} AND
				ov.store_id={$store_id} AND
				ov.item_id={$item_id}
		", '');
		$order = $orders[0]?>
		<tr>
			<td class="name-col">
				<b class="brend_info" brend_id="<?=$order['brend_id']?>"><?=$order['brend']?></b> 
				<a class="articul" href="<?=core\Item::getHrefArticle($order['article'])?>">
					<?=$order['article']?>
				</a>
				 <?=$order['title']?> 
			 </td>
			<td><?=$order['cipher']?></td>
			<td style="padding-right: 10px"><?=$order['date_from']?></td>
			<td><?=$order['date_to']?></td>
			<td><?=$order['quan']?></td>
			<td><span class="status-col <?=$order['class']?>"><?=$order['status']?></span></td>	
			<td><span class="price_format"><?=$order['price']?></span> <i class="fa fa-rub" aria-hidden="true"></i></td>
		</tr>
	</table>
	<table class="orders-table small-view">
		<tr>
			<th>Товар</th>
		</tr>
		<tr>
			<td>
			<b class="brend_info" brend_id="<?=$order['brend_id']?>"><?=$order['brend']?></b>
			<br> 
			<a class="articul" href="<?=core\Item::getHrefArticle($order['article'])?>"></a> <br> 
			<?=$order['title']?> 
			<br>
			<br>
				Поставщик: <strong><?=$order['cipher']?></strong>
				<br>
				Дата доставки: <strong><?=$order['date_to']?></strong> <br>
				Количество: <strong><?=$order['quan']?></strong> <br>
				Статус: <strong>
										<span class="status-col <?=$order['class']?>"><?=$order['status']?></span>
									</strong>
				<br>
				Цена: <strong><span class="price_format"><?=$order['price']?></span> <i class="fa fa-rub" aria-hidden="true"></i></strong>
			</td>
		</tr>
	</table>
	<?}
	if (!empty($messages)){?>
		<div class="dialog" style="margin-top: 20px">
			<div class="dialog-box">
			<?foreach ($messages as $message) {?>
				<div class="message-box">
					<span class="sender"><?=$message['sender']?></span>
					<span class="send-time"><?=$message['date']?></span>
					<p class="message"><?=$message['text']?></p>
					<?if ($message['fotos']){
					$fotos = explode(',', $message['fotos']);?>
					<div class="attachment">
						<?foreach ($fotos as $foto){?>
							<div class="img-wrap"><a href="<?=core\Config::$imgUrl?>/temp/<?=$foto?>"><img src="<?=core\Config::$imgUrl?>/temp/<?=$foto?>" alt=""></a></div>
						<?}?>
					</div>
					<?}?>
				</div>
			<?}?>
			</div>
			<hr>
		</div>
	<?}?>
	<form action="/core/send_message.php" id="send-message" method="post">
		<input type="hidden" name="message_send" value="1">
		<input type="hidden" name="json_fotos">
		<input type="hidden" name="correspond_id" value="<?=$_GET['id']?>">
		<input type="hidden" name="order_id" value="<?=$order_id?>">
		<input type="hidden" name="store_id" value="<?=$_GET['store_id']?>">
		<input type="hidden" name="item_id" value="<?=$_GET['item_id']?>">
		<input type="hidden" name="sender" value="user">
		<?if (!$_GET['id']){?>
			<div class="input-wrap">
				<label for="department">Тема сообщения: </label>
				<?$res_messages_themes = $db->query("
					SELECT
						mth.id,
						mth.title
					FROM
						#messages_themes mth
					ORDER BY
						mth.title
				", '')?>
				<select data-placeholder="Выберите тему" name="theme_id" id="department">
					<option value=""></option>
					<?while ($row = $res_messages_themes->fetch_assoc()) {?>
						<option value="<?=$row['id']?>"><?=$row['title']?></option>
					<?}?>
				</select>
			</div>
		<?}
		elseif (!$order_id){?>
			<input type="hidden" id="department"  name="theme_id" value="<?=$_GET['id']?>">
		<?}?>
		<p>Текст сообщения: </p>
		<textarea name="text" id="send-order-text" cols="30" rows="10"></textarea>
		<div style="position: relative;">
			<ul id="fotos"></ul>
			<div id="temp_foto"></div>
			<input type="button" accept="image/*" value="Загрузить фото" id="click_image">
			<span class="info_btn"></span>
			<div id="upload-info" class="info" style="display: none;">
				<p>Размер фотографии не должен превышать 2 мб. Допускаются файлы с расширением *.jpg, *.jpeg, *.gif, *.png</p>
			</div>
		</div>
		<button>Отправить</button>
	</form>
	<form style="display: none" id="upload_image" action="<?=$action?>" enctype="multipart/form-data" method="post">
		<input id="image" name="image" type="file">
		<input type="hidden" name="image_submit" value="1" style="display: none">
	</form>
</div>