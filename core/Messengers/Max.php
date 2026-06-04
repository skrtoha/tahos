<?php
namespace core\Messengers;
use core\Database;
use core\OrderValue;
use core\Provider;
use core\User;

class Max extends Messengers {
    private static $token = 'f9LHodD0cOJRSB4VJPG3SACaTR3yHB-X-baA_4lh1cL_6QWvYe9sQKOlNs6rcMFMY671LEXB-7W08xbL19Dg';
    private static $uri = 'https://platform-api.max.ru/';
    public function __construct() {}

    public function parseMessage($message) {
        $vcf_info = $message['message']['body']['attachments'][0]['payload']['vcf_info'];
        if ($vcf_info) {
            self::getInstance()->bindUser($vcf_info, $message['message']['sender']['user_id']);
            return;
        }
        switch($message['update_type']) {
            case 'bot_started':
                self::getInstance()->registerUser($message['user_id']);
                break;
            case 'bot_stopped':
                self::getInstance()->unbindUser($message['user']['user_id']);

        }
    }

    private function unbindUser($user_id){
        Database::getInstance()->delete('user_max', 'max_user_id = '.$user_id);
    }

    public function bindUser($vcf_info, $user_id): void
    {
        $matches = [];
        preg_match("/\d{11}/", $vcf_info, $matches);
        $tel = '+'.$matches[0];

        $userIdDatabase = Database::getInstance()->getField('users', 'id', 'phone', $tel);
        if (!$userIdDatabase) {
            self::sendMessages($user_id, [
                'text' => 'Пользователь с таким номером телефона не найден!'
            ]);
            return;
        }

        Database::getInstance()->insert('user_max', [
            'user_id' => $userIdDatabase,
            'max_user_id' => $user_id
        ]);
        self::sendMessages($user_id, [
            'text' => 'Аккаунт успешно привязан!'
        ]);
    }

    public function sendMessages($userId, $params) {
        return self::getInstance()->query('messages?user_id='.$userId, $params);
    }

    public function registerUser($userId) {
        self::sendMessages($userId, [
            'text' => 'Добро пожаловать в чат-бот Тахос!',
            'attachments' => [
                [
                    'type' => 'inline_keyboard',
                    'payload' => [
                        'buttons' => [
                            [
                                [
                                    'type' => 'request_contact',
                                    'text' => 'Привязать аккаунт'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    }
    public static function getInstance(): Max
    {
        static $self;
        if ($self) return $self;

        $self = new static();
        return $self;
    }

    public function query($method, $params){
        $headers = [
            'Authorization' => self::$token,
        ];
        return Provider::getCurlUrlData(self::$uri.$method, json_encode($params), $headers);
    }

    public static function sendMessageAwaitInStore($user_id, $item){
        $userMax = self::getInstance()->getMaxId($user_id);
        if (!$userMax || !$userMax['max_user_id']) {
            return;
        }
        self::getInstance()->sendMessages($userMax['max_user_id'], [
            'text' => "Ваша заявка на возврат $item согласована. Товар ожидается на складе."
        ]);
    }

    public static function sendMessageArrived($order_id, $item_id){
        $orderInfo = OrderValue::getOrderInfo($order_id);
        if ($orderInfo['delivery'] != 'Самовывоз'){
            return;
        }

        $userMax = self::getInstance()->getMaxId($orderInfo['user_id']);
        if (!$userMax || !$userMax['max_user_id']) {
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
        $params = [
            'text' => "Товар {$orderValue['brend']} {$orderValue['article']} {$orderValue['title_full']} прибыл на пункт выдачи по адресу: {$issueInfo['adres']}"
        ];
        self::getInstance()->sendMessages($userMax['max_user_id'], $params);
    }

    public static function sendMessageProviderRefuse($user_id, $message){
        $userMax = self::getInstance()->getMaxId($user_id);
        if (!$userMax || !$userMax['max_user_id']) {
            return;
        }
        self::getInstance()->sendMessages($userMax['max_user_id'], [
            'text' => $message
        ]);
    }

    public function getMaxId($user_id){
        static $output;
        if (isset($output[$user_id])){
            return $output[$user_id];
        }
        $result = Database::getInstance()->select_one('user_max', '*', "`user_id` = {$user_id}");
        $output[$user_id] = $result;
        return $result;
    }

    public static function sendMessageReturnPerformed($user_id, $item, $amount){
        $userMax = self::getInstance()->getMaxId($user_id);
        if (!$userMax || !$userMax['max_user_id']) {
            return;
        }
        self::getInstance()->sendMessages($userMax['max_user_id'], [
            'text' => "Ваша заявка на возврат $item зачтена в сумме $amount руб."
        ]);
    }
}