<?
require_once('../core/DataBase.php');
session_start();

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

$user_id = $_SESSION['user'];
$user = $db->select('users', 'password,email', "`id`=$user_id"); $user = $user[0];
$curr_password = $user['password'];
$old_password = md5($_POST['old_password']);
$new_password = md5($_POST['new_password']);
foreach ($_POST as $key => $value){
	switch($key){
		case 'old_password':
			if ($curr_password != $old_password){
				echo 1;
				exit();
			}
			else $update['password'] = $new_password;
			break;
		case 'telefon': 
			$telefon = '+'.str_replace(array(' ', ')', '(', '-'), '', $value);
			if ($db->getCount('users', "`id`!= $user_id AND `telefon`='$telefon'")){
				echo 4;
				exit();
			}
			$update['telefon'] = $telefon; 
			break; 
		case 'email':
			if ($db->getCount('users', "`id`!= $user_id AND `email`='$value'")){
				echo 2;
				exit();
			}
			else $update['email'] = $value;
			break;
		default: if ($key != 'new_password') $update[$key] = $value;
	}
}
if ($db->update('users', $update, "`id`=$user_id")) echo 3;
?>
