<?php

namespace core\Payment;

use core\Database;
use core\Exceptions\Paykeeper\PaymentAlreadyExistsException;
use core\Fund;
use core\OrderValue;
use core\Synchronization;
use core\User;

class Paykeeper{
    private static $user = "admin";
    private static $password = "89eb4d68eb32";
    private static $server = 'http://tahos.server.paykeeper.ru';
    private static $secret_seed = "2m}aFojrEqwjnEJW";

    public static $orderInfo = [];

    public static function getLinkReplenishBill($amount, $user_id, $orderId = null){
        $payment_data = [
            "pay_amount" => $amount
        ];
        if ($orderId){
            $payment_data['orderid'] = $orderId;
            $payment_data['service_name'] = "Оплата заказа №$orderId";
        }
        else{
            $resultUser = User::get(['user_id' => $user_id]);
            foreach($resultUser as $value){
                $userInfo = $value;
            }
            $payment_data['clientid'] = $userInfo['full_name'];
            $payment_data['service_name'] = "Пополнение счета для {$userInfo['full_name']}";
            $payment_data['client_email'] = $userInfo['email'];
            $payment_data['client_phone'] = $userInfo['phone'];
        }
        $base64 = base64_encode(self::$user.":".self::$password);
        $headers = [];
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $headers[] = 'Authorization: Basic ' . $base64;
        $uri = "/info/settings/token/";
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_URL,self::$server.$uri);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl,CURLOPT_HEADER, false);

        $response = curl_exec($curl);
        $php_array = json_decode($response, true);
        if (isset($php_array['token'])){
            $token = $php_array['token'];
        }
        else{
            return false;
        }

        $uri="/change/invoice/preview/";
        $request = http_build_query(array_merge($payment_data, ['token'=>$token]));
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_URL,self::$server.$uri);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl,CURLOPT_HEADER, false);
        curl_setopt($curl,CURLOPT_POSTFIELDS, $request);

        $response=json_decode(curl_exec($curl),true);
        if (isset($response['invoice_id'])){
            $invoice_id = $response['invoice_id'];
        }
        else{
            return false;
        }

        if ($orderId){
            Database::getInstance()->insert('order_paykeeper_invoice', [
                'order_id' => $orderId,
                'invoice_id' => $invoice_id
            ]);
        }

        return self::getLinkPay($invoice_id);
    }

    public static function getLinkPay($invoice_id): string
    {
        return self::$server."/bill/$invoice_id/";
    }

    public static function getRefundsInfo($payment_id){
        $base64 = base64_encode(self::$user.":".self::$password);
        $headers = [];
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $headers[] = 'Authorization: Basic ' . $base64;
        $uri = "/info/refunds/bypaymentid/?id=$payment_id";
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_URL,self::$server.$uri);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl,CURLOPT_HEADER, false);

        $response = curl_exec($curl);
        return json_decode($response, true);
    }

    private static function dischargeBill($amount, $params): bool
    {
        $user = [];
        if (isset($params['orderid']) && $params['orderid']) {
           $orderInfo = OrderValue::getOrderInfo($params['orderid']);
           $res_user = User::get(['user_id' => $orderInfo['user_id']]);
           $comment = "Отмена оплаты по заказу №{$params['orderid']}: Возврат оплаты в сумме $amount руб.";
        }
        if (isset($params['clientid']) && $params['clientid']) {
           $res_user = User::get(['full_name' => $params['clientid']]);
           $comment = "Отмена пополнения счета: Возврат оплаты в сумме $amount руб.";
        }
        foreach($res_user as $value){
            $user = $value;
        }
        if (empty($user)){
            return false;
        }

        $update = [];

        $remainder = $user['bill_cash'] - $amount;
        $update['bill_cash'] = "`bill_cash` - $amount";

        Database::getInstance()->startTransaction();
        $res_insert = Fund::insert(2, [
           'sum' => $amount,
           'remainder' => $remainder,
           'user_id' => $user['id'],
           'paid' => 0,
           'comment' => $comment,
           'bill_type' => User::BILL_CASH
        ]);
        if ($res_insert === true){
            User::update($user['id'], $update);
            Database::getInstance()->commit();
        }
        return true;
    }

    public static function setPayment($params): bool
    {
        $mdString = $params['id']
            .number_format($params['sum'], 2, ".", "")
            .$params['clientid']
            .$params['orderid']
            .self::$secret_seed;
        if ($params['key'] != md5($mdString)){
            return false;
        }

        if (isset($params['type']) && $params['type'] == 'cancel'){
            self::setResponse($params['id']);
            $refunds = self::getRefundsInfo($params['id']);
            $refundInfo = array_pop($refunds);
            return self::dischargeBill($refundInfo['amount'], $params);
        }

        try{
            self::checkPerformedPayment($params['id']);
        }
        catch(\Throwable $e){
            self::setResponse($params['id']);
            return false;
        }

        if (isset($params['orderid']) && $params['orderid']){
            $result = self::setPaymentOrder($params);
            if ($result) {
                Synchronization::createPayment1C([
                    'order_id' => $params['orderid'],
                    'user_id' => self::$orderInfo['user_id'],
                    'paykeeper_id' => $params['id'],
                    'sum' => $params['sum'],
                    'payment_arrangement' => Synchronization::$paymentPaykeeper1C[$params['ps_id']]
                ]);
            }
        }
        else{
            $result = self::setPaymentAccount($params);
        }

        self::setResponse($params['id']);

        return $result;
    }

    private static function setResponse($id){
        $hash = md5($id.self::$secret_seed);
        echo "OK $hash";
    }

    private static function setPaymentOrder($params): bool
    {
        self::$orderInfo = OrderValue::getOrderInfo($params['orderid'], false);
        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $params['obtain_datetime']);
        User::replenishBill([
            'user_id' => self::$orderInfo['user_id'],
            'sum' => $params['sum'],
            'comment' => "Оплата заказа №{$params['orderid']}: Платежное поручение №{$params['id']} от ".$dateTime->format('d.m.Y H:i:s'),
            'bill_type' => User::BILL_CASH
        ]);
        Database::getInstance()->update(
            'order_paykeeper_invoice',
            ['payed' => 1],
            "`order_id` = {$params['orderid']}"
        );
        self::addPerformedPayment($params['id'], self::$orderInfo['user_id']);
        return true;
    }

    private static function setPaymentAccount($params): bool
    {
        if ($params['client_email']){
            $where = ['email' => $params['client_email']];
        }
        elseif ($params['client_phone']){
            $where = ['phone' => $params['client_phone']];
        }
        else{
            $where = ['full_name' => $params['clientid']];
        }
        if (empty($where)){
            return false;
        }
        $result = User::get($where);
        if (!$result){
            return false;
        }
        foreach($result as $value){
            $userInfo = $value;
        }

        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $params['obtain_datetime']);
        User::replenishBill([
            'user_id' => $userInfo['id'],
            'sum' => $params['sum'],
            'comment' => "Поступление оплаты от клиента: Платежное поручение №{$params['id']} от ".$dateTime->format('d.m.Y H:i:s'),
            'bill_type' => User::BILL_CASH
        ]);

        self::addPerformedPayment($params['id'], $userInfo['id']);

        Synchronization::createPayment1C([
            'user_id' => $userInfo['id'],
            'paykeeper_id' => $params['id'],
            'sum' => $params['sum'],
            'payment_arrangement' => Synchronization::$paymentPaykeeper1C[$params['ps_id']]
        ]);

        return true;
    }

    /**
     * @throws PaymentAlreadyExistsException
     */
    private static function checkPerformedPayment($paymentId){
        $result = Database::getInstance()->getCount('user_paykeeper', "`paykeeper_id` = $paymentId");
        if ($result){
            throw new PaymentAlreadyExistsException('Данный платеж уже проведен!');
        }
    }

    private static function addPerformedPayment($paykeeper_id, $user_id){
        return Database::getInstance()->insert('user_paykeeper', [
            'paykeeper_id' => $paykeeper_id,
            'user_id' => $user_id
        ]);
    }

    public static function getPaymentSystems(){
        $auth_header =  array (
            'Authorization: Basic '.base64_encode(self::$user.':'.self::$password)
        );

        $request_headers = array_merge($auth_header, array("Content-type: application/x-www-form-urlencoded"));

        $context = stream_context_create(array (
                'http' => array (
                    'method' => 'GET',
                    'header' => $request_headers
                )
            )
        );

        $result = json_decode(file_get_contents(self::$server."/info/systems/list/", FALSE, $context), TRUE);

        foreach($result as $data) {
            foreach($data as $key => $value) {
                echo $key . " : " . $value;
                echo "\n";
            }
            echo "\n\n";
        }
    }
}