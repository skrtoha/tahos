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
    case 'removeEmptyOrders':
        $logger->alert('Удаление пустых заказов');
        $result = $db->query("
            SELECT
                o.id,
                COUNT(ov.order_id) as cnt
            FROM
                #orders o
            LEFT JOIN
                #orders_values ov ON ov.order_id = o.id
            GROUP by
                ov.order_id
            HAVING
                cnt = 0
        ");
        if(!$result->num_rows) break;
        $counter = 0;
        foreach($result as $row){
            $db->delete('orders', "id = {$row['id']}");
            $counter++;
        }
        $logger->alert("Удалено $counter записей");
        break;
    case 'clearAllPrices':
        $logger->alert('Полная очистка прайсов');
        Provider::clearStoresItems(false);
        break;
    case 'orderBerg':
        $logger->alert('Отправка заказа в Берг');
        $countSent = Provider\Berg::sendOrder();
        $logger->alert("Отправлено $countSent товаров");
        break;
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
                    break 2;
                }
                try{
                    $reader->open($workingFile);
                } catch(IOException $e){
                    $logger->warning("Ошибка:" . $e->getMessage());
                    $logger->info("Попытка обработки файла другим способом....");
                    parseWithPhpOffice($workingFile, $debuggingMode, $logger);
                    endSuccessfullyProccessing($price->isLogging, $logger);
                    break 2;
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
    case 'priceRossko':
        ini_set('memory_limit', '2048M');
        $logger->alert("Прайс Росско");
        $fileNames = [
            '77769_91489D6DA76B9D7A99061B9F7B18F3CE.csv' => 24,
            '77769_D27310FF6AA0D63D3D6B4B25EACB6C46.csv' => 25,
            '77769_C84E737C7E7B4C578310B631DC831E24.csv' => 258159
        ];
        $ciphers = [
            24 => 'ROSV',
            25 => 'ROSM',
            258159 => 'ROJA'
        ];
        $rossko = new core\Provider\Rossko($db);
        $imap = new core\Imap('{imap.mail.ru:993/imap/ssl}INBOX/Newsletters');
        $filename = $imap->getLastMailFrom(['from' => 'price@rossko.ru', 'name' => 'rossko_price.zip']);
        if (!$filename){
            $logger->error("Не удалось получить файл из почты.");
            throw new Exception($logger->getLastLogLine());
        }
        
        $zipArchive = new ZipArchive();
        $res = $zipArchive->open($filename);
        
        $numFiles = $zipArchive->numFiles;
        if (!$numFiles){
            $logger->error("Ошибка скачивания файла с почты");
            throw new Exception($logger->getLastLogLine());
        }
        $db->query("
			DELETE si FROM
				#store_items si
			LEFT JOIN
				#provider_stores ps ON ps.id=si.store_id
			WHERE
				ps.provider_id = " . core\Provider\Rossko::getParams()->provider_id . "
		", '');
        for ($num = 0; $num < $numFiles; $num++){
            $zipFile = $zipArchive->statIndex($num);
            $store_id = $fileNames[$zipFile['name']];
            if (!$store_id) {
                $logger->error("Неизвестное имя файла {$zipFile['name']}");
                throw new Exception($logger->getLastLogLine());
            }
            $emailPrice = [
                'isAddBrend' => 0,
                'isAddItem' => 0,
                'title' => 'price_'.$ciphers[$store_id],
                'isLogging' => true
            ];
            $price = new core\Price($db, $emailPrice);
            $file = $zipArchive->getStream($zipFile['name']);
            $i = 0;
            while ($data = fgetcsv($file, 1000, "\n")) {
                $row = iconv('windows-1251', 'utf-8', $data[0]);
                $row = explode(';', str_replace('"', '', $row));
                $i++;
                if (substr($row[0], 0, 3) != 'NSI') continue;
                
                if (!$row[1] || !$row[2]){
                    $price->setLog('error', "В строке $i произошла ошибка.");
                    continue;
                }
                $brend_id = $price->getBrendId($row[1]);
                if (!$brend_id) continue;
                $item_id = $price->getItemId([
                    'brend_id' => $brend_id,
                    'brend' => $row[1],
                    'article' => $row[10] ? $row[10] : $row[2],
                    'title' => $row[3],
                    'row' => $i
                ]);
                if (!$item_id) continue;
                $price->insertStoreItem([
                    'store_id' => $store_id,
                    'item_id' => $item_id,
                    'price' => $row[6],
                    'in_stock' => $row[8],
                    'packaging' => $row[5],
                    'row' => $i
                ]);
            }
            
            $price->setLog('alert',"Обработано $i строк");
            $price->setLog('alert',"Добавлено в прайс: $price->insertedStoreItems записей");
            $price->setLog('alert',"Вставлено: $price->insertedBrends брендов");
            $price->setLog('alert',"Вставлено: $price->insertedItems номенклатуры");
            
            $logger->alert($ciphers[$store_id]);
            $logger->alert("Обработано $i строк");
            $logger->alert("Добавлено в прайс: $price->insertedStoreItems записей");
            $logger->alert("Вставлено: $price->insertedBrends брендов");
            $logger->alert("Вставлено: $price->insertedItems номенклатуры");
            if ($price->isLogging){
                $logger->alert("Полный лог: $price->nameFileLog");
            }
        }
        Provider::updatePriceUpdated(['provider_id' => core\Provider\Rossko::getParams()->provider_id]);
        break;
    case 'BERG_GREB': //Прайс Москва
    case 'BERG_GERY': //Прайс Ярославль
    case 'BERG_2021': //Прайс Москва2
        ini_set('memory_limit', '2048M');
        $logger->alert("Прайс {$params[0]}");
        $emailPrice = [
            'isAddBrend' => 0,
            'isAddItem' => 0,
            'title' => $params[0],
            'isLogging' => true
        ];
        
        $price = new core\Price($db, $emailPrice);
        
        $imap = new core\Imap('{imap.mail.ru:993/imap/ssl}INBOX/Newsletters');
        $filename = $imap->getLastMailFrom(['from' => 'noreply@berg.ru', 'name' => $params[0]]);
        if (!$filename){
            $logger->alert("Не удалось получить файл из почты.");
            break;
        }
        $handle = fopen($filename, 'r');
        
        if ($params[0] == 'BERG_GREB') $store_id = 275;
        if ($params[0] == 'BERG_GERY') $store_id = 276;
        if ($params[0] == 'BERG_2021') $store_id = 337952;
        $db->delete('store_items', "`store_id`=$store_id");
        $i = 0;
        while ($data = fgetcsv($handle, 1000, "\n")) {
            $row = iconv('windows-1251', 'utf-8', $data[0]);
            $row = explode(';', str_replace('"', '', $row));
            $i++;
            if ($row[0] == 'Артикул') continue;
            // if ($i > 200) break;
            // debug($row); continue;
            if (!$row[0] || !$row[2]){
                $price->setLog('error', "В строке $i произошла ошибка.");
                continue;
            }
            $brend_id = $price->getBrendId($row[2]);
            if (!$brend_id) continue;
            $item_id = $price->getItemId([
                'brend_id' => $brend_id,
                'brend' => $row[2],
                'article' => $row[0],
                'title' => $row[1],
                'row' => $i
            ]);
            if (!$item_id) continue;
            $price->insertStoreItem([
                'store_id' => $store_id,
                'item_id' => $item_id,
                'price' => $row[5],
                'in_stock' => $row[4],
                'packaging' => $row[7],
                'row' => $i
            ]);
        }
        Provider::updatePriceUpdated(['store_id' => $store_id]);
        
        $price->setLog('alert', "Обработано $i строк");
        $price->setLog('alert', "Добавлено в прайс: $price->insertedStoreItems записей");
        $price->setLog('alert', "Вставлено: $price->insertedBrends брендов");
        $price->setLog('alert', "Вставлено: $price->insertedItems номенклатуры");
        
        $logger->alert("Обработано $i строк");
        $logger->alert("Добавлено в прайс: $price->insertedStoreItems записей");
        $logger->alert("Вставлено: $price->insertedBrends брендов");
        $logger->alert("Вставлено: $price->insertedItems номенклатуры");
        if ($price->isLogging){
            $logger->alert("Полный лог: $price->nameFileLog");
        }
        break;
    case 'priceMikado':
        ini_set('memory_limit', '2048M');
        $mikado = new core\Provider\Mikado();
        $files = [
            'MikadoStock' => [1],
            'MikadoStockReg' => [10, 35, 135, 43, 51, 50]
        ];
        $stocks = Provider\Mikado::getStocks();
        foreach($files as $zipName => $valuesList){
            foreach($valuesList as $value){
                $storeInfo = Provider::getStoreInfo($stocks[$value]);
                $logger->alert("Прайс {$storeInfo['cipher']}");
                $emailPrice = [
                    'isAddBrend' => 0,
                    'isAddItem' => 0,
                    'title' => $storeInfo['cipher'],
                    'isLogging' => true
                ];
                $price = new core\Price($db, $emailPrice);
                if ($value == 0) $url = 'https://mikado-parts.ru/office/GetFile.asp?File=MikadoStock.zip';
                else $url = "https://mikado-parts.ru/office/GetFile.asp?File=MikadoStockReg.zip&regID=$value";
                $url .= "&CLID=" . Provider\Mikado::getParams('entity')->ClientID . "&PSW=" . Provider\Mikado::getParams('entity')->Password;
                $file = file_get_contents($url);
                if (strlen($file) == 18){
                    $logger->error("Не удалось скачать $zipName в $url");
                    continue;
                }
                $resDownload = (
                file_put_contents(
                    core\Config::$tmpFolderPath . "/{$storeInfo['cipher']}.zip",
                    $file
                )
                );
    
                $zipArchive = new ZipArchive();
                $res = $zipArchive->open(core\Config::$tmpFolderPath . "/{$storeInfo['cipher']}.zip");
                $file = $zipArchive->getStream("mikado_price_{$value}.csv");
                
                $db->delete('store_items', "`store_id`=" .$stocks[$value]);
    
                $i = 0;
                while ($data = fgetcsv($file, 1000, "\n")) {
                    $row = iconv('windows-1251', 'utf-8', $data[0]);
                    $row = explode(';', str_replace('"', '', $row));
                    $i++;
                    if (!$row[1] || !$row[2]){
                        $price->setLog('error', "В строке $i произошла ошибка.");
                        continue;
                    }
                    if (preg_match('/УЦЕНКА/ui', $row[3])) continue;
                    $brend_id = $price->getBrendId($row[2]);
                    if (!$brend_id) continue;
                    $item_id = $price->getItemId([
                        'brend_id' => $brend_id,
                        'brend' => $row[2],
                        'article' => $row[1],
                        'title' => $row[3],
                        'row' => $i
                    ]);
                    if (!$item_id) continue;
                    $price->insertStoreItem([
                        'store_id' => $stocks[$value],
                        'item_id' => $item_id,
                        'price' => $row[4],
                        'in_stock' => $row[5],
                        'packaging' => 1,
                        'row' => $i
                    ]);
                }
    
                Provider::updatePriceUpdated(['store_id' => $stocks[$value]]);
    
                $price->setLog('alert', "Обработано $i строк");
                $price->setLog('alert', "Добавлено в прайс: $price->insertedStoreItems записей");
                $price->setLog('alert', "Вставлено: $price->insertedBrends брендов");
                $price->setLog('alert', "Вставлено: $price->insertedItems номенклатуры");
    
                $logger->alert($stocks[$value]);
                $logger->alert("Обработано $i строк");
                $logger->alert("Добавлено в прайс: $price->insertedStoreItems записей");
                $logger->alert("Вставлено: $price->insertedBrends брендов");
                $logger->alert("Вставлено: $price->insertedItems номенклатуры");
                if ($price->isLogging){
                    $logger->alert("Полный лог: $price->nameFileLog");
                }
            }
            
        }
        break;
    case 'subscribeUserPrices':
        $logger->alert('Рассылка прайсов');
        $successedDelivery = 0;
        $res_users = $db->query("
			SELECT
				u.id,
				u.discount,
				u.currency_id,
				u.subscribe_type,
				u.subscribe_email
			FROM
				#users u
			WHERE
				u.is_subscribe = 1
		", '');
        if (!$res_users->num_rows){
            $logger->info('Не найдено пользователей для рассылки');
            break;
        }
        
        $res_store_items = core\StoreItem::getStoreItemsByStoreID([core\Provider\Tahos::$store_id]);
        foreach($res_users as $user){
            // debug($user); exit();
            switch($user['subscribe_type']){
                case 'xls':
                    $file = core\Provider\Tahos::processExcelFileForSubscribePrices($res_store_items, 'user_price', $user['discount']);
                    break;
                case 'csv':
                    $file = core\Config::$tmpFolderPath . '/price.csv';
                    $fp = fopen($file, 'w');
                    foreach($res_store_items as $si){
                        $si['price'] = ceil($si['price'] - $si['price'] * $user['discount'] / 100);
                        fputcsv($fp, $si, ';');
                    }
                    fclose($fp);
                    break;
            }
            $res = core\Mailer::send([
                'emails' => $user['subscribe_email'],
                'subject' => 'Прайс с tahos.ru',
                'body' => 'Прайс с tahos.ru'
            ], [$file]);
            if ($res === true) $successedDelivery++;
        }
        $logger->alert("Всего отпрвлено $successedDelivery сообщений пользователям");
        break;
    case 'priceSportAvto':
        ini_set('memory_limit', '2048M');
        $logger->alert("Прайс Спорт-Авто");
        $emailPrice = [
            'isAddBrend' => 0,
            'isAddItem' => 0,
            'title' => 'priceSportAvto',
            'isLogging' => true
        ];
        
        $price = new core\Price($db, $emailPrice);
        
        $imap = new core\Imap('{imap.mail.ru:993/imap/ssl}INBOX/Newsletters');
        if ($imap->error) {
            $logger->error("Подключение не удалось");
            break;
        }
        $filename = $imap->getLastMailFrom(['from' => 'zakaz@sportavto.com', 'name' => 'price.zip']);
        if (!$filename){
            $textError = "Не удалось получить файл из почты.";
            echo "<br>$textError";
            throw new Exception($textError);
        }
        
        $zipArchive = new ZipArchive();
        $res = $zipArchive->open($filename);
        $file = $zipArchive->getStream('price.csv');
        
        $db->delete('store_items', "`store_id`=7");
        $i = 0;
        while ($data = fgetcsv($file, 1000, "\n")) {
            $row = iconv('windows-1251', 'utf-8', $data[0]);
            $row = explode(';', str_replace('"', '', $row));
            $i++;
            if ($row[0] == 'Код') continue;
            if (!$row[0] || !$row[1]){
                $price->setLog('error', "В строке $i произошла ошибка.");
                continue;
            }
            $brend_id = $price->getBrendId($row[1]);
            if (!$brend_id) continue;
            $item_id = $price->getItemId([
                'brend_id' => $brend_id,
                'brend' => $row[1],
                'article' => $row[0],
                'title' => $row[2],
                'row' => $i
            ]);
            if (!$item_id) continue;
            $price->insertStoreItem([
                'store_id' => 7,
                'item_id' => $item_id,
                'price' => $row[3],
                'in_stock' => $row[4],
                'packaging' => 1,
                'row' => $i
            ]);
        }
        
        Provider::updatePriceUpdated(['store_id' => 7]);
        
        $price->setLog('alert', "Обработано $i строк");
        $price->setLog('alert', "Добавлено в прайс: $price->insertedStoreItems записей");
        $price->setLog('alert', "Вставлено: $price->insertedBrends брендов");
        $price->setLog('alert', "Вставлено: $price->insertedItems номенклатуры");
    
        $logger->alert("Обработано $i строк");
        $logger->alert("Добавлено в прайс: $price->insertedStoreItems записей");
        $logger->alert("Вставлено: $price->insertedBrends брендов");
        $logger->alert("Вставлено: $price->insertedItems номенклатуры");
        if ($price->isLogging){
            $logger->alert("Полный лог: $price->nameFileLog");
        }
        
        break;
}
$logger->alert('----------КОНЕЦ-------------');
