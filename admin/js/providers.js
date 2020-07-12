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
	workSchedule: '',
	noReturn: 0
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
			store.prevail = +store.prevail ? 'checked' : '';
			store.noReturn = +store.noReturn ? 'checked' : '';
			store.is_main = +store.is_main ? 'checked' : '';
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
		'<a target="" href="?view=providers&act=priceEmail&store_id=' + store.id + '">Прайс с Email</a>';
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
				 	'<td>График выходных</td>' +
				 	'<td>' +
				 		get_str_work_schedule(store.workSchedule) +
				 	'</td>' +
			 	'</tr>' +
			 	'<tr>' +
				 	'<td>Основной склад</td>' +
				 	'<td><input type="checkbox" name="is_main" ' + store.is_main + ' value="1"></td>' +
			 	'</tr>' +
			 	'<tr>' +
				 	'<td>Подсвечивать</td>' +
				 	'<td><input type="checkbox" name="prevail" ' + store.prevail + ' value="1"></td>' +
			 	'</tr>' +
			 	'<tr>' +
				 	'<td>Без возврата</td>' +
				 	'<td><input type="checkbox" name="noReturn" ' + store.noReturn + ' value="1"></td>' +
			 	'</tr>' +
			 	'<tr>' + form_bottom + '</tr>' +
	 		'</table>' +
		'</form>';
	return str;
}
function get_str_work_schedule(jsonWorkSchedule){
	let workSchedule = jsonWorkSchedule ? JSON.parse(jsonWorkSchedule) : false;
	checked1 = workSchedule && workSchedule[1] ? 'checked' : '';
	checked2 = workSchedule && workSchedule[2] ? 'checked' : '';
	checked3 = workSchedule && workSchedule[3] ? 'checked' : '';
	checked4 = workSchedule && workSchedule[4] ? 'checked' : '';
	checked5 = workSchedule && workSchedule[5] ? 'checked' : '';
	checked6 = workSchedule && workSchedule[6] ? 'checked' : '';
	checked7 = workSchedule && workSchedule[7] ? 'checked' : '';
	output = 
		'<label class="workSchedule">' + 
			'<span>Пн</span>' +
			'<input ' + checked1 + ' value="1" name="workSchedule[1]" type="checkbox">' +
		'</label>' +
		'<label class="workSchedule">' + 
			'<span>Вт</span>' +
			'<input ' + checked2 + ' value="1" name="workSchedule[2]" type="checkbox">' +
		'</label>' +
		'<label class="workSchedule">' + 
			'<span>Ср</span>' +
			'<input ' + checked3 + ' value="1" name="workSchedule[3]" type="checkbox">' +
		'</label>' +
		'<label class="workSchedule">' + 
			'<span>Чт</span>' +
			'<input ' + checked4 + ' value="1" name="workSchedule[4]" type="checkbox">' +
		'</label>' +
		'<label class="workSchedule">' + 
			'<span>Пт</span>' +
			'<input ' + checked5 + ' value="1" name="workSchedule[5]" type="checkbox">' +
		'</label>' +
		'<label class="workSchedule">' + 
			'<span>Сб</span>' +
			'<input ' + checked6 + ' value="1" name="workSchedule[6]" type="checkbox">' +
		'</label>' +
		'<label class="workSchedule">' + 
			'<span>Вс</span>' +
			'<input ' + checked7 + ' value="1" name="workSchedule[7]" type="checkbox">' +
		'</label>';
	return output;
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
		under_order: '',
		prevail: ''
	}
}
$(function(){
	// console.log(store);
	$(document).on('click','#load_price', function(e){
		e.preventDefault();
		$(this).next('form').toggleClass('hidden');
	})
	$('.providers_box').on('click', function(e){
		if (e.target.className == 'provider_change') document.location.href = e.target.attributes[1].nodeValue;
		else document.location.href = $(this).attr('href');
	})
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
						$('tr[store_id=' + store.id + '] td:nth-child(1)').html(th.find('input[name=title]').val());
						$('tr[store_id=' + store.id + '] td:nth-child(2)').html(th.find('input[name=cipher]').val().toUpperCase());
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