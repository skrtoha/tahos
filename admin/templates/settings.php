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
}
function storesForSubscribe($res_mainStores, $currentStoresForSubscribe){?>
	<div class="t_form">
		<div class="bg">
			<form id="storesForSubscribe" method="post" enctype="multipart/form-data">
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
<?}?>
