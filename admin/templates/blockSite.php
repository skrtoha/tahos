<?php
use core\Managers;
$page_title = 'Блокировка сайта';
if (!empty($_POST)){
    $data = $_POST;
    $data['time'] = time();
    $data['count_seconds'] = $data['count_seconds'] * 60;
	core\Setting::update('is_blocked', json_encode($data));
	header("Location: /admin/?view=blockSite");
}
$data = json_decode(core\Setting::get('is_blocked'), true);
$act = $_GET['act'];?>
<div class="t_form">
	<div class="bg">
		<form method="post" enctype="multipart/form-data">
			<div class="field">
				<div class="title">Сайт заблокирован</div>
				<div class="value">
					<select name="is_blocked">
						<option <?=$data['is_blocked'] == '0' ? 'selected' : ''?> value="0">нет</option>
						<option <?=$data['is_blocked'] == '1' ? 'selected' : ''?> value="1">да</option>
					</select>
                    Количество минут:
                    <input name="count_seconds" type="text" value="<?=$data['count_seconds'] / 60?>" placeholder="Кол-во секунд">
				</div>
				<input type="submit" value="Сохранить">
			</div>
		</form>
	</div>
</div>
<div class="action"><a href="<?=$_SERVER['HTTP_REFERER']?>">Назад</a></div>
