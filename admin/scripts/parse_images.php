<?php
use core\Item;

set_time_limit(0);
require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/DataBase.php");
require_once ("{$_SERVER['DOCUMENT_ROOT']}/admin/templates/functions.php");

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$attempt = 2;

$identifier = time();
$basePath = $_SERVER['DOCUMENT_ROOT'] . '/Картинки';
$insertedItems = 0;
$missedItems = 0;
$updatedTitles = 0;
$missedProcessedBrends = 0;
$totalItemsProcessed = 0;

$db->delete('parse_images_brends', "`is_missed` = 1 AND attempt = $attempt");

$rossko = new core\Provider\Rossko($db);

$brendsList = scandir($basePath);
foreach($brendsList as $brend){
	if ($brend == '.' || $brend == '..') continue;

	$isProcessedBrend = $db->getCount('parse_images_brends', "`brend_title` = '$brend' AND attempt = $attempt");
	if ($isProcessedBrend){
		$missedProcessedBrends++;
		continue;
	}


	$brend_id = core\Provider\Armtek::getBrendId($brend, 'parse_images');
	$dbArrayInsert = [
		'brend_title' => $brend,
		'processed' => 0,
		'is_missed' => 0,
		'identifier' => $identifier,
		'attempt' => $attempt
	];
	if (!$brend_id){
		$dbArrayInsert['is_missed'] = 1;
		$db->insert('parse_images_brends', $dbArrayInsert);
		continue;
	} 

	$brendPath = "$basePath/$brend";
	$articlesList = scandir($brendPath);
	foreach($articlesList as $article){
		if ($article == '.' || $article == '..') continue;

		$itemInfo = Item::getByBrendIDAndArticle($brend_id, $article);
		if ($itemInfo['photo']) continue;

		$totalItemsProcessed++;

		if ($itemInfo){
			$item_id = $itemInfo['id'];
			if ($itemInfo['title_full'] == 'Деталь'){
				$title_full = $rossko->getPartTitleByBrendAndArticle($brend, $article);
				if ($title_full){
					Item::update(['title_full' => $title_full, 'title' => $title_full], ['id' => $item_id]);
					$updatedTitles++;
				} 
			}
		}
		else{
			$title_full = $rossko->getPartTitleByBrendAndArticle($brend, $article);
			if ($title_full){
				$res = Item::insert([
					'brend_id' => $brend_id,
					'article' => Item::articleClear($article),
					'article_cat' => $article,
					'title_full' => $title_full,
					'title' => $title_full,
					'source' => 'Парсинг картинок'
				]);
				if ($res == true){
					$item_id = Item::$lastInsertedItemID;
					$insertedItems++;
				} 
				else{
					$missedItems++;
					continue;
				}
			}
			else{
				$missedItems++;
				continue;
			} 
		}

		if (!$item_id){
			$missedItems++;
			continue;
		}

		$isMainImage = true;
		$imagesListPath = "$brendPath/$article";
		$imagesList = scandir($imagesListPath);
		foreach($imagesList as $image){
			if ($image == '.' || $image == '..') continue;
			$array = set_image("$imagesListPath/$image", $item_id);
			if ($isMainImage) Item::update(['photo' => $array['name']], ['id' => $item_id]);
			$isMainImage = false;
		}
		rmdir("$imagesListPath");
	}
	$dbArrayInsert['processed'] = 1;
	$db->insert('parse_images_brends', $dbArrayInsert/*, ['print' => true]*/);
}
ob_start();
echo "Всего обработано: $totalItemsProcessed<br>";
echo "Обновлено названий номенклатуры: $updatedTitles<br>";
echo "Добавлено новой номенклатуры: $insertedItems<br>";
echo "Не найдено номенклатуры: $missedItems<br>";
echo "Пропущено уже обработанных брендов: $missedProcessedBrends<br>";
echo "Обработано брендов: " . $db->getCount('parse_images_brends', "`identifier` = $identifier AND `processed` = 1 AND `attempt` = $attempt") . "<br>";
$missedBrends = $db->select('parse_images_brends', '*', "`identifier` = $identifier AND `is_missed` = 1 AND attempt = $attempt");
if ($missedBrends){
	echo "Не найденные бренды: <br>";
	foreach($missedBrends as $brendInfo){
		echo "{$brendInfo['brend_title']}<br>";
	}
}
$result = ob_get_clean();
echo $result;
core\Mailer::send([
	'emails' => ['skrtoha@gmail.com'],
	'subject' => 'Обработка закончена',
	'body' => $result
]);
