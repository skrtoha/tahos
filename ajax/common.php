<? use core\Mailer;

require_once ("../core/DataBase.php");
require_once('../core/functions.php');

/** @var $result mysqli_result */

ini_set('error_reporting', E_PARSE);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$db = new core\Database();
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
		$res = core\Item::insert([
			'brend_id' => $brend_id,
			'article' => $article,
			'article_cat' => $_POST['article'],
			'title' => $_POST['title'],
			'title_full' => $_POST['title'],
			'source' => 'страницы поиска'
		]);
		$item_id = core\Item::$lastInsertedItemID;
		echo $item_id;
		break;
	case 'searchArticles':
		$output = '';
		$query = core\Item::getQueryItemInfo();
		$article = core\Item::articleClear($_POST['value']);
		$query .= "
			WHERE
				i.article LIKE '$article%'
			LIMIT
				0, {$_POST['maxCountResults']}
		";
		$res_items = $db->query($query);
		if (!$res_items->num_rows) break;
		foreach($res_items as $item){
			$output .= "
				<tr item_id=\"{$item['id']}\" class=\"item\">
					<td>
						<a href=\"/article/{$item['id']}-{$item['article']}\">{$item['brend']} - {$item['article']}</a>
					</td>
					<td>{$item['title_full']}</td>
				</tr>";
		}
		echo $output;
		break;
	case 'rememberUserSearch':
		core\User::saveUserSearch($_POST);
		break;
    case 'saveVin':
        $arrayInsert = [
            'vin' => $_POST['vin'],
            'title' => "{$_POST['brend']} {$_POST['model']} {$_POST['year']}",
            'user_id' => $_POST['user_id']
        ];
        $db->insert('search_vin', $arrayInsert);
        break;
    case 'restore_password':
        $result = \core\User::get(['email' => $_POST['email']]);
        if (!$result->num_rows) break;
        $user = $result->fetch_assoc();
        $string = \core\Provider::getRandomString(24);
        \core\User::update($user['id'], ['auth_key' => $string]);
        
        $mailer = new Mailer(Mailer::TYPE_INFO);
        $mailer->send([
            'emails' => $_POST['email'],
            'subject' => 'Восстановление пароля',
            'body' => "
                <p>Доброго времени суток!</p>
                <p>Кто-то, возможно, вы, отправил запрос на восстановление пароля с сайта <a href='http://tahos.ru'>tahos.ru</a>.</p>
                <p>Если это были не вы, просто проигнорируйте это письмо.</p>
                <p>Ссылка на <a href='http://tahos.ru/recovery/$string'>восстановление</a> пароля.</p>
            "
        ]);
        echo 'ok';
        break;
}
?>