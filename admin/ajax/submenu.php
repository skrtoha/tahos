<?php
session_start();
require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/DataBase.php");
$title = trim($_GET['title']);
?>
<span id="userInfo">Пользователь: <b><?=$_SESSION['manager']['login']?></b></span>
<div id="header">
	<span class="icon-menu"></span>
	<h1><?=$title?></h1>
</div>
<div id="contents">
	<div id="submenu">
		<?foreach(core\Config::$leftMenu[$title] as $key => $value){?>
			<a href="/admin/?view=<?=$value?>"><?=$key?></a>
		<?}?>
	</div>
</div>
