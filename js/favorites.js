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
