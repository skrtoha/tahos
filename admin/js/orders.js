var ajax_url = '/admin/ajax/orders.php';
var reg_integer = /^\d+$/;
var is_reload = true;
function first_option(obj){
	obj.find('option').prop('selected', false);
	obj.find('option:first-child').prop('selected', true);
}
function set_items_to_order(){
    alert();
    $.ajax({
        type: 'post',
        url: '/admin/ajax/orders.php',
        data: {
            status_id: 'getItemsToOrder'
        },
        success: function (response){
            $('#itemsToOrder').html(response)
        }
    })
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
			if (is_reload) document.location.reload();
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
			if (is_reload) document.location.reload();
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
    set_items_to_order();
	$('#changeStatus select[name=status_id]').on('change', function(){
		$(this).closest('form').submit();
	})
	$('a.show_stringLog').on('click', function(e){
		e.preventDefault();
		$(this).next().toggleClass('active');
	});
	$(document).on('submit', 'form[name=store_change]', function(e){
		return false;
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
				if (is_reload) document.location.reload();
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
				data += '&arrived=' + th.find('input[name=arrived]').val();
				break;
			//выделено в отдельную функцию
			case 2:break;
			case 3:
				ordered = + th.find('input[name=ordered]').val();
				if (ordered == 1) arrived = ordered;
				else arrived = prompt('Укажите количество:', ordered);
				if (!check_value(th, arrived, ordered)) return false;
				data += '&arrived=' + arrived;
				break;
			case 8:
				ordered = + th.find('input[name=ordered]').val();
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
				if (is_reload) document.location.reload();
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
	$('a.editOrderValue').on('click', function(e){
		e.preventDefault();
		var th = $(this);
		$.ajax({
			type: 'post',
			url: '/admin/ajax/orders.php',
			data: {
				status_id: 'getOrderValue',
				osi: th.attr('osi')
			},
			success: function(response){
				let ov = JSON.parse(response);
				ov.comment = ov.comment ? ov.comment : '';
				modal_show(
					'<form class="editOrderValue">' +
						'<input type="hidden" name="status_id" value="editOrderValue">' +
						'<input type="hidden" name="osi" value="' + th.attr('osi') + '">' +
						'<table>' +
							'<tr>' +
								'<td>Цена:</td>' +
								'<td><input type="text" name="price" value="' + ov.price + '"></td>' +
							'</tr>' +
							'<tr>' +
								'<td>Количество:</td>' +
								'<td><input type="text" name="quan" value="' + ov.quan + '"></td>' +
							'</tr>' +
							'<tr>' +
								'<td>Комментарий:</td>' +
								'<td><input type="text" name="comment" value="' + ov.comment + '"></td>' +
							'</tr>' +
							'<tr>' +
								'<td colspan="2"><input type="submit"  value="Сохранить"></td>' +
							'</tr>' +
						'</table>' +
					'</form>'
				);
				$(document).on('submit', 'form.editOrderValue', function(e){
					e.preventDefault();
					let form = $(this);
					$.ajax({
						type: 'post',
						url: '/admin/ajax/orders.php',
						data: form.serialize(),
						success: function(response){
							ov = JSON.parse(response);
							let tr = th.closest('tr').next();
							tr.find('[label=Кол-во]').html('Заказ - ' + ov.quan + ' шт.');
							tr.find('[label=Цена]').html(ov.price);
							tr.find('[label=Сумма]').html(ov.price * ov.quan);
							tr.find('[label=Комментарий]').html(ov.comment);
							$('#modal-container').removeClass('active');
						}
					})
				})
			}
		})
	})
})
