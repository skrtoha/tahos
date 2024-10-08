const cookieOptions = {path: '/'};
const item_id = $('input[name=id]').val();
const delay = (function () {
    let timer = 0;
    return function (callback, ms) {
        clearTimeout(timer);
        timer = setTimeout(callback, ms);
    };
})();

function getParams(url = ''){
	let str = url ? url : window.location.search;
	if (!str) return false;
	const urlParams = new URLSearchParams(str);
	let output = {};
	for(const[name, value] of urlParams){
		output[name] = value;
	}
	return output;
}
function showGif(hide = true){
	let display = hide ? 'flex' : 'none';
	$('#popup').css('display', display);
}
function setNullToEmptyStrings(obj){
	for(let k in obj){
		if (obj[k] === null) obj[k] = '';
	}
	return obj;
}
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
function get_currencies(){
	var currencies;
	$.ajax({
		type: 'post',
		url: '/admin/ajax/providers.php',
		data: '&act=get_currencies',
		async: false,
		success: function(response){
			// console.log(response);
			currencies = JSON.parse(response);
		}
	});
	return currencies;
}
function getImgUrl(){
	return $('input[name=imgUrl]').val();
}
$('#left_menu > div > ul > li > a').on('click', function(){
	var th = $(this);
	if (th.next('ul').length){
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
	// 
	// 
	$(document).on('keyup', function(e){
		if (!e.ctrlKey) return false;
		if (e.keyCode != 13) return false;
		$('form.defaultSubmit').submit();
	})
	$("[tooltip]").on('mouseover', function (eventObject) {
       let data_tooltip = $(this).attr("tooltip");
       $("#tooltip").html(data_tooltip)
           .css({ 
             "top" : eventObject.pageY + 5,
             "left" : eventObject.pageX +5
           })
           .show();
       }).on('mouseout', function () {
         $("#tooltip").hide()
           .html("")
           .css({
               "top" : 0,
               "left" : 0
           });
   });
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
	$('.items_box').on('click', function(){
		document.location.href = "?view=items&act=item&id=" + $(this).attr('item_id');
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
function modal_show(content = null){
    $container = $('#modal-container');
    $container.addClass('active');
    if (content) $container.find('#modal_content').html(content);
}
