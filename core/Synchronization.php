<?php
namespace core;

use core\Messengers\Telegram;

class Synchronization{
    public static array $paymentPaykeeper1C = [
        94 => 'СБП',
        12 => 'ИТ-1'
    ];
	public static function getNoneSynchronizedOrders(){
		return self::getOrders([
            'synchronized' => 0,
            'created' => '2023-07-20 00:00:00'
        ], '');
	}
	public static function getOrders($params, $flag){
		$output = [];
		$res_order_values = OrderValue::get($params, $flag);
		foreach($res_order_values as $ov){
            if (!$ov['bill_type']) continue;

			$o = & $output[$ov['order_id']];
			$o['user_id'] = $ov['user_id'];
			$o['userName'] = mb_strtoupper($ov['userName']);
			$o['created'] = $ov['created'];
            $o['bill_type'] = $ov['bill_type'];
            $o['typeOrganization'] = $ov['typeOrganization'];
            $o['userCreated'] = $ov['userCreated'];
            $o['arrangement_uid'] = $ov['arrangement_uid'];
			$output[$ov['order_id']]['values'][] = [
				'status_id' => $ov['status_id'],
				'provider_id' => $ov['provider_id'],
				'provider' => mb_strtoupper($ov['provider']),
				'cipher' => $ov['cipher'],
				'order_id' => $ov['order_id'],
				'user_id' => $ov['user_id'],
				'store_id' => $ov['store_id'],
				'providerStore' => $ov['providerStore'],
                'typeOrganization' => $ov['typeOrganization'],
				'brend' => $ov['brend'],
				'brend_id' => $ov['brend_id'],
				'item_id' => $ov['item_id'],
				'article' => $ov['article'],
				'article_cat' => $ov['article_cat'] ?: $ov['article'],
				'title_full' => $ov['title_full'],
				'packaging' => $ov['packaging'],
				'price' => $ov['price'],
				'quan' => $ov['quan'],
				'ordered' => $ov['ordered'],
				'arrived' => $ov['arrived'],
				'issued' => $ov['issued'],
                'issue_id' => $ov['issue_id'],
                'issued_date' => $ov['issued_date'],
				'returned' => $ov['returned'],
				'updated' => $ov['updated'] ?: $ov['created'],
				'withoutMarkup' => $ov['withoutMarkup'],
                'return_price' => $ov['return_price'],
                'return_data' => $ov['return_data'],
                'pay_type' => $ov['pay_type']
			];
		}

        $userIdList = array_column($output, 'user_id');
        $userIdList = array_unique($userIdList);
        $userListResult = User::get(['user_id' => $userIdList]);
        $userList = [];
        foreach($userListResult as $user){
            $userList[$user['id']] = [
                'phone' => self::formatPhone($user['phone']),
                'email' => $user['email']
            ];
        }
        if (!empty($userList)){
            foreach($output as & $order){
                if (isset($userList[$order['user_id']])){
                    $order['email'] = $userList[$order['user_id']]['email'];
                    $order['phone'] = $userList[$order['user_id']]['phone'];
                }
                else{
                    $order['email'] = '';
                    $order['phone'] = '';
                }
            }
        }

		return $output;
	}

