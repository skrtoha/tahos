<?php
use core\Managers;
$page_title = 'Блокировка сайта';
if (!empty($_POST)){
	core\Setting::update('is_blocked', $_POST['is_blocked']);
	header("Location: /admin/?view=blockSite");
}
$is_blocked = core\Setting::get('is_blocked');
$act = $_GET['act'];?>
<div class="t_form">
	<div class="bg">
		<form method="post" enctype="multipart/form-data">
			<div class="field">
				<div class="title">Сайт заблокирован</div>
				<div class="value">
					<select name="is_blocked">
						<option <?=$is_blocked == '0' ? 'selected' : ''?> value="0">нет</option>
						<option <?=$is_blocked == '1' ? 'selected' : ''?> value="1">да</option>
					</select>
				</div>
				<input type="submit" value="Сохранить">
			</div>
		</form>
	</div>
</div>
<div class="action"><a href="<?=$_SERVER['HTTP_REFERER']?>">Назад</a></div>
