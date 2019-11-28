<?php
require_once ("../class/database_class.php");

$db = new DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

session_start();
switch($_POST['act']){
	case 'delete_new':
		$res = $db->update(
			'news_read',
			['hidden' => 1],
			"`user_id`={$_SESSION['user']} AND `new_id`={$_POST['id']}"
		);
		if (!$db->row_count()) $db->insert(
			'news_read',
			[
				'user_id' => $_SESSION['user'],
				'hidden' => 1,
				'new_id' => $_POST['id']
			]
		);
		break;
	case 'delete_message':
		$res = $db->update(
			'corresponds',
			['is_hidden' => 1],
			"`id`={$_POST['id']}"
		);
		break;
	case 'delete_all_messages':
		$r1 = $db->update(
			'corresponds',
			['is_hidden' => 1],
			"`user_id`={$_SESSION['user']}"
		);
		$r2 = $db->update(
			'news_read',
			['hidden' => 1],
			"`user_id`={$_SESSION['user']}"
		);
		if ($r1 && $r2) $res = true;
		break;
	case '':
		$res = $db->insert('messages_themes', ['title' => $_POST['m']]);
		if (gettype($res) == 'boolean') echo $db->get_last_id();
		else echo $res;
		exit();
}
if ($res) echo "ok";
?>