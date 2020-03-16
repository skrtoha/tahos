<?php 
require_once ("../core/DataBase.php");

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$categories = $db->select('categories', 'id,title', "`parent_id`=".$_POST['category_id'], '', '', '', true);?>
<select style="margin-left: 10px;opacity: 1000" id="subcategory">
	<option value="">ничего не выбрано</option>
	<?if (count($categories)){
			foreach ($categories as $id => $category){?>
				<option value="<?=$id?>"><?=$category['title']?></option>
	<?}
	}?>
</select>
<a href="" id="apply_category">Применить</a>
