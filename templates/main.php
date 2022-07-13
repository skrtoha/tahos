<?php

use core\Setting;

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title><?=$title?></title>
	<meta name="description" content="">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<!-- Template Basic Images Start -->
	<meta property="og:image" content="path/to/image.jpg">
	<link rel="shortcut icon" href="/img/favicon/favicon.png" type="image/x-icon">
	<link rel="apple-touch-icon" href="/img/favicon/apple-touch-icon.png">
	<link rel="apple-touch-icon" sizes="72x72" href="/img/favicon/apple-touch-icon-72x72.png">
	<link rel="apple-touch-icon" sizes="114x114" href="/img/favicon/apple-touch-icon-114x114.png">
	<link href="/css/main.css" rel="stylesheet" type="text/css" />
    <link href="/css/_main.css" rel="stylesheet" type="text/css" />
    <?if (file_exists($_SERVER['DOCUMENT_ROOT'])."/css/_$view.css"){?>
        <link rel="stylesheet" type="text/css" href="/css/_<?=$view?>.css">
    <?}?>
	<link href="/css/<?=$view?>.css" rel="stylesheet" type="text/css" />
		<?if (in_array($view, ['category'])){?>
		<link rel="stylesheet" type="text/css" href="/vendor/paginationjs/pagination.css">
	<?}?>
    <?if (in_array($view, ['settings', 'registration'])){?>
        <link rel="stylesheet" href="/vendor/addressee/jquery.fias.min.css">
        <link rel="stylesheet" href="/vendor/addressee/style.css">
    <?}?>
    <?if (in_array($view, ['basket'])){?>
        <link rel="stylesheet" href="/admin/fonts/icomoon.eot">
        <link rel="stylesheet" href="/vendor/addressee/style.css">
    <?}?>
    <?if (in_array($view, ['article', 'category'])){?>
        <link rel="stylesheet" type="text/css" href="/vendor/blueimp/css/blueimp-gallery.min.css">
    <?}?>
	<link rel="stylesheet" href="/css/fonts.min.css">
	<meta name="theme-color" content="#0081BC">
	<script type="text/javascript" src="/js/libs.min.js"></script>
	<meta name="msapplication-navbutton-color" content="#0081BC">
	<meta name="apple-mobile-web-app-status-bar-style" content="#0081BC">
