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
    case 'updateCurrencies':
        $logger->alert('Обновление валют');
        $date = date("d/m/Y");
        $link = "http://www.cbr.ru/scripts/XML_daily.asp?date_req=$date";
        $content = file_get_contents($link);
        $dom = new domDocument("1.0", "cp1251");
        $dom->loadXML($content);
        $root = $dom->documentElement;
        $childs = $root->childNodes;
        $data = array('USD', 'EUR', 'JPY', 'CNY', 'UAH');
        for ($i = 0; $i < $childs->length; $i++) {
            $childs_new = $childs->item($i)->childNodes;
            for ($j = 0; $j < $childs_new->length; $j++) {
                $el = $childs_new->item($j);
                $code = $el->nodeValue;
                if (in_array($code, $data)) $data[] = $childs_new;
            }
        }
        for ($i = 0; $i < count($data); $i++) {
            $list = $data[$i];
            for ($j = 0; $j < $list->length; $j++) {
                $el = $list->item($j);
                if ($el->nodeName == "CharCode") $charcode = $el->nodeValue;
                elseif ($el->nodeName == "Value") $value = $el->nodeValue;
                if ($value and $charcode) $currencies[$charcode] = str_replace(',', '.', $value);
            }
        }
        foreach ($currencies as $charcode => $value){
            switch($charcode){
                case 'UAH': $val = $value/10; break;
                case 'JPY': $val = $value / 100; break;
                default: $val = $value;
            }
            $db->update('currencies', array('rate' => $val), "`charcode`='$charcode'");
        }
        break;
}
$logger->alert('Обработка '.$_SERVER['argv'][1].' закончена');
