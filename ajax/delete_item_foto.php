<?php 
require_once ("../core/DataBase.php");

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$res_1 = $db->delete('fotos', "`item_id`=".$_POST['item_id']." AND `title`='".$_POST['foto_name']."'");
$res_2 = unlink(core\Config::$imgPath . '/items/'.$_POST['item_id'].'/'.$_POST['foto_name']);
if ($res_2 and $res_1) echo "ok";
?>
