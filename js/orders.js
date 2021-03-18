var reasonsOfReturn;
function getReturns(){
	$.ajax({
		url: '/ajax/order.php',
		type: 'post',
		processData: true,
		data: {
			act: 'get_returns',
		},
		success: function(response){
			let $tab = $('div[data-name=returns]');
			$tab.find('table.orders-table tbody').empty();
			if (!response) return false;
			let items = JSON.parse(response);
			for(var k in items){
				let returnUndo = (items[k].status_id == '2' || items[k].status_id == 1) ? '<a class="undoReturn">Отменить</a>' : '';
				$tab.find('table.orders-table > tbody').append(
					'<tr class="first">' +
						'<td>' + items[k].return_id + '</td>' +
						'<td label="Статус: " class="status_return_' + items[k].status_id + '">' + 
							items[k].status + 
							returnUndo +
						'</td>' +
						'<td label="Сумма: ">' + 
							(items[k].return_price * items[k].quan) + '<i class="fa fa-rub" aria-hidden="true"></i>' + 
						'</td>' +
						'<td class="icon">' +
							'<span class="icon-enlarge2"></span>' +
						'</td>' +
					'</tr>' +
					'<tr class="second">' +
						'<td colspan="4">' +
							'<table class="full_info">' +
								'<thead>' +
									'<tr>' +
										'<th>Наименование</th>' +
										'<th>Количество</th>' + 
										'<th>Причина</th>' + 
										'<th>Дата</th>' + 
									'</tr>' +
								'</thead>' +
								'<tbody>' +
									'<tr>' +
										'<td class="title" label="Наменование: ">' +
											'<b class="brend_info" brend_id="' + items[k].brend_id + '">' + items[k].brend + '</b> ' + 
											'<a href="/search/article/' + items[k].article + '" class="articul">' + items[k].article + '</a> ' +
												items[k].title_full +
										'</td>' +
										'<td label="Количество: ">' + items[k].quan + '</td>' +
										'<td label="Причина: ">' + items[k].reason + '</td>' +
										'<td label="Дата: ">' + items[k].created + '</td>' +
									'</tr>' +
								'</tbody>' +
							'</table>' +
						'</td>' +
					'</tr>'
				);
			} 
		}
	})
}
function show_form_returns(items){
	if (typeof reasonsOfReturn == 'undefined'){
		$.ajax({
			url: '/ajax/order.php',
			type: 'post',
			data: {
				act: 'get_reasons'
			},
			async: false,
			success: function(response){
				reasonsOfReturn = JSON.parse(response);
			}
		})
	}
	$.magnificPopup.open({
		type: 'inline',
		preloader: false,
		mainClass: 'product-popup-wrap',
		callbacks: {
			open: function(){
				$('#mgn_popup table.basket-table tbody').empty();
				let return_summ = 0;
				for(var k in items){
					let strReason = '';
					let commission;
					return_summ = items[k].return_price * items[k].quan;
					for(let i in reasonsOfReturn){
						strReason += '<option ' + (reasonsOfReturn[i].id == items[k].reason_id ? 'selected' : '') + ' value="' + reasonsOfReturn[i].id + '">' + reasonsOfReturn[i].title + '</option>';
					}
					if (return_summ != items[k].summ) commission = 
						'<span class="summ">'
							 + items[k].summ + 
							' <i class="fa fa-rub" aria-hidden="true"></i>' +
						'</span>' +
						'<span class="label">с комиссией</span>' +
						'<span class="summ_return">'
							 + '<span>' + return_summ + '</span>' +
							' <i class="fa fa-rub" aria-hidden="true"></i>' +
						'</span>';
					else commission = 
						'<span class="summ_return">'
							 + '<span>' + return_summ + '</span>' +
							' <i class="fa fa-rub" aria-hidden="true"></i>' +
						'</span>';
					$('#mgn_popup table.basket-table tbody').append(
						'<tr order_id="' + items[k].order_id + '" store_id="' + items[k].store_id + '" item_id="' + items[k].item_id + '">' +
							'<td label="Наменование: ">' + items[k].title + '</td>' + 
							'<td>' +
								'<select name="reason_id">' +
									strReason +
								'</select>' +
							'</td>' +
							'<td label="Количество: " class="quan">' + 
								'<input type="hidden" name="available" value="' + items[k].quan + '">' +
								'<div summand="' + items[k].return_price + '" packaging="' + items[k].packaging + '" class="count-block">' +
									'<span class="minus">-</span>' +
									'<input value="' + items[k].quan + '">' +
									'<span class="plus">+</span>' +
								'</div>' +
							'</td>' +
							'<td label="Сумма: ">' + 
								commission +
							'</td>' +
						'</tr>'
					)
				}
			}
		},
		items: {
			src: '#mgn_popup'
		}
	});
	$('select').styler();
}
$(function(){
	var get = window
		.location
		.search
		.replace('?','')
		.split('&')
		.reduce(
			function(p,e){
				var a = e.split('=');
				p[ decodeURIComponent(a[0])] = decodeURIComponent(a[1]);
				return p;
			},
			{}
		);
	var itemsForReturn = new Array();
	$(document).on('click', 'tr.first', function(){
		$(this).toggleClass('active');
	})
	pickmeup.defaults.locales['ru'] = {
		days: ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'],
		daysShort: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
		daysMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
		months: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
		monthsShort: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек']
	};
	pickmeup('[data-name=common] input[name=begin]', {
		date: $('[data-name=common] input[name=begin]').val(),
		format  : 'd.m.Y',
		locale : 'ru',
		hide_on_select: true
	});
	pickmeup('[data-name=common] input[name=end]', {
		date: $('[data-name=common] input[name=end]').val(),
		format  : 'd.m.Y',
		locale : 'ru',
		hide_on_select: true
	});
	pickmeup('[data-name=group] input[name=begin]', {
		date: $('[data-name=group] input[name=begin]').val(),
		format  : 'd.m.Y',
		locale : 'ru',
		hide_on_select: true
	});
	pickmeup('[data-name=group] input[name=end]', {
		date: $('[data-name=group] input[name=end]').val(),
		format  : 'd.m.Y',
		locale : 'ru',
		hide_on_select: true
	});
	$("[data-name=common] input[name=period]").change(function(event) {
		if (this.value == 'custom') {
			$(this).closest('[data-name=common]').find(".date-wrap input").prop('disabled', false);
		}
		else{
			$(this).closest('[data-name=common]').find(".date-wrap input").prop('disabled', true);
		}
	});
	$("[data-name=group] input[name=period]").change(function(event) {
		if (this.value == 'custom') {
			$(this).closest('[data-name=group]').find(".date-wrap input").prop('disabled', false);
		}
		else{
			$(this).closest('[data-name=group]').find(".date-wrap input").prop('disabled', true);
		}
	});
	$(".order-filter-form .pseudo-select").click(function(event) {
		$(this).toggleClass("opened").nextAll('.status-form').toggle();
	});
	$(document).mouseup(function (e){
		if (!$(e.target).closest('div.status').size()){
			$(".status-form").hide();
			$(".order-filter-form .pseudo-select").removeClass("opened");
		}
	});
	$(document).on('click', ".comment-btn", function(event) {
		var e = $(this);
		e.next('.comment-block').show();
		$(".h_overlay, .overlay").show();
	});
	$(document).on('click', ".cancel_comment", function(event) {
		$(".comment-block, .overlay, .h_overlay").hide();
	});
	$.ionTabs("#orders_tabs",{
		type: "hash",
		onChange: function(obj){
			switch(obj.tab){
				case 'returns': getReturns(); break;
			}
		}
	});
	$(document).on('click', 'a.undoReturn', function(e){
		var th = $(this);
		if (!confirm('Вы действительно хотите отменить возврат?')) return false;
		$.ajax({
			url: '/ajax/order.php',
			type: 'post',
			processData: true,
			data: {
				act: 'undoReturn',
				return_id: th.closest('tr').find('td:first-child').text()
			},
			success: function(response){
				$.cookie('message', 'Успешно отменено!', cookieOptions);
				$.cookie('message_type', 'ok', cookieOptions);
				document.location.reload();
			}
		})
	})
	if (get.tab) $.ionTabs.setTab('orders', get.tab);
	$('[data-name=group] tr[order_id]').on('click', function(){
		document.location.href = '/order/' + $(this).attr('order_id');
	})
	$('tr[sending_id]').on('click', function(e){
		document.location.href = '/sending/' + $(this).attr('sending_id');
	})
	$(document).on('click', 'a.return', function(e){
		e.preventDefault();
		var tr = $(this).closest('tr');
		var order_id = + tr.attr('order_id');
		var store_id = + tr.attr('store_id');
		var item_id = + tr.attr('item_id');
		var key = order_id + '-' + store_id + '-' + item_id;
		if (typeof itemsForReturn[key] !== 'undefined') return show_form_returns(itemsForReturn);
		itemsForReturn[key] = {
			order_id: order_id, 
			store_id: store_id, 
			item_id: item_id,
			title: tr.find('.name-col').html(),
			summ: + tr.find('.price_format').text(),
			return_price: + $(this).attr('return_price'),
			days_from_purchase: + $(this).attr('days_from_purchase'),
			packaging: + $(this).attr('packaging'),
			reason_id: 1,
			quan: + tr.find('.quan').text()
		};
		console.log(itemsForReturn);
		show_form_returns(itemsForReturn);
	})
	$(document).on('change', 'select[name=reason_id]', function(){
		var order_id = $(this).closest('tr').attr('order_id');
		var store_id = $(this).closest('tr').attr('store_id');
		var item_id = $(this).closest('tr').attr('item_id');
		itemsForReturn[order_id + '-' + store_id + '-' + item_id].reason_id = $(this).val();
	})
	$(document).on('click', ".count-block .minus, .count-block .plus", function(event) {
		var e = $(this);
		var act = e.attr('class');
		e = $(this).parent();
		var order_id = e.closest('tr').attr('order_id');
		var store_id = e.closest('tr').attr('store_id');
		var item_id = e.closest('tr').attr('item_id');
		var packaging = + e.attr('packaging');
		var summand = + e.attr('summand');
		var available = e.prevAll('input[name=available]').val();
		var newVal = 0;
		if (act == 'plus') newVal = +e.find('input').val() + packaging;
		else newVal = +e.find('input').val() - packaging;
		if (newVal < 1 || newVal > available) return false;
		e.find('input').val(newVal);
		e.closest('tr').find('span.summ_return > span').html(newVal * summand);
		itemsForReturn[order_id + '-' + store_id + '-' + item_id].quan = newVal;
	});
	$('#mgn_popup a.button').on('click', function(){
		let data = new Array();
		for(var k in itemsForReturn) data.push(itemsForReturn[k]);
		$.ajax({
			url: '/ajax/order.php',
			type: 'post',
			processData: true,
			data: {
				act: 'to_return',
				items: data
			},
			success: function(response){
				$.cookie('message', 'Возврат успешно оформлен', cookieOptions);
				$.cookie('message_type', 'ok', cookieOptions);
				// document.location.reload();
			}
		})
		return false;
	})
	$('a.removeFromOrder').on('click', function(){
		if (!confirm('Вы действительно хотите удалить?')) return false;
		var th = $(this);
		$.ajax({
			url: '/ajax/order.php',
			type: 'post',
			processData: true,
			data: {
				act: 'removeFromOrder',
				order_id: th.closest('tr').attr('order_id'),
				store_id: th.closest('tr').attr('store_id'),
				item_id: th.closest('tr').attr('item_id')
			},
			success: function(response){
				th.closest('tr').remove();
				show_message('Успешно удалено!');
			}
		})
		return false;
	})
});