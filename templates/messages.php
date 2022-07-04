<? use core\Breadcrumb;

if (!$_SESSION['user']) header('Location: /');
$title = 'Сообщения';
Breadcrumb::add('/messages', 'Сообщения');
$is_messages = false;
$news = $db->select_unique("
	SELECT
		n.id,
		n.text,
		DATE_FORMAT(n.created, '%d.%m.%Y %H:%i') AS date,
		IF (nr.user_id IS NULL, 0, 1) AS is_read
	FROM 
		#news n
	LEFT JOIN #news_read nr ON n.id=nr.new_id AND nr.user_id={$_SESSION['user']}
	WHERE 
		n.created>='{$user['created']}' AND
		(nr.hidden=0 OR nr.hidden IS NULL)
	ORDER BY n.created DESC
", '');
$corresponds = $db->query("
	SELECT
		c.id,
		c.user_id,
		c.theme_id,
		IF (mth.title IS NOT NULL, mth.title, 'Переписка в товаре заказа') AS theme,
		c.order_id,
		c.store_id,
		c.item_id,
		m.is_read,
		IF (m.is_read=0 AND m.sender=0, 0, 1) AS is_read,
		DATE_FORMAT(m.created, '%d.%m.%Y %H:%i') AS created,
		m.text,
		IF (
			m.sender=0, 
			'Администрация', 
			CONCAT_WS(' ', u.name_1, u.name_2, u.name_3)
		) AS sender
	FROM
		#corresponds c
	LEFT JOIN #messages m ON m.id=c.last_id
	LEFT JOIN #messages_themes mth ON mth.id=c.theme_id
	LEFT JOIN #users u ON u.id=c.user_id
	WHERE
		c.user_id={$_SESSION['user']} AND
		(c.is_hidden IS NULL OR c.is_hidden=0)
	ORDER BY m.created DESC
", '');
Breadcrumb::out();
?>
<div class="messages-page">
	<h1>Сообщения</h1>
	<button id="new_message">Написать новое сообщение</button>
	<table>
		<tr>
			<th>Сообщение</th>
			<th style="text-align: right"><img class="delete_all_messages" src="img/icons/icon_trash.png" alt="Удалить" id="delete_all_messages"></th>
		</tr>
		<?if (!empty($corresponds)){
			foreach ($corresponds as $value){
				if ($value['is_read']) continue;?>
				<tr type="correspond" id="<?=$value['id']?>">
					<td class="unread messages">
						<a class="message-link" href="#"></a>
						<p class="subject"><?=$value['theme']?></p>
						<a href="#" class="sender"><?=$value['sender']?></a>
						<p class="send-time"><?=$value['created']?></p>
						<p class="message"><?=$value['text']?></p>
					</td>
					<td class="unread">
						<span class="unread delete-message" type="message" correspond_id="<?=$value['id']?>">
							<i class="fa fa-times" aria-hidden="true"></i>
						</span>
					</td>
				</tr>
			<?}
		}
		if (!empty($news)){
			foreach ($news as $new) {
				if ($new['is_read']) continue;?>
				<tr type="new" id="<?=$new['id']?>">
					<td class="unread news">
						<a class="message-link" href="#"></a>
						<p class="subject">Новости</p>
						<a href="#" class="sender">Администрация</a>
						<p class="send-time"><?=$new['date']?></p>
						<p class="message"><?=stripslAShes($new['text'])?></p>
					</td>
					<td class="unread"><span class="delete-message" type="new"><i class="fa fa-times" aria-hidden="true"></i></span></td>
				</tr>
			<?}
		}
		if (!empty($corresponds)){
			foreach ($corresponds as $value){
				if (!$value['is_read']) continue;?>
				<tr type="correspond" id="<?=$value['id']?>">
					<td class="messages">
						<a class="message-link" href="#"></a>
						<p class="subject"><?=$value['theme']?></p>
						<a href="#" class="sender"><?=$value['sender']?></a>
						<p class="send-time"><?=$value['created']?></p>
						<p class="message"><?=$value['text']?></p>
					</td>
					<td class="">
						<span class="unread delete-message" type="message" correspond_id="<?=$value['id']?>">
							<i class="fa fa-times" aria-hidden="true"></i>
						</span>
					</td>
				</tr>
			<?}
		}
		if (!empty($news)){
			foreach ($news as $new) {
				if (!$new['is_read']) continue;?>
				<tr type="new" id="<?=$new['id']?>">
					<td class="news">
						<a class="message-link" href="#"></a>
						<p class="subject">Новости</p>
						<a href="#" class="sender">Администрация</a>
						<p class="send-time"><?=$new['date']?></p>
						<p class="message"><?=stripslAShes($new['text'])?></p>
					</td>
					<td><span class="delete-message" type="new"><i class="fa fa-times" aria-hidden="true"></i></span></td>
				</tr>
			<?}
		}
		if (empty($news) && empty($corresponds)){?>
			<tr>
				<td colspan="2">Сообщений не найдено</td>
			</tr>
		<?}?>
	</table>
</div>