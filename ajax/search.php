<?php  
session_start();
require_once ("../core/DataBase.php");
require_once ("../core/functions.php");

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$query = core\Item::getQueryItemInfo();
$query .= " 
	LEFT JOIN #search s ON s.item_id = i.id
	WHERE
		s.user_id = {$_POST['user_id']} 
	ORDER BY
		s.date desc
	LIMIT 0,10
";
$items = $db->query($query, '');
if (!$items->num_rows) exit();
ob_start();
$firstElement = true;?>
<?foreach($items as $item) {?>
	<tr item_id = "<?=$item['id']?>" class="item <?=$firstElement ? 'active' : ''?>">
		<td>
			<a href="/article/<?=$item['id']?>-<?=$item['article']?>">
				<?=$item['brend']?> - <?=$item['article']?>
			</a>
		</td>
		<td><?=$item['title_full']?></td>
	</tr>
	<?$firstElement = false;?>
<?}
echo ob_get_clean()
?>