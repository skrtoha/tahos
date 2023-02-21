<?php
ini_set('error_reporting', E_ERROR | E_PARSE);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

use core\Setting;
use core\Synchronization;
use core\User;

require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/DataBase.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/templates/functions.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/functions/orders.function.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/functions/order_issues.function.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();
$request = !empty($_POST) ? $_POST : $_GET;

$synchronization_token = Setting::get('site_settings', 'synchronization_token');
if ($synchronization_token != $request['synchronization_token']) die("Неверный токен");

switch($request['act']){
    case "synchronizeUsers":
        $output = [];
        $res_query = $db->query("
            SELECT
                u.id,
                'user' AS type,
                ".User::getUserFullNameForQuery()." AS name
            FROM
                #users u
            LEFT JOIN
                #organizations_types ot on ot.id = u.organization_type
            
            UNION
            
            SELECT
                p.id,
                'provider' AS type,
                p.title AS name
            FROM
                #providers p
            
            ORDER BY name
        ")->fetch_all(MYSQLI_ASSOC);
        echo json_encode($res_query, JSON_UNESCAPED_UNICODE);
        break;
	case 'getOrders':
		$output = Synchronization::getNoneSynchronizedOrders();
		echo json_encode($output, JSON_UNESCAPED_UNICODE);
		break;
	case 'setSynchronizedOSI':
		Synchronization::setOrdersSynchronized(json_decode($_POST['data'], true));
		break;
	//в 1С создает заказ поставщику и отправляет в "Заказано"
	case 'createOrderAndSendOrdered':
		$changedOrders = [];
		$osiArray = json_decode($request['data'], true);
        $orderValues = core\OrderValue::get(['osi' => $osiArray], '');
        foreach($orderValues as $ov){
            core\OrderValue::setStatusInWork($ov, true);
            $ov['ordered'] = $ov['quan'];
            $changedOrders[] = $ov;
		}
		echo json_encode($changedOrders);
		break;
	case 'setStatusArrived':
        $changedOrders = [];
        $data = json_decode($request['data'], true);
		$orders = Synchronization::getOrders(['osi' => array_keys($data)]);

        foreach($orders as $order){
            foreach($order['values'] as $ov){
                $osi = "{$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}";
                $ov['quan'] = $data[$osi];
                $ov['synchronized'] = 1;
                core\OrderValue::changeStatus(3, $ov);
                $changedOrders[] = $osi;
            }
        }
        echo json_encode($changedOrders);
		break;
	case 'setStatusIssued':
        $data = json_decode($_POST['data'], true);
        $orderValues = [];
        $orderValuesResult = \core\OrderValue::get(['osi' => array_keys($data)]);
        $isAllItemsIssued = [];
        foreach($orderValuesResult as $ov){
            $osi = "{$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}";
            if ($ov['issued'] >= $data[$osi]) $isAllItemsIssued[$osi] = true;
            else $isAllItemsIssued = false;

            $user_id = $ov['user_id'];
            break;
        }

        $income = [];
        foreach($data as $key => $value){
            $array = explode('-', $key);
            if ($isAllItemsIssued[$key]) continue;
            $income["{$array[0]}:{$array[2]}:{$array[1]}"] = $value;
        }

        if (empty($income)) break;

		$issues = new \Issues($user_id, $db);
		$issues->setIncome($income, true);
		break;
	case 'cancelItemsFromOrder':
		$osiArray = explode(',', $request['osi']);
		foreach($osiArray as $osiString){
			$osi = Synchronization::getArrayOSIFromString($osiString);
			$ov_result = core\OrderValue::get($osi);
			$ov = $ov_result->fetch_assoc();
			core\OrderValue::changeStatus(6, $ov);
		}
		break;
	case 'returnOrderValues':
		for ($i = 0; $i < count($request['osi']); $i++) { 
			$items = [];
			$osi = Synchronization::getArrayOSIFromString($request['osi'][$i]);
			$ov_result = core\OrderValue::get($osi);
			$ov = $ov_result->fetch_assoc();

            if ($ov['returned'] >= $request['quan'][$i]) continue;

            $items[] = [
				'order_id' => $ov['order_id'],
				'store_id' => $ov['store_id'],
				'item_id' => $ov['item_id'],
				'title' => "<b class=\"brend_info\" brend_id=\"{$ov['brend_id']}\">{$ov['brend']}</b> 
					<a href=\"/search/article/{$ov['article']}\" class=\"articul\">{$ov['article']}</a> 
					{$ov['title_full']}",									
				'summ' => $ov['quan'] * $ov['price'],
				'return_price' => $request['return_price'][$i],
				'days_from_purchase' => 14,
				'packaging' => $ov['packaging'],
				'reason_id' => 1,
				'quan' => $request['quan'][$i]
			];
			core\Returns::createReturnRequest($items);
			$paramsReturn = [
				'return_price' => $request['return_price'][$i],
				'quan' => $request['quan'][$i],
				'status_id' => 3
			];
			$res_return = core\Returns::get($osi);
			$return = $res_return->fetch_assoc();
			core\Returns::processReturn($paramsReturn, $return);
		}
		break;
    case 'createItem':
        $data = json_decode($request['data'], true);
        echo json_encode(Synchronization::createItem($data));
        break;
    case 'replenishBill':
        $data = json_decode($_POST['data'], true);
        $data['comment'] = mb_substr($data['comment'], 0, -8);

        User::replenishBill([
            'user_id' => $data['user_id'],
            'sum' => $data['sum'],
            'comment' => $data['comment'],
            'bill_type' => User::BILL_TYPE_CASHLESS
        ]);
        break;
}

?>
