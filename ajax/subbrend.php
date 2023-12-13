<?php 
require_once ("../core/Database.php");
require_once("../core/functions.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

switch($_POST['act']){
	case 'subbrend_add':
		$new_subbrend = $_POST['new_subbrend'];
		if ($db->getCount('brends', "`title`= '$new_subbrend'")) echo 0;
		else{
			$array = array('parent_id' => $_POST['brend_id'], 'title' => $new_subbrend, 'href' => translite($new_subbrend));
			if ($db->insert('brends', $array, ['print_query' => false])) echo $db->getMax('brends', 'id');
		}
		break;
	case 'subbrend_delete':
		if ($db->delete('brends', '`id`='.$_POST['subbrend_id'])) echo "ok";
		break;
}
?>