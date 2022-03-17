<?
ini_set('error_reporting', E_PARSE);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/DataBase.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/templates/functions.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();
switch($_POST['act']){
	case 'get_store':
		$store = core\Provider::getStoreInfo($_POST['store_id']);
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
			'noReturn' => $_POST['noReturn'] ? 1 : 0,
			'is_main' => $_POST['is_main'] ? 1 : 0,
			'block' => $_POST['block'] ? 1 : 0
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
		$storeInfo = core\Provider::getStoreInfo($_POST['store_id'], ['flag' => '']);
		$res_user = core\User::get(['user_id' => $_SESSION['user'] ? $_SESSION['user'] : false]);
		if (is_object($res_user)) $user = $res_user->fetch_assoc();
		else $user = $res_user;
		if(!$user['showProvider']){
			unset($storeInfo['provider'], $storeInfo['title']);
		}
		$commonOrderValues = $db->getCount('orders_values', "`store_id` = {$_POST['store_id']}");
		$refusedOrderValues = $db->getCount('orders_values', "`store_id` = {$_POST['store_id']} AND `status_id` = 6");
		if ($commonOrderValues){
			$storeInfo['percentRefusedOrderValues'] = round($refusedOrderValues / $commonOrderValues * 100);
			$storeInfo['percentSuccessOrderValues'] = 100 - $storeInfo['percentRefusedOrderValues'];
		}
		$date = new DateTime();
		if ($storeInfo['cron_hours'] && $storeInfo['cron_minutes']){
			$storeInfo['orderProcessed'] = $date->format('d.m.Y') . " {$storeInfo['cron_hours']}:{$storeInfo['cron_minutes']}";
		}
		getStoreInfo($storeInfo);
		break;
    case 'getProviderStores':
        $output = $db->select(
            'provider_stores',
            'id,cipher,title',
            "`provider_id` = {$_POST['provider_id']} AND `is_main` = 1",
            'is_main, cipher, title'
        );
        echo json_encode($output);
        break;
}
function getStoreInfo($storeInfo){?>
	<div id="providerInfo">
		<h3>Информация о складе</h3>
		<div class="information">
			<?if ($storeInfo['provider']){?>
				<div class="row">
					<span class="left">Поставщик:</span>
					<span class="right"><?=$storeInfo['provider']?></span>
				</div>
			<?}?>
			<?if ($storeInfo['orderProcessed']){?>
				<div class="row">
					<span class="left">Заказ будет обработан:</span>
					<span class="right"><?=$storeInfo['orderProcessed']?></span>
				</div>
			<?}?>
			<div class="row">
				<span class="left">Дата обновления прайса:</span>
				<span class="right"><?=$storeInfo['price_updated']?></span>
			</div>
		</div>
		<?if (isset($storeInfo['percentRefusedOrderValues']) && isset($storeInfo['percentSuccessOrderValues'])){?>
			<div class="donut">
				<span data-peity='{"fill":["#438e53", "#eeeeee"],"innerRadius": 30,"radius": 40}' class="donut"><?=$storeInfo['percentSuccessOrderValues']?>/100</span>
				<div class="text">
					<span class="percent green"><?=$storeInfo['percentSuccessOrderValues']?></span>
					<span class="describe">выдано</span>
				</div>
			</div>
			<div class="donut">
				<span data-peity='{"fill":["red", "#d8d8d8"],"innerRadius": 30,"radius": 40}' class="donut"><?=$storeInfo['percentRefusedOrderValues']?>/100</span>
				<div class="text">
					<span class="percent red"><?=$storeInfo['percentRefusedOrderValues']?></span>
					<span class="describe">отказано</span>
				</div>
			</div>
		<?}?>
		<?if (!$storeInfo['noReturn']){?>
			<div class="informationReturn">
				<span class="icon-circle-down"></span>
				<p>Возврат возможен</p>
			</div>
			<div class="informationReturn">
				<span class="icon-notification"></span>
				<p class="returnText">Возврат возможен в течение <span style="color: red; font-weight: bold;"><?=$storeInfo['daysForReturn']?></span> дней после получения товара</p>
			</div>
		<?}?>
	</div>
<?}
