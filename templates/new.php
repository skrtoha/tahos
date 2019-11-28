<?$db->insert('news_read', array('new_id' => $_GET['id'], 'user_id' => $_SESSION['user']));
$news = $db->select_unique("
	SELECT
		n.id,
		n.text,
		DATE_FORMAT(n.created, '%d.%m.%Y %H:%i') as date
	FROM 
		#news n
	WHERE 
		n.id={$_GET['id']}
", '');
$title = 'Новости';
?>
<div class="orders-message">
	<div class="dialog" style="margin-top: 20px">
		<div class="dialog-box">
			<?$new = $db->select('news', '*', "`id`=".$_GET['id']);?>
			<div class="message-box">
				<span class="sender">Администрация</span>
				<span class="send-time"><?=$news[0]['date']?></span>
				<p class="message"><?=stripslashes($news[0]['text'])?></p>
			</div>
		</div>
		<a class="button" href="<?=$_SERVER['HTTP_REFERER']?>">Назад</a>
	</div>
</div>