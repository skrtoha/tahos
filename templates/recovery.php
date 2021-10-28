<?
/** @var $result mysqli_result */
$title = 'Установка нового пароля';

$result = \core\User::get(['auth_key' => $_GET['auth_key']]);
$user = $result->fetch_assoc();
\core\User::update($user['id'], ['auth_key' => null]);
if (!$result->num_rows) die("Произошла ошибка!");

if (!empty($_POST)){
    $password = md5($_POST['password_1']);
    \core\User::update($_POST['user_id'], ['password' => $password]);
    $_SESSION['user'] = $_POST['user_id'];
    message('Пароль успешно изменен!');
    header("Location: /");
    die();
}
?>
<div class="settings-page" style="margin-top: 20px">
	<h1>Установка нового пароля</h1>
	<div class="col">
		<form id="change-password" method="post">
			<h3>Сменить пароль</h3>
            <input type="hidden" name="user_id" value="<?=$user['id']?>">
			<div class="input-wrap">
				<label for="new_password">
					Новый пароль:
				</label>
				<input required type="password" name="password_1">
			</div>
			<div class="input-wrap">
				<label for="repeat_new_password">
					Повторить пароль:
				</label>
				<input required type="password" name="password_2">
			</div>
			<button id="save_form">Сменить</button>
		</form>
	</div>
</div>