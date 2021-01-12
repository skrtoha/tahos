<?php
use core\Managers;
$status = "<a href=\"/\">Главная</a> > Настройки > ";
switch($_GET['act']){
	case 'storesForSubscribe':
		$page_title = "Основные склады для рассылки";
		$status .= $page_title;

		if (!empty($_POST)){
			$array = [];
			foreach($_POST as $store_id => $value){
				$array[] = $store_id; 
			}
			core\Setting::update('storesForSubscribe', json_encode($array));
			message('Успешно сохранено!');
		}

		$res_mainStores = $db->query("
			SELECT
				ps.id,
				ps.cipher,
				p.title
			FROM
				#provider_stores ps 
			LEFT JOIN
				#providers p ON p.id = ps.provider_id
			WHERE
				ps.is_main = 1
			ORDER BY
				p.title, ps.cipher
		", '');

		$currentStoresForSubscribe = json_decode(core\Setting::get('storesForSubscribe'));

		storesForSubscribe($res_mainStores, $currentStoresForSubscribe);
		break;
	case 'organization':
		$page_title = "Настройки организации";
		$status .= $page_title;
		if (!empty($_POST)){
			foreach($_POST as $name => $value){
				core\Setting::update('organization', $name, $value);
			}
			message('Успешно сохранено!');
		}
		$array = $db->select('settings', ['name', 'value'], "`view` = 'organization'");
		$settings = [];
		foreach($array as $value) $settings[$value['name']] = $value['value'];
		organization($settings);
		break;
	case 'providers':
		$page_title = 'Настройки поставщиков';
		$status .= $page_title;
		providers(core\Provider::get());
		break;
	case 'api_settings':
		$providerTitle = core\Provider::getProviderTitle($_GET['provider_id']);
		$page_title = 'Настройки API ' . $providerTitle;
		$status .= $page_title;
		if (!empty($_POST)){
			core\Setting::update('api_settings', $_GET['provider_id'], json_encode($_POST));
			api_settings($_POST);
			message('Успешно обновлено!');
		} 
		else{
			$api_settings = json_decode(core\Setting::get('api_settings', $_GET['provider_id']), true);
			if (empty($api_settings)) $api_settings = core\Provider::prepareSettingsAPI($_GET['provider_id']);
			api_settings($api_settings);
		}
		break;
}
function storesForSubscribe($res_mainStores, $currentStoresForSubscribe){?>
	<div class="t_form">
		<div class="bg">
			<form class="defaultSubmit" id="storesForSubscribe" method="post" enctype="multipart/form-data">
				<div class="field">
					<div class="title">Склады для рассылки</div>
					<div class="value">
						<?foreach($res_mainStores as $store){?>
							<label class="store">
								<?$checked = in_array($store['id'], $currentStoresForSubscribe) ? 'checked' : ''?>
								<input <?=$checked?> type="checkbox" name="<?=$store['id']?>" value="1">
								<span><?=$store['cipher']?> (<?=$store['title']?>)</span>
							</label>
						<?}?>
						<input type="submit" value="Сохранить">
					</div>
				</div>
			</form>
		</div>
	</div>
<?}
function organization($organization = array()){?>
	<div class="t_form">
		<div class="bg">
			<form class="defaultSubmit" id="storesForSubscribe" method="post" enctype="multipart/form-data">
				<div class="field">
					<div class="title">Короткое название</div>
					<div class="value">
						<input type="text" name="shortTitle" value="<?=$organization['shortTitle']?>">
					</div>
				</div>
				<div class="field">
					<div class="title">Полное название</div>
					<div class="value">
						<input type="text" name="fullTitle" value="<?=$organization['fullTitle']?>">
					</div>
				</div>
				<div class="field">
					<div class="title">Реквизиты</div>
					<div class="value">
						<input type="text" name="requisites" value="<?=$organization['requisites']?>">
					</div>
				</div>
				<div class="field">
					<div class="title">Почта организации</div>
					<div class="value">
						<input type="text" name="organizationEmail" value="<?=$organization['organizationEmail']?>">
					</div>
				</div>
				<div class="field">
					<div class="title">Почта для отправки прайса</div>
					<div class="value">
						<input type="text" name="priceEmail" value="<?=$organization['priceEmail']?>">
					</div>
				</div>
				<div class="field">
					<div class="title">Почта для отправки рассылок</div>
					<div class="value">
						<input type="text" name="subscribeEmail" value="<?=$organization['subscribeEmail']?>">
					</div>
				</div>
				<div class="field">
					<div class="title">Юридический адрес</div>
					<div class="value">
						<input type="text" name="legalAddress" value="<?=$organization['legalAddress']?>">
					</div>
				</div>
				<div class="field">
					<div class="title">Фактический адрес</div>
					<div class="value">
						<input type="text" name="factAddress" value="<?=$organization['factAddress']?>">
					</div>
				</div>
				<div class="field">
					<div class="title">IP адресс</div>
					<div class="value">
						<input type="text" name="ip" value="<?=$organization['factAddress']?>">
					</div>
				</div>
				<div class="field">
					<div class="title">Телефон</div>
					<div class="value">
						<input type="text" name="telephone" value="<?=$organization['telephone']?>">
					</div>
				</div>
				<input type="submit" value="Сохранить">
			</form>
		</div>
<?}
function providers($providers){?>
	<table id="providers" class="t_table" cellspacing="1">
		<thead>
			<tr class="head">
				<th>Название</th>
			</tr>
		</thead>
		<tbody>
			<?foreach($providers as $provider_id => $provider){?>
				<tr class="<?=$provider['is_active'] ? 'active': ''?>" provider_id="<?=$provider_id?>">
					<td><?=$provider['title']?></td>
				</tr>
			<?}?>
		</tbody>
	</table>
<?}
function api_settings($settings){

	?>
	<table id="api_settings">
		<form class="defaultSubmit" method="post">
			<?foreach($settings as $title => $value){?>
				<tr class="wrap">
					<td>
						<b>
							<?switch ($title){
								case 'private': echo "Физ. лицо"; break;
								case 'entity': echo 'Юр. лицо'; break;
								default: echo $title;
							}?>
						</b>
					</td>
					<td>
						<?if (is_array($value)){?>
							<table>
								<?foreach($value as $t => $v){?>
									<tr>
										<td><?=$t == 'isActive' ? "<b>активен</b>" : $t?></td>
										<td>
											<?if ($t == 'isActive'){
												?>
												<select name="<?=$title?>[<?=$t?>]">
													<option <?=$v == '0' ? 'selected' : ''?> value="0">нет</option>
													<option <?=$v == '1' ? 'selected' : ''?> value="1">да</option>
												</select>
											<?}
											else{?>
												<input type="text" name="<?=$title?>[<?=$t?>]" value="<?=$v?>">
											<?}?>
										</td>
									</tr>
								<?}?>
							</table>
						<?}
						else{?>
							<input type="text" name="<?=$title?>" value="<?=$value?>">
						<?}?>
					</td>
				</tr>
			<?}?>
			<tr>
				<td colspan="2">
					<input type="submit" value="Сохранить">
				</td>
			</tr>
		</form>
	</table>

<?}
