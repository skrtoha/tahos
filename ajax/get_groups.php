<?php 
require_once ("../core/DataBase.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$array = $db->select('issues', '*');
foreach ($array as $value) {
	$balloon = "<p style='text-align: center;font-weight: 700'><b>".$value['title']."</b></p>";
	$balloon .= "<p>".$value['adres']."</p>";
	$balloon .= "<p>".$value['desc']."</p>";
	$temp = explode(',', $value['coords']);
	// $balloon .= "<button class='apply_issue' issue_id='".$value['id']."' coord_1='".$temp[0]."' coord_2='".$temp[1]."' >Выбрать</button>";
	$result[] = array(
		'id' => $value['id'],
		'coord_1' =>$temp[0],
		'coord_2' => $temp[1],
		'title' => $value['title'],
		'balloon' => $balloon
		);
}
echo json_encode($result);