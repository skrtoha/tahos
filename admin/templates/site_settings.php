<?php
use core\Managers;
$page_title = 'Настройки сайта';
if (!empty($_POST)){
    $data = $_POST;
    $update['is_blocked'] = json_encode([
        'time' => time(),
        'count_seconds' => $data['count_seconds'] * 60,
        'is_blocked' => $data['is_blocked']
    ]);
    $update['1c_url'] = $data['1c_url'];
    $update['synchronization_token'] = $data['synchronization_token'];
    foreach($update as $key => $value) core\Setting::update('site_settings', $key, $value);
	header("Location: /admin/?view=site_settings");
}
$data = core\Setting::get('site_settings', null, 'all');
$data['is_blocked'] = json_decode($data['is_blocked'], true);
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
			</div>
            <div class="field">
                <div class="title">Токен синхронизации</div>
                <div class="value">
                    <input name="synchronization_token" value="<?=$data['synchronization_token']?>">
                </div>
            </div>
            <div class="field">
                <div class="title">Веб адрес 1С</div>
                <div class="value">
                    <input name="1c_url" value="<?=$data['1c_url']?>">
                </div>
            </div>
            <input type="submit" value="Сохранить">
		</form>
	</div>
</div>
<div class="action"><a href="<?=$_SERVER['HTTP_REFERER']?>">Назад</a></div>
