<?
$act = $_GET['act'];
switch ($act) {
	case 'delete':
		$similar_id = $_GET['similar_id'];
		$id = $_GET['id'];
		if ($db->delete('similar', "`id`=$similar_id")){
			setcookie('message', "Успешно удалено!");
			setcookie('message_type', "ок");
			header("Location: "."?view=similar&id=$id");
		}
		else echo('<script>show_message("Произошла ошибка!", "error")</script>');
		break;
	case 'add':
		$item_id = $_GET['item_id'];
		$similar_id = $_GET['similar_id'];
		if (count($db->select('similar', '*', "`similar_id`=$similar_id AND `item_id`=$item_id")) > 0){
			setcookie('message', "Такой товар уже присутствует!");
			setcookie('message_type', "error");
		}
		else{
			if ($db->insert('similar', array('item_id' => $item_id, 'similar_id' => $similar_id))){
				setcookie('message', "Товар успешно добавлен!");
				setcookie('message_type', "ok");
			}
			else{
				setcookie('message', "Произошла ошибка!");
				setcookie('message_type', "error");
			}
		}
		header("Location: "."?view=similar&id=$item_id");
		break;
};
view(); 	
function view(){
	global $db, $page_title, $status, $view;
	$id = $_GET['id'];
	$item = $db->select('items', 'id,title,category_id', "`id`=$id");
	$category_id = $item[0]['category_id'] ? $item[0]['category_id'] : $_GET['category_id'];
	$page_title = $item[0]['title']." - подобные товары";
	$status = "<a href='/admin'>Главная</a> > <a href='?view=categories&parent_id=0'>Каталог товаров</a> > ".get_navigation_item($category_id, $page_title);
	$similar = $db->select('similar', "*", "`item_id` = $id");
	// show_array($similar);
	$count = count($similar);?>
	<div id="total">Всего: <?=$count?></div>
	<div id="search_2">
		<form action="" name="search">
			<input item_id="<?=$id?>" type_table="similar" type="text" id="search_text_2" placeholder="Введите ID или название товара для добавления">
		</form>
		<input name="hide_search" type="hidden" id="hide_search">
		<div id="searched_2"></div>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Название товара</td>
			<td></td>
		</tr>
	<?for ($i = 0; $i < $count; $i++){
		$item = $db->select('items', '*', "`id`=".$similar[$i]['similar_id'])?>
	<tr>
		<td><b>ID <?=$item[0]['id']?>: </b><?=$item[0]['title']?></td>
		<td><a class="foto_delete" href="?view=similar&act=delete&similar_id=<?=$similar[$i]['id']?>&id=<?=$similar[0]['item_id']?>">Удалить</a></td>
	</tr>
	<?}?>
	</table>
	<div class="actions"><a href="<?=$_SERVER['HTTP_REFERER']?>">Назад</a></div>
<?}?>