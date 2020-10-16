<?require_once ("../core/DataBase.php");
require_once('../core/functions.php');

$db = new core\DataBase();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

switch($_POST['act']){
	case 'get_issue_by_id':
		$issue = $db->select_one('issues', '*', "`id`={$_POST['issue_id']}");
		echo json_encode($issue);
		break;
	case 'addItemFromSearch':
		// print_r($_POST);
		$brend_id = core\Provider\Armtek::getBrendId($_POST['brend'], 'addItemFromSearch');
		if (!$brend_id){
			$db->insert(
				'brends',
				[
					'title' => $_POST['brend'],
					'href' => translite($_POST['brend'])
				]
			);
			$brend_id = $db->last_id();
		}
		$article = preg_replace('/[\W_]+/', '', $_POST['article']);
		$res = $db->insert('items', [
			'brend_id' => $brend_id,
			'article' => $article,
			'article_cat' => $_POST['article'],
			'title' => $_POST['title'],
			'title_full' => $_POST['title'],
			'source' => 'страницы поиска'
		]/*, ['print' => true]*/);
		$item_id = $db->last_id();
		$db->insert('articles', ['item_id' => $item_id, 'item_diff' => $item_id]);
		echo $item_id;
		break;
}
?>