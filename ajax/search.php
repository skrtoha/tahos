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
	LEFT JOIN #search_items s ON s.item_id = i.id
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
	<tr item_id="<?=$item['id']?>" class="item <?=$firstElement ? 'active' : ''?>">
		<td>
			<a href="/article/<?=$item['id']?>-<?=$item['article']?>">
				<?=$item['brend']?> - <?=$item['article']?>
			</a>
		</td>
		<td><?=$item['title_full']?></td>
	</tr>
	<?$firstElement = false;?>
<?}
$searchVin = $db->select('search_vin', '*', "`user_id` = {$_POST['user_id']}", 'date', false, "0, 10");
if (!empty($searchVin)){
    foreach($searchVin as $row){?>
        <tr>
            <td>
                <a href="/original-catalogs/legkovie-avtomobili#/carInfo?q=<?=$row['vin']?>">
                    <?=$row['vin']?>
                </a>
            </td>
            <td><?=$row['title']?></td>
        </tr>
    <?}?>
<?}
echo ob_get_clean()
?>