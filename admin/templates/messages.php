<?require_once('templates/pagination.php');
if (!$_GET['archive']) $where = "c.is_archive IS NULL OR c.is_archive=0";
else $where = "c.is_archive>0";
if (!$_GET['archive']){
	$page_title = "Сообщения";
	$status = "<a href='/admin'>Главная</a> > $page_title";
} 
else{
	$page_title = 'Архив сообщений';
	$status = "
		<a href='/admin'>Главная</a> > 
		<a href='/admin/?view=messages'>Сообщения</a> > 
		$page_title
	";
} 
$res_corresponds = $db->query(" 
		SELECT
			c.id,
			c.user_id,
			c.theme_id,
			IF (mth.title IS NOT NULL, mth.title, 'Переписка в товаре заказа') AS theme,
			c.order_id,
			c.store_id,
			c.item_id,
			CONCAT_WS(' ', u.name_1, u.name_2, u.name_3) AS fio,
			IF (m.is_read=0 AND m.sender=1, 0, 1) AS is_read,
			DATE_FORMAT(m.created, '%d.%m.%Y %H:%i') as created,
			m.sender,
			(
				SELECT
					COUNT(*) 
				FROM #messages
				WHERE correspond_id=c.id
			) as count
		FROM
			#corresponds c
		LEFT JOIN #messages m ON m.id=c.last_id
		LEFT JOIN #messages_themes mth ON mth.id=c.theme_id
		LEFT JOIN #users u ON u.id=c.user_id
		WHERE
			$where
		ORDER BY is_read, m.created DESC
	", '');
if (!$_GET['archive']){?>
	<a href="?view=messages&archive=1">Архив сообщений</a>
<?}
else{?>
	<a href="?view=messages">Сообщения</a>
<?}?>
<a href="?view=news">Новости</a>
<div id="total">Всего: <?=$res_corresponds->num_rows?></div>
<table class="t_table" cellspacing="1">
	<tr class="head">
		<td>Тема</td>
		<td>Тип</td>	
		<td>ФИО</td>
		<td>Всего сообщений</td>
		<td>Последнее</td>
	</tr>
	<?if (!$res_corresponds->num_rows){?>
		<td colspan="6">Сообщений не найдено</td>
	<?}
	else{
		while($value = $res_corresponds->fetch_assoc()){?>
			<tr class="messages_box" correspond_id="<?=$value['id']?>" style="<?=!$value['is_read'] ? 'font-weight: 700; color: #a24646' : ''?>"">
				<td><?=$value['theme']?></td>
				<?$type = $value['order_id'] ? "Заказ" : "Личная переписка"?>
				<td><?=$type?></td>
				<td><?=$value['fio']?></td>
				<td><?=$value['count']?></td>
				<td><?=$value['created']?></td>
			</tr>
		<?}
	}?>
</table>