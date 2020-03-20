var ajax_url = '/admin/ajax/orders.php';
var reg_integer = /^\d+$/;
var currencies;
var store = {
	id: '',
	title: '',
	city: '',
	cipher: '',
	currency_id: '',
	percent: '0.00',
	provider_id: '',
	delivery: '',
	delivery_max: '',
	under_order: '',
	prevail: '',
	noReturn: 0
};
function get_str_currencies(currency_id = false){
	var str = '<select disabled name="currency_id">';
	for(var key in currencies) {
		var c = currencies[key];
		var selected = c.id == currency_id ? 'selected' : '';
		str += '<option ' + selected + ' value="' + c.id + '">' + c.title + '</option>';
	}
	str += '</select>';
	return str;
}
function get_str_form(){
	var form_bottom;
	var str = '';
	str +=
		'<form name="store_change">' +
			'<input type="hidden" name="store_id" value="' + store.id + '">' +
			'<table>' +
			 	'<tr>' +
				 	'<td>Название:</td>' +
				 	'<td><input disabled type="text" name="title" value="' +  store.title + '"></td>' +
			 	'</tr>' +
			 		'<tr>' +
				 	'<td>Город:</td>' +
				 	'<td><input disabled type="text" name="city" value="' +  store.city + '"></td>' +
			 	'</tr>' +
			 	'<tr>' +
				 	'<td>Шифр:</td>' +
				 	'<td><input disabled type="text" name="cipher" value="' +  store.cipher + '"></td>' +
			 	'</tr>' +
			 	'<tr>' +
				 	'<td>Валюта</td>' +
				 	'<td>' + get_str_currencies(store.currency_id) + '</td>' +
			 	'</tr>' +
			 	'<tr>' +
				 	'<td>Процент надбавки</td>' +
				 	'<td><input disabled type="text" name="percent" value="' + store.percent + '" /></td>' +
			 	'</tr>' +
		 		'<tr>' +
				 	'<td>Срок доставки</td>' +
				 	'<td><input disabled type="text" name="delivery" value="' + store.delivery + '" /></td>' +
			 	'</tr>' +
			 	'<tr>' +
				 	'<td>Максимальный срок</td>' +
				 	'<td><input disabled type="text" name="delivery_max" value="' + store.delivery_max + '" /></td>' +
			 	'</tr>' +
			 	'<tr>' +
				 	'<td>Под заказ</td>' +
				 	'<td><input disabled type="text" name="under_order" value="' + store.under_order + '" /></td>' +
			 	'</tr>' +
			 	'<tr>' +
				 	'<td>Подсвечивать</td>' +
				 	'<td><input disabled type="checkbox" name="prevail" ' + store.prevail + ' value="1"></td>' +
			 	'</tr>' +
			 	'<tr>' +
				 	'<td>Без возврата</td>' +
				 	'<td><input disabled type="checkbox" name="noReturn" ' + store.noReturn + ' value="1"></td>' +
			 	'</tr>' +
	 		'</table>' +
		'</form>';
	return str;
}
function set_store(store_id){
	var array = new Array();
	$.ajax({
		type: 'post',
		async: false,
		url: '/admin/ajax/providers.php',
		data: 'act=get_store&store_id=' + store_id,
		success: function(response){
			store = JSON.parse(response);
			store.prevail = +store.prevail ? 'checked' : '';
			store.noReturn = +store.noReturn ? 'checked' : '';
			// console.log(store);
		}
	})
}
function first_option(obj){
	obj.find('option').prop('selected', false);
	obj.find('option:first-child').prop('selected', true);
}
function arrived_new(obj){
	var th = obj.closest('#arrived_change');
	var type = th.find('input[name=new_value_radio]:checked').val();
	var current = + th.find('input[name=value]').val();
	var store_id = + th.attr('store_id');
	var item_id = + th.attr('item_id');
	var tr = $('tr[store_id=' + store_id + '][item_id=' + item_id + ']');
	var arrived = + tr.find('input[name=arrived]').val();
	var order_id = + tr.find('input[name=order_id]').val();
	var quan = + tr.find('input[name=quan]').val();
	if (type == 'arrived_new'){
		// console.log(current, arrived);
		if (current + arrived > quan || !reg_integer.test(current) || !current){
			show_message('Значение задано неккоректно!', 'error');
			$('#arrived_change input[name=value]').focus();
			return false;
		}
	}
	$.ajax({
		type: 'post',
		url: '/admin/ajax/orders.php',
		data: 'status_id=' + type + '&order_id=' + order_id + '&store_id=' + store_id + 
					'&item_id=' + item_id + '&current=' + current + '&user_id=' + tr.find('input[name=user_id]').val() +
					'&price=' + tr.find('input[name=price]').val(),
		success: function(response){
			// console.log(response); return false;
			$('#modal-container').removeClass('active');
			document.location.reload();
		}
	})
}
function issued_new(obj){
	var th = obj.closest('#issued_change');
	var provider_id = + th.attr('provider_id');
	var item_id = + th.attr('item_id');
	var tr = $('tr[provider_id=' + provider_id + '][item_id=' + item_id + ']');
	var order_id = + tr.find('input[name=order_id]').val();
	var user_id = + tr.find('input[name=user_id]').val();
	var price = + tr.find('input[name=price]').val();
	var issued = tr.find('input[name=issued]').val();
	var arrived = tr.find('input[name=arrived]').val();
	var issued_new = th.find('input[name=value]').val();
	if (!reg_integer.test(issued_new) || issued_new > arrived - issued){
		show_message('Значение задано неккоректно!', 'error');
		return false;
	}
	$.ajax({
		type: 'post',
		url: '/admin/ajax/orders.php',
		data: 'status_id=issued_new' + '&order_id=' + order_id + '&provider_id=' + provider_id + 
					'&item_id=' + item_id + '&issued_new=' + issued_new + '&user_id=' + user_id +
					'&price=' + price + '&bill=' + tr.find('input[name=bill]').val(),
		success: function(response){
			// console.log(response); return false;
			$('#modal-container').removeClass('active');
			document.location.reload();
		}
	})
}
function check_value(th, value, compared){
	if (value === null){
		first_option(th);
		return false;
	} 
	if (value == 0 || !reg_integer.test(value)){
		show_message('Количество указано неккоректно!', 'error');
		first_option(th);
		return false;
	}
	if (value > compared){
		show_message('Количество указано неккоректно!', 'error');
		first_option(th);
		return false;
	}
	return true;
}
function setTotalSumm(){
	var total = 0;
	$('tr[store_id][item_id]').each(function(){
		var th = $(this);
		summ = th.find('input[name=price]').val() * th.find('input[name=quan]').val();
		th.find('td.sum').html(summ);
		total += summ;
	})
	$('td.total').html(total);
}
$(function(){
	$.ajax({
		type: 'post',
		url: '/admin/ajax/providers.php',
		data: '&act=get_currencies',
		success: function(response){
			// console.log(response);
			currencies = JSON.parse(response);
		}
	});
	$(document).on('submit', 'form[name=store_change]', function(e){
		return false;
	})
	$(document).on('click', 'a.store', function(e){
		var store_id = $(this).attr('store_id');
		set_store(store_id);
		modal_show(get_str_form());
	})
	$('.orders_box').on('click', function(){
		document.location.href = $(this).attr('href');
	});
	$('a.allInWork').on('click', function(){
		if (!confirm('Вы уверены?')) return false;
	})
	$(document).on('submit', '#askForQuanAndPrice', function(e){
		e.preventDefault();
		var array = $(this).serializeArray();
		var store_id = array[0].value;
		var item_id = array[1].value;
		var form = $('tr[store_id=' + store_id + '][item_id=' + item_id + '] td.change_status form');
		var data = {
			status_id: 2,
			new_returned: array[2].value,
			returned: form.find('input[name=returned]').val(),
			ordered: form.find('input[name=ordered]').val(),
			store_id: store_id,
			item_id: item_id,
			price: array[3].value,
			bill: form.find('input[name=bill]').val(),
			order_id: form.find('input[name=order_id]').val(),
			user_id: form.find('input[name=user_id]').val(),
		}
		$.ajax({
			type: 'post',
			url: ajax_url,
			data: data,
			success: function(response){
				// console.log(response); return false;
				document.location.reload();
			}
		})
	})
	$('select.change_status').on('change', function(){
		var th = $(this);
		var status_id = + th.val();
		if (status_id != 2) return false;
		if (!confirm('Подтверждаете действие?')) return false;
		var form = th.closest('form');
		var issued = form.find('input[name=issued]').val();
		var returned = form.find('input[name=returned]').val();
		var tr = form.closest('tr');
		modal_show(
			'<form id="askForQuanAndPrice">' +
				'<input type="hidden" name="store_id" value="' + tr.attr('store_id') + '">' +
				'<input type="hidden" name="item_id" value="' + tr.attr('item_id') + '">' +
				'<table>' +
					'<tr>' +
						'<td>Количество:</td>' +
						'<td><input type="text" name="quan" value="' + (issued - returned) + '"></td>' +
					'</tr>' +
					'<tr>' +
						'<td>Цена:</td>' +
						'<td><input type="text" name="price" value="' + form.find('input[name=price]').val() + '"></td>' +
					'</tr>' +
					'<tr>' +
						'<td colspan="2"><input type="submit" value="Отправить"></td>' +
					'</tr>' +
				'</table>' +
			'</form>'
		);
	})
	$('select.change_status').on('change', function(){
		var th = $(this);
		var status_id = + th.val();
		//добавлено, т.к. обработка идет в другом месте
		if (status_id == 2) return false;
		if (status_id == 6 || status_id == 8){
			if (!confirm('Вы подтверждаете действие?')) return false;
		}
		th = th.closest('form');
		var order_id = + th.find('input[name=order_id]').val();
		var store_id = + th.find('input[name=store_id]').val();
		var item_id = + th.find('input[name=item_id]').val();
		var data = 'status_id=' + status_id + '&order_id=' + order_id + '&store_id=' + store_id + '&item_id=' + item_id;
		data += '&user_id=' + th.find('input[name=user_id]').val();
		data += '&price=' + th.find('input[name=price]').val();
		data += '&bill=' + th.find('input[name=bill]').val();
		data += '&reserved_funds=' + th.find('input[name=reserved_funds]').val();
		switch(status_id){
			case 1:
				break;
			//выделено в отдельную функцию
			case 2:break;
			case 3:
				var ordered = + th.find('input[name=ordered]').val();
				var arrived = prompt('Укажите количество:', ordered);
				if (!check_value(th, arrived, ordered)) return false;
				data += '&arrived=' + arrived;
				break;
			case 8:
				var ordered = + th.find('input[name=ordered]').val();
				data += '&ordered=' + ordered;
				break;
			case 11:
				var quan = + th.find('input[name=quan]').val();
				if (quan > 1) var ordered = prompt('Укажите количество:', quan);
				else ordered = quan;
				if (!check_value(th, ordered, quan)) return false;
				data += '&ordered=' + ordered;
				break;
		}
		$.ajax({
			type: 'post',
			url: ajax_url,
			data: data,
			success: function(response){
				// console.log(response); return false;
				// document.location.reload();
			}
		})
	})
	$('a.arrived_change').on('click', function(e){
		e.preventDefault();
		var th = $(this);
		modal_show(
			'<div id="arrived_change" store_id="' + th.closest('tr').attr('store_id') + '" item_id="' + th.closest('tr').attr('item_id') + '">' +
				'<label>' +
					'<input checked type="radio" name="new_value_radio" value="arrived_new">' +
					'Еще пришло: <input type="text" name="value" value="' + th.html() + '"/>' +
				'</label>' +
				'<label>' +
					'<input type="radio" name="new_value_radio" value="declined">' +
					'Отказ поставщика'  +
				'</label>' +
				'<input type="submit" value="Применить">' +
			'</div>'
		);
		setTimeout(function(){
			$('#arrived_change input[name=value]').focus();
		}, 100);
	});
	$('a.issued_change').on('click', function(e){
		e.preventDefault();
		var th = $(this);
		var arrived = + th.closest('tr').find('input[name=arrived]').val();
		var issued = + th.html();
		modal_show(
			'<div id="issued_change" store_id="' + th.closest('tr').attr('store_id') + '" item_id="' + th.closest('tr').attr('item_id') + '">' +
				'Еще выдано: <input type="text" name="value" value="' + (arrived - issued) + '"/>' +
				'<input type="submit" value="Применить">' +
			'</div>'
		);
		setTimeout(function(){
			$('#issued_change input[name=value]').focus();
		}, 100);
	});
	$(document).on('click', '#issued_change input[type=submit]', function(){
		issued_new($(this));
	})
	$(document).on('keyup', '#issued_change input[name=value]', function(e){
		if (e.keyCode != 13) return false;
		issued_new($(this));
	})
	$(document).on('change', '#arrived_change input[type=radio]', function(){
		var type = $('input[name=new_value_radio]:checked').val();
		if (type == 'declined'){
			$('#arrived_change input[name=value]').prop('disabled', true);
		}
		else{
			$('#arrived_change input[name=value]').prop('disabled', false);
		}
	})
	$(document).on('click', '#arrived_change input[type=submit]', function(){
		arrived_new($(this));
	})
	$(document).on('keyup', '#arrived_change input[name=value]', function(e){
		if (e.keyCode != 13) return false;
		arrived_new($(this));
	})
	$('#to_basket').on('click', function(){
		var str = '';
		$('input[name=return_to_basket]').each(function(){
			if (!$(this).is(':checked')) return true;
			var form = $(this).closest('tr').find('td.change_status form');
			var order_id = form.find('input[name=order_id]').val();
			var store_id = form.find('input[name=store_id]').val();
			var item_id = form.find('input[name=item_id]').val();
			$('tr[store_id=' + store_id + '][item_id=' + item_id + ']').remove();
			str += form.find('input[name=user_id]').val() + ':' + order_id + ':' + store_id + ':' + item_id + ',';
		});
		str = str.substr(0, str.length - 1);
		$.ajax({
			type: 'post',
			url: '/admin/ajax/orders.php',
			data: 'status_id=return_to_basket&str=' + str,
			success: function(response){
				// console.log(response); return;
				if (!$('tr[store_id][item_id]').size()){
					$.cookie('message', 'Успешно возвращено', cookieOptions);
					$.cookie('message_type', 'ok', cookieOptions);
					document.location.href = '/admin/?view=orders';
				}
				else show_message('Успешно возвращено!');
			}
		})
	})
	$('textarea[name=comment]').on('change', function(){
		var form = $(this).closest('tr').find('td.change_status form');
		$.ajax({
			type: "post",
			url: '/admin/ajax/orders.php',
			data: 'status_id=comment&text=' + $(this).val() + 
				'&store_id=' + form.find('input[name=store_id]').val() + 
				'&item_id=' + form.find('input[name=item_id]').val() +
				'&order_id=' + form.find('input[name=order_id]').val(),
			success: function(response){
				console.log(response);
			}
		})
	})
	$('.icon-cancel-circle').on('click', function(){
		if (!confirm('Вы действительно хотите удалить?')) return false;
		var th = $(this);
		$.ajax({
			type: 'post',
			url: '/admin/ajax/orders.php',
			data: {
				status_id: 'remove',
				order_id: $('input[name=order_id]').val(),
				item_id: $(this).closest('tr').attr('item_id'),
				store_id: $(this).closest('tr').attr('store_id')
			},
			success: function(response){
				if (!parseInt(response)) document.location.href = '/admin/?view=orders';
				th.closest('tr').remove();
				setTotalSumm();
			}
		})
	})
	$('input[type=text]').on('change', function(){
		var th = $(this);
		switch (th.attr('name')){
			case 'price':
				if(!reg_integer.test(th.val())){
					show_message ('Ошибка в значении!', 'error');
					return false;
				}
				break;
			case 'quan':
				if(!reg_integer.test(th.val())){
					show_message ('Ошибка в значении!', 'error');
					return false;
				}
				break;
		}
		$.ajax({
			type: 'post',
			url: '/admin/ajax/orders.php',
			data: {
				status_id: 'change_draft',
				name: th.attr('name'),
				value: th.val(),
				order_id: $('input[name=order_id]').val(),
				item_id: $(this).closest('tr').attr('item_id'),
				store_id: $(this).closest('tr').attr('store_id')
			},
			success: function(response){
				// console.log(response); return;
				setTotalSumm();
			}
		})
	})
	$('button').on('click', function(){
		var is_valid = true;
		$('tr[store_id][item_id]').each(function(){
			var th = $(this);
			if (!reg_integer.test(th.find('input[name=price]').val()) || !reg_integer.test(th.find('input[name=quan]').val())){
				is_valid = false; 
				return false;
			};
		})
		if (!is_valid) return show_message('Произошла ошибка!', 'error');
		$.ajax({
			type: 'post',
			url: '/admin/ajax/orders.php',
			data: {
				'status_id': 'to_order',
				'order_id': $('input[name=order_id]').val()
			},
			success: function(response){
				document.location.href = '/admin/?view=orders&id=' + $('input[name=order_id]').val() + '&act=change';
				// console.log(response); return false;
			}
		})
	})
})
