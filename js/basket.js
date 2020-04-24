$(function(){
	$('input[type=checkbox]').styler();
	$(".count-block .minus, .count-block .plus").click(function(event) {
		var e = $(this);
		var available = + e.closest('.good').find('input[name=available]').val();
		if (available !== 'undefined'){
			if (available == -1) return show_message('Данной позиции нет в наличии!', 'error');
		}
		// if (!e.closest('.good').find('input[name=toOrder]').prop('checked')) return false;
		var act = e.attr('class');
		e = $(this).parent();
		var store_id = e.attr('store_id');
		var item_id = e.attr('item_id');
		var packaging = + e.attr('packaging');
		if (act == 'minus' && + e.find('input').val() - packaging == 0) return false;
		if (act == 'plus' && + e.find('input').val() + packaging > available) {
			e.closest('div').nextAll('span.available').addClass('active');
			return show_message('Превышено доступное количество!', 'error');
		}
		else e.closest('div').nextAll('span.available').removeClass('active');
		var summand = e.attr('summand');
		var sel = '[store_id=' + store_id + '][item_id=' + item_id + ']';
		var data = 
			'act=' + act +
			'&store_id=' + store_id +
			'&item_id=' + item_id + 
			'&packaging=' + packaging +
			'&summand=' + summand;
		$.ajax({
			type: "POST",
			url: "/ajax/basket.php",
			data: data,
			success: function(msg){
				var newVal;
				if (act == 'plus'){
					newVal = +e.find('input').val() + packaging;
					$('#basket_basket').html(+$('#basket_basket').unmask() + packaging * summand);
					if (e.closest('.good').find('input[name=toOrder]').prop('checked')){
						$('#totalToOrder').html(+$('#totalToOrder').html() + packaging * summand);
					}
					$('.cart span').html(+$('.cart span').html() + packaging);
				} 
				if (act == 'minus'){
					newVal = +e.find('input').val() - packaging;
					$('#basket_basket').html(+$('#basket_basket').unmask() - packaging * summand);
					if (e.closest('.good').find('input[name=toOrder]').prop('checked')){
						$('#totalToOrder').html(+$('#totalToOrder').html() - packaging * summand);
					}
					$('.cart span').html(+$('.cart span').html() - packaging);
				} 
				e.parent().nextAll('.subtotal').find('span').html(newVal * summand);
				e.closest('.good').find('.subtotal .price_format').html(newVal * summand);
				$('.cart-popup-table tr' + sel).find('td:nth-child(2)').html(newVal + ' шт.');
				$('.cart-popup-table tr' + sel).find('td:nth-child(3) span').html(newVal * summand);
				e.find('input').val(newVal);
				price_format();
				$('.cart-popup ' + sel + ' td:nth-child(2)').html(newVal + ' шт.');
			}
		});
	});
	$(".favorites-btn").click(function(event) {
		$(this).toggleClass('fa-star fa-star-o');
		var elem = $(this);
		var item_id = elem.attr('item_id');
		var act = elem.hasClass('fa-star') ? 'add' : 'delete';
		// console.log(act);
		$.ajax({
			type: "POST",
			url: "/ajax/favorite.php",
			data: "item_id=" + item_id + '&act=' + act,
			success: function(msg){
				if (act == 'add'){
					show_message('Добавлено в избранное');
					$('i.fa-star[item_id=' + item_id + ']').removeClass('fa-star-o').addClass('fa-star');
				} 
				else{
					show_message('Удалено из избранного');
					$('i.fa-star-o[item_id=' + item_id + ']').removeClass('fa-star').addClass('fa-star-o');
				} 
				
			} 
		});
	});
	$('#get_offer').on('click', function(){
		var elem = $(this);
		var no_sendable = elem.attr('no_sendable');
		var loc = elem.attr('loc');
		document.location.href = elem.attr('loc');
	})
	$(".cancel_comment").click(function(event) {
		$(".comment-block, .overlay, .h_overlay").hide();
	});
	$(".overlay, .h_overlay").click(function(event) {
		$(this).hide();
		$(".cart-popup, .comments-block").hide();
	});
	$(".comment-btn").click(function(event) {
		var e = $(this);
		e.next('.comment-block').show();
		$(".h_overlay, .overlay").show();
	});
	$(".comment-block button").on('click', function(){
		var e = $(this).closest('div').prev();
		var comment = $(this).prevAll('.comment_textarea').val();
		var data =
					'item_id=' + e.closest('i').attr('item_id') + 
					'&store_id=' + e.closest('i').attr('store_id') + 
					'&comment=' + comment;
		if ($(this).prev('label').find('input').is(':checked')){
			data += '&filds=all';
			$('.comment_textarea').val(comment);
		} 
		$.ajax({
			type: "POST",
			url: "/ajax/basket_comment.php",
			data: data,
			success: function(msg){
				console.log(msg);
				$('.overlay').click();
				show_message('Комментарий успешно изменен', 'ok');
			} 
		});
	})
	$('.basket-table .delete-btn, .basket .good .delete-btn').on('click', function(){
		var e = $(this);
		if (e.attr('view_type') == 'mobile') var elem = e.next('.count-block');
		else var elem = e.closest('tr').find('.count-block');
		var store_id = elem.attr('store_id');
		var item_id = elem.attr('item_id');
		var quan = elem.find('input').val();
		var packaging = elem.attr('packaging');
		var summand = elem.attr('summand');
		var data = 'store_id=' + store_id +
				'&item_id=' + item_id +
				'&act=delete';
		// console.log(data);
		$.ajax({
			type: 'POST',
			url: '/ajax/basket.php',
			data: data,
			success: function(msg){
				e.closest('tr').remove();
				e.closest('.good').remove();
				$('#basket_basket').html(+$('#basket_basket').unmask() - (summand * quan));
				$('.cart-popup-table tr[store_id=' + store_id + '][item_id=' + item_id + ']').remove();
				$('.cart span').html(+$('.cart span').html() - quan);
				$('#total_basket').html(+$('#total_basket').unmask() - (summand * quan));
				$('#total_quan').html(+$('#total_quan').html() - quan);
				$('#totalToOrder').html(+$('#totalToOrder').html() - (summand * quan));
				if ($('.cart-popup-table tr').length == 2){
					$('.cart-popup-table').html('<tr><td colspan="4">Корзина пуста</td>').next().remove();
					$('.cart span').remove();
					$('.basket-table tr:nth-child(n+2)').remove();
					$('.basket-table').append(
						'<tr>' + 
							'<td colspan="10">Корзина пуста</td>' +
						'</tr>'
					);
					$('.total').remove();
					$('.basket a.button').remove();
				}
				$('#mgn_popup tr[store_id=' + store_id + '][item_id=' + item_id + ']').remove();
				price_format();
			}
		})
});
	$('.basket-table #basket_clear').on('click', function(){
		if (!confirm('Вы действительно хотите очистить корзину?')) return false;
		$.ajax({
			method: 'POST',
			url: '/ajax/basket.php',
			data: 'act=clear',
			success: function(msg){
				if (msg){
					$('.basket-table tr:nth-child(n+2)').remove();
					$('.basket-table').append(
						'<tr>' + 
							'<td colspan="10">Корзина пуста</td>' +
						'</tr>'
					);
					$('.total').remove();
					$('.basket a.button').remove();
					show_message('Корзина успешно очищена!');
					$('.cart-popup-table').html('<tr><td colspan=4>Корзина пуста</td></tr>');
					$('.cart-popup button').remove();
					$('.cart span').remove();
					$('.to-stock-btn').html('');
				}
				else show_message('Произошла ошибка');
			}
		})
	})
	$('div.count-block input').on('blur', function(){
		var count = $(this).val();
		var th = $(this).closest('div.count-block');
		var packaging = th.attr('packaging');
		var reg = /^\d+$/;
		var currTotalSum = 0;
		var totalCount = 0;
		var store_id = th.attr('store_id');
		var item_id = th.attr('item_id');
		// console.log(count % packaging);
		if (!reg.test(count) || count < 1){
			show_message("Введите целое число отличное от нуля!", 'error');
			$(this).focus();
			return false;
		}
		if (count % packaging != 0){
			show_message('Значение должно нацело делиться на ' + packaging + '!', 'error');
			$(this).focus();
			return false;
		}
		var subtotal = th.find('input').val() * th.attr('summand');
		th.closest('tr').find('.subtotal .price_format').html(subtotal);
		$('.cart-popup-table tr[store_id=' + store_id + '][item_id=' + item_id + '] td:nth-child(2)').html(count + ' шт.');
		$('.cart-popup-table tr[store_id=' + store_id + '][item_id=' + item_id + '] span.price_format').html(subtotal);
		$('.basket-table .subtotal .price_format').each(function(){
			currTotalSum = +$(this).html() + currTotalSum;
		});
		$('.basket-table div[store_id]').each(function(){
			totalCount = + $(this).find('input').val() + totalCount;
		})
		$('a.cart span').html(totalCount);
		$('#total_quan').html(totalCount);
		$('#basket_basket').html(currTotalSum);
		$('#total_basket').html(currTotalSum);
		$.ajax({
			type: 'post',
			url: '/ajax/basket.php',
			data: 'act=computing&store_id=' + store_id + 
						'&item_id=' + item_id + 
						'&packaging=' + packaging +
						'&summand=' + th.attr('summand') +
						'&value=' + th.find('input').val(),
			success: function(response){
				// console.log(response); return false;
			}
		})
	})
	$('div.basket > a.button').on('click', function(e){
		var noReturn = new Array();
		if ($(document).width() >= 880){
			$('table.basket-table input[name=toOrder]:checked').each(function () {
				var th = $(this).closest('tr');
				if (!th.find('.noReturn').size()) return 1;
				noReturn.push({
					brend: th.find('b.brend_info').html(),
					article: th.find('a.articul').html(),
					title: th.find('span.title').html()
				});
			});
		}
		else{
			$('div.mobile-view input[name=toOrder]:checked').each(function () {
				var th = $(this).closest('div.good');
				if (!th.find('.noReturn').size()) return 1;
				noReturn.push({
					brend: th.find('b.brend_info').html(),
					article: th.find('a.articul').html(),
					title: th.find('p.title').html()
				});
			});
		}
		// console.log(noReturn);
		// return false;
		if (noReturn.length){
			$.magnificPopup.open({
				type: 'inline',
				preloader: false,
				mainClass: 'product-popup-wrap',
				callbacks: {
					open: function(){
						var str = '<tr>' +
												'<th>Бренд</th>' +
												'<th>Артикул</th>' +
												'<th>Название</th>' +
											'</tr>';
						for(var k in noReturn){
							str += 
								'<tr>' +
									'<td>' + noReturn[k].brend + '</td>' + 
									'<td>' + noReturn[k].article + '</td>' +
									'<td>' + noReturn[k].title + '</td>' +
								'</tr>';
						} 
						$('#mgn_popup table.basket-table').html(str);
					}
				},
				items: {
					src: '#mgn_popup'
				}
			});
			return false;
		}
	})
	$('input[name=toOrder]').on('change', function(){
		var e = $(this);
		if (e.closest('tr').find('input[name=available]').val() == '-1') return show_message('Данной позиции нет в наличии!', 'error');
		if (e.attr('view_type') == 'mobile') var elem = e.closest('.good').find('.count-block');
		else var elem = e.closest('tr').find('.count-block');
		// console.log(elem);
		var quan = elem.find('input').val();
		var summand = elem.attr('summand');
		if (e.is(':checked')) $('#totalToOrder').html(+$('#totalToOrder').html() + quan * summand);
		else $('#totalToOrder').html(+$('#totalToOrder').html() - quan * summand);
		$.ajax({
			type: 'post',
			url: "/ajax/basket.php",
			data: {
				act: e.is(':checked') ? 'isToOrder' : 'noToOrder',
				store_id: elem.attr('store_id'),
				item_id: elem.attr('item_id')
			},
			success: function(response){
				// console.log(response); return false;
			}
		})
	})
})
