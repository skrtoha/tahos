<?php 
require_once ("../core/DataBase.php");

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$deliveries = $db->select('deliveries', '*', "`parent_id`={$_POST['id']}");
if (count($deliveries)){?>
	<option value=""></option>
	<?foreach ($deliveries as $value){?>
			<option value="<?=$value['id']?>"><?=$value['title']?></option>
	<?}
}
else echo false;?>
