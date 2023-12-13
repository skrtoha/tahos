<?php 
require_once ("../core/Database.php");
require_once('../admin/templates/functions.php');

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

switch($_POST['type']){
	case 'subcategories':
		$parent_id = $_POST['category_id'];
		$res['href']= $db->getFieldOnID('categories', $parent_id, "href");
		$categories = $db->select('categories', 'title,href', "`parent_id`=$parent_id", 'title', false);
		$res['items'] = $categories;
		break;
}
echo json_encode($res);
?>
