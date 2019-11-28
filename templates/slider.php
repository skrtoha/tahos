<?
$act = $_GET['act'];
switch ($act) {
	case 'add':
		$name = $_FILES['foto']['name'];
		if (count($db->select('slider', 'foto', "`foto` = '$name'")) > 0){
			setcookie('message', "Фото с таким именем уже присутствует!");
			setcookie('message_type', "error");
			// header("Location: ".$_SERVER['HTTP_REFERER']);
		}
		else{
			move_uploaded_file($_FILES['foto']['tmp_name'], "../images/slider/$name");
			$array = array('foto' => $name);
			if ($db->insert('slider', $array)){
				setcookie('message', "Фото успешно сохранено!");
				setcookie('message_type', "ок");
				// header("Location: "."?view=slider");
			} 
			else echo('<script>show_message("Произошла ошибка!", "error")</script>');
		}
		break;
	case 'delete':
		$id=$_GET['id'];
		if ($db->delete('slider', "`id`=$id")){
			setcookie('message', "Фото успешно удалено!");
			setcookie('message_type', "ок");
			header("Location: "."?view=slider");
		} 
		else echo('<script>show_message("Произошла ошибка!", "error")</script>');
		break;
}
show_form();
function show_form(){
	global $db, $page_title, $status;
	$page_title = "Настойки слайдера";
	$status = "<a href='/admin'>Главная</a> > Настойки слайдера";
	$fotos = $db->select('slider', '*');
	if (count($fotos) == 0){?>
	<div class="t_form">
		<div class="bg">
			<div class="field">
				<div class="title">Фото</div>
				<div class="value">
					<p>Для слайдера фото не задано</p>
					<form action="?view=slider&act=add" method="post" enctype="multipart/form-data">
						<input type="file" name="foto">
						<input type="submit" value="Добавить">
					</form>
				</div>
			</div>
		</div>
	</div>
	<?}
	else{?>
		<div class="t_form">
		<div class="bg">
		<?for ($i = 0; $i < count($fotos); $i++){?>
			<div class="field">
				<div class="title">Фото <?=$i + 1?></div>
				<div class="value">
					<img width="400" src="/images/slider/<?=$fotos[$i]['foto']?>" alt="">
					<a class="foto_delete" href="?view=slider&act=delete&id=<?=$fotos[$i]['id']?>">Удалить</a>
				</div>
			</div>
		<?}?>
		</div>
		<form style="margin-left: 300px" action="?view=slider&act=add" method="post" enctype="multipart/form-data">
			<input type="file" name="foto">
			<input type="submit" value="Добавить">
		</form>
	</div>
	<?}?>
	<div class="actions"><a href="<?=$_SERVER['HTTP_REFERER']?>">Назад</a></div>
<?}?>