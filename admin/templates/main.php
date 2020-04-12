<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title><?=$page_title?></title>
	<link rel="stylesheet" type="text/css" href="/css/admin.css">
	<?if(file_exists("css/{$_GET['view']}.css")){?>
		<link rel="stylesheet" type="text/css" href="css/<?=$_GET['view']?>.css">
	<?}?>
	<link rel="apple-touch-icon" href="/img/favicon/apple-touch-icon.png">
	<link rel="apple-touch-icon" sizes="72x72" href="/img/favicon/apple-touch-icon-72x72.png">
	<link rel="apple-touch-icon" sizes="114x114" href="/img/favicon/apple-touch-icon-114x114.png">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<link rel="stylesheet" href="/css/magnific-popup.css">
	<script src="/js/tinymce/tinymce.min.js"></script>
	<script type="text/javascript">
		tinymce.init({
			language: "ru",
			selector: 'textarea.need',
			height: 300,
			theme: 'modern',
			plugins: [
					'advlist autolink lists link image charmap print preview hr anchor pagebreak',
					'searchreplace wordcount visualblocks visualchars code fullscreen',
					'insertdatetime media nonbreaking save table contextmenu directionality',
					'emoticons template paste textcolor colorpicker textpattern imagetools codesample'
			],
			toolbar1: 'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
			toolbar2: 'print preview media | forecolor backcolor emoticons | codesample',
			image_advtab: true,
			templates: [
					{ title: 'Test template 1', content: 'Test 1' },
					{ title: 'Test template 2', content: 'Test 2' }
			],
			content_css: [
					'//fonts.googleapis.com/css?family=Lato:300,300i,400,400i',
					'//www.tinymce.com/css/codepen.min.css'
			]
		});
	</script>
</head>
</head>
<body>
<div id="message">
	<div><div></div></div>
</div>
<div id="additional" style="display: none"><div></div></div>
<div id="popup" style="display: none"><img src="/images/preload.gif" alt=""></div>
<input type="hidden" name="imgUrl" value="<?=core\Config::$imgUrl?>">
<div id="container">
	<div id="left_menu">
		<span id="closeLeftMenu" class="icon-cross1"></span>
		<div class="block">
			<div class="title">Главное меню</div>
			<ul>
				<li class="<?=($view == 'items' or $view == 'item' or $view == 'substitutes' or $view == 'analogies')  ? "checked" : ""?>"><a href="?view=items">Номенклатура</a>
				<li class="<?=($view == 'min_prices') ? "checked" : ""?>"><a href="?view=min_prices">Обновление цен</a>
				<li class="<?=($view == 'sendings')  ? "checked" : ""?>">
					<a href="?view=sendings">Доставки</a>
					<?getCountLeftMenu('sendings', '`is_new`=1')?>
				<li class="<?=($view == 'orders')  ? "checked" : ""?>">
					<a href="?view=orders">Заказы</a>
					<?getCountLeftMenu('orders', '`is_new`=1')?>
				</li>
				<li class="<?=($view == 'funds')  ? "checked" : ""?>">
					<a href="?view=funds">Финансовые операции</a>
					<?getCountLeftMenu('funds', '`type_operation`=1 AND `is_new`=1')?>
				<li class="<?=($view == 'categories' or $view =='category') ? "checked" : ""?>"><a href="?view=categories">Категории товаров</a>
				<li class="<?=($view == 'brends') ? "checked" : ""?>"><a href="?view=brends">Бренды товаров</a>
				<li class="<?=($view == 'messages' or $view == 'correspond' or $view == 'news')  ? "checked" : ""?>">
					<a href="?view=messages">Сообщения</a>
					<?getCountLeftMenu('messages', '`is_read`=0 AND `sender`=1');?>
				<li class="<?=($view == 'currencies' or $view == 'currencies')  ? "checked" : ""?>"><a href="?view=currencies">Валюта</a>
				<li class="<?=($view == 'prices')  ? "checked" : ""?>"><a href="?view=prices">Прайсы</a>
				<li class="<?=($view == 'providers')  ? "checked" : ""?>"><a href="?view=providers">Поставщики</a>
				<li class="<?=($view == 'issues')  ? "checked" : ""?>"><a href="?view=issues">Точки выдачи</a>
				<li class="<?=$view == 'users'  ? "checked" : ""?>"><a href="?view=users">Пользователи</a>
				<li class="<?=$view == 'original-catalogs'  ? "checked" : ""?>"><a href="?view=original-catalogs">Оригинальные каталоги</a>
				<li class="<?=$view == 'order_issues'  ? "checked" : ""?>"><a href="?view=order_issues">Выдачи товара</a>
				<li class="<?=$view == 'help'  ? "checked" : ""?>"><a href="?view=texts&tab=">Тексты</a>
				<li class="<?=$view == 'files'  ? "checked" : ""?>"><a href="?view=files">Файлы</a>
				<li class="<?=$view == 'reports'  ? "checked" : ""?>"><a href="?view=reports">Отчеты</a>
				<?if ($_SESSION['auth']){?>
					<li><a href="?view=authorization&act=regout">Выйти</a></li>
				<?}?>
			</ul>
		</div>
	</div>
	<div id="main_field">
		<div id="header">
			<span class="icon-menu"></span>
			<h1><?=$page_title?></h1>
		</div>
		<div id="status" class="t_form" style=""><div class="bg"><?=$status?></div></div>
		<div id="contents"><?=$content?></div>
	</div>
</div>
	<!-- Optimized loading JS Start -->
	<script>var scr = {"scripts":[
		{"src" : "/js/libs.min.js", "async" : false},
		{"src" : "/js/jquery.priceformat.min.js", "async" : false},
		{"src" : "/js/jquery.cookie.js", "async" : false},
		{"src" : "/js/admin.js", "async" : false},
		{"src" : "/js/jquery.form.js", "async" : false},
		{"src" : "/js/jquery.preload.min.js", "async" : false},
		{"src" : "/vendor/accordion.js", "async" : false},
		{"src" : "/vendor/paginationjs/pagination.min.js", "async" : false},
		<?if (file_exists("js/$view.js")){
			echo '{"src" : "/admin/js/'.$view.'.js", "async" : false},';
		}?>
		]};!function(t,n,r){"use strict";var c=function(t){if("[object Array]"!==Object.prototype.toString.call(t))return!1;for(var r=0;r<t.length;r++){var c=n.createElement("script"),e=t[r];c.src=e.src,c.async=e.async,n.body.appendChild(c)}return!0};t.addEventListener?t.addEventListener("load",function(){c(r.scripts);},!1):t.attachEvent?t.attachEvent("onload",function(){c(r.scripts)}):t.onload=function(){c(r.scripts)}}(window,document,scr);
	</script>
	<?if ($_GET['view'] == 'issues' and ($_GET['act'] == 'add' or $_GET['act'] == 'change')){?>
		<script src="//api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
	<?}?>
<div id="modal-container">
	<div class="modal">
		<div id="modal_content"></div>
		<div id="modal_close"></div>
	</div>
</div>
</body>
</html>