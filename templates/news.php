<?$characters = 100;
switch ($_GET['act']){
	case 'add':
		show_form('s_add');
		break;
	case 'change':
		show_form('s_change');
		break;
	case 's_add':
		if ($db->insert('news', array('new' => addslashes($_POST['new']), 'date' => time()))){
			message('Новость успешно сохранена!');
			header('Location: /admin/?view=news');
		}
		break;
	case 's_change':
		if ($db->update('news', array('new' => addslashes($_POST['new']), 'date' => time()), "`id`=".$_GET['id'])){
			message('Новость успешно сохранена!');
			header('Location: /admin/?view=news');
		}
		break;
	case 'delete':
		$res_1 = $db->delete('news', "`id`=".$_GET['id']);
		$res_2 = $db->delete('news_read', "`new_id`=".$_GET['id']);
		if ($res_1 and $res_2){
			message('Новость успешно удалена!');
			header('Location: /admin/?view=news');
		}
		break;
	default:
		view();
}
function view(){
	global $status, $db, $page_title;
	require_once('templates/pagination.php');
	$all = $db->getCount('news');
	$perPage = 15;
	$linkLimit = 10;
	$page = $_GET['page'] ? $_GET['page'] : 1;
	$chank = getChank($all, $perPage, $linkLimit, $page);
	$start = $chank[$page] ? $chank[$page] : 0;
	$news = $db->select('news', '*', '', 'date', false, "$start,$perPage");
	$count_news = count($news);
	$page_title= 'Новости';
	$status = "<a href='/admin'>Главная</a> > <a href='?view=messages'>Сообщения</a> > Новости";?>
	<div id="total">Всего: <?=$all?></div>
	<div class="actions"><a href="?view=news&act=add">Добавить</a></div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Дата</td>
			<td>Новость</td>
			<td></td>
		</tr>
		<?if (!$count_news){?>
			<td colspan="3">Новостей не найдено</td>
		<?}
		else{
			foreach($news as $new){?>
				<tr>
					<td><?=date('d.m.Y H:i', $new['date'])?></td>
					<td><?=stripslashes($new['new'])?></td>
					<td>
						<a href="/admin/?view=news&act=change&id=<?=$new['id']?>">Изменить</a>
						<a class="delete_item" href="/admin/?view=news&act=delete&id=<?=$new['id']?>">Удалить</a>
					</td>
				</tr>
			<?}
		}?>
	</table>
	<?=pagination($chank, $page, ceil($all / $perPage), $href = "/admin/?view=news&page=");?>
<?}
function show_form($act){
	global $status, $db;
	switch ($_GET['act']){
		case 'add':
			$status = "<a href='/admin'>Главная</a> > <a href='?view=messages'>Сообщения</a> > <a href='/admin/?view=news'>Новости</a> > Добавление новости";
			$action = "/admin/?view=news&act=s_add";
			break;
		case 'change':
			$new = $db->select('news', '*', "`id`=".$_GET['id']);
			$status = "<a href='/admin'>Главная</a> > <a href='?view=messages'>Сообщения</a> > <a href='/admin/?view=news'>Новости</a> > Редактирование новости";
			$action = "/admin/?view=news&act=s_change&id=".$new[0]['id'];
			break;
	}?>
	<form action="<?=$action?>" method="post">
		<p style="margin-top: 20px">Текст новости: </p>
		<textarea style="padding: 20px;box-sizing: border-box;font-size: 15px;height: 200px;display: block;width: 100%;" name="new" id="send-order-text" required><?=$new[0]['new']?></textarea>
		<button style="margin-top: 10px">Сохранить</button>
	</form>
<?}?>