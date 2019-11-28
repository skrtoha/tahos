function getFullItem(i){
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
	str +=
	'</div>' +
	'<div class="clearfix"></div>' + 
 	'<div class="gallery-block" style="' + s + '">';
	var is_foto = item.foto ? true : false;
	var c_fotos = Object.keys(i.fotos).length;
	// console.log(c_fotos);
	if (is_foto){
		var src_small = '/images/items/small/' + item.id + '/' + item.foto;
		var src_big = '/images/items/big/' + item.id + '/' + item.foto;
		str += '' +
				'<div id="main-pic">' + 
					'<img src="'+ src_small + '" data-zoom-image="' + src_big + '">' + 
				'</div>';
		if (c_fotos){
			str += '<div id="gallery">';
			for (var k in i.fotos){
				var src_small = '/images/items/small/' + item.id + '/' + i.fotos[k];
				var src_big = '/images/items/big/' + item.id + '/' + i.fotos[k];
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
		'<button href="/search/article/' + item.article + '"  id="search-same"><span class="icon_search"></span>Другие предложения</button>' +
		'<div class="clearfix"></div>' +
	'</div>';
	return str;
}
function set_max_height(){
	var max = 0;
	$('.description-block .ionTabs__item ').each(function(){
		if ($(this).height() > max) max = $(this).height();
		// console.log(
		// 	'this.height: ' + $(this).height(),
		// 	'\nmax: ' + max
		// )
	});
	$('.description-block .ionTabs__body').height(max);
}
function set_tabs(){
	$.ionTabs("#search-result-tabs",{
		type: "none",
		onChange: function(obj){
			// console.log(hidable_form);
			switch(obj.tab){
				case 'Tab_1': 
					var search_type = "articles"; 
					$('#price-from').val($('#price_from').val());
					$('#price-to').val($('#price_to').val());
					$('#time-from').val($('#time_from').val());
					$('#time-to').val($('#time_to').val());
					break;
				case 'Tab_2': var search_type = "substitutes"; break;
				case 'Tab_3': var search_type = "analogies"; break;
				case 'Tab_4': var search_type = "complects"; break;
			}
			$('#offers-filter-form').removeClass('hidden');
			if (search_type == 'articles' && hidable_form){
				$('#offers-filter-form').addClass('hidden');
			}
			$('#offers-filter-form button').attr('search_type', search_type);
			if (search_type == 'substitutes' || search_type == 'analogies' || search_type == 'complects'){
				var item_id = $('#item_id').val();
				var data = "item_id=" + item_id + "&search_type=" + search_type;
				// console.log(data);
				$.ajax({
					type: "POST",
					url: "/ajax/article_filter.php",
					data: data,
					beforeSend: function(){
						$(obj.tabId + ' .articul-table').html(
							'<tr class="gif">' +
								'<td colspan="7"></td>' +
							'</tr>');
						$(obj.tabId + ' .mobile-layout').html(
							'<div class="gif"></div>'
						);
					},
					success: function(msg){
						if (msg){
							// console.log(msg);
							// return;
							var res = JSON.parse(msg);
							var pi = providers_items(res.providers_items);
							// console.log(res);
							// console.log(Math.min.apply(null, res.prices));
							$('#price-from').val(Math.min.apply(null, res.prices));
							$('#price-to').val(Math.max.apply(null, res.prices));
							$('#time-from').val(Math.min.apply(null, res.deliveries));
							$('#time-to').val(Math.max.apply(null, res.deliveries));
						}
						else{
							pi = {
								full: '<div>Ничего не найдено.</div>',
								mobile: '<div>Ничего не найдено.</div>'
							}
							$('#offers-filter-form').addClass('hidden');
						} 
						switch(search_type){
							case 'substitutes': 
								$('#Tab__search-result-tabs__Tab_2 .articul-table').html(pi.full); 
								$('#Tab__search-result-tabs__Tab_2 .mobile-layout').html(pi.mobile); 
								break;
							case 'analogies': 
								$('#Tab__search-result-tabs__Tab_3 .articul-table').html(pi.full); 
								$('#Tab__search-result-tabs__Tab_3 .mobile-layout').html(pi.mobile); 
								break;
							case 'complects': 
								$('#Tab__search-result-tabs__Tab_4 .articul-table').html(pi.full); 
								$('#Tab__search-result-tabs__Tab_4 .mobile-layout').html(pi.mobile); 
								break;
						}
						price_format();
					} 
				});
			}
		}
	});
}
$(document).ready(function(){
	set_tabs();	
	$(document).on('click', '.product-popup-wrap', function(){
		set_tabs();
	})
	$(document).on('click', '.product-popup-link', function(e){
		e.preventDefault();
		var t = e.target;
		if (t.className == 'brend_info') return false;
		var item_id = $(this).closest('.item_id').attr('item_id');
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
	$('.remarks').blur(function(){
		var elem = $(this);
		$.ajax({
			type: "POST",
			url: "/ajax/remark.php",
			data: "item_id=" + elem.closest('.item_id').attr('item_id') + '&remark=' + elem.val() + '&act=remark',
			success: function(msg){
				if (msg) show_message('Заметка сохранена!', 'ok');
				else show_message('Произошла ошибка!', 'error');
			} 
		});
	});
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
				$('.item_id[item_id=' + e.attr('item_id') + ']').remove();
			}
		})
	})
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
	$(document).on('click', '.delete-btn', function(){
		var e = $(this);
		var item_id = e.closest('.item_id').attr('item_id');
		$.ajax({
			method: 'post',
			url: '/ajax/favorite.php',
			data: 'item_id=' + item_id + '&act=delete',
			success: function(){
				e.closest('.item_id').remove();
				show_message('Успешно удалено из избранного!');
				if ($('.favorites-table tr').length == 1 || $('.mobile-layout .good').length == 1){
					$('.favorites-table').append(
						'<tr><td colspan="4">Избранного не найдено</td></tr>'
					);
					$('.mobile-layout').append(
						'<p>Избранного не найдено</p>'
					);
				} 
					
			}
		})
	})
	$(document).on('click', '#search-same', function(){
		document.location.href = $(this).attr('href');
	})
	$('#favorite_clear').on('click', function(){
		if (!confirm('Вы действительно хотите удалить все?')) return false;
		$.ajax({
			method: 'post',
			url: '/ajax/favorite.php',
			data: 'act=clear',
			success: function(){
				show_message('Успешно удалено');
				$('.favorites-table tr:nth-child(n + 2)').remove();
				$('.favorites-table').append(
					'<tr><td colspan="4">Избранного не найдено.</td></tr>'
				);
				$('.mobile-layout').html('Избранного не найдено.');
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
})
