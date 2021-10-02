<?
use admin\functions\LeftMenu;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title><?=$page_title?></title>
	<link rel="stylesheet" type="text/css" href="/admin/css/common.css">
	<?if(file_exists("css/{$_GET['view']}.css")){?>
		<link rel="stylesheet" type="text/css" href="css/<?=$_GET['view']?>.css">
	<?}?>
	<?if (in_array($_GET['view'], ['connections', 'reports', 'returns', 'index', 'orders'])){?>
		<link rel="stylesheet" type="text/css" href="/vendor/datetimepicker/jquery.datetimepicker.min.css">
	<?}?>
	<?if (in_array($_GET['view'], ['items', 'categories'])){?>
		<link rel="stylesheet" type="text/css" href="/vendor/cropper/cropper.css">
	<?}?>
	<?if (in_array($_GET['view'], ['connections', 'index', 'brends'])){?>
		<link rel="stylesheet" type="text/css" href="/vendor/chosen/chosen.css">
	<?}?>
	<link rel="apple-touch-icon" href="/img/favicon/apple-touch-icon.png">
	<link rel="apple-touch-icon" sizes="72x72" href="/img/favicon/apple-touch-icon-72x72.png">
	<link rel="apple-touch-icon" sizes="114x114" href="/img/favicon/apple-touch-icon-114x114.png">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<link rel="stylesheet" href="/css/magnific-popup.css">
	<?if (core\Config::$isUseTinymce){?>
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
	<?}?>
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
	<?if ($_SESSION['auth']){
		?>
		<div id="left_menu">
			<span id="closeLeftMenu" class="icon-cross1"></span>
			<div class="block">
				<div class="title">Главное меню</div>
					<ul>
						<li><a href="/admin/?view=prices&act=items&id=23">Основной склад</a></li>
						<li><a href="/admin/?view=cron&act=updatePrices">Обновление цен</a></li>
						<?foreach(admin\functions\LeftMenu::$leftMenu as $key => $value){
							if (
								!is_array($value) &&
								core\Managers::isAccessForbidden($value)
							) continue;
							?>
							<li class="<?=$_GET['view'] == $value ? 'checked' : ''?>">
								<a class="<?=is_array($value) ? 'isExistsSubMenu' : ''?>" href="<?=is_array($value) ? '' : "?view=$value"?>">
									<?if (is_array($value)){?>
										<span class="<?=core\Managers::isActiveMenuGroup($value, $_GET['view']) ? 'icon-circle-up' : 'icon-circle-down'?>"></span>
									<?}?>
									<?=$key?>
									<?if (!is_array($value) && in_array($value, ['returns', 'orders', 'messages', 'funds'])){
										if ($countNew = LeftMenu::getCountNew($value)){?>
											<span>(<?=$countNew?>)</span>
										<?}
									}?>
								</a>
								<?if (is_array($value)){?>
									<ul class="<?=core\Managers::isActiveMenuGroup($value, $_GET['view']) ? 'active' : ''?>">
										<?foreach($value as $k => $v){
											/*if (core\Managers::isAccessForbidden($v)) continue*/;?>
											<li class="<?=$_GET['view'] == $v ? 'checked' : ''?>"><a href="?view=<?=$v?>"><?=$k?></a></li>
										<?}?>
									</ul>
								<?}?>
							</li>
						<?}?>
						<?if ($_SESSION['auth']){?>
							<li><a href="?view=authorization&act=regout">Выйти</a></li>
						<?}?>
					</ul>
				</ul>
			</div>
		</div>
	<?}?>
	<div id="main_field" class="<?=!$_SESSION['auth'] ? 'nonAuthorizated' : ''?>">
		<span id="userInfo">Пользователь: <b><?=$_SESSION['manager']['login']?></b></span>
		<div id="header">
			<span class="icon-menu"></span>
			<?if (isset($page_title)){?>
				<h1><?=$page_title?></h1>
			<?}?>
		</div>
		<?if (isset($status)){?>
			<div id="status" class="t_form" style=""><div class="bg"><?=$status?></div></div>
		<?}?>
		<div id="contents"><?=$content?></div>
	</div>
	<div style="clear: both"></div>
</div>
	<!-- Optimized loading JS Start -->
	<script>var scr = {"scripts":[
		{"src" : "/js/libs.min.js", "async" : false},
		{"src" : "/js/jquery.priceformat.min.js", "async" : false},
		{"src" : "/js/jquery.cookie.js", "async" : false},
		{"src" : "/admin/js/common.js", "async" : false},
		{"src" : "/js/jquery.form.js", "async" : false},
		{"src" : "/js/jquery.preload.min.js", "async" : false},
		{"src" : "/vendor/paginationjs/pagination.min.js", "async" : false},
		<?if (in_array($view, ['connections', 'reports', 'returns', 'index', 'orders'])){?>
			{"src" : "/vendor/datetimepicker/jquery.datetimepicker.full.min.js", "async" : false},
		<?}?>
		<?if (in_array($view, ['items', 'orders', 'returns', 'brends', 'providers'])){?>
			{"src" : "/admin/js/show_store_info.js", "async" : false},
		<?}?>
		<?if (in_array($view, ['items', 'categories'])){?>
			{"src" : "/vendor/cropper/cropper.js", "async" : false},
		<?}?>
		<?if (in_array($view, ['prices'])){?>
			{"src" : "/admin/js/add_item_to_store.js", "async" : false},
		<?}?>
		<?if (in_array($view, ['connections', 'index', 'brends'])){?>
			{"src" : "/vendor/chosen/chosen.jquery.min.js", "async" : false},
		<?}?>
		<?
		$arrayIntuitiveSearch = [
			'items', 
			'prices', 
			'test_api_providers', 
			'brends',
			'goods_arrival',
			'users',
			'category',
            'providers'
		];
		if (in_array($view, $arrayIntuitiveSearch)){?>
			{"src" : "/vendor/intuitive_search/script.js", "async" : false},
		<?}?>
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
		<span id="modal_close" class="icon-cross1"></span>
	</div>
</div>
<div id="tooltip"></div>
</body>
</html>
