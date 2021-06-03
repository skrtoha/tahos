<?php

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use core\Provider;
use core\Setting;
use Katzgrau\KLogger\Logger;
use Psr\Log\LogLevel;

ini_set('error_reporting', E_ERROR);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$self = pathinfo(__FILE__, PATHINFO_BASENAME);
$document_root = rtrim(str_replace($self, '', __FILE__), '/');
$document_root = substr($document_root, 0, -1);
$_SERVER['DOCUMENT_ROOT'] = $document_root;

require_once($_SERVER['DOCUMENT_ROOT'].'/core/Database.php');
require_once ($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');
$db = new core\Database();


$params = [];
$counter = 0;
if (!empty($_GET)){
    foreach($_GET as $value){
        $params[$counter] = $value;
        $counter++;
    }
}
else{
    for($i = 0; $i < count($_SERVER['argv']); $i++){
        if ($i == 0) continue;
        $params[$counter] = $_SERVER['argv'][$i];
        $counter++;
    }
}

$date = new DateTime();
$logger = new Logger(
    $_SERVER['DOCUMENT_ROOT'].'/admin/logs',
    LogLevel::DEBUG,
    [
        'filename' => 'common_'.$date->format('d.m.Y'),
        'dateFormat' => 'G:i:s',
        'logFormat' => '[{date}] {message}'
    ]
);
$logger->alert('----------СТАРТ-------------');

switch ($params[0]){
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
        $dateTime = new DateTime();
        Setting::update('currency', 'dateUpdate', $dateTime->format('d.m.Y H:i:s'));
        break;
    case 'updatePrices':
        $logger->alert('Обновление цен');
        $db->delete('prices', "item_id > 0");
        $res = $db->query("
			SELECT
				ci.item_id,
				MIN(
					FLOOR (si.price + si.price * ps.percent / 100)
				) AS price,
				MIN(ps.delivery) AS delivery
			FROM
				#categories_items ci
			LEFT JOIN
				#store_items si ON si.item_id = ci.item_id
			LEFT JOIN
				#provider_stores ps ON ps.id = si.store_id
			WHERE
				si.price IS NOT null
			GROUP BY
				ci.item_id
		", '');
        if (!$res->num_rows) return true;
        foreach($res as $item) $db->insert('prices', $item);
        break;
    case 'clearStores':
        $db->query("
			DELETE si FROM #store_items si
			LEFT JOIN
				#provider_stores ps ON ps.id = si.store_id
			WHERE
				ps.is_main = 0
		",'');
        $mysqli = $db->get_mysqli();
        $logger->info("Удалено {$mysqli->affected_rows} строк.");
        break;
    case 'emailPrice':
        ini_set('memory_limit', '2048M');
        $store_id = $params[1];
        $debuggingMode = false;
        require_once($_SERVER['DOCUMENT_ROOT'].'/admin/functions/providers.function.php');
        $emailPrice = $db->select_one('email_prices', '*', "`store_id`={$store_id}");
        $emailPrice = json_decode($emailPrice['settings'], true);
        $price = new core\Price($db, $emailPrice);
        $price->store_id = $store_id;
        if ($debuggingMode) debug($emailPrice, 'emailPrice');
        $store = $db->select_unique("
			SELECT
				ps.id AS store_id,
				ps.title AS store,
				ps.provider_id,
				p.title AS provider
			FROM
				#provider_stores ps
			LEFT JOIN
				#providers p ON p.id = ps.provider_id
			WHERE
				ps.id = {$price->store_id}
		");
        $store = $store[0];
    
        $logger->alert("Прайс {$emailPrice['title']}");
    
        $imap = new core\Imap('{imap.mail.ru:993/imap/ssl}INBOX/Newsletters');
        $fileImap = $imap->getLastMailFrom([
            'from' => $emailPrice['from'],
            'name' => $emailPrice['name']
        ], $debuggingMode);
        if ($debuggingMode) debug($fileImap, 'fileImap');
        if (!$fileImap){
            $errorText = "Не удалось скачать {$emailPrice['name']} из почты.";
            $logger->error($errorText);
            break;
        }
    
        switch($emailPrice['clearPrice']){
            case 'onlyStore': $db->delete('store_items', "`store_id`={$price->store_id}"); break;
            case 'provider': $db->query("
				DELETE si FROM
					#store_items si
				LEFT JOIN
					#provider_stores ps ON ps.id=si.store_id
				WHERE
					ps.provider_id = {$store['provider_id']}
			", '');
                break;
        }
    
        //добавлено специально для Армтек, чтобы если грузится ARMK, то очищаются
        //все склады, кроме ARMC
        if ($store['id'] == 4) $db->query("
				DELETE si FROM
					#store_items si
				LEFT JOIN
					#provider_stores ps ON ps.id = si.store_id
				WHERE
					ps.provider_id = 2 AND si.store_id != 3
			", '');
    
        if ($emailPrice['isArchive']){
            $zipArchive = new ZipArchive();
            $res = $zipArchive->open($fileImap);
            if (!$res){
                $errorText = "Ошибка чтения файла {$emailPrice['name']}";
                $logger->error($errorText);
                break;
            }
            try{
                $nameInArchive = $emailPrice['nameInArchive'];
                $res = $zipArchive->extractTo(core\Config::$tmpFolderPath . "/", [$nameInArchive]);
                if (!$res){
                    throw new Exception ("Ошибка извлечения файла {$emailPrice['nameInArchive']}. Попытка использовать альтернативный способ.");
                }
            } catch(Exception $e){
                $logger->warning($e->getMessage());
                if ($emailPrice['indexInArchive'] == '0' || $emailPrice['indexInArchive']){
                    $logger->info("обработка через indexInArchive");
                    $nameInArchive = $zipArchive->getNameIndex($emailPrice['indexInArchive']);
                    $bites = file_put_contents(core\Config::$tmpFolderPath . "/$nameInArchive", $zipArchive->getFromIndex($emailPrice['indexInArchive']));
                    if (!$bites){
                        $logger->error("Возникла ошибка. Ни один из способов извлечь архив не сработали");
                        throw new Exception($logger->getLastLogLine());
                    }
                }
                else{
                    $zip_count = $zipArchive->count();
                    if (!$zip_count){
                        $logger->error("Альтернативный способ не сработал - ошибка получения количества файлов в архиве");
                        throw new Exception($logger->getLastLogLine());
                    }
                    for ($i = 0; $i < $zip_count; $i++) {
                        $logger->info("Индекс $i: ". $zipArchive->getNameIndex($i));
                    };
                    $logger->info("Укажите настройках в поле \"Индекс файла в архиве\" необходимый индекс.");
                }
            }
            if ($emailPrice['fileType'] == 'excel') $workingFile = core\Config::$tmpFolderPath . "/$nameInArchive";
            else $workingFile = $zipArchive->getStream($nameInArchive);
        }
        else $workingFile = $fileImap;
    
        if ($debuggingMode) debug($workingFile, 'workingFile');
    
        /**
         * [$stringNumber counter for strings in file]
         * @var integer
         */
        $stringNumber = 0;
        switch($emailPrice['fileType']){
            case 'excel':
                try{
                    require_once ($_SERVER['DOCUMENT_ROOT']) . '/vendor/autoload.php';
                    $reader = ReaderEntityFactory::createReaderFromFile($workingFile);
                }
                catch(UnsupportedTypeException $e){
                    $logger->warning($e->getMessage());
                    $logger->info("Обработка с помощью PhpOffice...");
                    parseWithPhpOffice($workingFile, $debuggingMode, $logger);
                    endSuccessfullyProccessing($price->isLogging, $logger);
                    break;
                }
                try{
                    $reader->open($workingFile);
                } catch(IOException $e){
                    $logger->warning("Ошибка:" . $e->getMessage());
                    $logger->info("Попытка обработки файла другим способом....");
                    parseWithPhpOffice($workingFile, $debuggingMode, $logger);
                    endSuccessfullyProccessing($price->isLogging, $logger);
                }
                foreach ($reader->getSheetIterator() as $sheet) {
                    foreach ($sheet->getRowIterator() as $iterator) {
                        $cells = $iterator->getCells();
                        $row = [];
                        foreach($cells as $value) $row[] = $value->getValue();
                        $stringNumber++;
                    
                        if ($debuggingMode){
                            debug($row);
                            if ($stringNumber > 100){
                                $logger->alert("Обработка прошла");
                                break 3;
                            }
                        }
                        parse_row($row, $emailPrice['fields'], $price, $stringNumber);
                    }
                }
                break;
            case 'csv':
                while ($data = fgetcsv($workingFile, 1000, "\n")) {
                    $row = iconv('windows-1251', 'utf-8', $data[0]);
                    $row = explode(';', str_replace('"', '', $row));
                    $stringNumber++;
                    if ($debuggingMode){
                        debug($row);
                        if ($stringNumber > 100){
                            $logger->alert("Обработка прошла");
                            break 3;
                        }
                    }
                    parse_row($row, $emailPrice['fields'], $price, $stringNumber);
                }
                break;
        }
    
        switch($emailPrice['clearPrice']){
            case 'onlyStore': Provider::updatePriceUpdated(['store_id' => $price->store_id]); break;
            case 'provider': Provider::updatePriceUpdated(['provider_id' => $_GET['provider_id']]); break;
                break;
        }
    
        endSuccessfullyProccessing($price->isLogging, $logger);
        break;
}
$logger->alert('Обработка '.$params[0].' закончена');
$logger->alert('----------КОНЕЦ-------------');
