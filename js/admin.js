var cookieOptions = {path: '/'};
var item_id = $('input[name=id]').val();
function getParams(){
	if (!window.location.search) return false;
	return window
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
};
function show_message(msg, type = 'ok'){
	if (type == 'error') $('#message div div').css('background', 'rgba(214, 50, 56, 0.97)');
	else $('#message div div').css('background', 'green');
	$('#message div div').html(msg);
	$('#message').slideDown(500);
	setTimeout(function(){
		$('#message').slideUp(200);
	}, 3000);
	$.cookie('message', '', cookieOptions);
	$.cookie('message_type', '', cookieOptions);
}
function getImgUrl(){
	return $('input[name=imgUrl]').val();
}
$('#left_menu > div > ul > li > a').on('click', function(){
	var th = $(this);
	if (th.next('ul').size()){
		th.next().toggleClass('active');
		th.find('span').toggleClass('icon-circle-up').toggleClass('icon-circle-down');
		var title = th.text();
		title = title.trim(title);
		$.ajax({
			url: '/admin/ajax/submenu.php',
			type: 'get',
			data:{
				title: title
			},
			success: function(response){
				$('#main_field').html(response);
			}
		})
		return false;
	}
})
$(document).ready(function(e){
	// $('.price_format').priceFormat({
	// 	allowNegative: true,
	// 	 prefix: '',
	// 	 centsLimit: '',
	//     thousandsSeparator: ' ',
	//     clearOnEmpty: true,
	// });
	$.mask.definitions['~']='[+-]';
	$('input[name=telefon]').mask("+7 (999) 999-99-99");
	$('input[name=telefon_extra]').mask("+7 (999) 999-99-99");
	if ($.cookie('message')) show_message($.cookie('message'), $.cookie('message_type'));
	$(document).on('click', '.delete_item:not(.analogies_delete)', function(e){
		if (!confirm('Вы действительно хотите удалить?')) e.preventDefault();
	})
	$('#modal_close').on('click', function(){
		$('#modal-container').removeClass('active');
	})
	$('#modal-container').on('click', function(event){
		var t = $('#modal-container');
		if (t.is(event.target)) t.removeClass('active');
	})
	$(document).on('click', '[table=fotos]', function(e){
		e.preventDefault();
		if (!confirm("Вы действительно хотите удалить это фото?")) return false; 
		var li = $(this).closest('li');
		$.ajax({
			type: "POST",
			url: "/ajax/delete_foto.php",
			data: 'table=foto&item_id=' + li.closest('ul').attr('item_id') + '&foto_name=' + li.attr('foto_name'),
			success: function(msg){
				console.log(msg);
				li.remove();
				show_message('Фото успешно удалено!', 'ok');
			}
		})
	})
	$(document).on('change', '#image_item', function(){
		$('#upload_image').ajaxForm({
			target: '#temp_foto',
			beforeSubmit: function(e){
				// $('.uploading').show();
			},
			success:function(msg){
				show_message('Фото успешно загружено!', 'ok');
				$('#item_fotos').prepend($('#temp_foto').html());
				$('#temp_foto').empty();					
			},
			error:function(e){
			}
		}).submit();	
	})
	$(document).on('change', '.value_apply', function(){
		var item_id = $('#item_id').val() ? $('#item_id').val() : $(this).attr('item_id');
		var prop_value = $(this).val();
		$('#popup').css('display', 'flex');
		$.ajax({
			type: "POST",
			url: "/ajax/value_apply.php",
			data: "prop_value=" + prop_value + '&item_id=' + item_id,
			success: function(msg){
				console.log(msg);
				$('#popup').css('display', 'none');
				// alert(msg);
				if (msg == 'ok') show_message('Изменения успешно сохранены!', 'ok');
				else show_message('Произошла ошибка!', 'error');
			}
		})
	})
	$(document).on('change', '#category_id', function(){
		var item_id = $('#item_id').val();
		var category_id = $(this).val();
		$('#popup').css('display', 'flex');
		$.ajax({
			type: "POST",
			url: "/ajax/category_apply.php",
			data: "category_id=" + category_id + '&item_id=' + item_id,
			success: function(msg){
				$('#popup').css('display', 'none');
				if (msg == 'ok') {
					$.cookie('message', 'Изменения успешно сохранены!', cookieOptions);
					$.cookie('message_type', 'ok', cookieOptions);
					document.location.reload();
				}
				else show_message('Произошла ошибка!', 'error');
			}
		})
	})
	$(document).on('click', '#close_addtitional', function(){
		$('#additional').css('display', 'none');
	})
	$(document).on('click', '.prop_change', function(e){
		e.preventDefault();
		var act = $(this).attr('act');
		var category_id = $(this).attr('category_id');
		$('#popup').css('display', 'flex');
		switch(act){
			case 'value_ch_delete':
				var value_id = $(this).attr('value_id');
				var property_id = $(this).attr('property_id');
				if (confirm('Вы действительно хотите удалить данное значение?')){
					$.ajax({
						type: "POST",
						url: "/ajax/prop_change.php",
						data: '&act=' + act + '&value_id=' + value_id,
						success: function(msg){
							$('#popup').css('display', 'none');
							$('#additional').css('display', 'none');
							// $('#prop_' + property_id).html(msg);
							if (msg == 'ok'){
								var prop_id = '#prop_' + property_id + ' b';
								$('option[value=' + value_id + ']').remove();
								show_message('Значение успешно удалено!');
							}
							else show_message('Произошла ошибка', 'error');
						}
					});
				}
				break;
			case 'value_ch_save':
				var data = $(this).parent().serialize();
				var value_id = $(this).parent().find('input[type=hidden]').val();
				$.ajax({
					type: "POST",
					url: "/ajax/prop_change.php",
					data: data + '&act=' + act,
					success: function(msg){
						// alert(msg);
						$('#popup').css('display', 'none');
						$('#additional').css('display', 'none');
						$('option[value=' + value_id + ']').html(msg);
						show_message('Значение успешно изменено!');
					}
				});
				break;
			case 'value_ch':
				var value_id = $(this).attr('value_id');
				$.ajax({
					type: "POST",
					url: "/ajax/prop_change.php",
					data: "value_id=" + value_id + '&act=' + act,
					success: function(msg){
						$('#popup').css('display', 'none');
						$('#additional').css('display', 'flex');
						$('#additional div').html(msg);
					}
				});
				break;
			case 'value_add':
				var filter_id = $(this).attr('filter_id');
				var new_value = prompt('Введине название нового значения свойства');
				if (new_value){
					var data = "filter_id=" + filter_id + "&act=" + act + "&new_value=" + new_value;
					$.ajax({
						type: "POST",
						url: "/ajax/prop_change.php",
						data: data,
						success: function(msg){
							$('#popup').css('display', 'none');
							$('#additional').css('display', 'none');
							if (msg) {
								$('#filter_' + filter_id).html(msg);
								show_message('Значение свойства успешно добавлено!');
							} 
							else show_message('Произошла ошибка', 'error');
						}
					})
				}
				break;
			case 'values_ch':
				var filter_id = $(this).attr('filter_id');
				$.ajax({
					type: "POST",
					url: "/ajax/prop_change.php",
					data: 'filter_id=' + filter_id + "&act=" + act,
					success: function(msg){
						// alert(msg);
						$('#popup').css('display', 'none');
						$('#additional').css('display', 'flex');
						$('#additional div').html(msg);
					}
				})
				break;
			case 'filter_add':
				var new_filter = prompt('Введине название нового свойства');
				if (new_filter){
					var data = "category_id=" + category_id + "&act=" + act + "&new_filter=" + new_filter;
					$('#additional').css('display', 'none');
					$.ajax({
						type: "POST",
						url: "/ajax/prop_change.php",
						data: data,
						success: function(msg){
							if (msg) {
								$('#popup').css('display', 'none');
								$('#properties').append(msg);
								show_message('Свойство успешно добавлено! Для добавления значений перезагрузите страницу!');
							} 
							else show_message('Произошла ошибка', 'error');
						}
					})
				}
				break;
			case 'filters_ch':
				$.ajax({
					type: "POST",
					url: "/ajax/prop_change.php",
					data: 'category_id=' + category_id + "&act=" + act,
					success: function(msg){
						// alert(msg);
						$('#popup').css('display', 'none');
						$('#additional').css('display', 'flex');
						$('#additional div').html(msg);
					}
				})
				break;
			case 'filter_ch':
				var filter_id = $(this).attr('filter_id');
				$.ajax({
					type: "POST",
					url: "/ajax/prop_change.php",
					data: "filter_id=" + filter_id + '&act=' + act,
					success: function(msg){
						$('#popup').css('display', 'none');
						$('#additional').css('display', 'flex');
						$('#additional div').html(msg);
					}
				});
				break;
			case 'filter_ch_save':
				var data = $(this).parent().serialize();
				var filter_id = $(this).parent().find('input[type=hidden]').val();
				$.ajax({
					type: "POST",
					url: "/ajax/prop_change.php",
					data: data + '&act=' + act,
					success: function(msg){
						// alert(msg);
						$('#popup').css('display', 'none');
						$('#additional').css('display', 'none');
						$('b[data=' + filter_id + ']').html(msg + ":")
						show_message('Значение успешно изменено!');
					}
				});
				break;
			case 'filter_ch_delete':
				var filter_id = $(this).attr('filter_id');
				if (confirm('Вы действительно хотите удалить данный фильтр?')){
					$.ajax({
						type: "POST",
						url: "/ajax/prop_change.php",
						data: '&act=' + act + '&filter_id=' + filter_id,
						success: function(msg){
							$('#popup').css('display', 'none');
							$('#additional').css('display', 'none');
							$.cookie('message', 'Успешно удалено!', cookieOptions);
							$.cookie('message_type', 'ok');
							document.location.reload();
							// alert(msg);
						}
					});
				}
				break;
		}
	})
	$('.attachment').magnificPopup({
			delegate: 'a',
			type: 'image',
			tLoading: 'Загрузка #%curr%...',
			mainClass: 'mfp-img-mobile',
			gallery: {
				enabled: true,
				navigateByImgClick: true,
				preload: [0,1] // Will preload 0 - before current, and 1 after the current image
			},
			image: {
				tError: 'Не удалось загрузить <a href="%url%">изображение #%curr%</a>'
			}
		});
	$('#click_image').on('click', function(){
		$('#image').click();
	})
	$('#click_image_item').on('click', function(){
		$('#item_image').click();
	})
	$(document).on('change', '#image', function(){
		$('#upload_image').ajaxForm({
			target: '#temp_foto',
			beforeSubmit: function(e){
				// $('.uploading').show();
			},
			success:function(msg){
				show_message('Фото успешно загружено!', 'ok');
				$('#fotos').prepend($('#temp_foto').html());
				$('#temp_foto').empty();					
			},
			error:function(e){
			}
		}).submit();	
	})
	$(document).on('change', '#item_image', function(){
		$('#popup').css('display', 'flex');
		$('#upload_image').ajaxForm({
			target: '#temp_foto',
			beforeSubmit: function(e){
			},
			success:function(msg){
				$('#popup').css('display', 'none');
				show_message($.cookie('message'), $.cookie('message_type'));
				$('#fotos_item').prepend($('#temp_foto').html());
				$('#temp_foto').empty();					
			},
			error:function(e){
			}
		}).submit();	
	})
	$('#send-message button').on('click', function(e){
		var sendeble = true;
		var text_message = $('#send-order-text').val();
		if (text_message.length < 10){
			show_message('Длина сообщения должна быть не менее 10 символов!', 'error');
			e.preventDefault();
		}
		var department = +$('#department').val();
		console.log($('input[name=order_id]').val());
		if (!department && !$('input[name=order_id]').val()){
			show_message('Выберите тему!', 'error');
			e.preventDefault();
		}
		if (sendeble){
			fotos = [];
			$('#fotos li').each(function(){
				fotos.push($(this).attr('foto_name'));
			});
			$('#send-message input[name=json_fotos]').val(JSON.stringify(fotos));
			$.cookie('message', 'Сообщение успешно отправлено!', cookieOptions);
			$.cookie('message_type', 'ok', cookieOptions);
		}
	})
	$('.hide').on('click', function(e){
		e.preventDefault();
		if ($(this).html() == "Показать") $(this).html('Скрыть').next().show();
		else $(this).html('Показать').next().hide();
	})
	$('#add_category').on('click', function(e){
		$('#popup').css('display', 'flex');
		e.preventDefault();
		var elem = $(this);
		$.ajax({
			type: "POST",
			url: "/ajax/add_category.php",
			data: '',
			success: function(msg){
				$('#popup').css('display', 'none');
				elem.before(msg);
			}
		});
	})
	$(document).on('change', '#add_subcategories', function(){
		$('#popup').css('display', 'flex');
		elem = $(this);
		elem.next('select').remove();
		elem.next('a').remove();
		var category_id = elem.val();
		if (!category_id){
			$('#popup').css('display', 'none');
			return false;
		} 
		$.ajax({
			type: "POST",
			url: "/ajax/add_subcategories.php",
			data: 'category_id=' + category_id,
			success: function(msg){
				$('#popup').css('display', 'none');
				elem.after(msg);
			}
		});
	})
	$(document).on('click', '#apply_category', function(e){
		e.preventDefault();
		var category_id = $(this).prev('#subcategory').val();
		if (!category_id){
			show_message('Выберите подкатегорию!', 'error');
			return false;
		}
		$.ajax({
			type: "POST",
			url: "/ajax/apply_category.php",
			data: 'category_id=' + category_id + '&item_id=' + $('input[name=item_id]').val(),
			success: function(msg){
				$.cookie('message', 'Категоря успешно применена!', cookieOptions);
				$.cookie('message_type', 'ok', cookieOptions);
				document.location.reload();
			}
		});
	})
	$('#add_subcategory').on('click', function(e){
		e.preventDefault();
		var new_value = prompt('Введите название новой подкатегории:');
		if (new_value){
			var parent_id = $(this).attr('category_id');
			$.ajax({
				type: "POST",
				url: "/ajax/category.php",
				data: 'table=add&parent_id=' + parent_id + '&new_value=' + new_value,
				success: function(msg){
					// console.log(msg);
					var res = JSON.parse(msg);
					if (res.error) show_message(res.error, 'error');
					else{
						$('[colspan=4]').remove();
						var str = '<tr>' +
						'<td title="Нажмите, чтобы изменить" class="category" data-id="' + res.id + '">' + 
							res.title +
						'</td>' + 
						'<td title="Нажмите, чтобы изменить" class="href" data-id="' + res.id + '">' +
							res.href +
						'</td>' + 
						'<td>' + 
							'<a href="?view=category&act=items&id=' + res.id + '">Товаров (0)</a> ' + 
							'<a href="?view=category&act=filters&id=' + res.id + '">Фильров (0)</a>' +
						'</td>' +
						'<td>' + 
							'<a class="delete_item" href="?view=category&act=delete&id=' + res.id + '&parent_id=' + parent_id + '">Удалить</a>' + 
						'</td>' +
						'</tr>';
						$('.t_table').append(str);
						show_message("Подкатегория '" + new_value + "' успешно добавлена!");
					}
				}
			})
		}
	})
	$('.filter_title, .filter_pos').on('click', function(e){
		e.preventDefault();
		th = $(this);
		var new_value = prompt('Введите новое значение', th.html());
		if (new_value === null || !new_value) return false;
		$.ajax({
			method: 'post',
			url: '/ajax/category.php',
			data: 'table=' + th.attr('class') + '&new_value=' + new_value + '&id=' + th.closest('tr').attr('filter_id'),
			success: function(msg){
				var res = JSON.parse(msg);
				// console.log(msg);
				if (res.error) return show_message(res.error, 'error');
				else{
					th.html(new_value)
					show_message('Изменения успешно сохранены!');
				} 
			}
		})		
	})
	$('#add_filter').on('click', function(e){
		e.preventDefault();
		var title = prompt('Введите название нового фильтра:');
		if (!title) return false;
		var elem = $(this);
		$.ajax({
			type: "POST",
			url: "/ajax/add_filter.php",
			data: 'category_id=' + elem.attr('category_id') + '&title=' + title,
			success: function(msg){
				// alert(msg);
				if (msg) show_message(msg, 'error');
				else document.location.reload();
			}
		})
	})
	$('.delete_filter').on('click', function(e){
		e.preventDefault();
		if (confirm('Вы действительно хотите удалить данный фильтр?')){
			$('#popup').css('display', 'flex');
			elem = $(this);
			$.ajax({
				type: "POST",
				url: "/ajax/delete_filter.php",
				data: 'filter_id=' + $(this).attr('filter_id'),
				success: function(msg){
					// alert(msg);
					if (msg == "ok"){
						show_message('Фильтр успешно удален!');
						$('#popup').css('display', 'none');
						elem.parent().parent().remove();
					}
				}
			})
		}
	})
	$('#add_filter_value').on('click', function(e){
		e.preventDefault();
		var title = prompt('Введите название нового свойства:');
		if (!title) return false;
		var elem = $(this);
		$.ajax({
			type: "POST",
			url: "/ajax/add_filter_value.php",
			data: 'filter_id=' + elem.attr('filter_id') + '&title=' + title,
			success: function(msg){
				if (!msg) document.location.reload();
				else show_message(msg, 'error');
			}
		})
	})
	$('.change_filter_value').on('click', function(e){
		e.preventDefault();
		elem = $(this);
		var current_value = elem.parent().parent().find('td:first-child').html();
		var new_value = prompt('Введите новое название значения фильтра:', current_value);
		if (current_value == new_value) return false;
		if (!new_value) return false;
		$.ajax({
			type: "POST",
			url: "/ajax/change_filter_value.php",
			data: 'filter_value_id=' + $(this).attr('filter_value_id') + '&title=' + new_value,
			success: function(msg){
				if (msg) return show_message(msg, 'error');
				else{
					elem.closest('tr').find('td:first-child').html(new_value);
					show_message('Успешно изменено!');
				} 
			}
		})
	})
	$('.delete_filter_value').on('click', function(e){
		e.preventDefault();
		if (confirm('Вы действительно хотите удалить данное значение фильтра?')){
			$('#popup').css('display', 'flex');
			elem = $(this);
			$.ajax({
				type: "POST",
				url: "/ajax/delete_filter_value.php",
				data: 'filter_value_id=' + $(this).attr('filter_value_id'),
				success: function(msg){
					if (msg == "ok"){
						show_message('Свойство фильтра успешно удалено!');
						$('#popup').css('display', 'none');
						elem.parent().parent().remove();
					}
				}
			})
		}
	})
	$('.items_box').on('click', function(){
		document.location.href = "?view=items&act=item&id=" + $(this).attr('item_id');
	})
	$('.users_box').on('click', function(){
		document.location.href = "?view=users&act=change&id=" + $(this).attr('user_id');
	})
	$('.messages_box').on('click', function(){
		document.location.href = '/admin/?view=correspond&id=' + $(this).attr('correspond_id');
	})
	$('tr.clickable_1').on('click', function(){
		document.location.href = '/admin/?view=' + $(this).closest('table').attr('view') + '&act=item&id=' + $(this).attr('value_id');
	})
	$('input[name=delivery_type]').on('click', function(){
		if ($(this).next().html() == 'Доставка') $('select[name=issue_id]').prop('disabled', true);
		else $('select[name=issue_id]').prop('disabled', false);
	})
	$('input[name=user_type]').on('click', function(){
		if ($(this).next().html() == 'Физическое лицо'){
			$('input[name=organization_name]').prop('disabled', true).val('');
			$('select[name=organization_type]').prop('disabled', true);
		} 
		else{
			$('input[name=organization_name]').prop('disabled', false);
			$('select[name=organization_type]').prop('disabled', false);
		} 
	})
	$('input[name=is_legal]').on('click', function(){
		if ($(this).is(':checked')){
			$('input[name=fact_index]').prop('disabled', true).val('');
			$('input[name=fact_region]').prop('disabled', true).val('');
			$('input[name=fact_adres]').prop('disabled', true).val('');
		}
		else{
			$('input[name=fact_index]').prop('disabled', false);
			$('input[name=fact_region]').prop('disabled', false);
			$('input[name=fact_adres]').prop('disabled', false);
		}
	})
	$('#is_stay').on('click', function(e){
		$('input[name=is_stay]').val(1);
	})
	$('#add_subbrend').on('click', function(e){
		e.preventDefault();
		var new_subbrend = prompt('Введите новое имя бренда:');
		if (!new_subbrend) return false;
		// $('#popup').css('display', 'flex');
		$.ajax({
			type: "POST",
			url: "/ajax/subbrend.php",
			data: 'act=subbrend_add&new_subbrend=' + new_subbrend + '&brend_id=' + $('#add_subbrend').attr('brend_id'),
			success: function(msg){
				console.log(msg);
				if (msg == 0){
					show_message('Такое имя подбренда уже присутствует!', 'error');
				}
				else {
					$('#no_brends').remove();
					$('#add_subbrend').before('<span class="subbrend" subbrend_id="' + msg + '">' + new_subbrend 
						+ '<span class="subbrend_delete"></span>'
						+ '</span>');
					show_message('Подбренд успешно добавлен!');
				}
			}
		})
		$('#popup').css('display', 'none');
	})
	$(document).on('click', '.subbrend_delete', function(){
		var elem = $(this).parent();
		var subbrend_id = elem.attr('subbrend_id');
		if (!confirm('Вы действительно хотите удалить?')) return false;
		$.ajax({
			type: "POST",
			url: "/ajax/subbrend.php",
			data: 'act=subbrend_delete&subbrend_id=' + subbrend_id,
			success: function(msg){
				// alert(msg);
				if (msg == 'ok'){
					show_message('Подбренд успешно удален!');
					elem.remove();
					if (!$('.subbrend').size()) $('#add_subbrend').before('<span id="no_brends">Подбрендов не найдено</span>');
				}
			}
		})
	})
	$('.subcategory td.href, .subcategory td.category, .subcategory td.pos').on('click', function(){
		elem = $(this);
		var id = elem.closest('tr').data('id');
		var table = elem.attr('class');
		var old_value = elem.html();
		old_value = old_value.trim();
		var new_value = prompt('Введите новое значение:', old_value);
		if (!new_value) return false;
		if (new_value == old_value) return false;
		$.ajax({
			type: "POST",
			url: "/ajax/category.php",
			data: 'id=' + id + '&table=' + table + '&old_value=' + old_value + '&new_value=' + new_value,
			success: function(msg){
				// console.log(msg);
				// alert(msg);
				var res = JSON.parse(msg);
				if (res.error) show_message(res.error, 'error');
				else{
					if (table == 'category'){
						if (res.href) elem.next().html(res.href);
					}
					elem.html(new_value);
					show_message('Изменения успешно сохранены!');
				}
			}
		})
	})
	$(document).on('click', '.loop', function(e){
		e.preventDefault();
		var str = '';
		var li = $(this).parent().parent();
		var item_id = li.parent().attr('item_id');
		var curr = li.attr('foto_name');
		elems = li.parent().find('li');
		var i = 1;
		var child = 0;
		elems.each(function(){
			var title = $(this).attr('foto_name');
			if (title == curr) child = i;
			var href = '/images/items/big/' + item_id + '/' + title;
			str += '<a href="' + href +'" rel="alternate">' +
								'<img src="' + href + '" alt="" />' +
							'</a>';
			i++;
		})
		$('.popup-gallery').html(str);
		// console.log(child);
		$('.popup-gallery').magnificPopup({
			delegate: 'a',
			type: 'image',
			gallery:{
				enabled: true,
				navigateByImgClick: true,
				preload: [0, 1]
			}
		});
		$('.popup-gallery a:nth-child(' + child + ') img').click();
	})
	$('#add_theme').on('click', function(e){
		e.preventDefault();
		var m = prompt('Введите название новой темы:');
		if (!m) return false;
		$.ajax({
			type: 'post',
			url: '/ajax/message.php',
			data: 'm=' + m,
			success: function(msg){
				// console.log(msg);
				if (typeof +msg == 'number'){
					show_message('Тема успешно добавлена!');
					$('#department option:first-child').after(
						'<option selected value="' + msg + '">' + m + '</option>'
					);
				}
				else show_message('Ошибка: ' + msg, 'error');
			}
		})
	})
	$('.icon-menu').on('click', function(){
		$('#left_menu').addClass('show');
	})
	$('#closeLeftMenu').on('click', function(){
		$('#left_menu').removeClass('show');
	})
})
function modal_show(content){
	$('#modal-container')
		.addClass('active')
		.find('#modal_content')
		.html(content);
}
