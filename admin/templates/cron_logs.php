<?$act = $_GET['act'];
switch ($act) {
	default:
		view();
}
function view(){
	global $status, $db, $page_title;
	$page_title = "Логи крон";
	$status = "<a href='/admin'>Главная</a> > $page_title"?>
	<div style="height: 10px"></div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Название</td>
			<td></td>
		</tr>
		<?foreach(scandir('logs') as $value){
		    if (!preg_match('/^common_/', $value)) continue?>
			<tr>
				<td><a target="_blank" href="/admin/logs/<?=$value?>"><?=$value?></a></td>
				<td></td>
			</tr>
		<?}?>
		</table>
<?}?>