<?require_once ("../core/DataBase.php");
require_once ("../core/functions.php");
session_start();
/*debug($_POST);
exit();*/
$db = new core\Database();
if ($_POST['message_send']){
	if (!$_POST['correspond_id']){
		$result = $db->insert(
			'corresponds',
			[
				'user_id' => $_SESSION['user'] ? $_SESSION['user'] : $_POST['user_id'],
				'theme_id' => 5,//вопрос по заказу
				'order_id' => $_POST['order_id'],
				'store_id' => $_POST['store_id'],
				'item_id' => $_POST['item_id'],
				'last_id' => $db->getMax('messages', 'id') + 1,
			]
		);
		$last_correspond_id = $db->last_id();
	} 
	else $last_correspond_id = $_POST['correspond_id'];
	$insert = [
		'text' => $_POST['text'],
		'correspond_id' => $last_correspond_id
	];
	if ($_POST['sender'] == 'user') $insert['sender'] = 1;
	else $insert['sender'] = 0;
	$db->insert('messages', $insert, ['print_query' => 0]); 
	$last_id = $db->last_id();
	$db->update(
		'corresponds', 
		[
			'last_id' => $last_id,
			'is_archive' => 0,
			'is_hidden' => 0
		], 
		"`id`=$last_correspond_id"
	);
	$fotos = json_decode($_POST['json_fotos'], true);
	if (count($fotos)){
		foreach($fotos as $foto){
			$db->insert('msg_fotos', ['title'=>$foto, 'message_id' => $last_id]);
		}
	}
	if ($_POST['sender'] == 'user') header("Location: /correspond/$last_correspond_id");
	else header("Location: /admin/?view=correspond&id=$last_correspond_id");
}
?>