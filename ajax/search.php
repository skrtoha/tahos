<?php  
session_start();
require_once ("../class/database_class.php");
require_once ("../core/functions.php");

$db = new DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$user_id = $_SESSION['user'];
switch ($_POST['type_search']){
	case 'article': $type_search = 1;break;
	case 'barcode': $type_search = 2; break;
	case 'vin': $type_search = 3; break;
}
if (!$user_id){
	echo 0;
	exit();
}
$items = $db->select('search', '*', "`user_id`=$user_id AND `type`=$type_search", 'date', false);
// print_r($items);
$count = count($items);
if (!$count) echo 0;
else{
	ob_start();
	$str = "";?>
	<table>
		<?for ($i = 0; $i < $count; $i++) {?>
			<tr>
				<td>
					<a href="/search/<?=$_POST['type_search']?>/<?=$items[$i]['text']?>">
						<?=$items[$i]['text']?>
					</a>
				</td>
				<td><?=$items[$i]['title']?></td></span>
			</tr>
		<?}?>
	</table>
<?} 
$content = ob_get_contents();
ob_clean();
echo $content;
?>