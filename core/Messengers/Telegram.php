<?php
namespace core\Messengers;

use core\Provider;

class Telegram extends Base{
    private const BOT_API_KEY = '6848824136:AAHD6BMrkWyruMuF6WtAJXyEg3heEJfFd_0';
    private const BOT_USER_NAME = 'skrtoha_bot';

    public function __construct(){

    }

    public static function getInstance(): Telegram
    {
        static $self;
        if ($self) return $self;

        $self = new static();
        return $self;
    }

    public function query($method, $params){
        $result = Provider::getCurlUrlData("https://api.telegram.org/bot".self::BOT_API_KEY."/$method?" . http_build_query($params));
        return json_decode($result, true);
    }

    public function setWebhook(){
        $result = $this->query('setWebhook', [
            "url" => 'https://tahos.ru/telegram.php'
        ]);
        return $result;
    }

    public static function writeLogFile($string, $clear = false){
        $log_file_name = __DIR__."/message.txt";
        $now = (new \DateTime())->format('h:i:s');
        if(!$clear) {
            $now = date("Y-m-d H:i:s");
            file_put_contents($log_file_name, $now." ".print_r($string, true)."\r\n", FILE_APPEND);
        }
        else {
            file_put_contents($log_file_name, '');
            file_put_contents($log_file_name, $now." ".print_r($string, true)."\r\n", FILE_APPEND);
        }
    }
}