<?php

namespace core\Payment;

use core\User;

class Paykeeper{
    private static $user = "admin";
    private static $password = "89eb4d68eb32";
    private static $server = 'http://tahos.server.paykeeper.ru';

    public static function getLinkReplenishBill($user_id, $amount){
        $resultUser = User::get(['user_id' => $user_id]);
        foreach($resultUser as $value){
            $userInfo = $value;
        }


        $payment_data = [
            "pay_amount" => $amount,
            "clientid" => $user_id,
            "orderid" => "Пополнение счета №$user_id",
            "client_email" => $userInfo['email'],
            "service_name" => "Пополнение счета",
            "client_phone" => $userInfo['phone']
        ];
        $base64 = base64_encode(self::$user.":".self::$password);
        $headers = Array();
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

        return self::$server."/bill/$invoice_id/";
    }
}