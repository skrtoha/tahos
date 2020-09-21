<?php  
session_start();
require_once ("../core/DataBase.php");
require_once ("../core/functions.php");

$db = new core\DataBase();
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
if (empty($items)) echo 0;
else{
	ob_start();
	$str = "";?>
	<table>
		<?foreach($items as $item) {?>
			<tr>
				<td>
					<a href="/search/<?=$_POST['type_search']?>/<?=$item['text']?>">
						<?=$item['text']?>
					</a>
				</td>
				<td><?=$item['title']?></td></span>
			</tr>
		<?}?>
	</table>
<?} 
$content = ob_get_contents();
ob_clean();
echo $content;
?>