<?php
namespace core\Sms;

use core\Config;

class SmsAero implements ISms{
    const API_URL = "gate.smsaero.ru/v2/";

    public function generateUrl($method, $params){
        $config = Config::$SmsAero;
        $params ['sign'] = "SMS Aero";
        return "https://{$config['email']}:{$config['api_key']}@".self::API_URL.$method."?".http_build_query($params);
    }

    public function sendSms($number, $text){
        $request_url = self::generateUrl('sms/send', [
            'number' => $number,
            'text' => $text
        ]);
        $result = file_get_contents($request_url);
        return json_decode($result,true);
    }
}

