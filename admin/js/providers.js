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
	workSchedule: '',
	noReturn: 0,
    block: 0
};
var reg_interger = /^\d+$/;
function set_store(store_id){
	var array = new Array();
	$.ajax({
		type: 'post',
		async: false,
		url: '/admin/ajax/providers.php',
		data: 'act=get_store&store_id=' + store_id,
		success: function(response){
			store = JSON.parse(response);
			store.noReturn = +store.noReturn ? 'checked' : '';
			store.is_main = +store.is_main ? 'checked' : '';
			store.block = +store.block ? 'checked' : '';
		}
	})
}
function get_str_currencies(currency_id = false){
	currencies = get_currencies();
	var str = '<select name="currency_id">';
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
	if (!store.id) form_bottom = '<td colspan="2"><input type="submit" value="Сохранить" /></td>';
	else{
		form_bottom = 
			'<td><a href="" class="store_delete">Удалить</td>' +
			'<td><input type="submit" value="Сохранить"></td>';
		str += 
		'<a id="load_price" href="">Загрузить прайс</a>' +
		'<form class="hidden" method="post" enctype="multipart/form-data">' +
			'<input type="hidden" name="store_id" value="' + store.id + '">' + 
			'<input type="file" name="items"><br>' + 
			'<label for="parse_1"><input type="radio" id="parse_1" name="parse" value="full" checked> Полностью </label>' +
			'<label for="parse_2"><input type="radio" id="parse_2" name="parse" value="particulary"> Частично </label><br>' +
			'<input type="submit" value="Загрузить">' +
		'</form>' +
		'<a href="?view=prices&act=items&id=' + store.id + '">Прайс склада</a>' +
		'<a target="" href="?view=providers&act=priceEmail&store_id=' + store.id + '">Прайс с Email</a>' +
		'<a target="_blank" href="?view=providers&act=calendar&store_id=' + store.id + '">График поставок</a>';
	}; 
	str +=
		'<form name="store_change">' +
			'<input type="hidden" name="store_id" value="' + store.id + '">' +
			'<table>' +
			 	'<tr>' +
				 	'<td>Название:</td>' +
				 	'<td><input type="text" name="title" value="' +  store.title + '"></td>' +
			 	'</tr>' +
			 		'<tr>' +
				 	'<td>Город:</td>' +
				 	'<td><input type="text" name="city" value="' +  store.city + '"></td>' +
			 	'</tr>' +
			 	'<tr>' +
				 	'<td>Шифр:</td>' +
				 	'<td><input type="text" name="cipher" value="' +  store.cipher + '"></td>' +
			 	'</tr>' +
			 	'<tr>' +
				 	'<td>Валюта</td>' +
				 	'<td>' + get_str_currencies(store.currency_id) + '</td>' +
			 	'</tr>' +
			 	'<tr>' +
				 	'<td>Процент надбавки</td>' +
				 	'<td><input type="text" name="percent" value="' + store.percent + '" /></td>' +
			 	'</tr>' +
		 		'<tr>' +
				 	'<td>Срок доставки</td>' +
				 	'<td><input type="text" name="delivery" value="' + store.delivery + '" /></td>' +
			 	'</tr>' +
			 	'<tr>' +
				 	'<td>Максимальный срок</td>' +
				 	'<td><input type="text" name="delivery_max" value="' + store.delivery_max + '" /></td>' +
			 	'</tr>' +
			 	'<tr>' +
				 	'<td>Под заказ</td>' +
				 	'<td><input type="text" name="under_order" value="' + store.under_order + '" /></td>' +
			 	'</tr>' +
			 	'<tr>' +
				 	'<td>Количество дней возврата</td>' +
				 	'<td><input type="text" name="daysForReturn" value="' + store.daysForReturn + '" /></td>' +
			 	'</tr>' +
			 	'<tr>' +
				 	'<td>Основной склад</td>' +
				 	'<td><input type="checkbox" name="is_main" ' + store.is_main + ' value="1"></td>' +
			 	'</tr>' +
			 	'<tr>' +
				 	'<td>Без возврата</td>' +
				 	'<td><input type="checkbox" name="noReturn" ' + store.noReturn + ' value="1"></td>' +
			 	'</tr>' +
                '<tr>' +
                    '<td>Заблокировать</td>' +
                    '<td><input type="checkbox" name="block" ' + store.block + ' value="1"></td>' +
                '</tr>' +
			 	'<tr>' + form_bottom + '</tr>' +
	 		'</table>' +
		'</form>';
	return str;
}
function set_empty_store(){
	store = {
		id: '',
		title: '',
		city: '',
		cipher: '',
		currency_id: '',
		percent: '0.00',
		provider_id: '',
		delivery: '',
		delivery_max: '',
		under_order: ''
	}
}
$(function(){
	$(document).on('click', '[type=checkbox][name*=isWorkDay]', function(){
		let th = $(this);
		if (!th.is(':checked')){
			th.closest('tr').find('option').prop('selected', false);
			th.closest('tr').find('select').prop('disabled', true);
		}
		else{
			th.closest('tr').find('select').prop('disabled', false);
		}
	})
	$('#workSchedule').on('submit', function(e){
		let th = $(this);
		let everythingIsEmpty = true;
		th.find('select').prop('disabled', false);
		th.find('input[type=checkbox]').each(function(){
			if ($(this).is(':checked')) everythingIsEmpty = false;
		})
		if (everythingIsEmpty){
			e.preventDefault();
			th.find('select').prop('disabled', true);
			show_message('Не могут все дни быть выходными!', 'error');
		}
	})
	$(document).on('click','#load_price', function(e){
		e.preventDefault();
		$(this).next('form').toggleClass('hidden');
	})
	$('.providers_box').on('click', function(e){
		if (e.target.className == 'provider_change') document.location.href = e.target.attributes[1].nodeValue;
		else document.location.href = $(this).attr('href');
	})
	if (location.hash){
		let hash = location.hash;
		let store_id = hash.replace('#', '');
		set_store(store_id);
		modal_show(get_str_form());
	}
	$(document).on('click', 'tr.store:not(.removable)', function(e){
		var store_id = $(this).attr('store_id');
		set_store(store_id);
		modal_show(get_str_form());
	})
	$('#store_add').on('click', function(e){
		e.preventDefault();
		modal_show(get_str_form());
	})
	$(document).on('submit', 'form[name=store_change]', function(e){
		e.preventDefault();
		var th = $(this);
		if (!th.find('input[name=title]').val()){
			show_message('Название не должно быть пустым!', 'error');
			return false;
		}
		if (!th.find('input[name=cipher]').val()){
			show_message('Шифр не должен быть пустым!', 'error');
			return false;
		}
		$.ajax({
			type: 'post',
			url: '/admin/ajax/providers.php',
			data: '&act=store_change&provider_id=' + $('input[name=provider_id]').val() + '&' + th.serialize(),
			success: function(response){
				// console.log(response); return false;
				if (reg_interger.test(response)){
					if (response == 1){
					    let now = new Date();
						$('tr[store_id=' + store.id + '] td:nth-child(1)').html(th.find('input[name=title]').val());
						$('tr[store_id=' + store.id + '] td:nth-child(2)').html(now.toLocaleDateString() + ' ' + now.toLocaleTimeString());
						$('tr[store_id=' + store.id + '] td:nth-child(3)').html(th.find('input[name=cipher]').val().toUpperCase());
						show_message('Успешно изменено!', 'ok');
					}
					else{
						$('tr.store:last-child').after(
							'<tr class="store" store_id="' + response + '">' +
								'<td>' + th.find('input[name=title]').val() + '</td>' +
								'<td>' + th.find('input[name=cipher]').val().toUpperCase() + '</td>' +
							'</tr>'
						);
						$('tr.removable').remove();
						show_message('Успешно добавлено!');
					} 
					$('#modal-container').removeClass('active');
					set_empty_store();
				}
				else show_message(response, 'error');
			}
		})
	})
	$(document).on('click', 'a.store_delete', function(e){
		e.preventDefault();
		if (!confirm('Вы действительно хотите удалить?')) return false;
		var store_id = $(this).closest('form').find('input[name=store_id]').val();
		$.ajax({
			type: 'post',
			url: '/admin/ajax/providers.php',
			data: 'act=store_delete&id=' + store_id,
			success: function(response){
				// console.log(response); return false;
				$('#modal-container').removeClass('active');
				$('tr[store_id=' + store_id + ']').remove();
				show_message('Успешно удалено!', 'ok');
				set_empty_store();
			}
		})
	})
	$('#modal_close').on('click', function(){
		$('#modal-container').removeClass('active');
		set_empty_store();
	})
	$('#modal-container').on('click', function(event){
		var t = $('#modal-container');
		if (t.is(event.target)){
			t.removeClass('active');
			set_empty_store();
		} 
	})
	$('input[type=file][name=items]').on('change', function(){
		$('input[type=submit]').prop('disabled', false);
	})
})