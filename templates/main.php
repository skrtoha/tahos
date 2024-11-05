<?php

/** @var $title string */
/** @var $view string */
/** @var $device string */
/** @var $content string */
/** @global $db \core\Database */
/** @var $user array */

use core\Cache;
use core\Config;
use core\Seo;
use core\Setting;
use core\YandexCaptcha;
use core\User;

$token = Cache::getCacheToken();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8">

    <?$seo = Seo::get($_SERVER['REQUEST_URI']);
    if (isset($seo['description'])) {?>
        <meta name="description" content="<?=$seo['description']?>">
    <?}?>
    <? if(isset($seo['keywords'])){?>
        <meta name="keywords" content="<?=$seo['keywords']?>">
    <?}?>
    <?if (isset($seo['title'])) {?>
        <title><?=$seo['title']?></title>
    <?}
    else {?>
        <title><?=$title?></title>
    <?}?>

	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<!-- Template Basic Images Start -->
	<link rel="shortcut icon" href="/img/favicon/favicon.png" type="image/x-icon">
	<link rel="apple-touch-icon" href="/img/favicon/apple-touch-icon.png">
	<link rel="apple-touch-icon" sizes="72x72" href="/img/favicon/apple-touch-icon-72x72.png">
	<link rel="apple-touch-icon" sizes="114x114" href="/img/favicon/apple-touch-icon-114x114.png">
	<link href="/css/main.css?<?=$token?>" rel="stylesheet" type="text/css" />
	<link href="/css/<?=$view?>.css?<?=$token?>" rel="stylesheet" type="text/css" />
    <link href="/vendor/plugins/formstyler/jquery.formstyler.css?<?=$token?>" rel="stylesheet" type="text/css" />

    <link href="/vendor/plugins/owl.carousel/owl.carousel.min.css?<?=$token?>" rel="stylesheet" type="text/css" />
    <link href="/vendor/plugins/owl.carousel/owl.theme.default.min.css?<?=$token?>" rel="stylesheet" type="text/css" />

    <?if (in_array($view, ['orders', 'basket', 'order', 'account'])){?>
        <link href="/vendor/pickmeup/pickmeup.css?<?=$token?>" rel="stylesheet" type="text/css" />
    <?}?>
    <?if (in_array($view, ['basket'])){?>
        <link href="/vendor/basketAdditionalOptions/style.css?<?=$token?>" rel="stylesheet" type="text/css" />
    <?}?>
    <?if (in_array($view, ['article', 'orders'])){?>
        <link rel="stylesheet" type="text/css" href="/css/provider_info.css?<?=$token?>">
    <?}?>
    <?if (in_array($view, ['article', 'category', 'favorites'])){?>
        <link rel="stylesheet" type="text/css" href="/css/item_full.css?<?=$token?>">
    <?}?>
    <?if (in_array($view, ['category'])){?>
		<link rel="stylesheet" type="text/css" href="/vendor/paginationjs/pagination.css?<?=$token?>">
		<link rel="stylesheet" type="text/css" href="/vendor/plugins/ion.rangeSlider/ion.rangeSlider.min.css?<?=$token?>">
	<?}?>
    <?if (in_array($view, ['settings', 'registration'])){?>
        <link rel="stylesheet" href="/vendor/addressee/jquery.fias.min.css?<?=$token?>">
        <link rel="stylesheet" href="/vendor/addressee/style.css?<?=$token?>">
    <?}?>
    <?if (in_array($view, ['basket', 'index', 'article'])){?>
        <link rel="stylesheet" href="/admin/fonts/icomoon.eot">
        <link rel="stylesheet" href="/vendor/addressee/style.css?<?=$token?>">
    <?}?>
    <?if (in_array($view, ['article', 'category', 'favorites'])){?>
        <link rel="stylesheet" type="text/css" href="/vendor/blueimp/css/blueimp-gallery.min.css?<?=$token?>">
    <?}?>
    <?if (in_array($view, ['garage', 'account', 'favorites', 'orders'])){?>
        <link rel="stylesheet" type="text/css" href="/css/ionTabs.css?<?=$token?>">
    <?}?>
	<link rel="stylesheet" href="/css/fonts.min.css?<?=$token?>">
	<meta name="theme-color" content="#0081BC">

	<script src="/vendor/plugins/jquery.min.js?<?=$token?>"></script>
	<script src="/vendor/plugins/formstyler/jquery.formstyler.min.js?<?=$token?>"></script>
	<script src="/vendor/plugins/owl.carousel/owl.carousel.min.js?<?=$token?>"></script>
	<script src="/vendor/plugins/jquery.jscrollpane.min.js?<?=$token?>"></script>
    <script src="/vendor/plugins/ion.tabs.min.js?<?=$token?>"></script>
    <script src="/vendor/plugins/jquery.magnific-popup.min.js?<?=$token?>"></script>

	<meta name="msapplication-navbutton-color" content="#0081BC">
	<meta name="apple-mobile-web-app-status-bar-style" content="#0081BC">

    <?if (Config::$useYandexMetrica) {?>
        <!-- Yandex.Metrika counter --> <script type="text/javascript" > (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)}; m[i].l=1*new Date(); for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }} k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)}) (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym"); ym(45222504, "init", { clickmap:true, trackLinks:true, accurateTrackBounce:true, webvisor:true, ecommerce:"dataLayer" }); </script> <noscript><div><img src="https://mc.yandex.ru/watch/45222504" style="position:absolute; left:-9999px;" alt="" /></div></noscript> <!-- /Yandex.Metrika counter -->
    <?}?>
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
        <?if (!empty($debt)){?>
            <div class="top-message debt"><?=$debt['message']?></div>
        <?}?>
        <?if ($_SESSION['user'] && !User::isRegistedTelegram($_SESSION['user'])){?>
            <div class="top-message telegram">
                <a target="_blank" href="<?= Config::$telegram['url']?>">Подпишитесь на наш Телеграм бот</a>
                <span class="icon-cross1"></span>
            </div>
        <?}?>
        <?if (!$_SESSION['user'] && $view == 'article'){?>
            <div class="top-message telegram">
                <p>После авторизации будут доступны дополнительные предложения и цены.</p>
            </div>
        <?}?>
		<div class="wrapper">
			<a href="/" class="logo"></a>
			<div class="catalog_btn">
				<div class="arrow_up"></div>
			</div>
			<div class="catalog">
				<ul>
					<li>
						<a href="/original-catalogs">
							<span class="icon original_catalogs"></span>
							Оригинальные каталоги
						</a>
					</li>
					<?
                    /** @var array $categories */
                    if (count($categories))
					foreach ($categories as $key => $value) {
						if (!$value['href']) continue;?>
						<li>
							<a href="/category/<?=$value['href']?>">
								<span class="icon cat_<?=$value['id']?>"></span>
								<?=$key?>
							</a>
                            <?if (!empty($value['subcategories'])){?>
                                <div class="arrow_up"></div>
                                <ul class="subcategory">
                                    <?for ($i = 0; $i < 22; $i++){?>
                                        <li>
                                            <a href="/category/<?=$value['href']?>/<?=$value['subcategories'][$i]['href']?>">
                                                <?=$value['subcategories'][$i]['title']?>
                                            </a>
                                        </li>
                                    <?}?>
                                </ul>
                                <?if (count($value['subcategories']) > 22){?>
                                    <ul class="subcategory">
                                        <?for ($i = 22; $i < 44; $i++){?>
                                            <li>
                                                <a href="/category/<?=$value['href']?>/<?=$value['subcategories'][$i]['href']?>">
                                                    <?=$value['subcategories'][$i]['title']?>
                                                </a>
                                            </li>
                                        <?}?>
                                    </ul>
                                <?}?>
                                <?if (count($value['subcategories']) > 44){?>
                                    <ul class="subcategory">
                                        <?for ($i = 44; $i < 66; $i++){?>
                                            <li>
                                                <a href="/category/<?=$value['href']?>/<?=$value['subcategories'][$i]['href']?>">
                                                    <?=$value['subcategories'][$i]['title']?>
                                                </a>
                                            </li>
                                        <?}?>
                                    </ul>
                                <?}?>
                            <?}?>
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
						<label for="radio3" data-placeholder="Введите VIN-номер">Искать по VIN-номеру</label>
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
							<span class="account_sum"><?=round($user['bill_total'])?> <i class="fa fa-rub" aria-hidden="true"></i></span>
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
					<form id="authorization" action="/authorization" method="post">
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
                        <div id="yandex-policy"></div>
						<button>Войти</button>
						<div class="registration_link">
							<a href="/registration">Зарегистрироваться</a>
						</div>
					</form>
                    <a nohref id="show-social">Авторизоваться другим способом</a>
                    <div id="restore_password" class="product-popup mfp-hide">
                        <h2>Востановить пароль</h2>
                        <div class="content">
                            <form action="/">
                                <div class="wrapper">
                                    <div class="left">Введите ваш email:</div>
                                    <div class="right">
                                        <input required type="email" name="email">
                                    </div>
                                </div>
                                <? YandexCaptcha::show('restore_password')?>
                                <input type="submit" value="Отправить" disabled>
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
                    <td>
                        <?=$value['brend']?>
                        <a class="articul" href="/article/<?=$value['item_id']?>-<?=$value['article']?>/no-use-api">
                            <?=$value['article_cat']?>
                        </a> <?=$value['title']?>
                    </td>
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
				<td>Корзина пуста</td>
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
                        foreach ($categories as $key => $value){
                            if (!$value['isShowAtBottom']) continue; ?>
                            <li><a href="/category/<?=$value['href']?>"><?=$key?></a></li>
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
					<li><a target="_blank" rel="nofollow" href="https://vk.com/tahos">Мы Вконтакте</a></li>
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
						<a data-issue-id="<?=$issue['issue_id']?>" href="" id="driving_direction">Схема проезда</a>
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
							<a target="_blank" href="<?=$issue['twitter']?>" class="twitter"></a>
						<?}?>
						<?if ($issue['vk']){?>
							<a target="_blank" href="<?=$issue['vk']?>" class="vk"></a>
						<?}?>
						<?if ($issue['facebook']){?>
							<a target="_blank" href="<?=$issue['facebook']?>" class="facebook"></a>
						<?}?>
						<?if ($issue['google']){?>
							<a target="_blank" href="<?=$issue['google']?>" class="google"></a>
						<?}?>
						<?if ($issue['ok']){?>
							<a target="_blank" href="<?=$issue['ok']?>" class="ok"></a>
						<?}?>
					</div>
				<?}?>
				<div class="clear"></div>
			</div>
		</div>
	</footer>
	<div class="h_overlay"></div>
	<div class="overlay"></div>

	<!-- Optimized loading JS Start -->
	<script>var scr = {"scripts":[
		{"src" : "/js/jquery.cookie.js?<?=$token?>", "async" : false},
		// {"src" : "/js/jquery.priceformat.min.js?<?=$token?>", "async" : false},
		{"src" : "/js/common.js?<?=$token?>", "async" : false},
		{"src" : "/js/jquery.preload.min.js?<?=$token?>", "async" : false},
		{"src" : "/js/jquery.form.js?<?=$token?>", "async" : false},
		// {"src" : "/js/to_top.js?<?=$token?>", "async" : false},
		<?if (in_array($view, ['category', 'article', 'favorites'])){?>
			{"src" : "/js/item_full.js?<?=$token?>", "async" : false},
		<?}?>
        <?if (in_array($view, ['registration'])){?>
            {"src" : "/vendor/plugins/jquery.maskedinput.min.js?<?=$token?>", "async" : false},
        <?}?>
		<?if (in_array($view, ['category'])){?>
			{"src" : "/vendor/paginationjs/pagination.min.js?<?=$token?>", "async" : false},
			{"src" : "/vendor/plugins/ion.rangeSlider/ion.rangeSlider.min.js?<?=$token?>", "async" : false},
		<?}?>
		<?if (in_array($view, ['article', 'orders', 'order'])){?>
			{"src" : "/js/get_store_info.js?<?=$token?>", "async" : false},
		<?}?>
        <?if (in_array($view, ['orders', 'basket', 'order', 'account'])){?>
            {"src": "/vendor/pickmeup/pickmeup.min.js?<?=$token?>", "async" : false},
        <?}?>
        <?if (in_array($view, ['article', 'category', 'favorites'])){?>
            {"src": "/vendor/blueimp/js/jquery.blueimp-gallery.min.js?<?=$token?>"},
        <?}?>
		{"src" : "/js/<?=$view?>.js?<?=$token?>", "async" : false}
		]};!function(t,n,r){"use strict";var c=function(t){if("[object Array]"!==Object.prototype.toString.call(t))return!1;for(var r=0;r<t.length;r++){var c=n.createElement("script"),e=t[r];c.src=e.src,c.async=e.async,n.body.appendChild(c)}return!0};t.addEventListener?t.addEventListener("load",function(){c(r.scripts);},!1):t.attachEvent?t.attachEvent("onload",function(){c(r.scripts)}):t.onload=function(){c(r.scripts)}}(window,document,scr);
	</script>
	<!-- Optimized loading JS End -->
</body>
</html>
