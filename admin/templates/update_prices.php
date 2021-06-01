<?
require_once('../../core/database_class.php');
require_once('functions.php');
require_once ('../vendor/autoload.php');
$prices_name = 'prices_'.date('d.m.Y_H-i-s').'.txt';
$log = new Katzgrau\KLogger\Logger('../logs', Psr\Log\LogLevel::WARNING, array(
	'filename' => $prices_name,
	'dateFormat' => 'G:i:s'
));
set_time_limit(0);
$db = new core\Database();
$start = 0;
$step = 500;
$db->query('TRUNCATE TABLE `tahos_prices`');
$items_id = $db->query("
	SELECT item_id FROM #categories_items
", '');
$log->alert("Из categories_items выбрано {$items_id->num_rows} записей");
$prices_inserted = 0;
// echo "$items_id->num_rows";
if ($items_id->num_rows){
	while($row = $items_id->fetch_assoc()){
		$store_items = $db->query("
			SELECT
				si.item_id,
				IF(si.in_stock > 0, ps.delivery, ps.under_order) as delivery,
				@price:=ceil((si.price * c.rate + si.price * c.rate * ps.percent / 100)) as price
			FROM
				#store_items si
			LEFT JOIN
				#provider_stores ps ON ps.id=si.store_id
			LEFT JOIN
				#currencies c ON ps.currency_id=c.id
			WHERE
				si.price > 0 AND
				si.item_id={$row['item_id']}
		", '');
		if ($store_items->num_rows){
			$log->info("Из store_items для {$row['item_id']} выбрано {$store_items->num_rows} строк");
			$price = 100000000000000000;
			$delivery = 1000;
			$item_id = $row['item_id'];
			while($v = $store_items->fetch_assoc()){
				if ($v['price'] < $price) $price = $v['price'];
				if ($v['delivery'] < $delivery) $delivery = $v['delivery'];
			}
			$log->info("Для $item_id мин. цена $price, доставка $delivery");
			$item_insert = [
				'item_id' => $item_id,
				'price' => $price,
				'delivery' => $delivery
			];
			$res_insert = $db->insert(
				'prices',
				$item_insert
			);
			if ($res_insert === true){
				$log->debug("Успешная вставка:", $item_insert);
				$prices_inserted++;
			}
			else{
				$log->error("Ошибка: $res_insert | {$db->last_query}");
			}
			// exit();
		} 
		else $log->warning("item_id={$row['item_id']} не найден в прайсах");
	}
		
}
$log->alert("Всего вставлено $prices_inserted");
set_ratings();
message("Обновление завершено");
header("Location: /admin/?view=min_prices");

?>