<?php 
require_once ("../core/Database.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$id = $_POST['value_id'];
$table = $_POST['table'];
print_r($_POST);
switch ($table) {
	case 'items':
		$foto = $db->getFieldOnID('items', $id, 'foto');
		if (core\Item::update(['foto' => ''], ['id' => $id]) and unlink(core\Config::$imgPath . "/items/$id/$foto")) echo true;
		else echo false;
		break;
	case 'foto':
		$title = $_POST['foto_name'];
		$item_id = $_POST['item_id'];
		$db->delete('fotos', "`item_id`=$item_id AND `title`='$title'");
		unlink(core\Config::$imgPath . "/items/big/$item_id/$title");
		unlink(core\Config::$imgPath . "/items/small/$item_id/$title");
		echo true;
		break;
}
?>
