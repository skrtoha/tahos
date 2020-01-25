var cookieOptions = {path: '/'};
var cp_api = false;
var h_win = $(window).height();
function show_popup_basket(){
	event.preventDefault();
	var cart = $(".cart-popup");
	var left = 0 +  ($('header').outerWidth(true) - $('.wrapper').outerWidth(true)) / 2 +
						$('.logo').outerWidth(true) + 
						$('.catalog_btn').outerWidth(true) + 
						$('.search_btn_2').outerWidth(true) + 
						$('.cart').outerWidth(true) + 45 -
						cart.outerWidth(true);
	cart.toggle();
	if (cp_api){
		cp_api.reinitialise();
		cp_api.scrollToBottom();
	} 
	// if ($(document).width() <= 1024) cart.offset({ left: left});
	$(".cart .arrow_up").show();
	$(".h_overlay, .overlay").show();
}
function price_format(){
	// $('.price_format').priceFormat({
	// 	allowNegative: true,
	// 	 prefix: '',
	// 	 centsSeparator: ',',
	// 	 centsLimit: 0,
	//     thousandsSeparator: '&nbsp;',
	//     clearOnEmpty: true,
	// });
	// console.log($(this).html() + ': ' + $(this).next('i').attr('class'));
	// $('.price_format_2').priceFormat({
	// 	allowNegative: true,
	// 	 prefix: '',
	// 	 centsLimit: 2,
	//     thousandsSeparator: ' ',
	//     clearOnEmpty: true,
	// });
}
function get_basket(basket){
	var total_quan = 0;
	var total_price = 0;
	var c_tr = $('.cart-popup-table tr').length;
	var str = '' +
			'<tr>' +
				'<th>Наименование</th>' +
				'<th>Кол-во</th>' +
				'<th>Сумма</th>' +
				'<th><img id="basket_clear" src="/img/icons/icon_trash.png" alt="Удалить"></th>' +
			'</tr>';
	for (var k in basket){
		var b = basket[k];
		total_quan += +b.quan;
		total_price += (b.quan * b.price);
		str += '' +
				'<tr store_id="' + b.store_id + '" item_id="' + b.item_id + '">' +
					'<td>' + b.brend + ' <a class="articul" href="'+ b.href + '">' + b.article + '</a> ' + b.title + '</td>' +
					'<td>' + b.quan + ' шт.</td>' +
					'<td>' + 
						'<span class="price_format">' + (b.price * b.quan) + '</span>' + 
						'<i class="fa fa-rub" aria-hidden="true"></i>' +
					'</td>' +
					'<td>' +
						'<span division="'+ (b.price) + '" quan="' + b.quan + '" class="delete-btn"> ' + 
							'<i class="fa fa-times" aria-hidden="true"></i>' +
						'</span>' + 
					'</td>' +
				'</tr>';
	}
	str += '' +
				'<tr>' +	
					'<th>Итого</th>' +
					'<th><spanid="total_quan">' + total_quan + '</span>&nbsp;шт.</th>' +
					'<th colspan="2"><span class="price_format"  id="total_basket">' + total_price + '</span><i class="fa fa-rub" aria-hidden="true"></i></th>' +
				'</tr>';
	$('.cart-popup-table').html(str);
	if (c_tr == 1) $('.cart-popup-table').after('<button sourceindex="5">Перейти в корзину</button>');
	cp_init();
	// return basket[basket.length - 1].id;
}
function show_message(msg, type = 'ok'){
	if (type == 'error') $('#message div div').css('background', 'rgba(214, 50, 56, 0.97)');
	else $('#message div div').css('background', 'green');
	$('#message div div').html(msg);
	$('#message').slideDown(500);
	$.cookie('message', '', cookieOptions);
	$.cookie('message_type', '', cookieOptions);
	setTimeout(function(){
		$('#message').slideUp(200);
	}, 2000);
}
function cookie_message(){
	if ($.cookie('message')){
		show_message($.cookie('message'), $.cookie('message_type'));
	}
}
function cp_init(){
	var b = $('.cart-popup').height() >= h_win * 0.75;
	if (b){
		if (!cp_api){
			cp_api = $('.cart-popup').jScrollPane({
				showArrows: true,
				verticalGutter: 0,
			}).data('jsp')
		}
	};
	price_format();
}
$(function() {
	cp_init();
	price_format();
	// $("input[name=telephone]").mask("+7 (999) 999-99-99");
	$('#driving_direction').on('click', function(event) {
		event.preventDefault();
		th = $(this);
		$('#full-image .img-wrap')
			.html(
				'<div id="map"><img style="width: auto" src="/images/preload.gif" alt=""></div>'+ 
				'<a class="close" href="#" title="Закрыть"></a>'
			)
			.css('width', '80%')
			.css('float', 'none');
		$('#map')
			.css('width', '100%')
			.css('display', 'flex')
			.css('justify-content', 'center')
			.css('align-items', 'center');
		$("#full-image").show();
		$.getScript('https://api-maps.yandex.ru/2.1/?lang=ru_RU&amp;apikey=64b4b12b-f136-4cc3-bfe2-3418e1c7b59a', function(){
			ymaps.ready(function(){
				$.ajax({
					type: 'post',
					url: '/ajax/common.php',
					data: 'act=get_issue_by_id&issue_id=' + th.attr('issue_id'),
					success: function(response){
						// console.log(response); return false;
						var issue = JSON.parse(response);
						var coords = issue.coords.split(',');
						var coord_1 = parseFloat(coords[0]);
						var coord_2 = parseFloat(coords[1]);
						var myPlacemark = new ymaps.Placemark(
							[coord_1, coord_2],
							{
								balloonContentHeader: issue.title,
								balloonContentBody: issue.desc,
								balloonContentFooter: issue.adres,
							},
							{
								'balloonCloseButton': false
							}
						);
						$('#map').empty();
						var myMap = new ymaps.Map('map', {
							center: [coord_1, coord_2],
							zoom: 15
							}
						);
						myMap.geoObjects.add(myPlacemark);
						myPlacemark.balloon.open();
					} 
				})
			});
		})
			
		$(document).mouseup(function(e){
			var container = $("#full-image .img-wrap");
			if (!container.is(e.target) && container.has(e.target).length === 0) container.parent().hide();
		});
		$("#full-image .close").click(function(event) {
			event.preventDefault();
			$("#full-image").hide();
		});
	});
	$(document).on('click', '#main-pic img', function(event) {
		$('#full-image .img-wrap').html('<img src="' + $(this).attr('data-zoom-image') + '">' + 
			'<a class="close" href="#" title="Закрыть"></a>');
		$("#full-image").show();
		$(document).mouseup(function(e){
			var container = $("#full-image .img-wrap");
			if (!container.is(e.target) && container.has(e.target).length === 0) container.parent().hide();
		});
		$("#full-image .close").click(function(event) {
			event.preventDefault();
			$("#full-image").hide();
		});
	});
	$(document).on('click', '.brend_info', function(e){
		$.ajax({
			type: "POST",
			url: "/ajax/brend_info.php",
			data: "id=" + $(this).attr('brend_id'),
			success: function(msg){
				var res = JSON.parse(msg);
				var str = '<div id="brend_info">';
				if (res.short_desc) str += '' +
					'<p>Описание производителя</p>' +
					'<p>' + res.short_desc + '</p>';
				str += '' +
					'<table>' +
						'<tr>' +
							'<td><b>Название:</b></td>' +
							'<td>' + res.title + '</td>'+
						'</tr>' +
						'<tr>' +
							'<td><b>Страна:</b></td>' +
							'<td>' + res.country + '</td>' +
						'</tr>' +
						'<tr>' +
							'<td><b>Веб-сайт:</b></td>' +
							'<td>' + res.site + '</td>' +
						'</tr>' +
					'</table>' +
				'</div>' +
				'<a class="close" href="#" title="Закрыть"></a>';
				$('#full-image .img-wrap').html(str);
				$("#full-image").show();
				// $('#full-image .img-wrap').jScrollPane({
				// 	showArrows: true,
				// 	verticalGutter: 0
				// });
				$(document).mouseup(function(e){
				var container = $("#full-image .img-wrap");
				if (!container.is(e.target) && container.has(e.target).length === 0) container.parent().hide();
		});
		$("#full-image .close").click(function(event) {
			event.preventDefault();
			$("#full-image").hide();
		});
			}
		})
	})
	if ($.cookie('message')) show_message($.cookie('message'), $.cookie('message_type'));
	$(document).on('click', '.cart-popup-table .delete-btn', function(){
		var elem = $(this);
		var quan = +elem.attr('quan');
		var division = +elem.attr('division');
		var item_id = elem.closest('tr').attr('item_id');
		var store_id = elem.closest('tr').attr('store_id');
		var selector = '[store_id=' + store_id + '][item_id=' + item_id + ']';
		$.ajax({
			type: "POST",
			url: "/ajax/basket.php",
			data: 'act=delete&store_id=' + store_id  +
				'&item_id=' +item_id + 
				'&division=' + division +
				'&quan=' + quan,
			success: function(msg){
				elem.closest(selector).remove();
				$('#total_quan').html((+$('#total_quan').html() - quan));
				$('#total_basket').html(+$('#total_basket').unmask() - (quan * division));
				$('#totalToOrder').html(+$('#totalToOrder').html() - (quan * division));
				if ($('.cart-popup-table tr').length == 2){
					$('.cart-popup-table').html('<tr><td colspan="4">Корзина пуста</td>').next().remove();
					$('.cart span').remove();
					$('.quan li').empty();
					$('.quan').addClass('hidden');
				}
				$(selector).empty();
				$('.cart span').html(+$('.cart span').text() - quan);
				$('.basket-table ' + selector).closest('tr').remove();
				$('.basket .mobile-view ' + selector).closest('.good').remove();
				$('#basket_basket').html(+$('#basket_basket').unmask() - (quan * division));
				$('.quan li[store_id=' + store_id + '][item_id=' + item_id + ']').empty();
				price_format();
			}
		});
		show_message('Товар успешно удален из корзины!', 'ok');
		if (cp_api){
			cp_api.reinitialise();
			cp_api.scrollToBottom();
		} 
	})
	$(document).on('click', ".cart-popup-table #basket_clear", function(){
		if (!confirm('Вы действительно хотите очистить корзину?')) return false;
		$.ajax({
			type: "POST",
			url: "/ajax/basket_clear.php",
			data: '',
			success: function(msg){
				show_message('Корзина успешно очищена!');
				$('.cart-popup-table').html('<tr><td colspan=4>Корзина пуста</td></tr>');
				$('.cart-popup button').remove();
				$('.cart span').remove();
				$('.to-stock-btn').html('');
			} 
		});
		$('.quan li').empty();
		$('.quan').addClass('hidden');
		inBasket = {};
	})
	$(document).on('click', '.cart-popup button', function(){
		document.location.href = "/basket";
	})
	$("select").styler({
		onSelectOpened: function(){
			$('.page-wrap').css('position', 'static');
		},
		onSelectClosed: function(){
			if(this.hasClass('select_filter')) get_items();
			$('.page-wrap').css('position', 'relative');
		}
	});
	// var hints_count = $(".hints li").length;
	// if (hints_count > 6) hints_height = 300;
	// else hints_height = hints_count*50;
	$('.search_input').on('focus', function(e){
		var search_text = $('.search_input').val();
		// $('#popup').css('display', 'flex');
		// alert();
		var data = "type_search=" + $('.settings input:checked').val();
		// console.log(data);
		$.ajax({
			type: "POST",
			url: "/ajax/search.php",
			data: data,
			success: function(msg){
				// console.log(msg); return;
				if (msg == '0') return false;
				else $('.hints').html(msg);
				$(".hints").show();
				// $(".hints").jScrollPane({
				// 	showArrows: true,
				// 	verticalGutter: 0
				// });
				// $('#popup').css('display', 'none');
			} 
		});
	})
	$(document).on('click', '.hints table tr', function(){
		document.location.href = $(this).find('a').attr('href');
	})
	$("div.search_btn").click(function(){
		$(".overlay").addClass("none_bg");
		$(".h_overlay, .overlay").show();
		$("header .search").addClass("show");
	})
	$("button.search_btn").click(function(e){
		e.preventDefault();
		var search_text = $('.search_input').val() ? $('.search_input').val() : "9091901122";
		var type_search = $('input[name=type]:checked').val();
		console.log(search_text, type_search);
		if (type_search == 'vin' && search_text.match(/^[\w\d]{17}$/gi) === null){
			return show_message('VIN-номер введен неккоректно!', 'error')
		}
		window.location.href = "/search/" + type_search + '/' + search_text + '/yes';
	});
	$(".login_btn").click(function(){
		$('.overlay').click();
		// $(".h_overlay, .overlay, .profile, .profile_btn .arrow_up").show();
		$(".h_overlay, .overlay, header .login, header .login_btn .arrow_up").show();
	});
	$(".profile_btn").click(function(){
		$('.cart-popup').hide();
		$(".h_overlay, .overlay, .profile, .profile_btn .arrow_up, .login").show();
	});
	$(".settings_btn").click(function(){
		$(".h_overlay, .overlay, .settings_overlay, .settings, .settings_btn .arrow_up").show();
	});
	$(".catalog_btn").click(function(){
		var height = h_win-75;
		$(".catalog").css({"height":height+"px"});
		$(".h_overlay, .overlay, .catalog, .catalog_btn .arrow_up").show();
		$(".catalog").jScrollPane({
			showArrows: true,
			verticalGutter: 0
		});
	});
	function ResizeCatalog() {
		var height = h_win-75;
		$(".catalog").css({"height":height+"px"});
		$(".catalog").jScrollPane({
			showArrows: true,
			verticalGutter: 0
		});
	}
	$(window).resize(ResizeCatalog);
	$(".settings label").click(function(){
		var placeholder = $(this).attr("data-placeholder");
		$(".search_input").attr("placeholder",placeholder);
		$(".settings, .settings_btn .arrow_up").hide();
		$(".h_overlay, .overlay").hide();
	});
	$(".h_overlay, .overlay").click(function(){
		$(".overlay").removeClass("none_bg");
		$(".h_overlay, .overlay").hide();
		$("header .login, header .login_btn .arrow_up").hide();
		$(".profile, .profile_btn .arrow_up").hide();
		$(".catalog, .catalog_btn .arrow_up").hide();
		$(".catalog, .cart .arrow_up").hide();
		$("header .search").removeClass("show");
		$(".settings, .settings_btn .arrow_up").hide();
		$(".cart-popup").hide();
		$('.comment-block').hide();
	});
	$(document).mouseup(function(e) {
		var $target = $(e.target);
		if ($target.closest(".hints").length === 0 && !$target.hasClass("search_input")) {
		    $(".hints").hide();
		}
	});
	$(".cart").click(function(event) {
		show_popup_basket();
	});
	// mobile-footer
	if ($("html").width() <=550) {
		$("footer .item").click(function(event) {
			$(this).children("ul").slideToggle();
			$(this).children("h4").toggleClass('open');
		});
	}
});