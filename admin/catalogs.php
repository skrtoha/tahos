<?
set_time_limit(0);
require_once('../core/DataBase.php');
require_once('templates/functions.php');
require_once ($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

$db = new core\DataBase();

// $db->query("SET foreign_key_checks = 0");
// $db->delete('nodes', "`id`=1174291");
// debug($db->get_mysqli()); 
// $db->query("SET foreign_key_checks = 1");
// exit();

// file_put_contents('logs/catalog.txt', '');
// $catalog_name = 'catalog.txt';

$catalog_name = 'catalog_'.date('d.m.Y_H-i-s').'.txt';
$log = new Katzgrau\KLogger\Logger(__DIR__.'/logs', Psr\Log\LogLevel::WARNING, array(
	'filename' => $catalog_name,
	'dateFormat' => 'G:i:s'
));

// $db->query("SET foreign_key_checks = 0");
// $db->query("TRUNCATE tahos_models");
// $db->query("TRUNCATE tahos_vehicle_filter_values");
// $db->query("TRUNCATE tahos_vehicle_filter_values");
// $db->query("TRUNCATE tahos_vehicle_model_fvs");
// $db->query("TRUNCATE tahos_nodes");
// $db->query("TRUNCATE tahos_modifications");
// $db->query("TRUNCATE tahos_node_items");
// $db->query("TRUNCATE tahos_vehicle_filters");

// $db->query("TRUNCATE tahos_items");
// $db->query("TRUNCATE tahos_substitutes");
// $db->query("TRUNCATE tahos_articles");

// $db->query("SET foreign_key_checks = 1");
// exit();

$vehicle_number = 3;
$vehicle_path = "../catalog/$vehicle_number";
$vehicle_name = file_get_contents("$vehicle_path/name.txt");
$dd = ['deincrement_dublicate' => true];
$res = $db->insert(
	'vehicles', 
	[
		'title' => $vehicle_name, 
		'category_id' => 1,
		'href' => get_href($vehicle_name)
	],
	$dd
);
if ($res === true){
	$vehicle_id = $db->last_id();
	$log->info("Вид ТС $vehicle_name добавлен");
} 
else{
	$vehicle_id = $db->getField('vehicles', 'id', 'title', $vehicle_name);
	$log->warning("Вид ТС $vehicle_name уже присутствует с id=$vehicle_id");
}
if (!$vehicle_id){
	$log->error("Ошибка получения id для ТС");
	exit();
} 
// echo $vehicle_id; exit();
$brend_paths = glob("$vehicle_path/*");
foreach($brend_paths as $brend_path){
	$brend_number = basename($brend_path);
	if (is_numeric($brend_number)){
		$brend_name = file_get_contents("$brend_path/name.txt");
		if (in_array($brend_name, ['Evinrude', 'Johnson', 'Mariner', 'Mercury'])) {
			$log->alert("Загрузка $brend_name пропущена");
			continue;
		}
		$brend_select = $db->select_one('brends', 'id,parent_id', "`title`='$brend_name'");
		$brend_id = $brend_select['id'];
		if ($brend_select['parent_id']) $brend_item = $brend_select['parent_id'];
		else $brend_item = $brend_select['id'];
		$res = $db->insert(
			'vehicle_filters',
			[
				'vehicle_id' => $vehicle_id,
				'brend_id' => $brend_id,
				'title' => 'Год'
			],
			$dd
		);
		if ($res === true){
			$filter_id = $db->last_id();
			$log->info("Фильтр Год успешно добавлен с id=$filter_id");
		} 
		else{
			$select = $db->select_one('vehicle_filters', 'id', "`vehicle_id`=$vehicle_id AND `brend_id`=$brend_id AND `title`='Год'");
			$filter_id = $select['id'];
			$log->warning("Фильтр Год для $vehicle_name и $brend_name уже присутствует с id=$filter_id: $res");
		}
		if (!$filter_id){
			$log->emergency("Ошибка получения id для фильтра Год у $vehicle_name и $brend_name");
			exit();
		}
		$year_paths = glob("$brend_path/*");
		foreach($year_paths as $year_path){
			$year_name = basename($year_path);
			if (!is_numeric($year_name)) continue;
			$year = trim(file_get_contents("$year_path/name.txt"));
			$res = $db->insert(
				'vehicle_filter_values',
				[
					'filter_id' => $filter_id,
					'title' => $year
				],
				$dd
			);
			if ($res === true){
				$fv_id = $db->last_id();
				$log->info("$year для  $vehicle_name и $brend_name добавлен");
			} 
			else{
				$last_query = $db->last_query;
				$select = $db->select_one('vehicle_filter_values', 'id', "`filter_id`=$filter_id AND `title`='$year'");
				$fv_id = $select['id'];
				$log->warning("Год $year для $vehicle_name и $brend_name уже присутствует: $res");
			}
			if (!$fv_id){
				$log->emergency("Ошибка получения id для $year_name в $vehicle_name и $brend_name");
				exit();
			}
			$model_paths = glob("$year_path/*");
			foreach($model_paths as $model_path){
				$model_name = basename($model_path);
				if (!is_numeric($model_name)) continue;
				$model_title = file_get_contents("$model_path/name.txt");
				if (!$model_title){
					$log->error("Ошибка имени модели для $vehicle_name и $brend_name в $year");
					continue;
				}
				$res = $db->insert(
					'models',
					[
						'title' => $model_title,
						'href' => get_href($model_title),
						'brend_id' => $brend_id,
						'vehicle_id' => $vehicle_id
					],
					$dd
				);
				if ($res === true){
					$model_id = $db->last_id();
					$log->info("Модель $model_title для $vehicle_name и $brend_name в $year_name добалена с id=$model_id");
				} 
				else{
					$last_query = $db->last_query;
					$select = $db->select_one(
						'models',
						'id',
						"`title`='$model_title' AND `brend_id`=$brend_id AND `vehicle_id`=$vehicle_id"
					);
					$model_id = $select['id'];
					$log->warning("Модель $model_title для $vehicle_name и $brend_name в $year уже присутствует с id=$model_id: $res");
				}
				if (!$model_id){
					$log->error("Ошибка получения id для $model_title для $vehicle_name и $brend_name в $year: $last_query");
					continue;
				}
				$modification_title = $model_title;
				$res = $db->insert(
					'modifications',
					[
						'title' => $modification_title,
						'model_id' => $model_id,
						'fv_id' => $fv_id
					],
					$dd
				);
				if ($res === true){
					$modification_id = $db->last_id();
					$log->info("Модификация $model_title для модели $model_title для $vehicle_name и $brend_name в $year_name добавлена c id=$modification_id");
					$res = $db->insert('vehicle_model_fvs', ['modification_id' => $modification_id, 'fv_id' => $fv_id]);
					if ($res === true){
						$log->info("Фильтр $year для модели $model_title, $vehicle_name, $brend_name вставлен в vehicle_model_fvs");
					}
					else{
						$log->error("Ошибка вставки $model_title, $vehicle_name, $brend_name в vehicle_model_fvs: $res: {$db->last_query}");
						continue;
					}
				}
				else{
					$modification = $db->select_one("modifications", 'id', "`model_id`=$model_id AND `fv_id`=$fv_id");
					$modification_id = $modification['id'];
					$log->warning("Модификация модели $model_title c $year уже присутствует c id=$modification_id");
					continue;
				}
				if (!$modification_id){
					$log->error("Ошибка получения id модификации модели $model_title c $year для $model_title для $vehicle_name и $brend_name в $year_name");
					continue;
				}
				$nodes_paths = glob("$model_path/*");
				// debug($nodes_paths); continue;
				$i = 0;
				foreach ($nodes_paths as $node_path){
					$node_name = basename($node_path);
					if (!is_numeric($node_name)) continue;
					$node_title = file_get_contents("$node_path/name.txt");
					$res = $db->insert(
						'nodes',
						[
							'title' => $node_title,
							'modification_id' => $modification_id,
							'parent_id' => 0,
							'subgroups_exist' => 0
						],
						$dd
					);
					if ($res === true){
						$node_id = $db->last_id();
						$log->info("Узел $node_title для $model_title, $vehicle_name, $brend_name успешно вставлен с id=$node_id");
					}
					else {
						$node = $db->select_one("nodes", 'id', "`modification_id`=$modification_id AND `title`='$node_title'");
						$node_id = $node['id'];
						$log->warning("Узел $node_title для $model_title | $vehicle_name | $brend_name уже присутствует");
					}
					if (!$node_id){
						$log->error("Ошибка получения id узла для $model_title, $vehicle_name, $brend_name");
						continue;
					}
					$handle = fopen("$node_path/elements.csv", 'r');
					$row = 1;
					$updated_items = 0;//для подсчета обнолений номенклатуры
					$inserted_items = 0;//для подсчета вставленной номенклатуры
					$inserted_items_diff = 0;//количество замен
					$updated_items_diff = 0;
					$node_items_count = 0;
					while($d = fgetcsv($handle)){
						$row++;
						$r = explode(';', $d[0]);
						if ($r[2] == $substitution_miss){
							$log->info("Замена $substitution_miss пропуск следующая строка");
							continue;
						}
						if ($r[5] == 'quantity') continue;
						$log->debug($r[0], $r); 
						if (!is_numeric($r[5])){
							$log->error("Ошибка в количестве $node_path в строке $row");
							continue;
						} 
						$item = $db->select_one(
							'items',
							['id', 'title_full', 'article', 'article_cat'],
							"`article`='{$r[3]}' AND `brend_id`=$brend_item"
						);
						// $log->debug('item', $item);
						if (empty($item)){
							$res = $db->insert(
								'items', 
								[
									'title_full' => $r[0] ? $r[0] : 'Деталь',
									'title' => $r[0] ? $r[0] : 'Деталь',
									'article' => $r[3],
									'article_cat' => $r[2],
									'brend_id' => $brend_item
								],
								$dd
							);
							if ($res === true){
								$item_id = $db->last_id();
								$log->info("Номенклатура {$r[2]}, $brend_name добавлена с id=$item_id");
								$res_insert_articles = $db->insert('articles', ['item_id' => $item_id, 'item_diff' => $item_id]);
								if ($res_insert_articles == true) $log->info("Таблица articles пополнена для номенклатуры {$r[2]}, $brend_name");
								else $log->error("Ошибка вставки в articles: $res | {$db->last_query}");
								$inserted_items++;
							}
							else{
								$log->error("Ошибка вставки {$r[3]}, $brend_name: $res | {$db->last_query}");
								continue;
							}
						}
						else{
							$item_id = $item['id'];
							$log->notice("Деталь {$r[3]} бренда $brend_name уже присутствует с id=$item_id");
							if (preg_match('/^Деталь/u', $item['title_full'])){
								$bl_affected_1 = false;
								$bl_affected_2 = false;
								$res1 = $db->update(
									'items',
									[
										'title_full' => $r[0],
										'title' => $r[0]
									],
									"`id`=$item_id"
								);
								if ($db->rows_affected() > 0){
									$bl_affected_1 = true;
									$log->info("Наименование номенклатуры {$r[2]}, $brend_name успешно изменено с {$item['title_full']} на {$r[0]}");
								}
								else{
									$log->error("Ошибка обновления наименования Деталь у номенклатуры $article, $brend_name: $res | {$db->last_query}");
								}
							}
							// $res2 = $db->update('items', ['article_cat' => $r[2]], "`id`=$item_id");
							$res2 = core\Item::update(['article_cat' => $r[2]], ['id' => $item_id]);
							if ($res2 === true && $db->rows_affected() > 0){
								$bl_affected_2 = true;
								$log->info("Каталожный номер у {$item['article']}, $brend_name изменен с {$item['article_cat']} на {$r[2]}");
							}
							if ($bl_affected_1 || $bl_affected_2) $updated_items++;
						}
						//если в наличии замена
						if ($r[4]){
							$substitution_miss = $r[4];
							$article = article_clear($r[4]);
							$item_diff = $db->select_one('items', 'id', "`article`='$article' AND `brend_id`=$brend_item");
							//если замены нету в базе
							if (empty($item_diff)){
								$res = $db->insert(
									'items',
									[
										'brend_id' => $brend_item,
										'title_full' => $r[0],
										'article' => $article,
										'article_cat' => $r[4]
									],
									$dd
								);
								if ($res === true){
									$inserted_items_diff++;
									$item_diff_id = $db->last_id();
									$log->info("Замена {$r[4]} бренда $brend_name добавлена с id=$item_diff_id");
								}
								else{
									$item_diff_id = $item_diff['id'];
									$log->notice("Замена {$r[4]} бренда $brend_name уже присутствует с id=$item_diff_id");
								}
								$res = $db->insert('articles', ['item_id' => $item_diff_id, 'item_diff' => $item_diff_id]);
								if ($res === true){
									$log->info("Таблица articles пополнена для {$r[4]} бренда $brend_name с id=$item_diff_id");
								}
								else{
									$log->error("Ошибка вставки articles для {$r[4]} бренда $brend_name с id=$item_diff_id: $res | {$db->last_query}");
								}
							}
							else $item_diff_id = $item_diff['id'];
							if (!$item_diff_id) {
								$log->error("Ошибка получения item_diff_id");
							}
							else{
								$res1 = $db->insert('substitutes', ['item_id' => $item_id, 'item_diff' => $item_diff_id]);
								if ($res1 !== true) $log->info("Дублирующая запись substitutes: $res1 | {$db->last_query}");
								$res2 = $db->insert('substitutes', ['item_id' => $item_diff_id, 'item_diff' => $item_id]);
								if ($res2 !== true) $log->info("Дублирующая запись substitutes: $res2 | {$db->last_query}");
								if ($res1 === true && $res2 === true) $updated_items_diff++;
							}
						}
						$arr_node = [
							'pos' => $r[1],
							'node_id' => $node_id,
							'item_id' => $item_id,
							'quan' => $r[5],
						];
						$log->debug("Деталь {$r[0]} узла $node_title:", $arr_node); //continue;
						$res = $db->insert(
							'node_items',
							$arr_node
						);
						if ($res === true){
							$log->info("Таблица node_items для $node_title успешно пополнена");
						}
						else{
							$log->notice("Ошибка вставки node_items: $res | {$db->last_query}");
							continue;
						} 
					}
					if (file_exists("$node_path/image.gif")) $is_copied_image = image_catalog("$node_path/image.gif");
					if (file_exists("$node_path/image.jpg")) $is_copied_image = image_catalog("$node_path/image.jpg");
					if (file_exists("$node_path/image.jpeg")) $is_copied_image = image_catalog("$node_path/image.jpeg");
					if ($is_copied_image) $log->info("Изображение для узла $node_title скопировано");
					else $log->error("Произошла ошибка копирования изображения для $node_title");
					$log->alert("Для узла $node_title | $model_title | $brend_name вставлено номенклатуры: $inserted_items, замен: $inserted_items_diff, обновлено номенклатуры: $updated_items, обновлено замен: $updated_items_diff");
					// if ($row >= 20) exit();
				}
			} 
		}
	}
}
function image_catalog($file){
	error_reporting(E_ERROR);
	global $brend_name, $node_id;
	$array = [];
	$name = $file['name'];
	if (!$name) {
		$array['error'] = '';
		return $array;
	}
	$dir_big = core\Config::$imgPath."/nodes/big/$brend_name";
	$dir_small = core\Config::$imgPath."/nodes/small/$brend_name";
	require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/class.upload.php');
	if (!file_exists($dir_big)) mkdir($dir_big);
	if (!file_exists($dir_small)) mkdir($dir_small);
	$handle = new upload($file);
	$handle_big = new upload($file);
	// debug($hande);
	// debug($handle_big);
	// echo "<hr>";
	// $hande->image_convert = 'jpeg';
	// $handle_big->image_convert = 'jpeg';
	$need_ratio = [
		'x' => 304,
		'y' => 418
	];
	$handle->file_new_name_body = $node_id;
	$handle_big->file_new_name_body = $node_id;
	$handle->image_resize = true;
	$src_x = $handle->image_src_x;
	$src_y = $handle->image_src_y;
	if (($need_ratio['x'] / $need_ratio['y']) >= ($src_x / $src_y)){
		$handle->image_x = $need_ratio['x'];
		$handle->image_y = floor($need_ratio['x'] * $src_y / $src_x);
		$t = floor($handle->image_y / 2 - $need_ratio['y'] / 2);
		$handle->image_crop = "$t 0";
	}
	else{
		$handle->image_y = $need_ratio['y'];
		$handle->image_x = floor($need_ratio['y'] * $src_x / $src_y);
		$t = floor($handle->image_x / 2 - $need_ratio['x'] / 2);
		$handle->image_crop = "0 $t";
	}
	$handle_big->process($dir_big);
	$handle->process($dir_small);
	if ($handle_big->processed && $handle->processed) return true;
	return false;
}
function get_href($str){
	// $str = preg_replace('/[\W_]+/', '', $str);
	$str = str_replace(
		[',', ' ', '-', '&', '(', ')'], 
		['', '-', '-', '', '', ''],
		$str
	);
	return translite($str);
}
// debug($brend_paths);
?>
