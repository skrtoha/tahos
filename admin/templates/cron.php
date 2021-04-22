<?php
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use core\Provider\Mikado;
use core\Provider;

set_time_limit(0);
core\Timer::start();

if ($_GET['act'] != 'subscribeTahosPrice'){
	echo "<br>Начало: <b>".date("d.m.Y H:i:s")."</b>";
}
switch($_GET['act']){
	case 'bonuses':
		require_once ('../vendor/autoload.php');
		$bonus_name = 'bonus_'.date('d.m.Y_H-i-s').'.txt';
		$log = new Katzgrau\KLogger\Logger('../logs', Psr\Log\LogLevel::WARNING, array(
			'filename' => $bonus_name,
			'dateFormat' => 'G:i:s'
		));

		$res = $db->query("
			SELECT
				*
			FROM
				#funds
			WHERE
				transfered = 0 AND
				type_operation = 3 AND
				TO_DAYS(CURRENT_DATE) - TO_DAYS(`created`) >= {$settings['days_for_return']}
		", '');
		if (!$res->num_rows){
			$log->warning('Записей для зачисления бонусов не найдено');
			die();
		}
		while($row = $res->fetch_assoc()){
			$db->query("
				UPDATE
					#users
				SET
					`bonus_count`=`bonus_count`+{$row['sum']}
				WHERE
					`id`={$row['user_id']}
			", '');
			$db->query("
				UPDATE
					#funds
				SET
					`transfered`=1
				WHERE
					`id`={$row['id']}
			", '');
			$str = stripslashes($row['comment']);
			$str = strip_tags($str);
			$str = preg_replace('/\s+/', ' ', $str);
			$log->alert("$str для пользователя id={$row['user_id']} в размере {$row['sum']} руб.");
		}
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
		echo "<br>Удалено {$mysqli->affected_rows} строк.";
		break;
	case 'orderArmtek':
		echo "<h2>Отправка заказа в Армтек</h2>";
		core\Provider\Armtek::sendOrder();
		echo "<br>Обработка завершена.";
		break;
	case 'orderRossko':
		// debug($_GET); exit();
		echo "<h2>Отправка заказа в Росско</h2>";
		core\Provider\Rossko::sendOrder();
		if ($_GET['order_id']){
			header("Location: ?view=orders&act=change&id={$_GET['order_id']}");
			exit();
		} 
		echo "<br>Обработка завершена.";
		break;
	case 'orderForumAuto':
		echo "<h2>Отправка заказа в Форум-Авто</h2>";
		core\Provider\ForumAuto::sendOrder();
		echo "<br>Обработка завершена.";
		break;
	case 'orderVoshod':
		core\Provider\Abcp::sendOrder(6);
		break;
	case 'orderMparts':
		core\Provider\Abcp::sendOrder(13);
		break;
	case 'orderFavoriteParts':
		echo "<h2>Отправка заказа Фаворит</h2>";
		$res = core\Provider\FavoriteParts::toOrder();
		if ($res === false) echo "<p>Нет товаров для отправки</p>";
		else echo "<p>$res</p>";
		break;
	case 'orderAutoeuro':
		core\Provider\Autoeuro::sendOrder();
		break;
	case 'orderAutokontinent':
		core\Provider\Autokontinent::sendOrder();
		break;
	case 'orderAutopiter':
		core\Provider\Autopiter::sendOrder();
		break;
	case 'getItemsVoshod':
		$abcp = new core\Provider\Abcp(NULL, $db);
		$countTransaction = 50;
		$seconds = 21600;
        break;
	case 'BERG_MSK':
	case 'BERG_Yar':
		echo "<h2>Прайс {$_GET['act']}</h2>";
        $emailPrice = [
            'isAddBrend' => 0,
            'isAddItem' => 0,
            'title' => $_GET['act'],
            'isLogging' => true
        ];
		
		$price = new core\Price($db, $emailPrice);

		$imap = new core\Imap('{imap.mail.ru:993/imap/ssl}INBOX/Newsletters');
		$filename = $imap->getLastMailFrom(['from' => 'noreply@berg.ru', 'name' => $_GET['act']]);
		if (!$filename){
			$errorText = "Не удалось получить файл из почты.";
			echo "<br>$errorText";
			throw new Exception($errorText);
		} 
		$handle = fopen($filename, 'r');

		if ($_GET['act'] == 'BERG_Yar') $store_id = 276;
		if ($_GET['act'] == 'BERG_MSK') $store_id = 275;
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

		echo "<br>Обработано <b>$i</b> строк";
		echo "<br>Добавлено в прайс: <b>$price->insertedStoreItems</b> записей";
		echo "<br>Вставлено: <b>$price->insertedBrends</b> брендов";
		echo "<br>Вставлено: <b>$price->insertedItems</b> номенклатуры";
		echo "<br><a target='_blank' href='/admin/logs/$price->nameFileLog'>Лог</a>";
		break;
	case 'priceRossko':

		echo "<h2>Прайс Росско</h2>";
		$fileNames = [
			'77769_91489D6DA76B9D7A99061B9F7B18F3CE.csv' => 24,
			'77769_D27310FF6AA0D63D3D6B4B25EACB6C46.csv' => 25
		];
		$ciphers = [
			24 => 'ROSV',
			25 => 'ROSM'
		];
		$rossko = new core\Provider\Rossko($db);
		$imap = new core\Imap('{imap.mail.ru:993/imap/ssl}INBOX/Newsletters');
		$filename = $imap->getLastMailFrom(['from' => 'price@rossko.ru', 'name' => 'rossko_price.zip']);
		if (!$filename){
			$errorText = "Не удалось получить файл из почты.";
			echo "<br>$errorText";
			throw new Exception($errorText);
		} 
		
		$zipArchive = new ZipArchive();
		$res = $zipArchive->open($filename);

		$numFiles = $zipArchive->numFiles;
		if (!$numFiles){
			$errorText = "Ошибка скачивания файла с почты";
			echo "<br>$errorText";
			throw new Exception($errorText);
			break;
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
				$errorText = "Неизвестное имя файла {$zipFile['name']}";
				echo "<br>$errorText";
				throw new Exception($errorText);
				continue;
			}
            $emailPrice = [
                'isAddBrend' => 0,
                'isAddItem' => 0,
                'title' => 'price_'.$ciphers[$store_id],
                'isLogging' => true
            ];
			$price = new core\Price($db, $emailPrice);
			// $price->isInsertBrend = true;
			// $price->isInsertItem = true;
			$file = $zipArchive->getStream($zipFile['name']);
			$i = 0;
			while ($data = fgetcsv($file, 1000, "\n")) {
				$row = iconv('windows-1251', 'utf-8', $data[0]);
				$row = explode(';', str_replace('"', '', $row));
				$i++;
				if (substr($row[0], 0, 3) != 'NSI') continue;

				// debug($row);
				// if ($i > 100) break;
				// continue;

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
			
			echo "<br><b>{$ciphers[$store_id]}:</b>";
			echo "<br>Обработано <b>$i</b> строк";
			echo "<br>Добавлено в прайс: <b>$price->insertedStoreItems</b> записей";
			echo "<br>Вставлено: <b>$price->insertedBrends</b> брендов";
			echo "<br>Вставлено: <b>$price->insertedItems</b> номенклатуры";
			if ($price->isLogging){
                echo "<br><a target='_blank' href='/admin/logs/$price->nameFileLog'>Лог</a>";
            }
		}
		Provider::updatePriceUpdated(['provider_id' => core\Provider\Rossko::getParams()->provider_id]);
		break;
	case 'priceVoshod':
		echo "<h2>Прайс Восход</h2>";
        $emailPrice = [
            'isAddBrend' => 0,
            'isAddItem' => 0,
            'title' => 'priceVoshod',
            'isLogging' => true
        ];
		$price = new core\Price($db, $emailPrice);

		$imap = new core\Imap('{imap.mail.ru:993/imap/ssl}INBOX/Newsletters');
		$filename = $imap->getLastMailFrom(['from' => 'price@voshod-avto.ru', 'name' => 'Voshod.zip']);
		if (!$filename) die("<br>Не удалось получить файл из почты.");
		
		$zipArchive = new ZipArchive();
		$res = $zipArchive->open($filename);
		$file = $zipArchive->getStream('Voshod.csv');

		if (!$file){
			$errorText = "Ошибка скачивания файла Voshod.zip с почты.";
			echo "<br>$errorText";
			throw new Exception($errorText);
		} 

		$db->delete('store_items', "`store_id`=8");
		$i = 0;
		while ($data = fgetcsv($file, 1000, "\n")) {
			$row = iconv('windows-1251', 'utf-8', $data[0]);
			$row = explode(';', str_replace('"', '', $row));
			$i++;
			if ($row[0] == 'НаименованиеПолное') continue;
			// if ($i > 200) break;
			if (!$row[1] || !$row[2]){
				$price->setLog('error', "В строке $i произошла ошибка.");
				continue;
			}
			$brend_id = $price->getBrendId($row[2]);
			if (!$brend_id) continue;
			$item_id = $price->getItemId([
				'brend_id' => $brend_id,
				'brend' => $row[2],
				'article' => $row[1],
				'title' => $row[0],
				'row' => $i
			]);
			if (!$item_id) continue;
			$price->insertStoreItem([
				'store_id' => 8,
				'item_id' => $item_id,
				'price' => $row[7],
				'in_stock' => $row[5],
				'packaging' => $row[6],
				'row' => $i
			]);
		}

		Provider::updatePriceUpdated(['store_id' => 8]);

		$price->setLog('alert',"Обработано $i строк");
        $price->setLog('alert',"Добавлено в прайс: $price->insertedStoreItems записей");
        $price->setLog('alert',"Вставлено: $price->insertedBrends брендов");
        $price->setLog('alert',"Вставлено: $price->insertedItems номенклатуры");
		
		echo "<br>Обработано <b>$i</b> строк";
		echo "<br>Добавлено в прайс: <b>$price->insertedStoreItems</b> записей";
		echo "<br>Вставлено: <b>$price->insertedBrends</b> брендов";
		echo "<br>Вставлено: <b>$price->insertedItems</b> номенклатуры";
		if ($price->isLogging){
            echo "<br><a target='_blank' href='/admin/logs/$price->nameFileLog'>Лог</a>";
        }
		break;
	case 'priceMikado':
		$mikado = new core\Provider\Mikado();
		$files = [
			'MikadoStock' => 1,
			'MikadoStockReg' => 35
		];
        
        foreach($files as $zipName => $value){
            $emailPrice = [
                'isAddBrend' => 0,
                'isAddItem' => 0,
                'title' => $zipName,
                'isLogging' => true
            ];
            $price = new core\Price($db, $emailPrice);
			$url = "http://www.mikado-parts.ru/OFFICE/GetFile.asp?File={$zipName}.zip&CLID=" . Mikado::getParams('entity')->ClientID . "&PSW=" . Mikado::getParams('entity')->Password;
			echo $url;
			$file = file_get_contents($url);
			if (strlen($file) == 18){
				$errorText = "Не удалось скачать $zipName в $url";
				echo "<br>$errorText";
				throw new Exception($errorText);
			} 
			$resDownload = (
				file_put_contents(
					core\Config::$tmpFolderPath . "/{$zipName}.zip", 
					$file
				)
			);
			
			$zipArchive = new ZipArchive();
			$res = $zipArchive->open(core\Config::$tmpFolderPath . "/{$zipName}.zip");
			$file = $zipArchive->getStream("mikado_price_{$value}_" . Mikado::getParams()->ClientID . ".csv");

			$stocks = Mikado::getStocks();
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
				// $db->insert('mikado_zakazcode', ['item_id' => $item_id, 'ZakazCode' => $row[0]], ['print_query' => false]);
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
			
			echo "<br><b>$zipName</b>:";
			echo "<br>Обработано <b>$i</b> строк";
			echo "<br>Добавлено в прайс: <b>$price->insertedStoreItems</b> записей";
			echo "<br>Вставлено: <b>$price->insertedBrends</b> брендов";
			echo "<br>Вставлено: <b>$price->insertedItems</b> номенклатуры";
			if ($price->isLogging){
                echo "<br><a target='_blank' href='/admin/logs/$price->nameFileLog'>Лог</a>";
            }
		}
		break;
	case 'priceSportAvto':
		echo "<h2>Прайс Спорт-Авто</h2>";
        $emailPrice = [
            'isAddBrend' => 0,
            'isAddItem' => 0,
            'title' => 'priceSportAvto',
            'isLogging' => true
        ];
		
		$price = new core\Price($db, $emailPrice);

		$imap = new core\Imap('{imap.mail.ru:993/imap/ssl}INBOX/Newsletters');
		if ($imap->error) die("Подключение не удалось");
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
			// if ($i > 200) break;
			// debug($row); continue;
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
		
		echo "<br>Обработано <b>$i</b> строк";
		echo "<br>Добавлено в прайс: <b>$price->insertedStoreItems</b> записей";
		echo "<br>Вставлено: <b>$price->insertedBrends</b> брендов";
		echo "<br>Вставлено: <b>$price->insertedItems</b> номенклатуры";
		if ($price->isLogging){
            echo "<br><a target='_blank' href='/admin/logs/$price->nameFileLog'>Лог</a>";
        }
		break;
	case 'priceArmtek':
		echo "<h2>Прайс Армтек</h2>";
		$fileNames = [
			'Armtek_msk_40068974' => 3
			// ,'Armtek_CRS_40068974' => 4
		];
		$ciphers = [
			3 => 'ARMC'
			// ,4 => 'ARMK'
		];
		$armtek = new core\Provider\Armtek($db);
		$imap = new core\Imap('{imap.mail.ru:993/imap/ssl}INBOX/Newsletters');
		if (isset($imap->error)) {
			echo "$imap->error";
			break;
		}
		$zipArchive = new ZipArchive();

		foreach($fileNames as $fileName => $store_id){
            $emailPrice = [
                'isAddBrend' => 0,
                'isAddItem' => 0,
                'title' => 'price_'.$ciphers[$store_id],
                'isLogging' => true
            ];
			$price = new core\Price($db, $emailPrice);
			
			$fileImap = $imap->getLastMailFrom(['from' => 'price@armtek.ru', 'name' => $fileName]);
			if (!$fileImap){
				$textError = "Не удалось скачать $fileName с price@armtek.ru";
				echo "<br>$textError";
				throw new Exception($textError);
			}

			$db->query("
				DELETE si FROM
					#store_items si
				LEFT JOIN
					#provider_stores ps ON ps.id = si.store_id
				WHERE
					ps.provider_id = 2 AND si.store_id != 4
			", '');
			
			$res = $zipArchive->open($fileImap);
			if (!$res){
				echo "<br>Ошибка чтения файла $fileName.zip";
				continue;
			};
			$res = $zipArchive->extractTo(core\Config::$tmpFolderPath . "/", ["{$fileName}.xlsx"]);
			if (!$res){
				echo "<br>Ошибка извлечения файла $fileName.xlsx";
				continue;
			};

			$xls = \PhpOffice\PhpSpreadsheet\IOFactory::load(core\Config::$tmpFolderPath . "/$fileName.xlsx");
			$xls->setActiveSheetIndex(0);
			$sheet = $xls->getActiveSheet();
			$rowIterator = $sheet->getRowIterator();
			$i = 0;
			foreach ($rowIterator as $row) {
				$cellIterator = $row->getCellIterator();
				$row = array();
				foreach($cellIterator as $cell){
					$row[] = $cell->getCalculatedValue();
				} 
				$i++;

				// debug($row);
				// if ($i > 100) break;
				// continue;

				if ($row[0] == 'Бренд') continue;
				if (!$row[0] || !$row[1]){
					$price->setLog('error', "В строке $i произошла ошибка.");
					continue;
				}
				$brend_id = $price->getBrendId($row[0]);
				if (!$brend_id)continue;
				$item_id = $price->getItemId([
					'brend_id' => $brend_id,
					'brend' => $row[0],
					'article' => $row[4] ? $row[4] : $row[1],
					'title' => $row[2],
					'row' => $i
				]);
				if (!$item_id) continue;
				$price->insertStoreItem([
					'store_id' => $store_id,
					'item_id' => $item_id,
					'price' => $row[6],
					'in_stock' => $row[5],
					'packaging' => 1,
					'row' => $i
				]);
			}

			Provider::updatePriceUpdated(['store_id' => $store_id]);

			$price->setLog('alert',"Обработано $i строк");
            $price->setLog('alert',"Добавлено в прайс: $price->insertedStoreItems записей");
            $price->setLog('alert',"Вставлено: $price->insertedBrends брендов");
            $price->setLog('alert',"Вставлено: $price->insertedItems номенклатуры");
			
			echo "<br><b>{$ciphers[$store_id]}:</b>";
			echo "<br>Обработано <b>$i</b> строк";
			echo "<br>Добавлено в прайс: <b>$price->insertedStoreItems</b> записей";
			echo "<br>Вставлено: <b>$price->insertedBrends</b> брендов";
			echo "<br>Вставлено: <b>$price->insertedItems</b> номенклатуры";
			if ($price->isLogging){
                echo "<br><a target='_blank' href='/admin/logs/$price->nameFileLog'>Лог</a>";
            }
		}
		break;
	case 'priceMparts':
		echo "<h2>Прайс МПартс</h2>";
		$imap = new core\Imap('{imap.mail.ru:993/imap/ssl}INBOX/Newsletters');
		if (isset($imap->error)){
			echo "<br>$imap->error";
			break;
		}
		$emailPrice = [
		    'isAddBrend' => 0,
            'isAddItem' => 0,
            'title' => 'priceMparts',
            'isLogging' => true
        ];
		$price = new core\Price($db, $emailPrice);

		$fileImap = $imap->getLastMailFrom(['from' => 'price@v01.ru', 'name' => 'MPartsPrice.XLSX']);
		if (!$fileImap){
			$textError = "Не удалось получить MPartsPrice.xlsx из почты.";
			echo "<br>$textError";
			throw new Exception($textError);
			break;
		}

		$db->query("
			DELETE si FROM
				#store_items si
			LEFT JOIN
				#provider_stores ps ON ps.id=si.store_id
			WHERE 
				ps.provider_id = 13
		", '');

		$xls = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileImap);
		$xls->setActiveSheetIndex(0);
		$sheet = $xls->getActiveSheet();
		$rowIterator = $sheet->getRowIterator();
		$i = 0;
		foreach ($rowIterator as $row) {
			$cellIterator = $row->getCellIterator();
			$row = array();
			foreach($cellIterator as $cell){
				$row[] = $cell->getCalculatedValue();
			} 
			$i++;

			// debug($row);
			// if ($i > 100) break;
			// continue;

			if ($row[0] == 'Производитель') continue;
			if (!$row[0] || !$row[1]){
				$price->setLog('error', "В строке $i произошла ошибка.");
				continue;
			}
			$brend_id = $price->getBrendId($row[0]);
			if (!$brend_id) continue;
			$item_id = $price->getItemId([
				'brend_id' => $brend_id,
				'brend' => $row[0],
				'article' => $row[1],
				'title' => $row[3],
				'row' => $i
			]);
			if (!$item_id) continue;
			$price->insertStoreItem([
				'store_id' => 22,
				'item_id' => $item_id,
				'price' => $row[5],
				'in_stock' => $row[4],
				'packaging' => $row[6],
				'row' => $i
			]);
		}

		Provider::updatePriceUpdated(['provider_id' => 13]);

		$price->setLog('alert',"Обработано $i строк");
        $price->setLog('alert',"Добавлено в прайс: $price->insertedStoreItems записей");
        $price->setLog('alert',"Вставлено: $price->insertedBrends брендов");
        $price->setLog('alert',"Вставлено: $price->insertedItems номенклатуры");
		
		echo "<br>Обработано <b>$i</b> строк";
		echo "<br>Добавлено в прайс: <b>$price->insertedStoreItems</b> записей";
		echo "<br>Вставлено: <b>$price->insertedBrends</b> брендов";
		echo "<br>Вставлено: <b>$price->insertedItems</b> номенклатуры";
		if ($price->isLogging){
            echo "<br><a target='_blank' href='/admin/logs/$price->nameFileLog'>Лог</a>";
        }
		break;
	case 'priceForumAuto':
		echo "<h2>Прайс Forum-Auto</h2>";
        $emailPrice = [
            'isAddBrend' => 0,
            'isAddItem' => 0,
            'title' => 'priceForumAuto',
            'isLogging' => true
        ];
		$price = new core\Price($db, 'priceForumAuto');

		$imap = new core\Imap('{imap.mail.ru:993/imap/ssl}INBOX/Newsletters');
		$fileImap = $imap->getLastMailFrom(['from' => 'post@mx.forum-auto.ru', 'name' => 'Forum-Auto_Price.zip']);
		if (!$fileImap){
			$errorText = "Не удалось получить Forum-Auto_Price.zip из почты";
			echo "<br>$errorText";
			throw new \Exception($errorText);
		} 

		$db->query("
			DELETE si FROM
				#store_items si
			LEFT JOIN
				#provider_stores ps ON ps.id=si.store_id
			WHERE 
				ps.provider_id = 17
		", '');
		
		$zipArchive = new ZipArchive();
		$res = $zipArchive->open($fileImap);
		if (!$res){
			echo "<br>Ошибка чтения файла Forum-Auto_Price.zip";
			break;
		};
		$res = $zipArchive->extractTo(core\Config::$tmpFolderPath . "/", ["Forum-Auto_Price.xlsx"]);
		if (!$res){
			echo "<br>Ошибка извлечения файла Forum-Auto_Price.xlsx";
			break;
		};

		$filePath = core\Config::$tmpFolderPath . "/Forum-Auto_Price.xlsx";
		$reader = ReaderEntityFactory::createReaderFromFile($filePath);
		$reader->open($filePath);
		foreach ($reader->getSheetIterator() as $sheet) {
		   foreach ($sheet->getRowIterator() as $iterator) {
				$cells = $iterator->getCells();
				$row = [];
				foreach($cells as $value) $row[] = $value->getValue();
				$i++;
				// if ($i > 223801000) die("Обработка закончена");

				if (!$row[0]) continue;
				if ($row[0] == 'ГРУППА') continue;
				if (!$row[0] || !$row[1]){
					$price->setLog('error', "В строке $i произошла ошибка.");
					continue;
				}
				$brend_id = $price->getBrendId($row[0]);
				if (!$brend_id) continue;
				$item_id = $price->getItemId([
					'brend_id' => $brend_id,
					'brend' => $row[0],
					'article' => $row[1],
					'title' => $row[2],
					'row' => $i
				]);
				if (!$item_id) continue;
				$price->insertStoreItem([
					'store_id' => 22380,
					'item_id' => $item_id,
					'price' => $row[4],
					'in_stock' => $row[5],
					'packaging' => $row[6],
					'row' => $i
				]);
			}
		}

		Provider::updatePriceUpdated(['provider_id' => 17]);

		$price->setLog('alert', "Обработано $i строк");
        $price->setLog('alert',"Добавлено в прайс: $price->insertedStoreItems записей");
        $price->setLog('alert',"Вставлено: $price->insertedBrends брендов");
        $price->setLog('alert',"Вставлено: $price->insertedItems номенклатуры");
		
		echo "<br>Обработано <b>$i</b> строк";
		echo "<br>Добавлено в прайс: <b>$price->insertedStoreItems</b> записей";
		echo "<br>Вставлено: <b>$price->insertedBrends</b> брендов";
		echo "<br>Вставлено: <b>$price->insertedItems</b> номенклатуры";
		if ($price->isLogging){
            echo "<br><a target='_blank' href='/admin/logs/{$price->nameFileLog}'>Лог</a>";
        }
		break;
	case 'emailPrice':
        ini_set('memory_limit', '2048M');
		$debuggingMode = false;
		require_once($_SERVER['DOCUMENT_ROOT'].'/admin/functions/providers.function.php');
		$emailPrice = $db->select_one('email_prices', '*', "`store_id`={$_GET['store_id']}");
		$emailPrice = json_decode($emailPrice['settings'], true);
        $price = new core\Price($db, $emailPrice);
        
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
				ps.id = {$_GET['store_id']}
		");
        $store = $store[0];
        
        echo "<h2>Прайс {$emailPrice['title']}</h2>";
        
        
        $imap = new core\Imap('{imap.mail.ru:993/imap/ssl}INBOX/Newsletters');
		$fileImap = $imap->getLastMailFrom([
			'from' => $emailPrice['from'],
			'name' => $emailPrice['name']
		], $debuggingMode);
		if ($debuggingMode) debug($fileImap, 'fileImap');
		if (!$fileImap){
			$errorText = "Не удалось скачать {$emailPrice['name']} из почты.";
			echo "<br>$errorText";
			throw new Exception($errorText);
		} 

		switch($emailPrice['clearPrice']){
			case 'onlyStore': $db->delete('store_items', "`store_id`={$_GET['store_id']}"); break;
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
				throw new Exception($errorText);
				break;
			} 
			try{
				$nameInArchive = $emailPrice['nameInArchive'];
				$res = $zipArchive->extractTo(core\Config::$tmpFolderPath . "/", [$nameInArchive]);
				if (!$res) throw new Exception ("Ошибка извлечения файла {$emailPrice['nameInArchive']}. Попытка использовать альтернативный способ.");
			} catch(Exception $e){
				echo "<br>" . $e->getMessage(); 
				if ($emailPrice['indexInArchive'] == '0' || $emailPrice['indexInArchive']){
					echo "<br>обработка через indexInArchive";
					$nameInArchive = $zipArchive->getNameIndex($emailPrice['indexInArchive']);
					$bites = file_put_contents(core\Config::$tmpFolderPath . "/$nameInArchive", $zipArchive->getFromIndex($emailPrice['indexInArchive']));
					if (!$bites) throw new Exception("Возникла ошибка. Ни один из способов извлечь архив не сработали");
				}
				else{
					$zip_count = $zipArchive->count();
					if (!$zip_count) throw new Exception("Альтернативный способ не сработал - ошибка получения количества файлов в архиве");
					for ($i = 0; $i < $zip_count; $i++) { 
						echo "<br>" . "Индекс $i: ". $zipArchive->getNameIndex($i);
					};
					echo "<br>Укажите настройках в поле \"Индекс файла в архиве\" необходимый индекс.";
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
				catch(\Box\Spout\Common\Exception\UnsupportedTypeException $e){
					echo "<br>" . $e->getMessage();
					echo "<br>Обработка с помощью PhpOffice...";
					parseWithPhpOffice($workingFile, $debuggingMode);
					break;
				}
				try{
					$reader->open($workingFile);
				} catch(\Box\Spout\Common\Exception\IOException $e){
					echo "<br>Ошибка: <b>" . $e->getMessage() . "</b>";
					echo "<br>Попытка обработки файла другим способом....";
					parseWithPhpOffice($workingFile, $debuggingMode);
				}
				foreach ($reader->getSheetIterator() as $sheet) {
				   foreach ($sheet->getRowIterator() as $iterator) {
						$cells = $iterator->getCells();
						$row = [];
						foreach($cells as $value) $row[] = $value->getValue();
						$stringNumber++;

						if ($debuggingMode){
							debug($row);
							if ($stringNumber > 100) die("Обработка прошла");
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
						if ($stringNumber > 100) die("Обработка прошла");
					}
					parse_row($row, $emailPrice['fields'], $price, $stringNumber);
				}
				break;
		}

		switch($emailPrice['clearPrice']){
			case 'onlyStore': Provider::updatePriceUpdated(['store_id' => $_GET['store_id']]); break;
			case 'provider': Provider::updatePriceUpdated(['provider_id' => $_GET['provider_id']]); break;
			break;
		}

		endSuccessfullyProccessing($price->isLogging);

		break;
	case 'subscribeUserPrices':
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
		if (!$res_users->num_rows) break;

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
		echo "<h2>Рассылка прайсов</h2>";
		echo "<br>Всего отпрвлено $successedDelivery сообщений пользователям";
		break;
	
	case 'updatePrices':
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
		message('Обновление прошло успешно!');
		break;
}
if (isset($_GET['from'])){
	header("Location: {$_SERVER['HTTP_REFERER']}");
	exit();
} 
echo "<br>Время обработки: <b>".core\Timer::end()."</b> секунд";
