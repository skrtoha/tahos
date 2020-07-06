<?require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/DataBase.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/templates/functions.php");

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();
switch($_POST['act']){
	case 'get_store':
		$store = $db->select_one('provider_stores', '*', "`id`={$_POST['store_id']}");
		echo json_encode($store);
		break;
	case 'store_change';
		$array = [
			'title' => $_POST['title'],
			'cipher' => strtoupper($_POST['cipher']),
			'city' => $_POST['city'],
			'currency_id' => $_POST['currency_id'],
			'percent' => $_POST['percent'],
			'provider_id' => $_POST['provider_id'],
			'delivery' => $_POST['delivery'],
			'delivery_max' => $_POST['delivery_max'],
			'under_order' => $_POST['under_order'],
			'daysForReturn' => $_POST['daysForReturn'],
			'prevail' => $_POST['prevail'] ? 1 : 0,
			'noReturn' => $_POST['noReturn'] ? 1 : 0,
			'is_main' => $_POST['is_main'] ? 1 : 0
		];
		if ($_POST['store_id']) $res = $db->update(
			'provider_stores',
			$array,
			"`id`={$_POST['store_id']}"
		);
		else{
			$res = $db->insert('provider_stores', $array);
			if ($res === true) $res = $db->last_id();
		} 
		echo $res;
		break;
	case 'store_delete':
		$db->delete('provider_stores', "`id`={$_POST['id']}");
		break;
	case 'get_currencies':
		$currencies = $db->select('currencies', 'id,title');
		echo json_encode($currencies);
		break;
	case 'getAllProviders':
		echo json_encode(core\Provider::get());
		break;
	case 'setProviderBrend':
		$res = $db->insert(
			'provider_brends',
			[
				'brend_id' => $_POST['brend_id'],
				'provider_id' => $_POST['data'][0]['value'],
				'title' => $_POST['data'][1]['value']
			],
		'');
		if ($res !== false) echo "$res";
		else echo true;
		break;
	case 'providerBrendDelete':
		$db->delete('provider_brends', "`brend_id`={$_POST['brend_id']} AND `provider_id`= {$_POST['provider_id']}");
		break;
	case 'getStoreInfo':
		$storeInfo = core\Provider::getStoreInfo($_POST['store_id']);
		if ($storeInfo['is_main']) getStoreInfo($storeInfo);
		else{
			getProviderInfo($db->select_one('providers', '*', "`id` = {$storeInfo['provider_id']}"));
		}
		break;
}
function getStoreInfo($storeInfo){?>
	<table id="providerInfo">
		<?foreach($storeInfo as $key => $value){
			if (!$value) continue;?>
			<tr>
				<?switch($key){
					case 'title':?> 
						<td>Название</td><td><?=$value?></td>
						<?break;
					case 'city':?>
						<td>Город</td><td><?=$value?></td>
						<?break;
					case 'provider':?>
						<td>Поставщик</td><td><?=$value?></td>
						<?break;
					case 'cipher':?>
						<td>Шифр</td><td><?=$value?></td>
						<?break;
					case 'delivery':?>
						<td>Доставка</td><td><?=$value?></td>
						<?break;
					case 'delivery_max':?>
						<td>Максимальный срок	</td><td><?=$value?></td>
						<?break;
					case 'under_order':?>
						<td>Под заказ	</td><td><?=$value?></td>
						<?break;
					case 'is_main':?>
						<td>Основной склад</td><td><?=$value ? 'Да' : 'Нет'?></td>
						<?break;
					case 'noReturn':?>
						<td>Возврат</td><td><?=$value ? 'Да' : 'Нет'?></td>
						<?break;
					case 'daysForReturn':?>
						<td>Кол-во дней возврата</td><td><?=$value?></td>
						<?break;
					case 'currency':?>
						<td>Валюта</td><td><?=$value?></td>
						<?break;
				}?>
			</tr>
		<?}?>
	</table>
<?}
function getProviderInfo($providerInfo){?>
	<table id="providerInfo">
		<?foreach($providerInfo as $key => $value){
			if (!$value) continue;?>
			<tr>
				<?switch($key){
					case 'title':?> 
						<td>Название</td><td><?=$value?></td>
						<?break;
					case 'email':?>  
						<td>E-mail</td><td><?=$value?></td>
						<?break;
					case 'telephone':?>  
						<td>Телефон</td><td><?=$value?></td>
						<?break;
					case 'telephone_extra':?>  
						<td>Дополнительный телефон</td><td><?=$value?></td>
						<?break;
					case 'ogrn':?>  
						<td>ОГРН</td><td><?=$value?></td>
						<?break;
					case 'okpo':?>  
						<td>ОКПО</td><td><?=$value?></td>
						<?break;
					case 'inn':?> 
						<td>ИНН</td><td><?=$value?></td>
						<?break;
					case 'legal_index':?>  
						<td>Юридический адрес: индекс</td><td><?=$value?></td>
						<?break;
					case 'legal_region':?>  
						<td>Юридический адрес: регион</td><td><?=$value?></td>
						<?break;
					case 'legal_adres':?>  
						<td>Юридический адрес: адрес</td><td><?=$value?></td>
						<?break;
					case 'fact_index':?>  
						<td>Фактический адрес: индекс</td><td><?=$value?></td>
						<?break;
					case 'fact_region':?>  
						<td>Фактический адрес: регион</td><td><?=$value?></td>
						<?break;
					case 'fact_adres':?>  
						<td>Фактический адрес: адрес</td><td><?=$value?></td>
						<?break;
				}?>
			</tr>
		<?}?>
	</table>
<?}