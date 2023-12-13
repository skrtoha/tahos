<?php  
require_once ("../core/Database.php");
require_once('../core/functions.php');
session_start();

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();
$output = [];

// debug ($_GET);

$params = ['viewTab' => $_GET['viewTab']];
if (isset($_GET['search']) && $_GET['search']) $params['search'] = $_GET['search'];
if (isset($_GET['fv']) && $_GET['fv']) $params['fv'] = $_GET['fv'];
if (isset($_GET['sliders']) && $_GET['sliders']) $params['sliders'] = $_GET['sliders'];
$params['comparing'] = isset($_GET['comparing']) && $_GET['comparing'] == 'on';

foreach($_GET['acts'] as $act){
	switch($act){
		case 'items':
			$start = ($_GET['pageNumber'] - 1) * $_GET['perPage'];
			$params['limit'] = "$start,{$_GET['perPage']}";
			$params['sort'] = $_GET['sort'];
			$params['direction'] = $_GET['direction'];
			$items = core\Item::getItemsByCategoryID($_GET['category_id'], $params);
			if (empty($items)){
				$output['items'] = [];
				break;
			}
			foreach($items as $key => $item){
				if ($item['photo']){
					$items[$key]['src'] = core\Config::$imgUrl. "/items/small/{$item['item_id']}/{$item['photo']}";
					$items[$key]['alt'] = '';
				} 
				else{
					$items[$key]['src'] = core\Config::$imgUrl. '/no_foto.png';
					$items[$key]['alt'] = 'Фото отсутствует';
				}
				if (isset($item['filter_values']) && !empty($item['filter_values'])){
					$items[$key]['description'] = '<div class="description">';
					foreach($item['filter_values'] as $value) $items[$key]['description'] .= "<p>{$value['filter_value']}</p>";
					$items[$key]['description'] .= '</div>';
				}
				else $items[$key]['description'] = '';
				$items[$key]['rating'] = core\Item::getHtmlRating($item['rating']);
				if ($item['price'] || $item['delivery']) $items[$key]['priceDelivery'] = '
					<div class="price-and-delivery">
						<p class="price">от <span>' . $item['price'] . '</span></p>
						<p class="delivery">от ' . $item['delivery'] . ' дн.</p>
					</div>
				';
				else $items[$key]['priceDelivery'] = '';
			}
			$output['items'] = $items;
			break;
		case 'filters':
			$output['filters'] = core\Filter::getFilterValuesByCategoryID($_GET['category_id'], $params);
			break;
		case 'totalNumber':
			$query = core\Item::getQueryItemsByCategoryID($_GET['category_id'], $params);
			$res = $db->query($query, '');
			$output['totalNumber'] = $res->num_rows;
			break;
	}
}

echo json_encode($output);
?>