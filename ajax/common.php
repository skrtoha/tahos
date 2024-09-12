<?
session_start();

use core\Authorize;
use core\Database;
use core\Mailer;
use core\YandexCaptcha;

require_once ("../core/Database.php");
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
        $query = str_replace('SELECT', 'SELECT DISTINCT SQL_CALC_FOUND_ROWS', $query);
		$query .= "
		    LEFT JOIN
		        #store_items si ON si.item_id = i.id
			WHERE
				i.article LIKE '$article%' OR 
				(i.title_full LIKE '{$_POST['value']}%' AND si.price IS NOT NULL)
            ORDER BY si.price DESC, i.article
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
        if ($db->found_rows() > $_POST['maxCountResults']){
            $output .= "<tr>
            <td colspan='2'><a class='show_more' href='/search/article/$article'>показать больше</a></td>
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
            'title' => $_POST['title'],
            'user_id' => $_POST['user_id']
        ];
        $db->insert('search_vin', $arrayInsert);
        break;
    case 'restore_password':
        try{
            $yandexCaptcha = new YandexCaptcha();
            $yandexCaptcha->check($_POST['smart-token']);
        }
        catch (\Exception $exception){
            message('Произошла ошибка проверки каптча', false);
            die();
        }
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
    case 'send_email_confirmation':
        $mailer = new Mailer('info');
        $token = \core\Provider::getRandomString(12);
        $db->update('users', ['confirm_email_token' => $token], "`id` = {$_SESSION['user']}");
        $result = $mailer->send([
            'emails' => [$_POST['email']],
            'subject' => 'Подтверждение адреса электронной почты',
            'body' => "
                <p>
                    Для подтверждения электронной почты перейдите по
                    <a href=\"{$_SERVER['HTTP_ORIGIN']}/confirm.php?token=$token\">ссылке</a>
                </p>
            "
        ]);
        if ($result === true) echo 'ok';
        else echo "Произошла ошибка";
        break;
    case 'send_sms_confirmation':
        $code = rand(1000, 9999);
        $result = \core\User::get(['user_id' => $_SESSION['user']]);
        $userInfo = $result->fetch_assoc();
        $db->update('users', ['confirm_sms_code' => $code], "`id` = {$_SESSION['user']}");
        $smsAero = new core\Sms\SmsAero();
        $smsAero->sendSms(
            $userInfo['phone'],
            "Tahos.ru, код подтверждения - $code"
        );
        break;
    case 'confirm_phone_number':
        $result = \core\User::get(['user_id' => $_SESSION['user']]);
        $userInfo = $result->fetch_assoc();
        if ($_POST['code'] == $userInfo['confirm_sms_code']){
            \core\User::update($_SESSION['user'], ['phone_confirmed' => 1]);
            echo 'ok';
        }
        break;
    case 'authorize':
        $login = $_POST['login'];
        if (!$login && $_POST['phone']) $login = $_POST['phone'];
        $password = md5($_POST['password']);
        if (!preg_match("/.+@.+/", $login)) $login = str_replace(array(' ', ')', '(', '-'), '', $login);
        $user = $db->select_one('users', "id,email", "(`email`='$login' OR `phone`='$login') AND `password`='$password'");
        if (empty($user)) echo('error');
        else{
            $_SESSION['user'] = $user['id'];
            if (isset($_POST['remember']) && $_POST['remember'] == 'on'){
                $jwt = Authorize::getJWT([
                    'user_id' => $user['id'],
                    'login' => $user['email']
                ]);
                setcookie('jwt', $jwt, time()+60*60*24*30);
            }
            $db->update('user_ips', ['user_id' => $user['id']], "ip = '{$_SERVER['REMOTE_ADDR']}'");
            echo 'ok';
        }
        break;
    case 'get-basket-amount':
        $result = Database::getInstance()->select(
            'basket',
            ['store_id', 'item_id', 'quan'],
            "`user_id` = {$_SESSION['user']}"
        );
        echo json_encode($result);
        break;
    case 'get-in-stock':
        $items = json_decode($_POST['items'], true);
        $where = '';
        foreach($items as $row){
            $where .= "(item_id = {$row['item_id']} AND store_id = {$row['store_id']}) OR ";
        }
        $where = substr($where, 0, -4);
        $result = Database::getInstance()->query("
            select
                si.store_id,
                si.item_id,
                concat(
                    si.in_stock,
                    ' ',
                    IF(
                        si.packaging != 1,
                        CONCAT(
                            ' (<span>уп. ',
                            si.packaging,
                            ' шт.</span>)'
                        ),
                        ''
                    )
                ) as in_stock
            from
                #store_items si
            where $where
        ")->fetch_all(MYSQLI_ASSOC);
        echo json_encode($result);
        break;
}
?>