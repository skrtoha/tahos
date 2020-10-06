/**
 * для подключения необходимо создать ссылку с классом store c аттрибутом store_id
 * в файле main.php добавить вид в массив
 * <?if (in_array($view, ['items', 'orders', 'returns'])){?>
		{"src" : "/admin/js/show_store_info.js", "async" : false},
	<?}?>
	в родитель добавить класс storeInfo
 */
(function($){
	window['show_store_info'] = {
		store: {
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
			daysForReturn: '',
			prevail: '',
			noReturn: 0
		},
		get_str_currencies: function (currency_id = false){
			let currencies = get_currencies();
			var str = '<select disabled name="currency_id">';
			for(var key in currencies) {
				var c = currencies[key];
				var selected = c.id == currency_id ? 'selected' : '';
				str += '<option ' + selected + ' value="' + c.id + '">' + c.title + '</option>';
			}
			str += '</select>';
			return str;
		},
		set_store: function (store_id){
			$.ajax({
				type: 'post',
				url: '/admin/ajax/providers.php',
				data: 'act=get_store&store_id=' + store_id,
				async: false,
				beforeSend: function(){
					showGif();
				},
				success: function(response){
					show_store_info.store = JSON.parse(response);
					show_store_info.store.prevail = + show_store_info.store.prevail ? 'checked' : '';
					show_store_info.store.noReturn = + show_store_info.store.noReturn  ? 'checked' : '';
					showGif(false);
				}
			})
		},
		get_str_form: function (){
			let store = show_store_info.store;
			var form_bottom;
			var str = '';
			str +=
				'<form name="store_change">' +
					'<input type="hidden" name="store_id" value="' + store.id + '">' +
					'<table>' +
						'<tr>' +
						 	'<td>Поставщик:</td>' +
						 	'<td><input disabled type="text" name="provider" value="' +  store.provider + '"></td>' +
					 	'</tr>' +
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
						 	'<td>' + show_store_info.get_str_currencies(store.currency_id) + '</td>' +
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
						 	'<td>Срок возврата</td>' +
						 	'<td><input disabled type="text" name="daysForReturn" value="' + store.daysForReturn + '" /></td>' +
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
		},
		init: function(){
			$('.storeInfo').on('click', 'a.store', function(e){
				e.preventDefault();
				var store_id = $(this).attr('store_id');
				show_store_info.set_store(store_id);
				modal_show(show_store_info.get_str_form());
			})
		}
	}
})(jQuery)
$(function(){
	show_store_info.init();
})