<?php  
require_once ("../core/DataBase.php");

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$act = $_POST['act'];
ob_start();
switch ($act) {
	case 'filters_ch':
		$category_id = $_POST['category_id'];
		$filters = $db->select('filters', '*', "`category_id` = $category_id");
		$count = count($filters);?>
		<p><b>Свойства</b></p>
		<table>
		<?for ($i = 0; $i < $count; $i++){?>
			<tr>
				<td><?=$filters[$i]['title']?></td>
				<td><a filter_id="<?=$filters[$i]['id']?>" class="prop_change" act="filter_ch" href="">Редактировать</a></td>
			</tr>
		<?}?>
		</table>
		<button style="bottom: 5px;left: 10px;position: absolute;" category_id="<?=$category_id?>" act="filter_add" class="prop_change">Добавить</button>
		<button style="bottom: 5px;right: 10px;position: absolute;" id="close_addtitional">Отменить</button>
		<?break;
	case 'filter_add':
		if ($db->insert('filters', array('title' => $_POST['new_filter'], 'category_id' => $_POST['category_id']))){
			$last_id = $db->getLastID('filters');?>
			<span id="filter_<?=$last_id?>" class="properties"><b><?=$_POST['new_prov']?></b>
				<a href="" filter_id="<?=$last_id?>" class="prop_change" act="values_ch">Изменить список</a>
			</span>
		<?}
		break;
	case 'filter_ch':
		$filter_id = $_POST['filter_id'];
		$filter = $db->select('filters', "*", "`id` = $filter_id");?>
		<p>Фильтр: <b><?=$filter[0]['title']?></b></p>
		<form action="" method="post">
			<input type="text" name="title" value="<?=$filter[0]['title']?>">
			<input type="hidden" name="filter_id" value="<?=$filter[0]['id']?>">
			<button style="" act="filter_ch_save" class="prop_change">Сохранить</button>
		</form>
		<button style="bottom: 5px;left: 10px;position: absolute;" act="filter_ch_delete" filter_id="<?=$filter[0]['id']?>" class="prop_change">Удалить</button>
		<button style="bottom: 5px;right: 10px;position: absolute;" id="close_addtitional">Отменить</button>
		<?break;
	case 'filter_ch_save':
		$title = $_POST['title'];
		$filter_id = $_POST['filter_id'];
		if ($db->update('filters', array('title' => $title), "`id` = $filter_id")) echo "$title";
		break;
	case 'filter_ch_delete':
		$filter_id = $_POST['filter_id'];
		$filter_values = $db->select('filters_values', 'id', "`filter_id`=$filter_id");
		if (count($filter_values)){
			foreach ($filter_values as $filter_value) {
				$db->delete('items_values', "`value_id`=".$filter_value['id']);
			}
		}
		$db->delete('filters_values', "`filter_id`=$filter_id");
		$db->delete('filters', "`id`=$filter_id");
		echo "ok";
		break;
	case 'values_ch':
		// print_r($_POST);
		// exit();
		$filter_id = $_POST['filter_id'];
		$values = $db->select('filters_values', '*', "`filter_id`=$filter_id");
		$count = count($values);?>
		<p>Свойства для <b>"<?=$db->getFieldOnID('filters', $filter_id, 'title')?>"</b></p>
		<table>
		<?for ($i = 0; $i < $count; $i++){?>
			<tr>
				<td><?=$values[$i]['title']?></td>
				<td><a value_id="<?=$values[$i]['id']?>" class="prop_change" act="value_ch" href="">Редактировать</a></td>
			</tr>
		<?}?>
		</table>
		<button style="bottom: 5px;left: 10px;position: absolute;" filter_id="<?=$filter_id?>" act="value_add" class="prop_change">Добавить</button>
		<button style="bottom: 5px;right: 10px;position: absolute;" id="close_addtitional">Отменить</button>
		<?break;
	case 'value_add':
		$filter_id = $_POST['filter_id'];
		$new_value = $_POST['new_value'];
		if ($db->insert('filters_values', array('title' => $new_value, 'filter_id' => $filter_id))) {
			$filter_values = $db->select('filters_values', "*", "`filter_id` = $filter_id");?>
			<option value="">ничего не выбрано</option>
			<?foreach($filter_values as $filter_value){?>
				<option value="<?=$filter_value['id']?>"><?=$filter_value['title']?></option>
			<?}?>
		<?}
		break;
	case 'value_ch':
		$value_id = $_POST['value_id'];
		$value = $db->select('filters_values', "*", "`id` = $value_id");?>
		<p>Значение: <b><?=$value[0]['title']?></b></p>
		<form action="" method="post">
			<input type="text" name="title" value="<?=$value[0]['title']?>">
			<input type="hidden" name="value_id" value="<?=$value[0]['id']?>">
			<button style="" act="value_ch_save" class="prop_change">Сохранить</button>
		</form>
		<button style="bottom: 5px;left: 10px;position: absolute;" act="value_ch_delete" value_id="<?=$value[0]['id']?>" class="prop_change">Удалить</button>
		<button style="bottom: 5px;right: 10px;position: absolute;" id="close_addtitional">Отменить</button>
		<?break;
	case 'value_ch_save':
		$title = $_POST['title'];
		$value_id = $_POST['value_id'];
		if ($db->update('filters_values', array('title' => $title), "`id` = $value_id")) echo "$title";
		break;
	case 'value_ch_delete':
		$value_id = $_POST['value_id'];
		$res_1 = $db->delete('filters_values', "`id` = $value_id");
		$res_2 = $db->delete('items_values', "`value_id`=$value_id");
		if ($db->delete('filters_values', "`id` = $value_id")) echo "ok";
		break;
}
$content = ob_get_contents();
ob_clean();
echo $content;?>