</head>
<body>
	<input type="hidden" name="device" value="<?=$device?>">
	<input type="hidden" name="imgUrl" value="<?=core\Config::$imgUrl?>">
	<input type="hidden" name="user_id" value="<?=$_SESSION['user']?>">
	<div id="popup" style="display: none"><img src="/images/preload.gif" alt=""></div>
	<div id="message">
		<div><div></div></div>
	</div>
	<header>
		<div class="wrapper">
			<a href="/" class="logo"></a>
			<div class="catalog_btn">
				<div class="arrow_up"></div>
			</div>
			<div class="catalog">
			<?$categories = $db->select('categories', '*', '`parent_id`=0', 'pos', true);?>
				<ul>
					<li>
						<a href="/original-catalogs">
							<span class="icon original_catalogs"></span>
							Оригинальные каталоги
						</a>
					</li>
					<?if (count($categories))
					foreach ($categories as $value) {
						if (!$value['href']) continue;?>
						<li>
							<a href="/category/<?=$value['href']?>">
								<span class="icon cat_<?=$value['id']?>"></span>
								<?=$value['title']?>
							</a>
						</li>
					<?}?>
				</ul>
			</div>
			<div class="search">
				<?$type = $_GET['type'] ? $_GET['type'] : 'article';
				//строка добавлена из-за того, что на других страницах, кроме стартовой, не работал checked
				if ($type != 'article' && $type != 'barcode' && $type != 'vin') $type = '';
				?>
				<form action="/search/" method="get">
					<input class="search_input" value="" name="search_input" type="text" placeholder="Введите VIN или артикул, например: 9091901122" autocomplete="off">
					<div class="settings">
						<input type="radio" <?=$type == 'article' || !$type ? "checked" : ""?> value="article" name="type" id="radio1">
						<label for="radio1" data-placeholder="Введите номер детали">Искать по VIN или номеру детали</label>
						
						<input type="radio" <?=$type == 'barcode' ? "checked" : ""?> name="type" id="radio2" value="barcode">
						<label  for="radio2" data-placeholder="Введите штрих-код">Поиск по штрих-коду</label>
						
						<input type="radio" <?=$type == 'vin' ? "checked" : ""?> name="type" id="radio3" value="vin">
						<label for="radio3" type_search="vin" data-placeholder="Введите VIN-номер">Искать по VIN-номеру</label>
					</div>
					<button class="search_btn"></button>
				</form>
				<div class="hints">
					<table class="previous_search"></table>
					<table class="coincidences"></table>
				</div>
			</div>
			<div class="search_btn search_btn_2"></div>
			<a href="#" class="cart">
				<div class="arrow_up"></div>
				<?if ($_SESSION['user']){
					$count_basket = 0;
					if (!empty($basket)) foreach ($basket as $val) $count_basket += $val['quan'];
				} 
				if ($count_basket){?>
					<span><?=$count_basket?></span>
				<?}?>
			</a>
			<?if ($_SESSION['user']){?>
				<div class="profile_btn">
					<span><?=$user['name_2']?></span>
					<div class="arrow_up"></div>
				</div>
				<div class="profile">
				<ul>
					<li><a href="/garage">Гараж</a></li>
					<li><a href="/orders">Заказы</a></li>
					<?
					$c_news = $db->count_unique("
						SELECT
							COUNT(n.id) as count
 						FROM 
							#news n
						LEFT JOIN #news_read nr ON nr.user_id={$_SESSION['user']} AND nr.new_id=n.id
						WHERE 
							n.created>='{$user['created']}' AND nr.new_id IS NULL
					", '');
					$c_messages = $db->count_unique("
						SELECT 
							COUNT(*) as count
						FROM
							#corresponds c
						LEFT JOIN #messages m ON c.last_id=m.id
						WHERE 
							c.user_id={$_SESSION['user']} AND
							m.sender=0 AND
							m.is_read=0
					", '');
					$c_news_messages = $c_news + $c_messages;
					?>
					<li>
						<a href="/messages">Сообщения
							<?if ($c_news_messages){?>
								<span><?=$c_news_messages?></span>
							<?}?>
						</a>
					</li>
					<li><i class="fa fa-heart-o" aria-hidden="true"></i><a href="/favorites">Избранное</a></li>
					<li><a href="/payment">Оплата</a></li>
					<li><a href="/settings">Настройки</a></li>
					<li><a href="/exit">Выход</a></li>
					<li>
						<a href="/account">
							<span class="account_title">На вашем счету</span>
							<span class="account_sum"><?=get_bill()?></span>
						</a>
					</li>
				</ul>
			</div>
			<?}
			else{?>
				<div class="login_btn">
					<span>Войти</span>
					<div class="arrow_up"></div>
				</div>
				<div class="login">
					<h3>Авторизация</h3>
					<form action="/authorization" method="post">
						<input type="hidden" name="form_autorization" value="1">
						<p>Логин</p>
						<input type="text" name="login" placeholder="Введите телефон или почту">
						<p>Пароль</p>
						<input type="password" name="password">
						<div class="forgot_password">
							<a href="#">Напомнить пароль</a>
						</div>
						<div class="not_remember">
							<input id="w_not_remember_checkbox" type="checkbox" name="remember">
							<label for="w_not_remember_checkbox">Запомнить</label>
						</div>
						<button>Войти</button>
						<div class="registration_link">
							<a href="/registration">Зарегистрироваться</a>
						</div>
					</form>
					<div class="social_buttons">
						<script src="//ulogin.ru/js/ulogin.js"></script>
						<div id="uLogin" data-ulogin="display=buttons;fields=first_name,last_name;redirect_uri=http%3A%2F%2Ftahos.ru/authorization;mobilebuttons=0">
							<?$socials = $db->select('socials', '*');
							foreach ($socials as $key => $value){?>
								<span class="social <?=$value['title']?>" data-uloginbutton="<?=$value['title']?>"></span>
							<?}?>
						</div>
					</div>
                    <div id="restore_password" class="product-popup mfp-hide">
                        <h2>Востановить пароль</h2>
                        <div class="content">
                            <form action="">
                                <div class="wrapper">
                                    <div class="left">Введите ваш email:</div>
                                    <div class="right">
                                        <input type="email" name="email">
                                    </div>
                                </div>
                                <input type="submit" value="Отправить">
                            </form>
                        </div>
                    </div>
				</div>
			<?}?>
			<div class="clear"></div>
		</div>
	</header>
    <h1 class="hidden"><?=$title?></h1>
	<input type="hidden" name="currency_id" value="<?=$user['currency_id']?>">
	<div class="cart-popup">
		<table class="cart-popup-table">
		<?if (!empty($basket)){?>
			<tr>
				<th>Наименование</th>
				<th>Кол-во</th>
				<th>Сумма</th>
				<th><img user_id="<?=$_SESSION['user']?>" id="basket_clear" src="/img/icons/icon_trash.png" alt="Удалить"></th>
			</tr>
			<?$total_basket = 0;
			$total_quan = 0;
            foreach ($basket as $value) {
                $total_quan += $value['quan'];
                if ($user['markupSettings']['markup'] && $user['markupSettings']['showInBasket'] == 'on'){
                    $price = floor($value['price'] * $user['markupSettings']['markup'] / 100 + $value['price']);
                }
                else $price = $value['price'];
                $total_price += $price * $value['quan']?>
                <tr store_id="<?=$value['store_id']?>" item_id="<?=$value['item_id']?>">
                    <td><?=$value['brend']?> <a class="articul" href="<?=$value['href']?>"><?=$value['article_cat']?></a> <?=$value['title']?></td>
                    <td><?=$value['quan']?> шт.</td>
                    <td>
                        <input type="hidden" name="quan" value="<?=$value['quan']?>">
                        <input type="hidden" name="price" value="<?=$value['price']?>">
                        <span class="price_format"><?=$price * $value['quan']?></span>
                        <i class="fa fa-rub" aria-hidden="true"></i>
                    </td>
                    <td>
                        <span division="<?=$value['price']?>" quan="<?=$value['quan']?>" class="delete-btn">
                            <i style="margin: 0" class="fa fa-times" aria-hidden="true"></i>
                        </span>
                    </td>
                </tr>
            <?}?>
			<tr>
				<th>Итого</th>
				<th><span id="total_quan"><?=$total_quan?></span>&nbsp;шт.</th>
				<th colspan="2"><span class="price_format" id="total_basket"> <?=$total_price?></span><i class="fa fa-rub" aria-hidden="true"></i></th>
			</tr>
		</table>
		<button>Перейти в корзину</button>
	<?}
		else{?>
			<tr>
				<td colspan="4">Корзина пуста</td>
			</tr>
			<?}?>
		</table>
	</div>
	<!-- <div class="page-wrap"> -->
		<div id="main"><?=$content?></div>
	<!-- </div> -->
	<div id="full-image">
		<div class="img-wrap">
			<a class="close" href="#" title="Закрыть"></a>
		</div>
	</div>
 
    <?
    $titles = json_decode(Setting::get('texts', 'titles'), true);
    $articleList = $db->select('text_articles', ['href', 'title', 'column'], "`column` IN (1, 4)");
    ?>
 
	<footer>
		<div class="wrapper">
			<div class="item information">
				<h4><?=$titles[1]?></h4>
				<ul>
                    <li><a href="/sitemap">Карта сайта</a></li>
                    <?foreach($articleList as $article){
                        if ($article['column'] != 1) continue; ?>
                        <li>
                            <a href="/page/<?=$article['href']?>"><?=$article['title']?></a>
                        </li>
                    <?}?>
				</ul>
			</div>
			<div class="item shop">
				<h4><?=$titles[2]?></h4>
				<?
                $rubricList = $db->select('text_rubrics', '*');
                ?>
				<ul>
					<li><a href="/help">Помощь</a></li>
					<?if (count($rubricList)){
                        foreach($rubricList as $rubric){?>
                            <li><a href="/help/<?=$rubric['id']?>"><?=$rubric['title']?></a></li>
                        <?}
					}?>
				</ul>
			</div>
			<div class="item catalog_list">
				<h4><?=$titles[3]?></h4>
				<ul>
					<?if (!empty($categories)){
                        foreach ($categories as $value){
                            if (!$value['isShowAtBottom']) continue; ?>
                            <li><a href="/category/<?=$value['href']?>"><?=$value['title']?></a></li>
                        <?}?>
                    <?}?>
				</ul>
			</div>
			<div class="item partnership">
				<h4><?=$titles[4]?></h4>
				<ul>
                    <?foreach($articleList as $article){
                        if ($article['column'] != 4) continue; ?>
                        <li><a href="<?=$article['href']?>"><?=$article['title']?></a></li>
                    <?}?>
					<li><a href="https://vk.com/tahos">Мы Вконтакте</a></li>
				</ul>
				<div class="payment_systems"></div>
			</div>
			<div class="item contacts">
				<div class="item_box">
					<h4>Контакты</h4>
					<?if ($user['issue_id']) $issue = [
						'issue_id' => $user['issue_id'],
						'title' => $user['issue_title'],
						'desc' => $user['issue_desc'],
						'adres' => $user['issue_adres'],
						'telephone' => $user['issue_telephone'],
						'email' => $user['issue_email'],
						'twitter' => $user['issue_twitter'],
						'vk' => $user['issue_vk'],
						'facebook' => $user['issue_facebook'],
						'google' => $user['issue_google'],
						'ok' => $user['issue_ok'],
						'coords' => $user['issue_coord']
					];
					else{
						$issue = $db->select_one('issues', "*", "`is_main`=1");
						$issue['issue_id'] = $issue['id'];
					}?>
					<div class="phones">
						<?if ($issue['telephone']){?>
							<input type="text" name="telephone" readonly value="<?=$issue['telephone']?>">
							<?if ($issue['email']){?>
								<a href="mailto:<?=$issue['email']?>" class="footer_email"><?=$issue['email']?></a>
							<?}
						}?>
					</div>
					<div class="address">
						<?if ($issue['adres']){?>
							<?=$issue['adres']?>
						<?}?>
					</div>
					<?if ($issue['adres']){?>
						<a issue_id="<?=$issue['issue_id']?>" href="" id="driving_direction">Схема проезда</a>
					<?}?>
				</div>
				<?if (
						$issue['twitter'] ||
						$issue['vk'] || 
						$issue['facebook'] || 
						$issue['google'] || 
						$issue['ok']
					){?>
					<div class="social_networks">
						<?if ($issue['twitter']){?>
							<a target="_blanc" href="<?=$issue['twitter']?>" class="twitter"></a>
						<?}?>
						<?if ($issue['vk']){?>
							<a target="_blanc" href="<?=$issue['vk']?>" class="vk"></a>
						<?}?>
						<?if ($issue['facebook']){?>
							<a target="_blanc" href="<?=$issue['facebook']?>" class="facebook"></a>
						<?}?>
						<?if ($issue['google']){?>
							<a target="_blanc" href="<?=$issue['google']?>" class="google"></a>
						<?}?>
						<?if ($issue['ok']){?>
							<a target="_blanc" href="<?=$issue['ok']?>" class="ok"></a>
						<?}?>
					</div>
				<?}?>
				<div class="clear"></div>
			</div>
			<div class="clear"></div>
		</div>
	</footer>
	<div class="h_overlay"></div>
	<div class="overlay"></div>
	<!-- Optimized loading JS Start -->
	<script>var scr = {"scripts":[
		{"src" : "/js/jquery.cookie.js", "async" : false},
		{"src" : "/js/jquery.priceformat.min.js", "async" : false},
		{"src" : "/js/common.js", "async" : false},
		{"src" : "/js/jquery.preload.min.js", "async" : false},
		{"src" : "/js/jquery.form.js", "async" : false},
		// {"src" : "/js/to_top.js", "async" : false},
		<?if (in_array($view, ['category', 'article'])){?>
			{"src" : "/js/item_full.js", "async" : false},
		<?}?>
		<?if (in_array($view, ['category'])){?>
			{"src" : "/vendor/paginationjs/pagination.min.js", "async" : false},
		<?}?>
		<?if (in_array($view, ['article', 'orders', 'order'])){?>
			{"src" : "/js/get_store_info.js", "async" : false},
		<?}?>
        <?if (in_array($view, ['orders', 'basket', 'order', 'account'])){?>
            {"src": "/vendor/pickmeup/pickmeup.min.js", "async" : false},
        <?}?>
        <?if (in_array($view, ['article', 'category'])){?>
            {"src": "/vendor/blueimp/js/jquery.blueimp-gallery.min.js"},
        <?}?>
		{"src" : "/js/<?=$view?>.js", "async" : false}
		]};!function(t,n,r){"use strict";var c=function(t){if("[object Array]"!==Object.prototype.toString.call(t))return!1;for(var r=0;r<t.length;r++){var c=n.createElement("script"),e=t[r];c.src=e.src,c.async=e.async,n.body.appendChild(c)}return!0};t.addEventListener?t.addEventListener("load",function(){c(r.scripts);},!1):t.attachEvent?t.attachEvent("onload",function(){c(r.scripts)}):t.onload=function(){c(r.scripts)}}(window,document,scr);
	</script>
	<!-- Optimized loading JS End -->
</body>
</html>
