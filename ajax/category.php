<?php 
require_once ("../core/DataBase.php");
require_once('../admin/templates/functions.php');
session_start();
error_reporting(E_ERROR);

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$id = $_POST['id'];
// print_r($_POST);
$old_value = $_POST['old_value'];
$new_value = $_POST['new_value'];
$category = $db->select('categories', '*', "`id`=$id");
$category = $category[0];
$parent_id = $category['parent_id'];
$title = $db->getFieldOnID('categories', $parent_id, 'title');
switch($_POST['table']){
	case 'href':
		$where = "`id`!=$id AND `parent_id`=$parent_id AND `href`='$new_value'";
		if ($db->getCount('categories', $where)){
			$res['error'] = "Ссылка '$new_value' в категории '$title' уже присутствует!";
		}
		else{
			$res['error'] = false;
			$array['href'] = str_replace(' ', '-', translite($new_value));
			$db->update('categories', $array, "`id`=$id");
		}
		break;
	case 'pos':
		$db->update('categories', ['pos' => $new_value], "`id`=$id");
		$res['error'] = false;
		break;
	case 'category':
		$where = "`id`!=$id AND `parent_id`=$parent_id AND `title`='$new_value'";
		if ($db->getCount('categories', $where)){
			$res['error'] = "Подкатегория '$new_value' в категории '$title' уже присутствует!";
		}
		else{
			$res['error'] = false;
			$array = ['title' => $new_value];
			if (!$category['href']){
				$array['href'] = str_replace(' ', '-', translite($array['title']));
				$res['href'] = $array['href'];
			} 
			$db->update('categories', $array, "`id`=$id");
		} 
		break;
	case 'add':
		$parent_id = $_POST['parent_id'];
		$array = [
			'title' => $new_value,
			'href' => str_replace(' ', '-', translite($new_value)),
			'parent_id' => $parent_id
		];
		$where = "`parent_id`=$parent_id AND `href`='{$array['href']}'";
		if ($db->getCount('categories', $where)) $res['error'] = 'Ошибка формирования ссылки!';
		end_exit();
		$where = "`parent_id`=$parent_id AND `title`='$new_value'";
		if ($db->getCount('categories', $where)){
			$res['error'] = "Подкатегория '$new_value' в категории '$title' уже присутствует!";
		}
		end_exit();
		$res['error'] = false;
		$db->insert('categories', $array);
		$array['id'] = $db->getMax('categories', 'id');
		$res = array_merge($res, $array);
		break;
	case 'favorite':
		$array = [
			'item_id' => $_POST['item_id'],
			'user_id' => $_POST['user_id']
		];
		if ($_POST['act']) $db->insert('favorites', $array);
		else $db->delete('favorites', "`item_id`={$_POST['item_id']} AND `user_id`={$_POST['user_id']}");
		$res['error'] = false;
		break;
	case 'rating':
		$array = [
			'user_id' => $_SESSION['user'], 
			'rate' => $_POST['rate'],
			'item_id' => $_POST['item_id']
		];
		$db->insert('items_ratings', $array);
		$db->query("
			UPDATE #items SET `rating` = `rating` + {$_POST['rate']} 
			WHERE `id`={$_POST['item_id']}
		");
		$res['error'] = false;
		break;
	case 'filter_title':
		$r = $db->update('filters', ['title' => $new_value], "`id`=$id");
		if ($r === true) $res['error'] = false;
		else $res['error'] = $db->error();
		break;
	case 'filter_pos':
		$r = $db->update('filters', ['pos' => $new_value], "`id`=$id");
		if ($r === true) $res['error'] = false;
		else $res['error'] = $db->error();
		break;
}
function end_exit(){
	global $res;
	if ($res['error']){
		echo json_encode($res);
		exit();
	}
}
// print_r($res);
echo json_encode($res);
?>
