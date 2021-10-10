var cookieOptions = {path: '/'};
var cp_api = false;
let countCharactersForSearch = 3;
var h_win = $(window).height();
function showPopupAddGarage(data){
    let src = `
        <div class="wrapper">
            <form id="to_garage__form" action="">
                <table style="margin-top: 20px">
                    <tr>
                        <td>Название</td>
                        <td>
                            <input type="text" name="title" value="${data.title}">
                        </td>
                    </tr>
                    <tr>
                        <td>Год</td>
                        <td>
                            <input type="text" name="year" value="${data.year}">
                        </td>
                    </tr>
                    <tr>
                        <td>Владелец</td>
                        <td>
                            <input type="text" name="owner" value="">
                        </td>
                    </tr>
                    <tr>
                        <td>Телефон</td>
                        <td>
                            <input type="text" name="phone" value="">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <input type="submit" value="Сохранить">
                        </td>
                    </tr>
                </table>
                <input type="hidden" value="${data.user_id}" name="user_id">
                <input type="hidden" value="${data.act}" name="act">
                <input type="hidden" value="${data.title}" name="title">
    `;
    if (typeof data.modification_id !== 'undefined'){
        src += `<input type="hidden" value="${data.modification_id}" name="modification_id">`
    }
    if (typeof data.catalogId !== 'undefined'){
        src += `<input type="hidden" value="${data.catalogId}" name="catalogId">`
    }
    if (typeof data.modelId !== 'undefined'){
        src += `<input type="hidden" value="${data.modelId}" name="modelId">`
    }
    if (typeof data.carId !== 'undefined'){
        src += `<input type="hidden" value="${data.carId}" name="carId">`
    }
    if (typeof data.q !== 'undefined'){
        src += `<input type="hidden" value="${data.q}" name="q">`
    }
    src += `
            </form>
        </div>
    `;
    $.magnificPopup.open({
        items: {
            src: src,
            type: 'inline'
        }
    });
    $.mask.definitions['~'] = '[+-]';
    $("input[name=phone]").mask("+7 (999) 999-99-99");
}
function eventAddGarage(object, data){
    let th = $(object).find('button');
    if (th.hasClass('is_garaged')){
        data.act = 'removeFromGarage';
        $.ajax({
            type: 'post',
            url: '/ajax/parts-catalogs.php',
            data: data,
            success: function(response){
                th.removeClass('is_garaged');
                $.magnificPopup.close();
            }
        })
    }
    else{
        data.act = 'addToGarage';
        showPopupAddGarage(data);
    }
}
function getParams(url = ''){
	let str = url ? url : window.location.search;
	if (!str) return false;
	const urlParams = new URLSearchParams(str);
	let output = {};
	for(const[name, value] of urlParams){
		output[name] = value;
	}
	return output;
}
function show_popup_basket(){
	event.preventDefault();
	var cart = $(".cart-popup");
	var left = ($('header').outerWidth(true) - $('.wrapper').outerWidth(true)) / 2 +
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
						`<input name="price" value="${b.price}" type="hidden">` +
						`<input name="quan" value="${b.quan}" type="hidden">` +
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
					'<th><span id="total_quan">' + total_quan + '</span>&nbsp;шт.</th>' +
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
function getImgUrl(){
	return $('input[name=imgUrl]').val()
}

function rememberUserSearch(item_id){
	let user_id = $('input[name=user_id]').val();
	if (!user_id.length) return false;
	$.ajax({
		type: 'post',
		url: '/ajax/common.php',
		data: {
			act: 'rememberUserSearch',
			item_id: item_id,
			user_id: user_id
		},
		success: function(){}
	})
}

function handlePressedEnterSearch(event){
    event.preventDefault();
	let $elem = $('div.search tr.active');
	if ($elem.length){
		rememberUserSearch($elem.attr('item_id'));
		document.location.href = $elem.find('a').attr('href');
	} 
	else{
        let val = $('input[name=search_input]').val();
        if (!val) val = '9091901122';
        document.location.href = '/search/article/' + val;
    }
}
function selectItemByKey(event){
	let $input = $(event.target);
	let tableClass;
	if ($input.val().length < countCharactersForSearch) tableClass = 'previous_search';
	else tableClass = 'coincidences';

	let activeTable = $('.hints table.' + tableClass);
	let activeTr = activeTable.find('tr.active');

	if (event.keyCode == 40){
		if (activeTr.length == 0) return activeTable.find('tr:first-child').addClass('active');

		if (activeTr.next().size() == 0) return false;
		$('.hints').find('tr').removeClass('active');
		activeTr.next().addClass('active');
	} 
	if (event.keyCode == 38){
		if (activeTr.prev().size() == 0) return false;
		$('.hints').find('tr').removeClass('active');
		activeTr.prev().addClass('active');
	} 
}
$(function() {
	cp_init();
	price_format();
	$('input[name=remember]').styler();
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
    $(document).on('submit', '#to_garage__form', function (e){
        e.preventDefault();
        let th = $(this);
        let formData = {};
        $.each(th.serializeArray(), function(i, item){
            formData[item.name] = item.value;
        })
        $.ajax({
            type: 'post',
            url: '/ajax/parts-catalogs.php',
            data: formData,
            success: function(response){
                const button = $('#to_garage button');
                let added = '';
                if (formData.act === 'addToGarage'){
                    button.addClass('is_garaged');
                    added = 'added';
                }
                else button.removeClass('is_garaged');
                button.closest('div').attr('class', added);
                $.magnificPopup.close();
            }
        })
    })
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
						'</tr>';
				if (res.country != null) str +=
						'<tr>' +
							'<td><b>Страна:</b></td>' +
							'<td>' + res.country + '</td>' +
						'</tr>';
				if (res.site != null){
					res.site = res.site.replace(/http:\/\/|https:\/\//, '');
					str +=
						'<tr>' +
							'<td><b>Веб-сайт:</b></td>' +
							`<td><a href="http://${res.site}" target="_blank"> ${res.site} </td>` +
						'</tr>';
				} 
				str +=
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
				$('#basket_basket').html(+$('#basket_basket') - (quan * division));
				$('.quan li[store_id=' + store_id + '][item_id=' + item_id + ']').empty();
				price_format();
				if (typeof window['applyUserMarkup'] == 'function') applyUserMarkup();
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
	$('.search_input').on('focus', function(e){
		let text = $('input.search_input').val();
		let user_id = $('input[name=user_id]').val();

		if (text.length < countCharactersForSearch){
			if ($('.hints .previous_search').is(':empty') && user_id.length){
				$.ajax({
					type: "POST",
					url: "/ajax/search.php",
					data: {
						user_id: $('input[name=user_id]').val()
					},
					success: function(msg){
						$('.hints')
							.find('table.previous_search')
							.show()
							.html(msg);
					} 
				});
			}

			$('.hints table.previous_search').show();
			$('.hints table.coincidences').hide();
		}
		else{
			$('.hints table.previous_search').hide();
			$('.hints table.coincidences').show();
		}
		$('.hints').show();
	})
	$('.search_input').on('keyup input', function(event){
		if (event.keyCode == 38 || event.keyCode == 40){
			return selectItemByKey(event);
		}
		if (event.keyCode == 13){
			return handlePressedEnterSearch(event);
		}
		let inputValue = $(this).val();
		
		if (inputValue.length < countCharactersForSearch){
			$('.hints .previous_search').show();
			if (!$('.hints .previous_search tr.active').size()){
				$('.hints .previous_search tr:first-child').addClass('active');
			}
			$('.hints .coincidences').hide();
			return false;
		} 
		else{
			$('.hints .previous_search').hide();
			$('.hints .previous_search tr').removeClass('active');
			$('.hints .coincidences').show();
		} 

		let htmlArticleBarcole = '';
		if (inputValue.length == 13 || inputValue.length == 17){
			htmlArticleBarcole += `
				<tr class="active">
					<td colspan="2">
						<a href="/search/article/${inputValue}">${inputValue} - искать артикул</a>
					</td>
				</tr>
			`;
			if (inputValue.length == 13) htmlArticleBarcole += `
				<tr>
					<td colspan="2">
						<a href="/search/barcode/${inputValue}">${inputValue} - искать штрихкод</a>
					</td>
				</tr>
			`;
			if (inputValue.length == 17) htmlArticleBarcole += `
				<tr>
					<td colspan="2">
						<a href="/original-catalogs/legkovie-avtomobili#/carInfo?q=${inputValue}">${inputValue} - искать VIN</a>
					</td>
				</tr>
			`;
		}

		$('.hints table.coincidences').html(htmlArticleBarcole);
		$.ajax({
			type: 'post',
			url: '/ajax/common.php',
			data: {
				act: 'searchArticles',
				value: inputValue,
				maxCountResults: 5
			},
			success: function(response){
				$('table.coincidences tr.item').remove();
				$('table.coincidences').append(response);
			}
		})
	})
	$(document).on('click', '.hints table tr', function(e){
		let tr = $(this);
		rememberUserSearch(tr.attr('item_id'));
		document.location.href = tr.find('a').attr('href');
	})
	$("div.search_btn").click(function(){
		$(".overlay").addClass("none_bg");
		$(".h_overlay, .overlay").show();
		$("header .search").addClass("show");
	})
	$("button.search_btn").click(function(e){
		return handlePressedEnterSearch(e);
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
		let $target = $(e.target);
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