    private static function formatPhone($phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        $code = substr($phone, 1, 3);
        $number1 = substr($phone, 4, 3);
        $number2 = substr($phone, 7, 2);
        $number3 = substr($phone, 9, 2);
        return "+7($code)$number1-$number2-$number3";
    }
	public static function setOrdersSynchronized($osi){
        foreach($osi as $row){
            $array = explode('-', $row);
            $GLOBALS['db']->query("
                UPDATE 
                    #orders_values 
                SET 
                    synchronized = 1 
                WHERE 
                    (`order_id` = {$array[0]} AND `store_id` = {$array[1]} AND `item_id` = {$array[2]}) AND 
                    synchronized = 0
	    	");
        }
	}
	public static function getArrayOSIFromString($osi){
		$array = explode('-', $osi);
		return [
			'order_id' => $array[0],
			'store_id' => $array[1],
			'item_id' => $array[2]
		]; 
	}
    public static function createItem($data){
        /** @var Database $db */
        $db = $GLOBALS['db'];

        $output = [
            'error' => '',
            'result' => []
        ];

        if ($data['brend']['id'] == 0){
            $result = $db->select_one(
                'brends',
                'id,parent_id',
                "`title` = '{$data['brend']['title']}'"
            );
            if (!empty($result)){
                if ($result['parent_id']){
                    $output['result']['created_brend_id'] = $result['parent_id'];
                    $data['brend_id'] = $result['parent_id'];
                }
                else{
                    $output['result']['created_brend_id'] = $result['id'];
                    $data['brend_id'] = $result['id'];
                }
            }
            else{
                $result = $db->insert('brends', [
                    'title' => $data['brend']['title'],
                    'href' => translite($data['brend']['title']),
                    'parent_id' => 0
                ]);
                if ($result !== true){
                    $output['error'] = $result;
                    return $output;
                }
                $data['brend_id'] = $db->last_id();
                $output['result']['created_brend_id'] = $data['brend_id'];
            }
        }
        else $data['brend_id'] = $data['brend']['id'];

        unset($data['brend']);


        if ($data['id']){
            unset($data['title']);
            unset($data['title_full']);
            $result = Item::update($data, ['id' => $data['id']]);
        }
        else{
            $query = Item::getQueryItemInfo();
            $query .= " WHERE i.article = '{$data['article']}' AND i.brend_id = {$data['brend_id']}";

            $result = $db->query($query);
            if ($result->num_rows){
                $row = $result->fetch_assoc();
                $output['result']['created_item_id'] = $row['id'];
                return $output;
            }

            $result = Item::insert($data);
            if ($result === true) $output['result']['created_item_id'] = Item::$lastInsertedItemID;
        }

        if ($result !== true){
            $output['error'] = $result;
            return $output;
        }

        return $output;
    }
    public static function httpQuery($method, $data, $type = 'get'){
        static $settings;
        if (!$settings){
            $settings = Setting::get('common', null, 'all');
        }
        $url = $settings['1c_url'];
        $headers = [
            'synchronization_token' => $settings['synchronization_token'],
            'Authorization' => $settings['1c_authorization']
        ];
        if ($type == 'get') {
            $url .= "$method/?".http_build_query($data);
            $result = Provider::getCurlUrlData($url, [], $headers);
        }
        elseif ($type == 'post') {
            $result = Provider::getCurlUrlData($url, json_encode($data), $headers);
        }

        return $result;
    }
    public static function set1CDocument(string $title, array $data){
        $json = json_encode($data);
        Database::getInstance()->insert(
            '1c_documents',
            [
                'title' => $title,
                'data' => $json
            ],
            ['duplicate' => [
                'data' => $json
            ]]
        );
    }
    public static function get1CDocument($title){
        $result = Database::getInstance()->select_one('1c_documents', "*", "`title` = '$title'");
        if (!$result){
            return [];
        }
        return json_decode($result['data'], true);
    }
    public static function createReturn($params){
        $result = self::httpQuery('create-return', $params);
        return $result;
    }

    public static function createPayment1C($params, $type = '') {
        Telegram::writeLogFile($params);
        $settings = Setting::get('common', null, 'all');

        $curl = curl_init();

        $params['type'] = $type;
        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$settings['1c_url']}create-payment",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($params, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => array(
                "synchronization_token: {$settings['synchronization_token']}",
                'Content-Type: application/json',
                "Authorization: {$settings['1c_authorization']}"
            ),
        ));

        $response = curl_exec($curl);
        Telegram::writeLogFile(curl_error($curl));
        Telegram::writeLogFile($response);

        curl_close($curl);
        return $response;
    }

    public static function cancelOperation($queryParams, \mysqli_result $result, $bill_type) {
        $fund = $result->fetch_assoc();

        User::decreasePayment([
            'user_id' => $queryParams['user_id'],
            'sum' => 0,
            'previous_sum' => $queryParams['sum'],
            'bill_type' => $bill_type,
            'fund_id' => $fund['id'],
            'delete' => true
        ]);

        $db = Database::getInstance();
        $db->startTransaction();

        Database::getInstance()->delete(
            'fund_distribution',
            "`replenishment_id` = {$fund['id']} AND `sum` = {$fund['sum']}"
        );
        Database::getInstance()->delete('funds', "`id` = {$fund['id']}");
        $documentTitle = preg_replace('/\d\d:\d\d:\d\d.*/i', '', $queryParams['document_title']);
        Database::getInstance()->delete('1c_documents', "`title` LIKE '$documentTitle%'");

        $db->commit();

    }

    public static function cancelItemsFromOrder($queryParams, $orderValueList): void
    {
        foreach($orderValueList as $ov){
            $osi = "{$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}";

            if (!isset($queryParams[$osi])) {
                continue;
            }

            $oOsi = & $queryParams[$osi];

            if (in_array($ov['status_id'], [6, 8, 12])) {
                continue;
            }

            $db = Database::getInstance();
            $db->startTransaction();
            if ($oOsi['quan'] == $ov['quan']) {
                $status_id = OrderValue::getStatusIdByTitle($oOsi['reason']);
            }
            else {
                $status_id = $ov['status_id'];
            }

            if ($ov['status_id'] == 11) {
                User::updateReservedFunds(
                    $ov['user_id'],
                    $ov['price'] * $oOsi['quan'],
                    'minus',
                    $ov['pay_type']
                );
            }
            $update = [
                'status_id' => $status_id,
                'declined' => $oOsi['quan'],
                'synchronized' => 1
            ];
            if ($ov['ordered'] > 0) {
                $update['ordered'] = $ov['quan'] - $oOsi['quan'];
            }
            OrderValue::update($update, $ov);
            $db->commit();
        }
    }

    public static function setStatusArrived($queryParams, $orderValueList): void
    {
        if (empty($queryParams)) {
            return;
        }

        $db = Database::getInstance();
        foreach($orderValueList as $ov) {
            $osi = "{$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}";
            $db->startTransaction();

            if (isset($queryParams[$osi])) {
                if (in_array($ov['status_id'], [5, 7])) {
                    User::updateReservedFunds(
                        $ov['user_id'],
                        $ov['price'] * $queryParams[$osi],
                        'plus',
                        $ov['pay_type']
                    );
                }
                $db->update(
                    'orders_values',
                    [
                        'ordered' => $queryParams[$osi],
                        'arrived' => $queryParams[$osi],
                        'status_id' => 3,
                        'synchronized' => 1
                    ],
                    str_replace(['WHERE', 'ov.'], '', OrderValue::getWhere(['osi' => $osi]))
                );
            }
            else {
                if ($ov['status_id'] == 3) {
                    $db->update(
                        'orders_values',
                        [
                            'arrived' => 0,
                            'status_id' => 11,
                            'synchronized' => 1
                        ],
                        str_replace(['WHERE', 'ov.'], '', OrderValue::getWhere(['osi' => $osi]))
                    );
                }
            }

            $db->commit();
        }
    }

    public static function changePriceOrder($queryParams): void
    {
        foreach($queryParams as $osi => $price) {
            Database::getInstance()->update(
                'orders_values',
                [
                    'price' => $price['price'],
                    'synchronized' => 1
                ],
                str_replace(['WHERE', 'ov.'], '', OrderValue::getWhere(['osi' => $osi]))
            );
        }
    }
}
