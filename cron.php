<?php 
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$self = pathinfo(__FILE__, PATHINFO_BASENAME);
$document_root = rtrim(str_replace($self, '', __FILE__), '/');
$document_root = substr($document_root, 0, -1);
$_SERVER['DOCUMENT_ROOT'] = $document_root;

require_once($_SERVER['DOCUMENT_ROOT'].'/core/Database.php');
require_once ($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');
$db = new core\Database();

$date = new DateTime();
$logger = new \Katzgrau\KLogger\Logger(
    $_SERVER['DOCUMENT_ROOT'].'/admin/logs',
    \Psr\Log\LogLevel::ALERT,
    [
        'filename' => 'common_'.$date->format('d.m.Y'),
        'dateFormat' => 'G:i:s'
    ]
);

switch ($_SERVER['argv'][1]){
    case 'orderRossko':
        $logger->alert('Отправка заказа в Росско');
        core\Provider\Rossko::sendOrder();
        break;
    case 'orderForumAuto':
        $logger->alert('Отправка заказа в Форум-Авто');
        core\Provider\ForumAuto::sendOrder();
        break;
    case 'orderVoshod':
        $logger->alert('Отправка заказа в Восход');
        core\Provider\Abcp::sendOrder(6);
        break;
    case 'orderMparts':
        $logger->alert('Отправка заказа в МПартс');
        core\Provider\Abcp::sendOrder(13);
        break;
    case 'orderFavoriteParts':
        $logger->alert('Отправка заказа Фаворит Партс');
        $res = core\Provider\FavoriteParts::toOrder();
        if ($res === false) $logger->alert("Нет товаров для отправки");
        else $logger->warning($res);
        break;
    case 'orderAutoeuro':
        $logger->alert('Отправка заказа Автоевро');
        core\Provider\Autoeuro::sendOrder();
        break;
    case 'orderAutokontinent':
        $logger->alert('Отправка заказа Автоконтинент');
        core\Provider\Autokontinent::sendOrder();
        break;
    case 'orderAutopiter':
        $logger->alert('Отправка заказа Автопитер');
        core\Provider\Autopiter::sendOrder();
        break;
    case 'orderArmtek':
        $logger->alert('Отправка заказа в Армтек');
        core\Provider\Armtek::sendOrder();
        break;
}
$logger->alert('Обработка '.$_SERVER['argv'][1].' закончена');
