<?
set_time_limit(0);
require_once('../core/DataBase.php');
require_once('templates/functions.php');
require_once ($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');
$db = new core\DataBase();

file_put_contents('logs/dubles.txt', '');

// $catalog_name = 'dubles_'.date('d.m.Y_H-i-s').'.txt';
$file_name = 'dubles.txt';
$log = new Katzgrau\KLogger\Logger(__DIR__.'/logs', Psr\Log\LogLevel::INFO, array(
	'filename' => $file_name,
	'dateFormat' => 'G:i:s'
));

$res_brends = $db->query("
	SELECT id FROM #brends WHERE parent_id=0
", '');
$log->alert("Выбрано {$res_brends->num_rows} брендов.");
while($brend = $res_brends->fetch_assoc()){
	$brend_id = $brend['id'];
	$log->info("Обработка по brend_id=$brend_id");
	$res_items = $db->query("
		SELECT
			id,
			article,
			COUNT(id) AS cnt
		FROM
			tahos_items
		WHERE
			brend_id=$brend_id
		GROUP BY
			article
		HAVING
			cnt>=2
	");
	if (!$res_items->num_rows){
		$log->alert("Дублей не найдено");
		continue;
	}
	$deleted = 0;
	while($item = $res_items->fetch_assoc()){
		$res_delete_items = $db->delete('items', "`article`='{$item['article']}' AND `id`!={$item['id']}");
		$rows_affected = $db->rows_affected();
		if ($rows_affected) $deleted += $rows_affected;
		// echo "<hr>";
		// $i++;
		// if ($i == 10) exit();
	}
	$log->alert("Всего удалено $deleted");
}
?>
