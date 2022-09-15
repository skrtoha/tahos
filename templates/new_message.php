<? use core\Breadcrumb;

/* @var $db \core\Database */

if (!$_SESSION['user']) header('Location: /');
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
			<li foto_name="<?=$image_name?>"><img src="/<?=$target_dir.$image_name?>" alt=""></li>
		<?}
	}
	exit();
}
$messages_themes = $db->select('messages_themes', '*', '', '`id`<=5', '', '', true);
Breadcrumb::add('/new message', 'Отправить сообщение');
Breadcrumb::out();
?>
<div class="orders-message">
	<h1>Отправить сообщение</h1>
	<form action="/core/send_message.php" id="send-message" method="post">
	<input type="hidden" name="message_send" value="1">
		<input type="hidden" name="json_fotos">
		<input type="hidden" name="order_id" value="<?=$order_id?>">
		<input type="hidden" name="sender" value="user">
			<div class="input-wrap">
				<label for="department">Тема сообщения: </label>
				<select data-placeholder="Выберите тему" name="theme_id" id="department">
					<option value=""></option>
					<?foreach ($messages_themes as $id => $value) {?>
						<option value="<?=$id?>"><?=$value['title']?></option>
					<?}?>
				</select>
			</div>
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