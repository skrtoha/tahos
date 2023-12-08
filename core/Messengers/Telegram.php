<?php
namespace core\Messengers;

use core\Database;
use core\OrderValue;
use core\Provider;
use core\User;

class Telegram{
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

    public function sendMessage($params){
        if (!isset($params['parse_mode'])){
            $params['parse_mode'] = 'html';
        }
        $result = $this->query('sendMessage', $params);
        return $result;
    }

    private function bindContact($phone, $user_telegram_id){
        $output = [];

        if (!preg_match('/^\+/', $phone)){
            $phone = '+'.$phone;
        }

        $result = User::get(['phone' => $phone]);
        foreach($result as $value) $user = $value;

        if (empty($user)){
            $output['result'] = '';
            $output['error'] = 'Пользователь с таким номером телефона не найден. Измените номер телефона и введите /start';
            return $output;
        }

        Database::getInstance()->insert(
            'user_telegram',
            [
                'user_id' => $user['id'],
                'telegram_id' => $user_telegram_id
            ],
            [['duplicate' => [
                'telegram_id' => $user_telegram_id
            ]]]
        );
        $output['result'] = 'Пользователь успешно привязан';
        $output['error'] = '';
        return $output;
    }

    private function deleteMessage($chat_id, $message_id){
        return $this->query('deleteMessage', [
            'chat_id' => $chat_id,
            'message_id' => $message_id
        ]);
    }

    public function parseMessage($message){
        if (isset($message['contact']) && !isset($message['text'])){
           $resBindContact = $this->bindContact($message['contact']['phone_number'], $message['contact']['user_id']);
           if (!$resBindContact['error']){
               $query = [
                   'chat_id' => $message['chat']['id'],
                   'text' => $resBindContact['result']
               ];
               $this->sendMessage($query);
           }
           else{
               $query = [
                   'chat_id' => $message['chat']['id'],
                   'text' => $resBindContact['error']
               ];
               $this->sendMessage($query);
           }
           $this->deleteMessage($message['chat']['id'], $message['message_id']);
           return;
        }
        switch($message['text']){
            default:
                $telegram_id = $message['from']['id'];
                $query = [
                    'chat_id' => $telegram_id,
                    'parse_mode' => 'html'
                ];

                $query['text'] = "Добро пожаловать в бот Тахос!\n";
                $result = Database::getInstance()->getCount('user_telegram', "`telegram_id`= {$telegram_id}");
                if (!$result){
                    $query['text'] .= "Вы еще не связали телеграм-бот со своим аккаунтом на Тахос. Нажмите кнопку \"Связать\".";
                    $query['reply_markup'] = json_encode([
                        'one_time_keyboard' => true,
                        'resize_keyboard' => true,
                        'keyboard' => [
                            [
                                [
                                    'text' => 'Связать',
                                    'request_contact' => true
                                ]
                            ]
                        ]
                    ]);
                }
                $this->sendMessage($query);
                break;
        }
    }

    public static function sendMessageArrived($order_id, $item_id){
        $orderInfo = OrderValue::getOrderInfo($order_id);
        if ($orderInfo['delivery'] != 'Самовывоз'){
            return;
        }

        $userTelegram = Database::getInstance()->select_one('user_telegram', '*', "`user_id` = {$orderInfo['user_id']}");
        if (!$userTelegram || !$userTelegram['telegram_id']){
            return;
        }
        $resUser = User::get(['id' => $orderInfo['user_id']]);
        foreach($resUser as $value) $user = $value;

        $issueInfo = Database::getInstance()->select_one('issues', "*", "`id` = {$user['issue_id']}");

        $orderValue = OrderValue::get([
            'order_id' => $order_id,
            'item_id' => $item_id
        ])->fetch_assoc();

        if (empty($orderValue)){
            return;
        }
        $query = [
            'chat_id' => $userTelegram['telegram_id'],
            'text' => "Товар {$orderValue['brend']} {$orderValue['article']}{$orderValue['title_full']} прибыл на пункт выдачи по адресу: {$issueInfo['adres']}"
        ];
        self::getInstance()->sendMessage($query);
    }
}