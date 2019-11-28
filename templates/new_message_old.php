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
		$target_dir = "images/temp/";
		$image_name = microtime().".jpg";
		if (move_uploaded_file($tmp_name, $target_dir.$image_name)){
			message('Фото успешно загружено!');?>
			<li foto_name="<?=$image_name?>"><img src="/<?=$target_dir.$image_name?>" alt=""></li>
		<?}
	}
	exit();
}
$order_id = 0;
$themes_messages = $db->select('themes_messages', '*', '', '', '', '', true);
$messages = $db->select('messages', '*', "`order_id`=$order_id AND `hidden`=0", 'date', false);?>
<div class="orders-message">
	<?if (count($messages)){?>
			<h1><?=$themes_messages[$messages[0]['theme_id']]['title']?></h1>
	<?}
	else{?>
	<h1>Отправить сообщение</h1>
	<?}?>
	<!-- <?if (count($messages)){
		$user = $db->select('users', 'name_1,name_2,name_3', "`id`=".$_SESSION['user']);
		$user = $user[0];
		$user = $user['name_1']." ".$user['name_2']." ".$user['name_3'];?>
		<div class="dialog" style="margin-top: 20px">
			<div class="dialog-box">
			<?foreach ($messages as $message) {?>
				<div class="message-box">
					<span class="sender"><?=$message['user_sender'] ? $user : 'Администрация'?></span>
					<span class="send-time"><?=date('d.m.Y H:i', $message['date'])?></span>
					<p class="message"><?=$message['message']?></p>
					<?$msg_fotos = $db->select('msg_fotos', 'title', "`message_id`=".$message['id']);
					if (count($msg_fotos)){?>
						<div class="attachment">
						<?foreach ($msg_fotos as $msg_foto){?>
							<div class="img-wrap"><a href="/images/temp/<?=$msg_foto['title']?>"><img src="/images/temp/<?=$msg_foto['title']?>" alt=""></a></div>
						<?}?>
						</div>
					<?}?>
				</div>
			<?}?>
			</div>
			<hr>
		</div>
	<?}?> -->
	<form action="/core/send_message.php" id="send-message" method="post">
	<input type="hidden" name="message_send" value="1">
		<input type="hidden" name="json_fotos">
		<input type="hidden" name="order_id" value="<?=$order_id?>">
		<input type="hidden" name="user_id" value="<?=$_SESSION['user']?>">
		<input type="hidden" name="user_sender" value="1">
		<?if (!count($messages)){?>
			<div class="input-wrap">
				<label for="department">Тема сообщения: </label>
				<select data-placeholder="Выберите тему" name="department" id="department">
					<option value=""></option>
					<?foreach ($themes_messages as $id => $theme_message) {?>
						<option value="<?=$id?>"><?=$theme_message['title']?></option>
					<?}?>
				</select>
			</div>
		<?}
		else{?>
			<input type="hidden" id="department"  name="department" value="<?=$messages[0]['theme_id']?>">
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
	<form style="display: none" id="upload_image" action="/new_message" enctype="multipart/form-data" method="post">
		<input id="image" name="image" type="file">
		<input type="hidden" name="image_submit" value="1" style="display: none">
	</form>
</div>