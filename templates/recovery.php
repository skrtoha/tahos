<?
use core\User;

/** @var $result mysqli_result */
$title = 'Установка нового пароля';

if (!empty($_POST)){
    $password = md5($_POST['password_1']);

    $user_id = User::checkAuthKey($_POST['auth_key']);

    User::update($user_id, [
        'password' => $password,
        'auth_key' => null
    ]);
    $_SESSION['user'] = $user_id;
    message('Пароль успешно изменен!');
    header("Location: /");
    die();
}

$user_id = User::checkAuthKey($_GET['auth_key']);

?>
<div class="settings-page" style="margin-top: 20px">
	<h1>Установка нового пароля</h1>
	<div class="col">
		<form id="change-password" method="post">
			<h3>Сменить пароль</h3>
            <input type="hidden" name="auth_key" value="<?=$_GET['auth_key']?>">
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