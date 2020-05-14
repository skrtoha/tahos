function setMosaicList(obj){
	$('.mosaic-view').html(obj.mosaic);
	$('.list-view').html(obj.list);
}
function getHtmlMosaicList(arr){
	var obj = {};
	var mosaic = '';
	var list = '';
	var c = arr.href;
	for (var key in arr.items){
		var item = arr.items[key];
		mosaic += '<div class="item">' + 
									'<a href="/category/' + c + '/' + item.href + '">' +
										'<h3>' + item.title + '</h3>' + 
									'</a>' + 
								'</div>';
		list += '<p>' + 
							'<a href="/category/' + c + '/' + item.href + '">' + item.title + '</a>' + 
						'</p>';
	}
	return {
		mosaic: mosaic,
		list: list
	}
}
function getHtmlRating(rating){
	var str = '';
	var div = rating / 2;
	var fa = '';
	for (i = 1; i <= 5; i++){
		if (i < div) fa = 'fa-star';
		else{
			if (div == i) fa = 'fa-star';
			else if (div + 0.5 == i) fa = 'fa-star-half-o';
			else fa = 'fa-star-o';
		} 
		str += '<i class="fa ' + fa + '" aria-hidden="true"></i>';
	}
	return str;
}
function getHtmlItems(items){
	var filters = get_filters($('#filters').val());
	// console.log(filters);
	var mosaic = '';
	var list = '';
	for (var key in items){
		var item = items[key];
		if (item.foto) var foto = '<img src="' + getImgUrl() + '/items/small/' + item.id + '/' + item.foto + '" alt="' + item.title + '">';
		else foto = '<img src="/images/no_foto.png" alt="Фото отсутствует">';
		var values_mosaic = '';
		var values_list = '';
		var str = '';
		for (var id in filters){
			var filter = filters[id];
			// console.log(id + ": " + filter.title);
			var str = item.filters_values[id];
			str = str ? str : '';
			values_list += '<td>' + str + '</td>';
			if (!str) continue;
			values_mosaic += '<p>' + str + '</p>';
		}
		mosaic += '<div class="item_1 product-popup-link" item_id="' + item.id + '">' +
							'<div class="product">' +
								'<p>' +
									'<b class="brend_info" brend_id="' + item.brend_id + '">' + item.brend + '</b> ' + 
									'<a href="' + item.href + '" class="articul">' + item.article + '</a>' + 
								'</p>' + 
								'<p><strong>' + item.title_full + '</strong></p>' +
								'<div class="pic-and-description">' +
									'<div class="img-wrap">' + foto + '</div>' +
									'<div class="description">' + values_mosaic + '</div>' +
								'</div>' +
								'<div class="clearfix"></div>' +
								'<div class="rating no_selectable">' +
									getHtmlRating(item.rating) +
								'</div>' +
							'</div>' +
							'<div class="price-and-delivery">' +
								'<p class="price">от <span>' + item.price + '</span></p>' +
								'<p class="delivery">от ' + item.delivery + ' дн.</p>' +
							'</div>' +
						'</div>';
		list += '<tr class="product-popup-link" item_id="' + item.id + '">' +
							'<td class="name-col">' +
								'<b style="font-weight: 700">' + item.brend + '</b> ' + 
								'<a href="' + item.href + '" class="articul">' + item.article + '</a>' + 
								item.title_full +
							'</td>' +
							values_list +
							'<td class="rating no_selectable">' +
								getHtmlRating(item.rating) +
							'</td>' + 
							'<td>' + item.delivery + '</td>' +
							'<td>' + item.price + '</td>' +
						'</tr>'
	}
	list += '<div class="clearfix"></div>';
	return {
		mosaic: mosaic,
		list: list
	}
}
function getFormData(){
	var str = 'search=' + $('#search').val() + '&';
	str += 'sub_id=' + $('#sub_id').val() + '&';
	if ($('#filters_on').val()) str += 'filters_on=1&';
	$('select').each(function(){
		str += $(this).attr('name') + "=" + $(this).val() + '&';
	});
	$('.slider').each(function(){
		var e = $(this);
		str += e.attr('name') + '=' + e.attr('from') + ',' + e.attr('to') + '&';
	})
	if ($(document).width() > 700){
		var a = $('.option-panel > a.active');
		str += 'sort=' + a.attr('sort') + '&';
		if (a.hasClass('desc')) str += 'desc=1&';
	}
	else{
		str += 'sort=' + $('#sort-change-mobile').attr('type') + '&';
		if (!$('#sort-direction-mobile').hasClass('up')) str += 'desc=1&';
	}
	return str;
}
function set_fixed(){
	if ($(document).width() <= 1100) return false;
	if (!$('#sub_id').val()) return false;
	var ff = $('.filter-form');
	ff.attr('style', '');
	var h_items = $('.items').height();
	var h_filterForm = ff.outerHeight();
	$('#sub_filter').attr('style', '');
	if (h_items >= $(window).height()){
		$('#sub_filter').show();
		ff.addClass('fixed');
		ff.offset({left: $('header').outerWidth(true)/2 - $('#main').width()/2});
	} 
	else{
		$('#sub_filter').hide();
		ff.removeClass('fixed');
	} 
}
function get_filters(str){
	var s = str.replace(/#/g, '"');
	return JSON.parse(s);
}
var get_items = function(){
	chunk = 0;
	if ($(document).width() > 700) $(document).scrollTop(0);
	$.ajax({
		type: "POST",
		url: "/ajax/get_items.php",
		data: "chunk=" + chunk + '&' + getFormData(),
		beforeSend: function(){
			$('.wide-view tr:nth-child(n+2)').remove();
			$('.wide-view').append(
				'<tr class="gif">' + 
					'<td colspan="' + $('.wide-view tr').find('th').size() + '"></td>' +
				'</tr>'
			);
			$('.mosaic-view').html('<div class="gif"></div>');
			set_fixed();
		},
		success: function(msg){
			$('.gif').remove();
			// console.log(msg);
			// return;
			set_fixed();
			// return;
			var res = JSON.parse(msg);
			if (!res.items){
				$('.mosaic-view').html('<div>Ничего не найдено</div>');
				$('.wide-view tr:nth-child(n+2)').remove();
				$('.wide-view').append('<tr><td colspan="4">Ничего не найдено</td></tr>');
				set_fixed();
				c_item = 0;
				return false;
			} 
			var get_html = getHtmlItems(res.items);
			$('.mosaic-view').html(get_html.mosaic);
			$('.wide-view').append(get_html.list);
			set_fixed();
			chunk = res.chunk;
		}
	})
	// console.log(start);
};
function getFullItem(i){
	// console.log(i);
	var s = '';
	var item = i.item;
	var b = i.min.price;
	var del = i.min.delivery;
	// console.log(item);
	var d = item.full_desc || item.characteristics || item.applicability;
	if (!d) s = 'float: none; display: block; margin: 0 auto;';
	var str = '' +
	'<div id="div_10">' +
		'<h2 class="title"><b>' + item.brend + '</b> ' + item.article + '</h2>' + 
		'<p>' + item.title + '</p>' +
	'</div>';
	if (Object.keys(b).length){
		str +=
		'<div id="div_table">' +
			'<table id="item_into">' +
				'<tr>' +
					'<td>' + b.delivery + ' дн.</td>' +
					'<td>' + b.user_price + i.designation + '</td>' +
					'<td>' + 
						'<i price="' + b.price + '" store_id="' + b.store_id + 
							'" item_id="' + item.id + '" packaging="' + 
							b.packaging + 
							'" class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">';
		if (+b.in_basket) str += '' + 
							'<i class="goods-counter">' + b.in_basket + '</i>';
				str +=	'</i>' +
					'</td>' +
				'</tr>';
				if (del.price) str += '' +
				'<tr>' +
					'<td>' + del.delivery + ' дн.</td>' +
					'<td>' + del.user_price + i.designation + '</td>' +
					'<td>' + 
						'<i price="' + del.price + '" provider_id="' + del.provider_id + 
							'" item_id="' + item.id + '" packaging="' + 
							del.packaging + 
							'" class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">';
		if (del.in_basket) str += '' + 
							'<i class="goods-counter">' + del.in_basket + '</i>';
				str +=	'</i>' +
					'</td>' +
				'</tr>';
				str += '' +
			'</table>';
	}
	str +=
	'</div>' +
	'<div class="clearfix"></div>' + 
 	'<div class="gallery-block" style="' + s + '">';
	var is_foto = item.foto ? true : false;
	var c_fotos = Object.keys(i.fotos).length;
	// console.log(c_fotos);
	if (is_foto){
		var src_small = getImgUrl() + '/items/small/' + item.id + '/' + item.foto;
		var src_big = getImgUrl() + '/items/big/' + item.id + '/' + item.foto;
		str += '' +
				'<div id="main-pic">' + 
					'<img src="'+ src_small + '" data-zoom-image="' + src_big + '">' + 
				'</div>';
		if (c_fotos){
			str += '<div id="gallery">';
			for (var k in i.fotos){
				var src_small = getImgUrl() + '/items/small/' + item.id + '/' + i.fotos[k];
				var src_big = getImgUrl() + '/items/big/' + item.id + '/' + i.fotos[k];
				str += '<img src="' + src_small + '" data-big-img="' + src_big + '">';
			}
			str += '</div>';
		}
	} 
	else str += '' +
				'<div id="pic">' + 
					'<img src="/images/no_foto.png">' + 
				'</div>';
	var rating = item.rating ? item.rating - 1 : -1;
	var no_selectable = item.rating ? 'no_selectable' : '';
	s = is_foto ? '' : 'margin-top: 0px';
	str += '<div style="' + s + '" item_id="' + item.id + '" class="rating ' + no_selectable + '">';
	for (var k = 0; k < 5; k++){
		var kkk = k <= rating ? 'fa-star' : 'fa-star-o';
		str += '<i class="fa ' + kkk + '" aria-hidden="true"></i>';
	}
	str += '</div>' +
	'</div>';
	if (d){
		str += '<div class="description-block">';
		str += '<div class="ionTabs product-popup-tabs" data-name="product-popup-tabs">';
		str += '<ul class="ionTabs__head">';
		if (item.full_desc) str += '<li class="ionTabs__tab" data-target="Tab_1_name"><i class="fa fa-question-circle-o" aria-hidden="true"></i></li>';
		if (item.characteristics) str += '<li class="ionTabs__tab" data-target="Tab_2_name"><i class="fa fa-cog" aria-hidden="true"></i></li>';
		if (item.applicability) str += '<li class="ionTabs__tab" data-target="Tab_3_name"><i class="fa fa-wrench" aria-hidden="true"></i></li>';
		str += '</ul>';
		if ($(document).height() > 700){
			if (c_fotos) s = 'height: 304px';
			else s = 'height: 215px';
		}
		str += '<div style="' + s + '" class="ionTabs__body">';
		if (item.full_desc) str += '<div class="ionTabs__item" data-name="Tab_1_name">' + item.full_desc + '</div>';
		if (item.characteristics) str += '<div class="ionTabs__item" data-name="Tab_2_name">' + item.characteristics + '</div>';
		if (item.applicability) str += '<div class="ionTabs__item" data-name="Tab_3_name">' + item.applicability + '</div>';
		str += '<div class="ionTabs__preloader"></div>';
		str += '</div>';
	}
	str +=	'</div>' +
	'</div>' +
	'<div class="clearfix"></div>' +
	'<div class="buttons">' +
		'<button class="brend_info" brend_id="' + item.brend_id + '"><span class="icon_put-in-basket"></span>Информация о бренде</button>';
	if (i.user_id){
		var added = +item.in_favorite ? 'added' : '';
		str += '<button class="' + added + '" item_id="' + item.id + '" user_id="' + i.user_id + '" id="add-to-favorits">' + 
							'<span class="icon_heart"></span>' +
						'</button>';
	}
	str +=	'' +
		'<button href="/article/' + item.id + '-' + item.article + '"  id="search-same"><span class="icon_search"></span>Другие предложения</button>' +
		'<div class="clearfix"></div>' +
	'</div>';
	return str;
}
$(document).ready(function(){
	var chunk = 1;
	var sub_id = $('#sub_id').val();
	var c_item = $('.mosaic-view .item_1').length;
	var reset = false;
	set_fixed();
	if ($(document).width() <= 700){
		if (!sub_id){
			$('.mosaic-view').hide();
			$('.list-view').show();
			set_fixed();
		}
		// $().UItoTop({ easingType: 'easeOutQuart' });
	}
	var inProgress = false;
	var desc = new Object();
	var asc = {
		mosaic: $('.mosaic-view').html(),
		list: $('.list-view').html()
	};
	var sub_id = $('#sub_id').val();
	$(document).on('click', '.articul', function(){
		$.magnificPopup.hide();
		location.href = $(this).attr('href');
	})
	$(document).on('click', ".option-panel a", function(event) {
		event.preventDefault();
		elem = $(this);
		$(".option-panel a").removeClass("active");
		elem.addClass('active');
		if (elem.hasClass('active')) elem.toggleClass('desc');
		if(!$('.mosaic-view .item_1').length) return false;
		if (!sub_id){
			if (!elem.hasClass('desc')) setMosaicList(asc);
			else{
				if (desc.mosaic) setMosaicList(desc);
				else{
					$.ajax({
						type: "POST",
						url: "/ajax/category_items.php",
						data: 'type=subcategories&category_id=' + $('#category_id').val(),
						success: function(msg){
							return;
							// console.log(msg);
							var res = JSON.parse(msg);
							desc = getHtmlMosaicList(res);
							setMosaicList(desc);
						}
					})
				}
			}
		}
		else{
			// console.log('Пошло');
			setTimeout(get_items, 1);
		}
	});
	$(".view-switch").click(function(event) {
		$(".view-switch").removeClass("active");
		$(this).addClass("active");
		switch_id = $(this).attr("id");
		if (switch_id == "mosaic-view-switch") {
			$(".mosaic-view").show();
			$(".list-view").hide();
		}else{
			$(".mosaic-view").hide();
			$(".list-view").show();
		}
		set_fixed();
	});
	$("#sort-change-mobile").click(function(event) {
		event.preventDefault();
		$(".sort-block").toggle();
		var t = ($(this).position().top) + 20;
		var l = ($(this).position().left) - 50;
		$(".sort-block").offset({top: t, left: l});
	});
	$(document).mouseup(function (e)	{
		var container = $(".sort-block");
		if (!container.is(e.target) // if the target of the click isn't the container...
				&& container.has(e.target).length === 0) // ... nor a descendant of the container
		{
			container.hide();
		}
	});
	$(".sort-block a").click(function(event) {
		event.preventDefault();
		$("#sort-change-mobile").text($(this).text()).attr('type', $(this).attr('type'));
		$(".sort-block").hide();
		setTimeout(get_items, 1);
	});
	$("#sort-direction-mobile").click(function(event) {
		$(this).toggleClass('up down');
		setTimeout(get_items, 1);
	});
	var curr_href = '/category/' + $('#category_href').val() + '/';
	$('.subcategory').on('change', function(){
		elem = $(this);
		// console.log(elem.val());
		window.location.href = curr_href + elem.val();
		return false;
	})
	$('select:not([name=sub])').on('change', function(){
		$('#filters_on').val(1);
		setTimeout(get_items, 1);
	});
	$('#search').on('blur', get_items);
	$('#search').on('keydown', function(e){
		if (e.keyCode == 13){
			e.preventDefault();
			setTimeout(get_items, 1);
		}
	})
	$(document).scrollTop(0);
	$(window).scroll(function(){
		if (!sub_id) return false;
		if (!c_item) return false;
		if ($('.filter-form').hasClass('fixed')){
			var diff = $(document).height() - $(window).scrollTop();
			// console.log('window: ' + $(window).height() + ' document:' + $(document).height() + ' diff: ' + diff);
			// var top_fixed = $('footer').outerHeight(true) + $('.filter-form').outerHeight(true) + $('header').outerHeight(true) + 100;
			var top_fixed = $('footer').outerHeight(true) + $('.filter-form').outerHeight(true);
			if (diff <= top_fixed + 200) $('.filter-form').css('position', 'absolute').css('top', $('body').outerHeight(true) - top_fixed - 34);
			else $('.filter-form').css('position', 'fixed').css('top', 110);
		}
		var w_width = $(window).width();
		var scroll_height;
		if (w_width >= 1200) scroll_height = 1500;
		else if (w_width > 765) scroll_height = 1500;
		else scroll_height = 2000;
		// console.log(
		// 	'\nscroll: ' + $(window).scrollTop() +
		// 	'\nheight: ' + $(window).height()
		// )
		var bool = $(window).scrollTop() + $(window).height() >= $(document).height() - scroll_height;
		// console.log(bool + ' ' + inProgress);
		if(bool && !inProgress) {
			$.ajax({
				type: "POST",
				url: "/ajax/get_items.php",
				data: "chunk=" + chunk + '&' + getFormData(),
				beforeSend: function(){
					inProgress = true;
					if (reset){
						$('.wide-view').append(
							'<tr class="gif">' + 
								'<td colspan="' + $('.wide-view tr').find('th').size() + '"></td>' +
							'</tr>'
						);
						$('.mosaic-view').append('<div class="gif"></div>');
					} 
					reset = false;
				},
				success: function(msg){
					$('.gif').remove();
					if (!msg){
						// fixed = false;
						return false;
					} 
					// fixed = true;
					console.log(msg);
					// return;
					var res = JSON.parse(msg);
					if (Object.keys(res).length < 5 && chunk >= 10) fixed = false;
					else  fixed = true;
					var get_html = getHtmlItems(res.items);
					$('.mosaic-view').append(get_html.mosaic);
					$('.wide-view').append(get_html.list);
					if (res.reset) reset = true;
					inProgress = false;
					chunk = res.chunk;
				}
			})
		}
		// console.log(start);
	})
	$('[type=reset]').on('click', function(){
		document.location.reload();
	})
	$(document).on('click', '#search-same', function(){
		document.location.href = $(this).attr('href');
	})
	$(document).on('click', '#add-to-favorits', function(){
		var e = $(this);
		var data = 'item_id=' + e.attr('item_id');
		if (!e.hasClass('added')) data += '&act=add';
		else data += '&act=delete';
		// console.log(data);
		$.ajax({
			type: "POST",
			url: "/ajax/favorite.php",
			data: data,
			success: function(msg){
				e.toggleClass('added');
				if (e.hasClass('added')) show_message('Успешно добавлено в избранное!');
				else show_message('Успешно удалено из избранного!');
			}
		})
	})
	$(document).on('click', '.to-stock-btn', function(){
		// $('.mfp-wrap').click();
		var e = $(this);
		var store_id = +e.attr('store_id');
		var price = +e.attr('price');
		var item_id = e.attr('item_id');
		var packaging = +e.attr('packaging');
		if ($('.login_btn span').html() == 'Войти'){
			$('.login_btn').click();
			show_message('Для добавления товара в корзину необходимо авторизоваться!', 'error');
			return false;
		}
		$.ajax({
			type: "POST",
			url: "/ajax/to_basket.php",
			data: "store_id=" + store_id + '&price=' + price + '&packaging=' + packaging + '&item_id=' + item_id,
			success: function(msg){
				// console.log(msg);
				// return;
				// console.log(JSON.parse(msg));
				get_basket(JSON.parse(msg));
				if (!$('.cart span').text()) $('.cart').html('<div class="arrow_up"></div><span>' + packaging + '</span>');
				else $('.cart span').html(parseInt($('.cart span').text()) + parseInt(packaging));
				show_popup_basket();
				setTimeout(function(){
					 if (!$('.cart-popup').is(':hover')) $('.overlay').click();
				}, 2500);
				var curr = +e.find('i').html();
				if (curr) curr += packaging;
				else curr = +packaging;
				e.html('<i class="goods-counter">' + curr + '</i>');
			} 
		});
	});
	$(document).on('mouseover', '.product-popup .rating i', function(){
		e = $(this);
		if (e.parent().hasClass('no_selectable')) return false;
		e.removeClass('fa-star-o');
		e.addClass('fa-star');
		e.prevAll().removeClass('fa-star-o');
		e.prevAll().addClass('fa-star');
	});
	$(document).on('mouseout', '.product-popup .rating i', function(){
		e = $(this);
		if (e.parent().hasClass('no_selectable')) return false;
		e.removeClass('fa-star');
		e.addClass('fa-star-o');
		e.prevAll().removeClass('fa-star');
		e.prevAll().addClass('fa-star-o');
	})
	$(document).on('click', '.product-popup-link', function(e){
		e.preventDefault();
		var t = e.target;
		if (t.className == 'brend_info') return false;
		var item_id = $(this).attr('item_id');
		$.ajax({
			type: "POST",
			url: "/ajax/item_full.php",
			data: 'id=' + item_id + '&category=1',
			success: function(msg){
				// console.log(msg);
				// return;
				// console.log(JSON.parse(msg));
				$('#mgn_popup').html(getFullItem(JSON.parse(msg)));
				$.magnificPopup.open({
					type: 'inline',
					preloader: false,
					mainClass: 'product-popup-wrap',
					items: {
						src: '#mgn_popup'
					},
					callbacks: {
						beforeOpen: function() {
							if($(window).width() < 700) this.st.focus = false;
							else this.st.focus = '#name';
						},
						open: function() {
							$("#gallery img").on("click", function(event) {
								$("#main-pic img").attr("src", $(this).attr("src"));
								$("#main-pic img").attr("data-zoom-image", $(this).attr('data-big-img'));
								// console.log($("#main-pic").html());
							});
							$.ionTabs(".product-popup-tabs",{
								type: "none"
							});
							$(".ionTabs__tab:first-child").click();
							$('#gallery').owlCarousel({
								loop: true,
								margin: 5,
								nav: true,
								dots: false,
								items: 3
							});
						}
					}
				});
			} 
		})
	})
	$(document).on('click', '.product-popup .rating i', function(){
		if ($('.login_btn span').html() == 'Войти') return false;
		var e = $(this);
		if (e.parent().hasClass('no_selectable')) return false;
		var rate = $(this).prevAll().length + 1;
		$.ajax({
			type: "POST",
			url: "/ajax/category.php",
			data: 'table=rating&item_id=' + e.parent().attr('item_id') + '&user_id=' + $('#user_id').val() + '&rate=' + rate,
			success: function(msg){
				// console.log(msg);
				e.parent().addClass('no_selectable');
				e.removeClass('fa-star-o').addClass('fa-star');
				e.prevAll().removeClass('fa-star-o').addClass('fa-star');
			} 
		})
	})
	$('.slider').each(function(){
		var e = $(this);
		e.ionRangeSlider({
			type: "double",
			min: e.attr('min'),
			max: e.attr('max'),
			onFinish: function(data){
				e.attr('from', data.from);
				e.attr('to', data.to);
				$('#filters_on').val(1);
				setTimeout(get_items, 1);
			}
		});
	})
	$(document).on('click', '.product-popup .count-block span', function(){
		var e = $(this);
		var i = e.parent().find('input');
		var p = +i.attr('packaging');
		var c = +i.val();
		var n = 0;
		if (e.hasClass('minus')){
			if (c - p <= 0) return false;
			n = c - p;
		}
		else n = c + p;
		// console.log(n);
		i.val(n);
	})
});