<?php
namespace core\Messengers;

use core\Config;
use core\Database;
use core\Exceptions\Telegram\NotAllowedIP;
use core\OrderValue;
use core\Provider;
use core\User;

class Telegram{
    /**
     * @throws NotAllowedIP
     */
    public function __construct($ip = null){
        /*if ($ip && !in_array($ip, Config::$telegram['allowed_ip'])){
            throw new NotAllowedIP('Запрещенный ip сервера');
        }*/
    }

    public static function getInstance(): Telegram
    {
        static $self;
        if ($self) return $self;

        $self = new static();
        return $self;
    }

    public function query($method, $params){
        $result = Provider::getCurlUrlData("https://api.telegram.org/bot".Config::$telegram['api_key']."/$method?" . http_build_query($params));
        return json_decode($result, true);
    }

    public function setWebhook(){
        $result = $this->query('setWebhook', [
            "url" => Config::$telegram['hook']
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

    private function showAccountInfo($message){
        $telegram_id = $message['from']['id'];
        $isBound = $this->checkBoundUser($telegram_id, false);
        if (!$isBound){
            return;
        }

        $userTelegram = self::getInstance()->getUserId($telegram_id);

        $userResult = User::get(['user_id' => $userTelegram['user_id']]);
        foreach($userResult as $value) $user = $value;

        $query = [
            'chat_id' => $telegram_id,
        ];
        $designation = 'руб.';
        $text = "";
        if (in_array($user['bill_mode'], [User::BILL_MODE_CASH, User::BILL_MODE_CASH_AND_CASHLESS])){
            $text .= "<b>Наличный</b>\n";
            $text .= "Кредитный лимит: {$user['credit_limit_cash']} $designation\n";
            $text .= "Средств на счету: {$user['bill_cash']} $designation\n\n";
        }


        if (in_array($user['bill_mode'], [User::BILL_MODE_CASHLESS, User::BILL_MODE_CASH_AND_CASHLESS])){
            $text .= "<b>Безналичный</b> \n";
            $text .= "Кредитный лимит: {$user['credit_limit_cashless']} $designation\n";
            $text .= "Средств на счету: {$user['bill_cashless']} $designation\n\n";
        }

        $text .= "<b>Зарезервировано:</b> ".($user['reserved_cash'] + $user['reserved_cashless'])."\n";
        $text .= "<b>Итого:</b> {$user['bill_available']} $designation\n";
        $text .= "<b>Отсрочка платежа:</b> {$user['defermentOfPayment']} д.\n";

        $query['text'] = $text;
        $this->sendMessage($query);
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
            case '/account':
                $this->showAccountInfo($message);
                break;
            default:
                $telegram_id = $message['from']['id'];
                self::getInstance()->checkBoundUser($telegram_id);
                break;
        }
    }

    private function checkBoundUser($telegram_id, $sayHello = true): bool
    {
        $output = true;
        $query = [
            'chat_id' => $telegram_id,
            'parse_mode' => 'html',
            'text' => ''
        ];

        if ($sayHello){
            $query['text'] = "Добро пожаловать в бот Тахос!\n";
        }

        $result = self::getInstance()->getUserId($telegram_id);
        if (!$result){
            $output = false;
            $query['text'] .= "Вы еще не связали телеграм-бот со своим аккаунтом на Тахос. Нажмите кнопку \"Связать\" внизу.";
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
        return $output;
    }

    private function getTelegramId($user_id){
        $userTelegram = Database::getInstance()->select_one('user_telegram', '*', "`user_id` = {$user_id}");
        return $userTelegram;
    }

    private function getUserId($telegram_id){
        static $output;
        if (isset($output[$telegram_id])){
            return $output[$telegram_id];
        }
        $userTelegram = Database::getInstance()->select_one('user_telegram', '*', "`telegram_id` = {$telegram_id}");
        $output[$telegram_id] = $userTelegram;
        return $userTelegram;
    }

    public static function sendMessageArrived($order_id, $item_id){
        $orderInfo = OrderValue::getOrderInfo($order_id);
        if ($orderInfo['delivery'] != 'Самовывоз'){
            return;
        }

        $userTelegram = self::getInstance()->getTelegramId($orderInfo['user_id']);
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
            'text' => "Товар {$orderValue['brend']} {$orderValue['article']} {$orderValue['title_full']} прибыл на пункт выдачи по адресу: {$issueInfo['adres']}"
        ];
        self::getInstance()->sendMessage($query);
    }

    public static function sendMessageAwaitInStore($user_id, $item){
        $userTelegram = self::getInstance()->getTelegramId($user_id);
        if (!$userTelegram || !$userTelegram['telegram_id']){
            return;
        }
        self::getInstance()->sendMessage([
            'chat_id' => $userTelegram['telegram_id'],
            'text' => "Ваша заявка на возврат $item согласована. Товар ожидается на складе."
        ]);
    }

    public static function sendMessageReturnPerformed($user_id, $item, $amount){
        $userTelegram = self::getInstance()->getTelegramId($user_id);
        if (!$userTelegram || !$userTelegram['telegram_id']){
            return;
        }
        self::getInstance()->sendMessage([
            'chat_id' => $userTelegram['telegram_id'],
            'text' => "Ваша заявка на возврат $item зачтена в сумме $amount руб."
        ]);
    }
}