<?
set_time_limit(0);
require_once('vendor/simple_html_dom.php');
require_once('../core/DataBase.php');
require_once ($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');
file_put_contents('logs/psndemo.txt', '');
$log = new Katzgrau\KLogger\Logger(__DIR__.'/logs', Psr\Log\LogLevel::INFO, array(
	'filename' => 'psndemo',
	'extension' => 'txt'
));
$db = new core\DataBase();

$db->query("SET foreign_key_checks = 0");
$db->query("TRUNCATE tahos_models");
$db->query("TRUNCATE tahos_vehicle_filter_values");
$db->query("TRUNCATE tahos_vehicle_filter_values");
$db->query("TRUNCATE tahos_vehicle_model_fvs");
$db->query("TRUNCATE tahos_nodes");
$db->query("TRUNCATE tahos_node_modifications");
$db->query("TRUNCATE tahos_modifications");
$db->query("TRUNCATE tahos_node_items");
$db->query("TRUNCATE tahos_vehicle_filters");
$db->query("SET foreign_key_checks = 1");
exit();

$vehicle_id = 1;
$html = str_get_html(file_get_contents("http://www.psndemo1.com/fiche_select.asp?srt=cat"));
if (!method_exists($html, 'find')){
	$log->error("Ошибка загрузки списка брендов");
	exit();
};
$brends_count = 0;
$models_count = 0;
$modifications_count = 0;
$need_brends = [
	'Honda'
];
foreach($html->find('#Motorcycles_content a.NV_mod_detail_link_text_1') as $a_vehicle){
	$brend_title = trim($a_vehicle->innertext);
	if (!empty($need_brends) && !in_array($brend_title, $need_brends)) continue;
	$a_brend = str_get_html(file_get_contents(get_url($a_vehicle->href)));
	$res_brend_insert = $db->insert(
		'brends',
		[
			'title' => trim($brend_title),
			'href' => trim(translite($brend_title)),
			'parent_id' => 0
		]
		// ['deincrement_dublicate' => 1]
	);
	if ($res_brend_insert !== true){
		$brend_select = $db->select_one('brends', 'id,parent_id', "`title`='$brend_title'");
		$brend_id = $brend_select['id'];
		if ($brend_select['parent_id']) $brend_item = $brend_select['parent_id'];
		else $brend_item = $brend_select['id'];
		$log->notice("$brend_title уже присутствует в базе c id=$brend_id: $res_brend_insert");
	}
	else{
		$brend_id = $db->last_id();
		$brends_count++;
	} 
	$res_vehicle_filters_insert = $db->insert(
		'vehicle_filters',
		[
			'vehicle_id' => $vehicle_id,
			'brend_id' => $brend_id,
			'title' => 'Год'
		],
		['deincrement_dublicate' => 1]
	);
	if ($res_vehicle_filters_insert !== true){
		$select = $db->select_one('vehicle_filters', 'id', "`title`='Год' AND `vehicle_id`=$vehicle_id AND `brend_id`=$brend_id");
		$filter_id = $select['id'];
	} 
	else{
		$filter_id = $db->last_id();
		$log->notice("Создан фильтр Год для $brend_title с id=$filter_id");
	}
	foreach($a_brend->find('#mainLevel > tr > td > table') as $dom_year){//перебор по годам
		$year = trim($dom_year->find('span.NV_mod_detail_headers_text', 0)->innertext);
		if (!$year) continue;
		$res_vehicle_filter_values_insert = $db->insert(
			'vehicle_filter_values',
			[
				'filter_id' => $filter_id,
				'title' => $year,
			],
			['deincrement_dublicate' => 1]
		);
		if ($res_vehicle_filter_values_insert !== true){
			$select = $db->select_one('vehicle_filter_values', 'id', "`filter_id`=$filter_id AND `title`='$year'");
			$filter_value_id = $select['id']; 
			$log->notice("Год $year для $brend_title уже присутствует c id=$filter_value_id: $res_vehicle_filter_values_insert");
		} 
		else $filter_value_id = $db->last_id();
		if (!method_exists($dom_year, 'find')){
			$log->error("Ошибка загрузки списка годов $brend_title");
		}
		$models = array();
		foreach($dom_year->find('div.content_2 a.NV_mod_detail_link_text_1') as $a_model){//перебор по моделям в годе
			$model = [
				'title' => preg_replace('/^\s+\d+&nbsp;/', '', $a_model->innertext),
				'url' => get_url($a_model->href),
				'href' => get_href($a_model->innertext)
			];
			$res_model_insert = $db->insert(
				'models',
				[
					'title' => $model['title'],
					'vin' => '',
					'href' => $model['href'],
					'brend_id' => $brend_id,
					'vehicle_id' => $vehicle_id
				],
				['deincrement_dublicate' => 1]
			);
			if ($res_model_insert === true){//если модель отсутствует в базе
				$model_id = $db->last_id();
				$log->info("Добавлена модель {$model['title']} c id=$model_id");
				$models_count++;
			}
			else{
				$select = $db->select_one('models', 'id', "`title`='{$model['title']}' AND `brend_id`=$brend_id AND `vehicle_id`=$vehicle_id");
				$model_id = $select['id'];
				$log->notice("Модель {$model['title']} c брендом $brend_title уже присутствует с id=$model_id: $res_model_insert");
			}
			$res_modification_insert = $db->insert(
				'modifications',
				[
					'title' => $model['title'],
					'model_id' => $model_id
				]
			);
			if ($res_modification_insert === true){
				$modification_id = $db->last_id();
				$modifications_count++;
				$log->info("Модификация {$model['title']} успешно вставлена с id=$modification_id");
				$res_vehicle_model_fvs = $db->insert(
					'vehicle_model_fvs',
					[
						'modification_id' => $modification_id,
						'fv_id' => $filter_value_id
					]
				);
				if ($res_vehicle_model_fvs === true){//парсинг узлов
					$log->info("Значение $year добавлено к модификации {$model['title']}");
					$log->alert("Парсим узлы модели {$model['title']} $brend_title");
					$nodes = array();
					$dom_nodes = str_get_html(file_get_contents($model['url']));
					if (!method_exists($dom_nodes, 'find')){
						$log->error("Ошибка загрузки списка узлов для {$model['title']}");
						continue;
					}
					foreach($dom_nodes->find('a.NV_mod_detail_link_text') as $a_node){
						$node_title = trim(strip_tags(preg_replace('/<img.+>\s+\d+\- /', '', $a_node->innertext)));
						$node_title = preg_replace('/\s{2,}/', ' ', $node_title);
						$nodes[] = [
							'title' => $node_title,
							'url' => get_url($a_node->href)
						];
					}
					$log->alert('Получено '.count($nodes). ' узлов');
					$log->debug("Узлы (nodes) для модели {$model['title']}", $nodes);
					$nodes_count = 0;
					foreach($nodes as $node){
						if ($node['title'] == 'Model Numbers'){
							$log->info("Model Numbers пропущен");
							continue;
						}
						$db->insert(
							'nodes',
							[
								'title' => $node['title'],
								'parent_id' => 0,
								'subgroups_exist' => 0
							],
							['deincrement_dublicate' => 1]
						);
						$node_id = $db->last_id();
						$nodes_count++;
						$db->insert(
							'node_modifications', 
							['modification_id' => $modification_id, 'node_id' => $node_id]
						);
						$dom_node = str_get_html(file_get_contents($node['url']));
						$log->info('Парсим узел: '.$node['title'].' модели '.$model['title']);
						if ($node['title'] == 'Model number'){
							$log->info("{$node['title']} пропущен");
							continue;
						}
						//копируем изображение
						$locImage = getLocImage($dom_node);
						$log->info('Изображение: '.$locImage);
						if (copy($locImage, 'image.jpg')) image_catalog('image.jpg');
						else $log->error("Не удалось скопировать изображение");
						$node_items = array();
						foreach($dom_node->find('#PartsList tr') as $tr){
							$array = [
								'pos' => $tr->children(1)->innertext,
								'title' => $tr->children(2)->innertext,
								'quan' => $tr->children(3)->innertext
							];
							if (!is_numeric($array['quan'])){
								// $log->error("Ошибочное значение количества {$array['title']}");
								continue;
							} 
							$a = explode('<br>', $array['title']);
							$array['title'] = trim($a[0]);
							preg_match('/replaces&nbsp;(.*)\)<\/span>.*/i', $a[1], $matches);
							// $log->debug('matches', $matches);
							if (!empty($matches)){
								$array['article_cat'] = trim(preg_replace('/\s+<span.*span>/', '', $a[1]));
								$array['article'] = preg_replace('/[\W_]+/', '', $array['article_cat']);
								$array['replaces'] = $matches[1];
							}
							else {
								$array['article_cat'] = trim($a[1]);
								$array['article'] = preg_replace('/[\W_]+/', '', $array['article_cat']);
							}
							if (!$array['article_cat'] || !$array['article']){
								$log->warning("В детали узла {$node_item['title']} отсутствует артикул");
								$db->debug('Деталь с отсутствующим артикулом', $array);
							}
							$node_items[] = $array;
						}
						$log->info('Получено '.count($node_items).' узлов '.$node['title']);
						$log->debug("Детали узла {$node['title']} для модели {$model['title']}", $node_items);
						if (empty($node_items)){
							$log->warning("Детали узла для {$node['title']} отсутствуют");
							continue;
						} 
						$updated_items = 0;//для подсчета обнолений номенклатуры
						$inserted_items = 0;//для подсчета вставленной номенклатуры
						$inserted_items_diff = 0;//количество замен
						$node_items_count = 0;
						foreach($node_items as $node_item){
							$item = $db->select_one(
								'items',
								['id', 'title_full', 'article'],
								"`article`='{$node_item['article']}' AND `brend_id`=$brend_item"
							);
							if (empty($item)){//номенклатура отсутствует
								$log->info("Деталь {$node_item['article_cat']} c брендом $brend_title отсутствует");
								//если присутствует имя узла
								if ($node_item['title']) $title_full = $node_item['title'];
								else{
									$title_full = 'Деталь';
									$log->warning("Отсутсует наименование у {$node_item['article_cat']} бренда $brend_title");
								}
								$res_items_insert = $db->insert(
									'items', 
									[
										'title_full' => $title_full,
										'title' => $title_full,
										'article' => $node_item['article'],
										'article_cat' => $node_item['article_cat'],
										'brend_id' => $brend_item
									],
									['deincrement_dublicate' => 1]
								);
								if ($res_items_insert === true){
									$item_id = $db->last_id();
									$inserted_items++;
									$log->info("Номенклатура {$node_item['article_cat']} с брендом $brend_title успешно вставлен с id=$item_id");
									$res_articles_insert = $db->insert('articles', ['item_id' => $item_id, 'item_diff' => $item_id]);
									if ($res_articles_insert === true) $log->info("Таблица articles пополнена c item_id и item_diff = $item_id артикул {$node_item['article_cat']} и $brend_title");
									else $log->error("Ошибка вставки articles c item_id и item_diff = $item_id артикул {$node_item['article_cat']} и $brend_title: $res_articles_insert");
								}
								else {
									$log->error("Ошибка вставки детали узла {$node_item['article_cat']} c брендом $brend_title: $res_items_insert");
									continue;
								}
							}
							else{//номенклатура пристуствует
								$item_id = $item['id'];
								$log->notice("Номенклатура {$node_item['article_cat']} c брендом $brend_title уже пристуствует с id=$item_id");
								// if ($item['title_full'] == 'Деталь' && $node_item['title']){
									$res_items_update = $db->update(
										'items',
										[
											'title_full' => $node_item['title'],
											'title' => $node_item['title'],
											'article_cat' => $node_item['article_cat']
										],
										"`article`='{$node_item['article']}' AND `brend_id`=$brend_item"
									);
									if ($res_items_update === true && $db->rows_affected() > 0){
										$log->notice("Наименование номенклатуры {$item['title_full']} c артикулом {$node_item['article_cat']} и брендом $brend_title успешно изменено на {$node_item['title']}");
										$updated_items++;
									}
									else $log->error("Ошибка обновления наименования номенклатуры {$item['title_full']} c брендом $brend_title на {$node_item['title']}: $res_items_update | {$db->last_query}");
								// }
							}
							//если в наличии замена
							if ($node_item['replaces']){
								$article = preg_replace('/[\W_]+/', '', $node_item['replaces']);
								$item_diff = $db->select_one('items', 'id', "`article`='$article' AND `brend_id`=$brend_item");
								//если замена отсутствует в базе
								if (empty($item_diff)){
									$res_item_diff_insert = $db->insert(
										'items',
										[
											'brend_id' => $brend_item,
											'title_full' => $node_item['title'],
											'title' => $node_item['title'],
											'article' => $article,
											'article_cat' => $node_item['replaces']
										],
										['deincrement_dublicate' => 1]
									);
									if ($res_item_diff_insert === true){
										$item_diff_id = $db->last_id();
										$inserted_items_diff++;
										$log->info("Деталь-замена $brend_title - {$node_item['replaces']} для {$node_item['article_cat']} добавлена с id=$item_diff_id ");
										$res_articles_insert = $db->insert('articles', ['item_id' => $item_diff_id, 'item_diff' => $item_diff_id]);
										if ($res_articles_insert === true) 
											$log->info("Таблица articles пополнена заменой c item_id и item_diff = $item_diff_id артикул {$node_item['article_cat']} и $brend_title");
										else 
											$log->error("Ошибка вставки замены в articles c item_id и item_diff = $item_id артикул {$node_item['article_cat']} и $brend_title: {$db->last_id} | $res_articles_insert");
										$res_substitutes_insert_1 = $db->insert('substitutes', ['item_id' => $item_id, 'item_diff' => $item_diff_id]);
										$res_substitutes_insert_2 = $db->insert('substitutes', ['item_id' => $item_diff_id, 'item_diff' => $item_id]);
										if ($res_substitutes_insert_1 === true && $res_substitutes_insert_2 === true){
											$log->info("Таблица substitutes пополнена с item_id=$item_id с артикулом {$node_item['article_cat']} и item_diff=$item_diff_id с артикулом {$node_item['replaces']} и брендом $brend_title");
										}
										else $log->error("Ошибка вставки substitutes с item_id=$item_id с артикулом {$node_item['article_cat']} и item_diff=$item_diff_id с артикулом {$node_item['replaces']} и брендом $brend_title: $res_substitutes_insert_1, $res_substitutes_insert_2");
									}
								}
								//если замена есть в базе обновляем substitutes
								else{
									$log->notice("Замена $brend_title - {$node_item['replaces']} для {$node_item['article_cat']} уже присутствует");
									$item_diff_id = $item_diff['id'];
									$res_substitutes_insert_1 = $db->insert('substitutes', ['item_id' => $item_id, 'item_diff' => $item_diff_id]);
										$res_substitutes_insert_2 = $db->insert('substitutes', ['item_id' => $item_diff_id, 'item_diff' => $item_id]);
										if ($res_substitutes_insert_1 === true && $res_substitutes_insert_2 === true)
											$log->info("Таблица substitutes пополнена с item_id=$item_id с артикулом {$node_item['article_cat']} и item_diff=$item_diff_id с артикулом {$node_item['replaces']} и брендом $brend_title");
										else $log->warning("Ошибка вставки substitutes с item_id=$item_id с артикулом {$node_item['article_cat']} и item_diff=$item_diff_id с артикулом {$node_item['replaces']} и брендом $brend_title: $res_substitutes_insert_1 | $res_substitutes_insert_2");
								}
							}
							//вставка деталей узла
							$res_node_items_insert = $db->insert(
								'node_items',
								[
									'pos' => $node_item['pos'],
									'node_id' => $node_id,
									'item_id' => $item_id,
									'quan' => $node_item['quan'],
								]
							);
							if ($res_node_items_insert === true){
								$log->info("Деталь {$node_item['article_cat']} бренд $brend_title узла {$node['title']} успешно вставлена");
								$node_items_count++;
							} 
							else $log->notice("Деталь узла {$node['title']} с артикулом {$node_item['article_cat']} бренд $brend_title  уже присутствует: {$db->last_id} | $res_node_items_insert");
						}
						$log->alert("Для узла {$node['title']} добавлено номенклатуры: $inserted_items, обновлено: $updated_items, замен: $inserted_items_diff, деталей узлов: $node_items_count");
						// die("Остановлено на обработке узла {$node['title']} модели {$model['title']}");
					}
					$log->alert("Обработано $nodes_count узлов модели {$model['title']}");
				}
			}
			else{
				$log->error("Возникла ошибкa: $res_modification_insert");
				continue;
			}
			// die("Остановлено на обработке модели {$model['title']}");
		}
		$log->alert("Добавлено моделей: $models_count, модификаций: $modifications_count");
	}
	$log->alert("Добавлено брендов: $brends_count");
	// exit();
}
function translite($var){
	$var = mb_strtolower($var, 'UTF-8');
	$var = preg_replace('/\s+/', '-', $var);
	$var = preg_replace('/W+/', '', $var);
	$alpha = array("а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d","е"=>"e","ё"=>"jo","ж"=>"zh","з"=>"z","и"=>"i","й"=>"i","к"=>"k","л"=>"l","м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r","с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"kh","ц"=>"c","ч"=>"ch","ш"=>"sh","щ"=>"shh","э"=>"je","ю"=>"yu","я"=>"ja","ы"=>"i","ъ"=>"","ь"=>"","/"=>"","\\"=>"", ' ' => '-');
	$var = strtr($var,$alpha);
	return $var;
}
function get_url($str){
	return preg_replace('/^\./', 'http://www.psndemo1.com', $str);
}
function get_href($str){
	$str = preg_replace('/^\s+\d+&nbsp;/', '', $str);
	$str = preg_replace('/[^а-яА-Я\w ]+/u', '', $str);
	return strtolower(preg_replace('/\s+/', '-', $str));
}
function article_clear($article){
	return preg_replace('/[\W_]+/', '', $article);
}
function getLocImage($html){
	foreach($html->find('script') as $script){
		if (!method_exists($html, 'find')) return $log->error("Ошибка загрузки изображения");
		if (preg_match('/ficheImageLoc ?= ?"(.*?)"/u', $script->innertext, $array) != 1) continue;
		return $array[1];
	}
}
function image_catalog($file){
	error_reporting(E_ERROR);
	global $brend_title, $node_id, $log;
	if (!$file) return $log->error("Изображение не найдено");
	$array = [];
	$dir_big = "../images/nodes/big/$brend_title";
	$dir_small = "../images/nodes/small/$brend_title";
	// echo "$dir_big $dir_small";
	require_once('../vendor/class.upload.php');
	if (!file_exists($dir_big)) mkdir($dir_big);
	if (!file_exists($dir_small)) mkdir($dir_small);
	$handle = new upload($file);
	$handle->file_auto_rename = false;
	if ($handle->file_is_image != 1) return $log->error("Ссылка $file не является изображением");
	$handle_big = new upload($file);
	$handle_big->file_auto_rename = false;
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
	$handle->process($dir_small);
	$handle_big->process($dir_big);
	// echo $handle_big->log."<br><br>".$handle->log."<hr>";
	if ($handle->processed && $handle_big->processed) return $log->info("Изображение успешно скопировано");
	else $log->error("Ошибка копирования изображения");
}
function debug($obj, $name = ''){?>
	<div style="clear: both"></div>
	<?if ($name){?>
		<p style="font-weight: 800"><?=$name?>:</p>
	<?}?>
	<pre><?print_r($obj)?></pre>
<?}
?>