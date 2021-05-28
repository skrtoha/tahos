<?php 
require_once ("../core/DataBase.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$categories = $db->select('categories', 'id,title', "`parent_id`=0", '', '', '', true);?>
<span class="properties">
	<select style="opacity: 1000" id="add_subcategories">
		<option value="">ничего не выбрано</option>
		<?foreach ($categories as $id => $category){?>
			<option value="<?=$id?>"><?=$category['title']?></option>
		<?}?>
	</select>
</span>
