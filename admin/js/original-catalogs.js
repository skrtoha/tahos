function get_vehicle_categories(category = '', arr = false){
	var categories;
	var selected = '';
	var str = '<select name="category">';
	$.ajax({
		method: 'post',
		url: '/ajax/original-catalogs.php',
		async: false,
		data: 'act=get_vehicle_categories',
		success: function(res){
			categories = JSON.parse(res);
			// console.log(categories);
		}
	});
	if (arr) return categories;
	for (var key in categories){
		var c = categories[key];
		if (category && c.title == category) selected = 'selected'; 
		else selected = '';
		str += '<option ' + selected +  ' value="' + c.id + '">' + c.title + '</option>';
	} 
	str += '</select>';
	return str;
}
function get_brends(vehicle_id, brend_id = ''){
	var brends;
	var selected = '';
	// console.log(vehicle_id);
	var str = '<select name="brend">';
	$.ajax({
		method: 'post',
		url: '/ajax/original-catalogs.php',
		async: false,
		data: 'act=get_brends&vehicle_id=' + vehicle_id,
		success: function(res){
			// console.log(res); return;
			brends = JSON.parse(res);
		}
	});
	for (var k in brends){
		var b = brends[k];
		if (brend_id && b.id == brend_id) selected = 'selected'; 
		else selected = '';
		str += '<option ' + selected +  ' value="' + b.id + '">' + b.title + '</option>';
	} 
	str += '</select>';
	return str;
}
function get_from_uri(){
	var str = document.location.href;
	str = str.replace(/http\:\/\//g, '');
	str = str.replace(/\&/g, '|');
	str = str.replace(/\//g, '\\');
	str = str.replace(/\?/g, '^');
	return '\\' + str;
}
$(function(){
		//Оригинальные каталоги
	var filters;
	$(document).on('click', 'tr.clickable', function(event){
		var t = $('.not_clickable');
		if (t.is(event.target)) return false;
		document.location.href = $(this).find('td:first-child > a').attr('href');
	})
	$(document).on('keyup', function(e){
		// console.log(e);
		if (e.keyCode != 27) return false;
		$('#modal-container').removeClass('active');
	})
	$('a.vehicle_add').on('click', function(){
		// categories = get_vehicle_categories();
		// console.log(categories);
		modal_show(
			'<form id="vehicle_form">' + 
				'<table>' +
				 	'<tr>' +
					 	'<td>Введите название траспортного средства:</td>' +
					 	'<td><input type="text" name="vehicle"></td>' +
				 	'</tr>' +
				 	'<tr>' +
					 	'<td>Выберите категорию:</td>' +
					 	'<td>' + get_vehicle_categories() + '</td>' +
				 	'</tr>' +
			 		'<tr>' +
					 	'<td>Позиция:</td>' +
					 	'<td><input type="text" name="pos" value="0" placeholder="0"></td>' +
				 	'</tr>' +
			 		'<tr>' +
					 	'<td>Отображать плиткой:</td>' +
					 	'<td>' +
					 		'<select name="is_mosaic">' +
						 		'<option value="1">да</option>' +
						 		'<option selected value="0">нет</option>' +
					 		'</select>' +
					 	'</td>' +
				 	'</tr>' +
					'<tr><td colspan="2"><input class="vehicle_add" type="submit" value="Добавить"></td></tr>' +
				'</table>' +
			'</form>'
		);
	})
	$(document).on('click', 'a.model_image_add', function(e){
		e.preventDefault();
		$('#model_image input[name=model_id]').val($(this).attr('model_id'));
		$('#model_image input[type=file]').click();
	})
	$(document).on('change', '#model_image input[type=file]', function(){
		$('#model_image').ajaxForm({
			target: 'div.model_image',
			beforeSubmit: function(e){},
			success:function(msg){
				var model_id = $('#model_image input[name=model_id]').val();
				$('tr[model_id=' + model_id + '] input[name=model_image_exists]').val(1);
			},
			error:function(e){}
		}).submit();	
	})
	$(document).on('click', 'input.vehicle_add', function(e){
		e.preventDefault();
		if (!$('input[name=vehicle]').val()) return show_message('Введите название траспортного средства!', 'error');
		$.ajax({
			method: 'post', 
			url: '/ajax/original-catalogs.php',
			data: 
				'title=' + $('input[name=vehicle]').val() + 
				'&act=vehicle_add' + 
				'&category_id=' + $('select[name=category]').val() +
				'&pos=' + $('input[name=pos]').val() +
				'&is_mosaic=' + $('select[name=is_mosaic]').val(),
			success: function(res){
				// console.log(res);
				var vehicle = JSON.parse(res);
				$('tr.removable').remove();
				$('table.vehicles').append(
					'<tr class="vehicle clickable" vehicle_id="' + vehicle.id + '">' +
						'<td>' + 
							'<a href="?view=original-catalogs&act=vehicle_brends&vehicle_id=' + vehicle.id + '">' + 
								vehicle.title + 
							'</a>' + 
						'</td>' +
						'<td>' + vehicle.pos + '</td>' +
						'<td>' + vehicle.is_mosaic + '</td>' +
						'<td>' + vehicle.category + '</td>' +
						'<td>' +
							'<a href="#" class="vehicle_change not_clickable">Изменить</a> ' +
							'<a href="#" class="vehicle_remove not_clickable">Удалить</a>' +
						'</td>' +
					'</tr>'
				);
				$('#modal-container').removeClass('active');
				$('#total span').html(+$('#total span').html() + 1);
			}
		})
	})
	$(document).on('click', 'a.vehicle_remove', function(e){
		e.preventDefault();
		var elem = $(this).closest('tr');
		if (confirm('Вы действительно хотите удалить?')){
			$.ajax({
				method: 'post',
				url: '/ajax/original-catalogs.php',
				data: 'act=vehicle_remove&id=' + elem.attr('vehicle_id'),
				success: function(){
					show_message('Успешно удалено!');
					elem.remove();
					$('#total span').html(+$('#total span').html() -1);
				}
			})
		}
	})
	$(document).on('click', 'a.vehicle_change', function(e){
		e.preventDefault();
		var elem = $(this).closest('tr');
		var mosaic_str = '<select name="is_mosaic">';
		var mosaic_selected =  elem.find('td:nth-child(3)').html() == 'да' ? 'selected' : '';
		mosaic_str += '<option ' + mosaic_selected + ' value="1">да</option>';
		var mosaic_selected =  elem.find('td:nth-child(3)').html() == 'нет' ? 'selected' : '';
		mosaic_str += '<option ' + mosaic_selected + ' value="0">нет</option>';
		mosaic_str += '</select>';
		var vehicle_image = '<div class="vehicle_image">';
		if (parseInt(elem.find('input[name=is_image]').val())) vehicle_image +=
			'<img src="/images/vehicles/' + elem.attr('vehicle_id') + '.jpg">' +
			'<a vehicle_id="' + elem.attr('vehicle_id') + '" href="#" class="vehicle_image_delete">Удалить</a>';
		else vehicle_image +=
			'<a class="vehicle_image_add" vehicle_id="' + elem.attr('vehicle_id') + '">Добавить изображение</a>';
		vehicle_image += '</div>';
		modal_show(
			vehicle_image +
			'<form id="vehicle_form">' + 
				'<table>' +
				 	'<tr>' +
					 	'<td>Введите название траспортного средства:</td>' +
					 	'<td><input type="text" name="vehicle" value="' + elem.find('td:nth-child(1) a').html().trim() + '"></td>' +
				 	'</tr>' +
			 		'<tr>' +
					 	'<td>Выберите категорию:</td>' +
					 	'<td>' + get_vehicle_categories(elem.find('td:nth-child(4)').html().trim()) + '</td>' +
				 	'</tr>' +
				 	'<tr>' +
					 	'<td>Позиция:</td>' +
					 	'<td><input type="text" name="pos" value="' + elem.find('td:nth-child(2)').html().trim() + '"></td>' +
				 	'</tr>' +
			 		'<tr>' +
					 	'<td>Отображать плиткой:</td>' +
					 	'<td>' + mosaic_str + '</td>' +
				 	'</tr>' +
					'<tr>' +
						'<td colspan="2">' + 
							'<input vehicle_id="' + elem.attr('vehicle_id') + '" class="vehicle_change" type="submit" value="Изменить">' + 
						'</td>' +
					'</tr>' +
				'</table>' +
			'</form>'
		);
	})
	$(document).on('click', 'input.vehicle_change', function(e){
		e.preventDefault();
		var id = $(this).attr('vehicle_id');
		var title = $('input[name=vehicle]').val();
		var category_id = $('select[name=category]').val();
		var pos = $('input[name=pos]').val();
		var is_mosaic = $('select[name=is_mosaic]').val();
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			data: 
				'act=vehicle_change&id=' + id + '&title=' + title + 
				'&category_id=' + category_id + '&pos=' + pos + '&is_mosaic=' + is_mosaic,
			success: function(res){
				console.log(res);
				$('#modal-container').removeClass('active');
				$('table.vehicles tr[vehicle_id=' + id + '] td:first-child').html(
					'<a href="?view=original-catalogs&act=vehicle_brends&vehicle_id=' + id + '">' + title + '</a>'
				);
				$('table.vehicles tr[vehicle_id=' + id + '] td:nth-child(2)').html(pos);
				$('table.vehicles tr[vehicle_id=' + id + '] td:nth-child(4)').html(
					$('select[name=category] option:selected').html()
				);
				$('table.vehicles tr[vehicle_id=' + id + '] td:nth-child(3)').html(
					$('select[name=is_mosaic] option:selected').html()
				);
			}
		})
	})
	$(document).on('click', 'a.vehicle_image_delete', function(){
		if (!confirm('Вы действительно хотите удалить?')) return false;
		var th = $(this);
		var vehicle_id = th.attr('vehicle_id');
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			data: 'act=vehicle_image_delete&path=' + th.prev('img').attr('src'),
			success: function(res){
				console.log(res);
				th.closest('div.vehicle_image').html(
					'<a class="vehicle_image_add" vehicle_id="' + vehicle_id + '">Добавить изображение</a>'
				)
				$('tr[vehicle_id=' + vehicle_id + '] input[name=is_image]').val('');
			}
		})
	})
	$(document).on('click', 'a.vehicle_image_add', function(e){
		console.log();
		$('form.vehicle_image input[type=text]').val($(this).attr('vehicle_id'));
		$('form.vehicle_image input[type=file]').click();
	})
	$('form.vehicle_image input[type=file]').on('change', function(){
		$('form.vehicle_image').ajaxForm({
			target: 'div.vehicle_image',
			beforeSubmit: function(e){},
			success:function(msg){
				var vehicle_id = $('form.vehicle_image input[name=vehicle_id]').val();
				$('tr[vehicle_id=' + vehicle_id + '] input[name=is_image]').val(1);
			},
			error:function(e){}
		}).submit();	
	})
	$('a.brend_add').on('click', function(e){
		e.preventDefault();
		modal_show(
			'<form id="brend_form">' + 
				'<table>' +
				 	'<tr>' +
					 	'<td>Выберите бренд:</td>' +
					 	'<td>' + get_brends($('table.brends').attr('vehicle_id')) + '</td>' +
				 	'</tr>' +
					'<tr>' +
						'<td colspan="2">' + 
							'<input class="brend_add" type="submit" value="Добавить">' + 
						'</td>' +
					'</tr>' +
				'</table>' +
			'</form>'
		);
	})
	$(document).on('click', 'input.brend_add', function(e){
		e.preventDefault();
		var vehicle_id = $('table.brends').attr('vehicle_id');
		var brend_id = $('select[name=brend]').val();
		if (!brend_id) return show_message('Выберите бренд!', 'error');
		$.ajax({
			method: 'post', 
			url: '/ajax/original-catalogs.php',
			data: 
				'act=brend_add&brend_id=' + brend_id + '&vehicle_id=' + vehicle_id,
			success: function(res){
				// console.log(res);
				var brend = JSON.parse(res);
				$('table.brends').append(
					'<tr class="brend clickable" brend_id="' + brend.id + '">' +
						'<td>' +
							'<a href="' + document.location.href + '&brend_id=' + brend.id + '">' + brend.title + '</a></td>' +
						'<td>' +
							'<a href="/admin/?view=brends&id=' + brend.id + '&act=change&from=' + get_from_uri() + '">Изменить</a> ' +
							'<a href="#" class="brend_remove">Удалить</a>' +
						'</td>' +
					'</tr>'
				);
				$('tr.removable').remove();
				$('#total span').html(+$('#total span').html() + 1);
				$('#modal-container').removeClass('active');
			}
		})
	})
	$(document).on('click', 'a.brend_remove', function(e){
		e.preventDefault();
		var elem = $(this).closest('tr');
		if (confirm('Вы действительно хотите удалить?')){
			$.ajax({
				method: 'post',
				url: '/ajax/original-catalogs.php',
				data: 'act=brend_remove&brend_id=' + elem.attr('brend_id') + '&vehicle_id=' + $('table.brends').attr('vehicle_id'),
				success: function(msg){
					// console.log(msg);
					show_message('Успешно удалено!');
					elem.remove();
					if ($('table.brends tr').size() == 1) $('table.brends').append(
						'<tr class="removable">' +
							'<td colspan="2">Брендов не найдено</td>' +
						'</tr>'
					);
					$('#total span').html($('#total span').html() - 1);
				}
			})
		}
	})
	$('a.model_add').on('click', function(e){
		e.preventDefault();
		modal_show(
			'<form id="model_form">' + 
				'<table>' +
				 	'<tr>' +
					 	'<td>Название модели:</td>' +
					 	'<td><input type="text" name="title"></td>' +
				 	'</tr>' +
			 		'<tr>' +
					 	'<td>VIN:</td>' +
					 	'<td><input type="text" name="vin"></td>' +
				 	'</tr>' +
				 	'<tr>' +
					 	'<td>Алиас:</td>' +
					 	'<td><input type="text" name="href"></td>' +
				 	'</tr>' +
					'<tr><td colspan="2"><input class="model_add" type="submit" value="Добавить"></td></tr>' +
				'</table>' +
			'</form>'
		);
	})
	$('a.filters').on('click', function(e){
		e.preventDefault();
		var vehicle_id = $('table.models').attr('vehicle_id');
		var brend_id = $('table.models').attr('brend_id');
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			async: false,
			data: 'act=get_filters&vehicle_id=' + vehicle_id+ '&brend_id=' + brend_id,
			success: function(res){
				// console.log(res);
				if (res) filters = JSON.parse(res);
				// console.log(filters);
			}
		});
		var str =
			'<a vehicle_id="' + vehicle_id + '" brend_id="' + brend_id + '" href="#" class="filter_add">Добавить</a>' +
			'<div class="div_filters">';
		if (Object.keys(filters).length) for (var key in filters) str += 
				'<a href="#" class="filter" filter_id="' + key+ '">' + filters[key].title + '</a>';
		else str += 
				'<span>Фильтров не найдено</span>';
		str +=
			'</div>';
		modal_show(str);
		// console.log(filters);
	})
	$(document).on('click', 'a.filter_add', function(e){
		e.preventDefault();
		var title = prompt('Введите название фильтра:');
		if (!title) return false;
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			data: 
				'&act=filter_add&vehicle_id=' + $(this).attr('vehicle_id') + 
				'&brend_id=' + $(this).attr('brend_id') +
				'&title=' + title,
			success: function(res){
				if (!parseInt(res)) return show_message (res, 'error');
				if (filters) filters[res] = {
					title: title
				}
				$('.div_filters').append(
					'<a href="#" class="filter" filter_id="' + res + '">' + title + '</a>'
				);
			}
		})
	})
	$(document).on('click', '.div_filters a.filter', function(e){
		e.preventDefault();
		var filter_id = $(this).attr('filter_id');
		var str = 
			'<p><b>' + filters[filter_id].title + '</b></p>' +
			'<a href="#" class="filter_change" filter_id="' + filter_id + '">Изменить</a>' +
			'<a href="#" class="filter_value_add" filter_id="' + filter_id + '">Добавить значение</a>' +
			'<div class="filter_values">';
		var fv = filters[filter_id].filter_values;
		// console.log(fv);
		if (fv) for(var key in fv) str +=
				'<a class="filter_value" fv_id="' + key + '">' + fv[key] + '</a>';
		else str+=
				'<span>Значений фильтров не задано</span>';
		modal_show(str);
	})
	$(document).on('click', 'a.filter_change', function(e){
		e.preventDefault();
		var filter_id = $(this).attr('filter_id');
		var title = prompt('Введите новое значение (пусто - удалить):', filters[filter_id].title);
		if (title === null) return false;
		if (!title) if (!confirm('Вы действительно хотите удалить?')) return false;
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			data: '&act=filter_change&filter_id=' + filter_id + '&title=' + title,
			success: function(msg){
				// console.log(msg);
				// return;
				if (msg == 'not') return show_message('Данный фильтр является единственным, его нельзя удалять!', 'error');
				if (title){
					$('#modal_content > p > b').html(title);
					show_message('Успешно изменено!');
				} 
				else{
					show_message('Фильтр успешно удален!');
					$('#modal-container').removeClass('active');
				}
			}
		})
	})
	$(document).on('click', 'a.filter_value_add', function(e){
		var title = prompt('Введите новое значение:');
		if (title === null) return false;
		if (!title) return show_message('Значение задано неккоретно!', 'error');
		var filter_id = $(this).attr('filter_id');
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			data: 'act=filter_value_add&filter_id=' + filter_id + '&title=' + title,
			success: function(res){
				// console.log(filters);
				if (!parseInt(res)) return show_message(res, 'error');
				// console.log(filters[filter_id].filter_values);
				if (typeof filters[filter_id].filter_values == 'undefined') filters[filter_id].filter_values = {};
				filters[filter_id].filter_values[res] = title;
				$('#modal_content div.filter_values span').remove();
				$('#modal_content div.filter_values').append(
					'<a class="filter_value" fv_id="' + res + '">' + title + '</a>'
				)
			}
		})
	})
	$(document).on('click', '.filter_values a.filter_value', function(e){
		e.preventDefault();
		elem = $(this);
		var title = prompt('Введите новое значение (пусто - удалить):', elem.html());
		if (title === null) return false;
		if (!title) if (!confirm('Вы действительно хотите удалить?')) return false;
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			data: 'act=filter_value_change&fv_id=' + elem.attr('fv_id') + '&title=' + title,
			success: function(msg){
				if (!title){
					elem.remove();
					if (!$('.filter_values a').size()) $('.filter_values').html('Значений фильтров не задано');
					show_message('Успешно удалено!');
				} 
				else{
					elem.html(title);
					show_message('Успешно изменено');
				}
			}
		})
	})
	$(document).on('click', 'input.model_add', function(e){
		e.preventDefault();
		if (!$('input[name=title]').val()) return show_message('Введите название модели!', 'error');
		$.ajax({
			method: 'post', 
			url: '/ajax/original-catalogs.php',
			data: 
				'act=model_add&title=' + $('input[name=title]').val() + 
				'&vin=' + $('input[name=vin]').val() +
				'&href=' + $('input[name=href]').val() +
				'&brend_id=' + $('table.models').attr('brend_id') + 
				'&vehicle_id=' + $('table.models').attr('vehicle_id'),
			success: function(res){
				// console.log(res);
				var model = JSON.parse(res);
				$('tr.removable').remove();
				$('table.models').append(
					'<tr class="model" model_id="' + model.id + '">' +
						'<td>' +
							'<a href="' + document.location.href + '&model_id=' + model.id + '">' + model.title + '</a>' +
						'</td>' +
						'<td>' + model.vin + '</td>' +
						'<td>' +
							'<a class="model_change not_clickable" href="">Изменить</a> ' +
							'<a href="#" class="model_remove">Удалить</a>' +
						'</td>' +
					'</tr>'
				);
				$('#total span').html(+$('#total span').html() + 1);
				$('#modal-container').removeClass('active');
			}
		})
	})
	$(document).on('click', 'a.model_remove', function(e){
		e.preventDefault();
		var elem = $(this).closest('tr');
		if (confirm('Вы действительно хотите удалить?')){
			$.ajax({
				method: 'post',
				url: '/ajax/original-catalogs.php',
				data: 'act=model_remove&id=' + elem.attr('model_id'),
				success: function(msg){
					console.log(msg);
					show_message('Успешно удалено!');
					elem.remove();
				}
			})
		}
	})
	$(document).on('click', 'a.model_change', function(e){
		var elem = $(this).closest('tr');
		var model_id = elem.attr('model_id');
		var img = 
			'<div model_id="' + model_id + '" class="model_image">';
		if (parseInt(elem.find('input[name=model_image_exists]').val())){
			img += 
				'<img src="/images/models/' + model_id + '.jpg">' +
				'<a href="#" class="model_image_delete" model_id="' + model_id + '">Удалить</a>'
		}	
		else img +=
				'<a href="#" class="model_image_add" model_id="' + elem.attr('model_id') + '">Добавить изображение</a>'; 
		img += 
			'</div>';
		modal_show(
			img +
			'<form id="model_form">' + 
				'<table>' +
				 	'<tr>' +
					 	'<td>Название модели:</td>' +
					 	'<td><input type="text" name="title" value="' + elem.find('td:first-child > a').html() + '"></td>' +
				 	'</tr>' +
			 		'<tr>' +
					 	'<td>VIN:</td>' +
					 	'<td><input type="text" name="vin" value="' + elem.find('td:nth-child(2)').html() + '"></td>' +
				 	'</tr>' +
				 	'<tr>' +
					 	'<td>Алиас:</td>' +
					 	'<td><input type="text" name="href" value="' + elem.attr('href') + '"></td>' +
				 	'</tr>' +
					'<tr><td colspan="2"><input model_id="' + elem.attr('model_id') + '" class="model_change" type="submit" value="Изменить"></td></tr>' +
				'</table>' +
			'</form>'
		);
	})
	$(document).on('click', 'a.model_image_delete', function(e){
		if (!confirm('Вы действительно желаете удалить?')) return false;
		var elem = $(this).closest('div.model_image');
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			data: 'act=model_image_delete&model_id=' + elem.attr('model_id'),
			success: function(res){
				elem.html(
					'<a href="#" class="model_image_add" model_id="' + elem.attr('model_id') + '">Добавить изображение</a>'
				);
				$('tr[model_id=' + elem.attr('model_id') + '] input[name=model_image_exists]').val(0);
				show_message('Успешно удалено!');
			}
		})
	})
	$(document).on('click', 'input.model_change', function(e){
		e.preventDefault();
		var id = $(this).attr('model_id');
		var title = $('input[name=title]').val();
		var vin = $('input[name=vin]').val();
		var href = $('input[name=href]').val();
		vin = vin.toUpperCase();
		if (vin && vin.match(/^[\w\d]{9}$/gi) === null) return show_message('VIN введен неккоретно!', 'error');
		// if (title.match(/^[\wа-яА-Я\s]{3,}$/ig) === null) return show_message('Название введено неккоретно!', 'error');
		if (href.match(/^[\w-]$/)) return show_message('Неккоректный href!', 'error');
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			data: 'act=model_change&id=' + id + '&title=' + title + '&vin=' + vin + '&href=' + href,
			success: function(res){
				// console.log(res);
				$('#modal-container').removeClass('active');
				var v = $('table.models tr[model_id=' + id + '] td:nth-child(3)').html();
				$('table.models tr[model_id=' + id + ']').attr('href', href).html(
					'<td>' +
						'<a href="' + document.location.href + '&model_id=' + id + '">' + title + '</a>' +
					'</td>' +
					'<td>' + vin + '</td>' +
					'<td>' + v + '</td>'
				);
				show_message('Успешно изменено!');
			}
		})
	})
	$(document).on('click', 'a.modification_change', function(e){
		e.preventDefault();
		var modification_id = $(this).closest('tr').attr('modification_id');
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			data: 
				'act=modification_change&modification_id=' + modification_id +
				'&vehicle_id=' + $('table.modifications').attr('vehicle_id') +
				'&brend_id=' + $('table.modifications').attr('brend_id') +
				'&model_id=' + $('table.modifications').attr('model_id'),
			success: function(msg){
				var res = JSON.parse(msg);
				var m = res.modification;
				str =
					'<form id="modification_change" modification_id="' + modification_id + '">' + 
						'<table>' +
						 	'<tr>' +
							 	'<td>Название:</td>' +
							 	'<td><input type="text" name="title" value="' +  m.title + '"></td>' +
						 	'</tr>';
				for (var key in res.filters){
					var f = res.filters[key];
					str +=
							'<tr>' +
							 	'<td>' + f.title + ':</td>' +
							 	'<td>' +
							 		'<select class="filters" name="' + key + '">' +
								 		'<option value=""></option>';
					for (var k in f.filter_values){
						var selected;
						if (typeof m.filter_values != 'undefined') selected = k == m.filter_values[key] ? 'selected' : '';
						else selected = '';
						str +=
										'<option ' + selected + ' value="' + k + '">' + f.filter_values[k] + '</option>';
					} 
					str +=
									'</select>' +
							 	'</td>' +
						 	'</tr>';
				}
				str +=
						 	'<tr>' + 
						 		'<td colspan="2">' +
							 		'<a modification_id="' + modification_id + '" vehicle_id="' + $('table.modifications').attr('vehicle_id') + '"' + 
							 			' model_id="' + $('table.modifications').attr('model_id') + '"' + 
							 			' brend_id="' + $('table.modifications').attr('brend_id') + '" class="modification_to_other_model" href="">' + 
							 				'Перевести в другую модель' + 
					 				'</a>' +
							 		'<input class="modification_change" type="submit" value="Изменить">' + 
					 			'</td>' +
					 		'</tr>' +
				 		'</table>' +
			 		'</form>';
				modal_show(str);
			}
		})
	})
	$(document).on('click', 'input.modification_change', function(e){
		e.preventDefault();
		var elem = $('#modification_change');
		var modification_id = elem.attr('modification_id');
		var tr = $('table.modifications').find('tr[modification_id=' + modification_id + ']');
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			data: 
				$('#modification_change').serialize() + 
				'&act=modification_change' + 
				'&modification_id=' + modification_id,
			success: function(res){
				$.each(elem.find('select.filters'), function(){
					var e = $(this);
					tr.find('td[filter_id=' + e.attr('name') + ']').html(
						e.find('option:selected').text()
					);
				})
				tr.find('td:first-child > a').html(elem.find('input[name=title]').val());
				$('#modal-container').removeClass('active');
				show_message('Успешно изменено!');
			}
		})
	})
	$(document).on('click', 'a.modification_add', function(e){
		e.preventDefault();
		var filters;
		var t = $('table.modifications');
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			data: 'act=get_filters&vehicle_id=' + t.attr('vehicle_id') + '&brend_id=' + t.attr('brend_id'),
			async: false,
			success: function(res){
				// console.log(res);
				filters = JSON.parse(res);
			}
		})
		str =
			'<form id="modification_change">' + 
				'<table>' +
				 	'<tr>' +
					 	'<td>Название:</td>' +
					 	'<td><input type="text" name="title"></td>' +
				 	'</tr>';
		for (var key in filters){
			var f = filters[key];
			str +=
					'<tr>' +
					 	'<td>' + f.title + ':</td>' +
					 	'<td>' +
					 		'<select class="filters" name="' + key + '">' +
						 		'<option value=""></option>';
			for (var k in f.filter_values)str +=
								'<option value="' + k + '">' + f.filter_values[k] + '</option>';
			str +=
							'</select>' +
					 	'</td>' +
				 	'</tr>';
		}
		str +=
				 	'<tr>' + 
				 		'<td colspan="2">' +
					 		'<input class="modification_add" type="submit" value="Сохранить">' + 
			 			'</td>' +
			 		'</tr>' +
		 		'</table>' +
	 		'</form>';
	 	modal_show(str);
		// console.log(filters);
	})
	$(document).on('click', 'input.modification_add', function(e){
		e.preventDefault();
		$('tr.removable').remove();
		if (!$('#modification_change input[name=title]').val()){
			return show_message('Наличие названия обязательно!', 'error');
		} 
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			data: 
				$('#modification_change').serialize() + 
				'&model_id=' + $('table.modifications').attr('model_id') +
				'&act=modification_add',
			success: function(modification_id){
				console.log(modification_id);
				var t = $('#modification_change');
				var str = 
					'<tr class="modification clickable" modification_id="' + modification_id + '">' +
						'<td>' +
							'<a href="' + document.location.href + '&modification_id=' + modification_id + '">' +
								t.find('input[name=title]').val() +
							'</a>' +
						'</td>';
				var fvs = new Array();
				t.find('select.filters').each(function(){
					th = $(this);
					fvs[th.attr('name')] = th.find('option:selected').html();
				});
				$('table.modifications tr.head td').each(function(){
					var th = $(this);
					// console.log(th.attr('filter_id'));
					if (typeof th.attr('filter_id') !== 'undefined'){
						filter_id = th.attr('filter_id');
						str += 
						'<td filter_id="' + filter_id + '">' + fvs[filter_id] + '</td>'
					} 
				})
				// console.log(fvs);
				str +=
						'<td>' +
							'<a class="modification_change not_clickable">Изменить</a> ' +
							'<a href="#" class="modification_remove not_clickable">Удалить</a>' +
						'</td>' +
					'</tr>';
				$('table.modifications').append(str);
				$('#modal-container').removeClass('active');
				$('#total span').html(+$('#total span').html() + 1);
				show_message('Успешно добавлено!');
			}
		})
	})
	$(document).on('click', 'a.modification_remove', function(e){
		e.preventDefault();
		if (!confirm('Вы действительно хотите удалить?')) return false;
		var elem = $(this).closest('tr');
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			data: 'act=modification_remove&modification_id=' + elem.attr('modification_id'),
			success: function(res){
				elem.remove();
				var t = $('table.modifications');
				if (t.find('tr').size() == 1) t.append(
					'<tr class="removable">' +
						'<td colspan="' + t.find('tr.head td').size() + '">Модификаций не найдено</td>' +
					'</tr>'
				);
				$('#total span').html(+$('#total span').html() - 1);
			}
		})
	})
	$(".tree-structure").on('click', '.jstree-anchor', function (e) {
		$(this).jstree(true).toggle_node(e.target);
	}).jstree();
	$('.tree-structure').on("changed.jstree", function (e, data) {
		$('#parent_id').val(data.node.li_attr.node_id);
		$('#node_change').attr('node_id', data.node.li_attr.node_id);
		$('#node_change').attr('title', data.node.text.replace(/[^\wа-яА-Я0-9 ]/g, ''));
		if (!data.node.children.length) $('#node_forward').attr('href', data.node.a_attr.href);
		else $('#node_forward').attr('href', '');
	});
	$('#node_change').on('click', function(e){
		e.preventDefault();
		var node_id = $(this).attr('node_id');
		var title = prompt('Введите новое значение (пусто - удалить)', $(this).attr('title'));
		if (title === null) return false;
		if (!title){
			if (!confirm('Вы действительно хотите удалить?')) return false;
		} 
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			data: 'act=node_change&title=' + title + '&node_id=' + node_id,
			success: function(msg){
				// console.log(msg);
				document.location.reload();
			}
		})
	})
	$('#node_add').on('click', function(e){
		e.preventDefault();
		modal_show(
			'<form class="node_add">' +
				'<input type="text" name="title" placeholder="Название узла">' +
				'<input style="margin-left: 10px" type="submit" value="Искать">' +
			'</form>' +
			'<div class="search_nodes"></div>'
		);
	});
	$(document).on('click', 'form.node_add input[type=submit]', function(e){
		e.preventDefault();
		var title = $('input[name=title]').val();
		if (!title) return show_message('Введите название узла!', 'error');
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			data: 'act=search_nodes&title=' + title,
			success: function(res){
				console.log(res);
				var str = '';
				if (!res) str = '<p>Ничего не найдено</p>';
				var nodes = JSON.parse(res);
				console.log(nodes);
				for(var key in nodes){
					var node = nodes[key];
					var href = '?view=original-catalogs&act=add_node&node_id=' + node.id + '&modification_id=' + $('#modification_id').val();
					if (node.parent) title = node.parent + ' - ' + node.title;
					else title = node.title;
					str += 
						'<a class="search_nodes" href="' +  href + '" title="Добавить">' +
							title +
						'</a>';
				}
				$('#modal_content .search_nodes').html(str);
				// console.log(nodes);
			}
		})
	})
	$('#node_forward').on('click', function(e){
		if (!$(this).attr('href')){
			e.preventDefault();
			return show_message('Узел является родительским!', 'error');
		} 
	})
	$('#node_create').on('click', function(e){
		e.preventDefault();
		var modification_id = $('#modification_id').val();
		modal_show(
			'<form action="/admin/?view=original-catalogs&act=node_create&modification_id=' + modification_id + '" method="post" class="node_create">' + 
				'<input type="hidden" name="parent_id" value="' + $('#parent_id').val() + '">' +
				'<input type="hidden" name="modification_id" value="' + modification_id + '">' +
				'<table>' +
				 	'<tr>' +
					 	'<td>Название:</td>' +
					 	'<td><input type="text" name="title"></td>' +
				 	'</tr>' +
			 		'<tr>' +
					 	'<td colspan="2"><input type="submit" value="Создать"></td>' +
				 	'</tr>' +
			 	'</table>' +
		 	'</form>' 	
		);
	})
	$(document).on('click', 'form.node_create input[type=submit]', function(e){
		if (!$('input[name=title]').val()){
			e.preventDefault();
			show_message('Введите название узла!', 'error');
		}
	})
	if ($(document).width ()>= 928){
		$(".zoom").elevateZoom({
			scrollZoom : true
		});
	}
	$('#item_add').on('click', function(e){
		e.preventDefault();
		modal_show(
			'<form class="item_add">' +
				'<input type="text" name="title" placeholder="Артикул">' +
				'<input style="margin-left: 10px" type="submit" value="Искать">' +
			'</form>' +
			'<div class="search_items"></div>'
		);
	});
	$(document).on('click', 'form.item_add input[type=submit]', function(e){
		e.preventDefault();
		var title = $('input[name=title]').val();
		if (!title) return show_message('Введите артикул!', 'error');
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			data: 'act=search_items&article=' + title,
			beforeSend: function(){
				$('div.search_items').addClass('gif');
			},
			success: function(res){
				// console.log(res);
				$('div.search_items').removeClass('gif');
				if (parseInt(res)) return show_message('Результат содержит ' + res + ' совпадений. Уточните поиск', false);
				var str = '';
				if (!res) str = '<p>Ничего не найдено</p>';
				var items = JSON.parse(res);
				// console.log(items);
				for(var key in items){
					var item = items[key];
					var attributes = 
						'article="' + item.article + '" item_id="' + item.id + '" title_full="' + 
						item.title_full + '" node_id="' + $('table.nodes').attr('node_id') + '"';
					str += 
						'<a ' + attributes + ' class="item_set" title="Добавить">' +
							item.brend + ' - ' + item.title_full
						'</a>';
				}
				$('#modal_content .search_items').html(str);
			}
		})
	})
	$(document).on('click', 'a.item_set', function(e){
		e.preventDefault();
		var elem = $(this);
		modal_show(
			'<form class="item_set">' + 
				'<input type="hidden" name="item_id" value="' + elem.attr('item_id') + '">' +
				'<input type="hidden" name="node_id" value="' + elem.attr('node_id') + '">' +
				'<input type="hidden" name="title_full" value="' + elem.attr('title_full') + '">' +
				'<input type="hidden" name="article" value="' + elem.attr('article') + '">' +
				'<table>' +
			 		'<tr>' +
					 	'<td>Позиция:</td>' +
					 	'<td><input type="text" name="pos"></td>' +
				 	'</tr>' +
				 	'<tr>' +
					 	'<td>Название:</td>' +
					 	'<td><input readonly type="text" value="' + elem.attr('title_full') + '"></td>' +
				 	'</tr>' +
			 		'<tr>' +
					 	'<td>Артикул:</td>' +
					 	'<td><input readonly type="text" value="' + elem.attr('article') + '"></td>' +
				 	'</tr>' +
				 	'<tr>' +
					 	'<td>Количество:</td>' +
					 	'<td><input type="text" name="quan" value="1"></td>' +
				 	'</tr>' +
				 	'<tr>' +
					 	'<td>Комментарий:</td>' +
					 	'<td><input type="text" name="comment"></td>' +
				 	'</tr>' +
			 		'<tr>' +
					 	'<td colspan="2"><input type="submit" value="Добавить"></td>' +
				 	'</tr>' +
			 	'</table>' +
		 	'</form>' 	
		);
	})
	$(document).on('click', 'form.item_set input[type=submit]', function(e){
		e.preventDefault();
		elem = $('form.item_set');
		var pos = elem.find('input[name=pos]').val();
		var item_id = elem.find('input[name=item_id]').val();
		var article = elem.find('input[name=article]').val();
		var title_full = elem.find('input[name=title_full]').val();
		var quan = elem.find('input[name=quan]').val();
		var comment = elem.find('input[name=comment]').val();
		var node_id = elem.find('input[name=node_id]').val();
		if (!pos) return show_message('Номер позиции задан неккоретно!', 'error');
		if (!quan || !parseInt(quan)) return show_message('Количество задано неккоретно!', 'error');
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			data: 
				'act=set_item&pos=' + pos + '&node_id=' + node_id +
				'&item_id=' + item_id + '&quan=' + quan + '&comment=' + comment,
			success: function(res){
				// console.log(res);
				if (res != 1) return show_message(res, 'error');
				$('tr.removable').remove();
				$('table.nodes').append(
					'<tr class="node_item" item_id="' + item_id + '">' +
						'<td>' + pos + '</td>' +
						'<td>' + title_full + '</td>' +
						'<td>' + article + '</td>' +
						'<td>' + quan + '</td>' +
						'<td>' + comment + '</td>' +
					'</tr>'
				);
				$('#total span').html(+$('#total span').html() + 1);
				$('#modal-container').removeClass('active');
			} 
		})
	})
	$(document).on('click', 'tr.node_item', function(){
		var elem = $(this);
		modal_show(
			'<a href="#" class="item_remove">Удалить</a>' +
			'<form class="item_change">' + 
				'<input value="' + $('table.nodes').attr('node_id') + '" name="node_id" type="hidden">' +
				'<input value="' + elem.attr('item_id') + '" name="item_id" type="hidden">' +
				'<table>' +
			 		'<tr>' +
					 	'<td>Позиция:</td>' +
					 	'<td><input type="text" name="pos" value="' + elem.find('td:nth-child(1)').html() + '"></td>' +
				 	'</tr>' +
				 	'<tr>' +
					 	'<td>Название:</td>' +
					 	'<td><input name="title_full" readonly type="text" value="' + elem.find('td:nth-child(2)').html() + '"></td>' +
				 	'</tr>' +
			 		'<tr>' +
					 	'<td>Артикул:</td>' +
					 	'<td><input name="article" readonly type="text" value="' + elem.find('td:nth-child(3)').html() + '"></td>' +
				 	'</tr>' +
				 	'<tr>' +
					 	'<td>Количество:</td>' +
					 	'<td><input type="text" name="quan" value="' + elem.find('td:nth-child(4)').html() + '"></td>' +
				 	'</tr>' +
				 	'<tr>' +
					 	'<td>Комментарий:</td>' +
					 	'<td><input type="text" name="comment" value="' + elem.find('td:nth-child(5)').html() + '"></td>' +
				 	'</tr>' +
			 		'<tr>' +
					 	'<td colspan="2"><input type="submit" value="Изменить"></td>' +
				 	'</tr>' +
			 	'</table>' +
		 	'</form>' 	
		);
	})
	$(document).on('click', 'form.item_change input[type=submit]', function(e){
		e.preventDefault();
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			data: $('form.item_change').serialize() + '&act=item_change',
			success: function(res){
				if (res != 1) return show_message(res, 'error');
				$('tr[item_id=' + $('input[name=item_id]').val() + ']').html(
					'<td>' + $('input[name=pos]').val() + '</td>' +
					'<td>' + $('input[name=title_full]').val() + '</td>' +
					'<td>' + $('input[name=article]').val() + '</td>' +
					'<td>' + $('input[name=quan]').val() + '</td>' +
					'<td>' + $('input[name=comment]').val() + '</td>'
				);
				$('#modal-container').removeClass('active');
				show_message('Успешно изменено!');
			}
		})
	})
	$(document).on('click', 'a.item_remove', function(e){
		e.preventDefault();
		if (!confirm('Вы действительно хотите удалить?')) return false;
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			data: 
				'node_id=' + $('input[name=node_id]').val() + '&item_id=' + $('input[name=item_id]').val() +
				'&act=item_remove',
			success: function(res){
				// console.log(res);
				$('tr[item_id=' + $('input[name=item_id]').val() + ']').remove();
				$('#total span').html(+$('#total span').html() - 1);
				$('#modal-container').removeClass('active');
			}
		})
	})
	$('.category_item .item_filter').on('click', function(e){
		e.preventDefault();
		var th = $(this);
		var category_id = th.parent().next().attr('category_id');
		var item_id = th.closest('#category_items').attr('item_id');
		var initials = new Array();
		$('.category[category_id=' + category_id + '] td').each(function(){
			var th = $(this);
			if (th.attr('filter_id')) initials[th.attr('filter_id')] = th.html();
		});
		// console.log(initials);
		$.ajax({
			method: 'post',
			url: '/ajax/item.php',
			data: 
				'act=get_filters&item_id=' + item_id +
				'&category_id=' + category_id,
			success: function(res){
				// console.log(res); return false;
				var filters = JSON.parse(res);
				console.log(initials);
				console.log(filters);
				var str = 
					'<form id="apply_filter">' + 
						'<table>';
				for (var key in filters){
					var f = filters[key];
					str += 
						'<tr>' +
							'<td>' + f.title + '</td>' +
							'<td>' +
								'<select name="' + f.id + '">' +
									'<option value=""></option>';
					if (Object.keys(f.filter_values).length){
						for (var k in f.filter_values){
							var selected = initials[f.id] == f.filter_values[k].title ? 'selected' : '';
							// console.log(initials[key], selected);
							str +=
									'<option ' + selected + ' value="' + f.filter_values[k].id +'">' + f.filter_values[k].title + '</option>';
						} 
					}
					str +=
								'</select>' +
							'</td>' +
						'</tr>';
				} 
				str +=
						'</table>' +
						'<input type="submit" item_id="' + item_id + '" category_id="' + category_id + '" value="Изменить">'
					'</form>';
				modal_show(str);
			}
		})
	})
	$(document).on('click', 'form#apply_filter input[type=submit]', function(e){
		e.preventDefault();
		var elem = $(this);
		var category_id = elem.attr('category_id');
		$.ajax({
			method: 'post',
			url: '/ajax/item.php',
			data: 
				$('#apply_filter').serialize() + 
				'&act=apply_filter' +
				'&item_id=' + elem.attr('item_id') + '&category_id=' + category_id,
			success: function(res){
				console.log(res);
				$('#modal-container').removeClass('active');
				$('form#apply_filter select').each(function(){
					var th = $(this);
					var filter_id = th.attr('name');
					var value = th.find('option:selected').html();
					$('table.category td[filter_id=' + filter_id + ']').html(value);
				})
			}
		})
	})
	$('#vehicle_categories').on('click', function(e){
		e.preventDefault();
		var str = 
			'<a href="#" id="vehicle_category_add">Добавить</a>' +
			'<div class="vehicle_categories">';
		var vehicle_categories = get_vehicle_categories('', true);
		for (var key in vehicle_categories) str +=
				'<a href="#" class="vehicle_category" data-id="' + vehicle_categories[key].id + '">' +
					vehicle_categories[key].title +
				'</a>';
		str +=
			'</div>';
		modal_show(str);
	})
	$(document).on('click', 'a.vehicle_category', function(e){
		e.preventDefault();
		var th = $(this);
		var new_value = prompt('Введите новое название (пусто - удаление вместе с траспортными средствами):', th.html());
		if (new_value === null) return false;
		if (!new_value){
			if (!confirm('Вы действительно хотите удалить?')) return false;
			$.ajax({
				method: 'post',
				url: '/ajax/original-catalogs.php',
				data: 'act=vehicle_category_delete&id=' + th.data('id'),
				success: function(msg){
					console.log(msg)
					$('a.vehicle_category:contains(' + th.html() + ')').remove();
					$('tr td:nth-child(4):contains(' + th.html() + ')').closest('tr').remove();
				}
			})
			return false;
		}
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			data: 'act=vehicle_category_change&new_value=' + new_value + '&id=' + th.data('id'),
			success: function(msg){
				console.log(msg);
				$('tr td:nth-child(4):contains(' + th.html() + ')').html(new_value);
				$('a.vehicle_category:contains(' + th.html() + ')').html(new_value);
			}
		})
	})
	$(document).on('click', '#vehicle_category_add', function(e){
		e.preventDefault();
		var new_value = prompt('Введите название новой категории:');
		if (new_value === null || !new_value) return false;
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			data: 'act=vehicle_category_add&new_value=' + new_value,
			success: function(msg){
				if (!parseInt(msg)) return show_message(msg, 'error');
				$('div.vehicle_categories').append(
					'<a href="#" class="vehicle_category" data-id="' + msg + '">' + new_value + '</a>'
				);
				show_message('Категория успешно добавлена!');
			}
		})
	})
	$(document).on('click', 'a.modification_to_other_model', function(e){
		e.preventDefault();
		var th = $(this);
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			data: 
				'act=get_models_adjourn&vehicle_id=' + th.attr('vehicle_id') + 
				'&brend_id=' + th.attr('brend_id') + '&modification_id=' + th.attr('modification_id') +
				'&model_id=' + th.attr('model_id'),
			success: function(msg){
				// console.log(msg); return false;
				var models = JSON.parse(msg);
				var str = '<select modification_id="' + th.attr('modification_id') + '" class="modification_to_other_model">';
				str += '<option value="">выберите модель...</option>';
				for (var k in models) str += '<option value="' + models[k].id + '">' + models[k].title + '</option>';
				str += '</select>';
				$('#modification_change').append(str);
			}
		})
	})
	$(document).on('change', 'select.modification_to_other_model', function(){
		var th = $(this);
		if (!th.val()) return show_message('Выберите модель!', 'error');
		$.ajax({
			method: 'post',
			url: '/ajax/original-catalogs.php',
			data: 'act=modification_to_other_model&modification_id=' + th.attr('modification_id') + '&model_id=' + th.val(), 
			success: function(msg){
				// console.log(msg);
				document.location = document.location.href + '&model_id=' + th.val()
			}
		})
	})
})