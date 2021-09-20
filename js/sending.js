$(function(){
	$('input[type=checkbox]').styler();
	$("#phone").mask("+7 (999) 999-99-99");
	$("#pasport").mask("9999 №999999");
	$(document).on('change', "select[name='delivery_way']", function(){
		var id = parseInt($(this).val());
		var bl_pasport = (id === 2 || id === 9);
		$.ajax({
			type: "POST",
			url: "/ajax/delivery.php",
			data: "id=" + id,
			success: function(msg){
				// console.log(msg);
				if (msg){
					$('#sub_delivery')
                        .empty()
						.html('<select name="sub_delivery" id=""></select>')
						.removeClass('hidden');
					$('[name=sub_delivery]').html(msg).styler();
				} 
				else{
					$('[name=sub_delivery]').parent().parent().addClass('hidden').html('');
				}
			} 
		});
		if (bl_pasport) $('#pasport').parent().removeClass('for-tk');
		else $('#pasport').parent().addClass('for-tk');
		if ($(this).val() === '1') $('select[name=speed]').parent().parent().removeClass('hidden');
		else $('select[name=speed]').parent().parent().addClass('hidden');
	});
	$('[name=entity]').on('change keydown keyup', function(){
		if ($(this).val()){
			$('[name=name_1]').prop('disabled', true);
			$('[name=name_2]').prop('disabled', true);
			$('[name=name_3]').prop('disabled', true);
		}
		else{
			$('[name=name_1]').prop('disabled', false);
			$('[name=name_2]').prop('disabled', false);
			$('[name=name_3]').prop('disabled', false);
		}
	})
	$('[name=name_1], [name=name_2], [name=name_3]').on('change keydown keyup', function(){
		if ($(this).val()) $('[name=entity]').prop('disabled', true);
		else $('[name=entity]').prop('disabled', false);
	})
	$('input.item').on('change', function(){
		var th = $(this);
		var quan = + th.closest('tr').find('span.quan').text();
		var weight = + th.closest('tr').find('span.weight').text();
		var price = + th.closest('tr').find('span.price').text();
		// console.log(quan, weight, price);
		if (th.is(':checked')){
			weight += + $('span.weight-total span').text();
			quan += + $('span.goods-count').text();
			price += + $('span.amount-total span').text();
		} 
		else{
			weight = $('span.weight-total span').text() - weight;
			quan = $('span.goods-count').text() - quan;
			price = $('span.amount-total span').text() - price;
		} 
		$('span.weight-total span').text(weight);
		$('span.goods-count').text(quan);
		$('span.amount-total span').text(price);
	})
	$('#delivery-form button').on('click', function(e){
	    let th = $(this).closest('form');
		var name_1 = $('input[name=name_1]').val();
		var name_2 = $('input[name=name_2]').val();
		var name_3 = $('input[name=name_3]').val();
		var entity = $('input[name=entity]').val();
		var delivery_way = $('[name=delivery_way]').val();
		var bl_pasport = (delivery_way == 2 || delivery_way == 9);
		var speed = $('input[name=speed]').val();
		var telefon = $('input[name=telefon]').val();
		var pasport = $('#pasport').val();
		var is_valid = true;
		if (bl_pasport && !pasport){
			is_valid = false;
			show_message ('Введите паспортные данные!', 'error');
		}
		if (!name_1 && !name_2 && !name_3 && !entity){
			is_valid = false;
			show_message('Введите ФИО или организацию!', 'error');
		}
		if (!name_1 && name_2){
			is_valid = false;
			show_message('Введите фамилию!', 'error');
		}
		if (name_1 && !name_2){
			is_valid = false;
			show_message('Укажите имя!', 'error');
		}
		if (!telefon){
			is_valid = false;
			show_message('Укажите номер телефона!', 'error');
		}
		if (!delivery_way){
			is_valid = false;
			show_message('Выберите способ доставки!', 'error');
		}
		if (!$('#sub_delivery').hasClass('hidden') && !$('[name=sub_delivery]').val()){
			is_valid = false;
			show_message('Корректно укажите способ доставки', 'error');
		}
		if (!is_valid){
			e.preventDefault();
			return false;
		}
		// e.preventDefault();
		var income = new Object();
		$('#main table:nth-child(3) input.item').each(function(){
			if (!$(this).is(':checked')) return true;
			income[$(this).attr('name')] = + $(this).val();
		})
		// console.log(income); 
		if (!Object.keys(income).length) return show_message('Нечего сохранять', 'error');
		$.ajax({
			type: 'post',
			url: '/admin/index.php?view=order_issues&user_id=' + $('input[name=user_id]').val(),
			data: {income: income},
			success: function(response){
				// console.log(response); //return false;
				$('input[name=issue_id]').val(response);
				th.submit();
			}
		})
	})
	$('.delete_template').on('click', function(){
		if (!confirm('Вы действительно хотите удалить?')) return false;
		var elem = $(this);
		$.ajax({
			type: "POST",
			url: "/ajax/template_delete.php",
			data: "id=" + elem.data('id'),
			success: function(msg){
				if (msg){
					// console.log(msg);
					if (msg){
						elem.parent().remove();
						show_message('Шаблон успешно удален', 'ok');
					}
				} 
			} 
		});
	})
	$('.templates-block li').on('click', function(){
		$('#insure').attr('checked', false).parent().removeClass('checked');
		$('[name=entity]').val('').attr('disabled', false);
		$('[name=telefon]').val('').attr('disabled', false);
		$('[name=name_1]').val('').attr('disabled', false);
		$('[name=name_2]').val('').attr('disabled', false);
		$('[name=name_3]').val('').attr('disabled', false);
		$('[name=pasport]').val('').parent().attr('class', 'input-wrap for-tk');
		$(this).find('span').each(function(){
			var key = $(this).attr('key');
			var value = $(this).attr('value');
			var html_value = $(this).html();
			if (value){
				switch (key){
					case 'insure': 
						if (value == '1') $('#insure').attr('checked', true).parent().addClass('checked');
						break;
					case 'delivery_way':
						var deliveries = $('#js_deliveries').val();
						deliveries = deliveries.replace(/#/gi, '"');
						deliveries = JSON.parse(deliveries);
						// console.log(deliveries);
						main_deliveries = new Array();
						for (var k in deliveries){
							var item = deliveries[k];
							if (item.parent_id == '0'){
								main_deliveries.push({"id": k, "title": item.title});
							} 
						}
						// console.log(main_deliveries);
						for (var k in deliveries){
							var item = deliveries[k];
							if (k == value){
								var parent_id = parseInt(item.parent_id);
								break;
							}
						}
						if (!parseInt(parent_id)){
							console.log('Пусто');
							//hmd = html_main_deliveries
							var hmd = '<select name="delivery_way">';
							hmd += '<option value=""></option>';
							for (var k in main_deliveries){
								var item = main_deliveries[k];
								var selected = item.id == value ? 'selected' : '';
								hmd += '<option ' + selected + ' value="' + item.id + '">' + item.title + '</option>';
							}
							hmd += '</select>';
							$('#main_deliveries').html(hmd);
							$('#sub_delivery').addClass('hidden');
							$('[name=delivery_way]').styler();
							break;
							// console.log(hmd);
						}
						var sub_deliveries = new Array();
						for (var k in deliveries){
							var item = deliveries[k];
							if (item.parent_id == parent_id) sub_deliveries.push({"id": k, "title": item.title});
						}
						// hsd = html_sub_deliveries
						hsd = '<select name="sub_delivery">';
						hsd += '<option value=""></option>';
						for (var k in sub_deliveries){
							var item = sub_deliveries[k];
							var selected = item.id == value ? 'selected' : '';
							hsd += '<option ' + selected + ' value="' + item.id + '">' + item.title + '</option>';
						}
						hsd += '</select>';
						//hmd = html_main_deliveries
						var hmd = '<select name="delivery_way">';
						hmd += '<option value=""></option>';
						for (var k in main_deliveries){
							var item = main_deliveries[k];
							var selected = item.id == parent_id ? 'selected' : '';
							// console.log(key + ": " + value + '    ' + selected);
							// console.log('item.id=' + item.id + ' value=' + value + ' ' + selected);
							hmd += '<option ' + selected + ' value="' + item.id + '">' + item.title + '</option>';
						}
						hmd += '</select>';
						// console.log(hmd);
						$('#main_deliveries').html(hmd);
						$('[name=delivery_way]').styler();
						$('#sub_delivery').html(hsd).removeClass('hidden');
						$('[name=sub_delivery]').styler();
						
						break;
					case 'entity': 
						$('[name=entity]').val(value); 
						$('[name=name_1]').prop('disabled', true);
						$('[name=name_2]').prop('disabled', true);
						$('[name=name_3]').prop('disabled', true);
						break;
					case 'telefon': $('[name=telefon]').val(value); break;
                    case 'address_id':
                        $('select[name=address_id] option').prop('selected', false);
                        $('select[name=address_id] option[value=' + value + ']').prop('selected', true);
                        $('select[name=address_id]').trigger('refresh');
                        break;
					case 'pasport': 
						if (value) $('[name=pasport]')
												.val(value)
												.parent()
												.removeClass('for-tk');
						break;
					case 'name_1': 
						$('[name=name_1]').val(value); 
						$('[name=entity]').prop('disabled', true);
						break;
					case 'name_2': 
						$('[name=name_2]').val(value); 
						$('[name=entity]').prop('disabled', true);
						break;
					case 'name_3': 
						$('[name=name_3]').val(value); 
						$('[name=entity]').prop('disabled', true);
						break;
				}
			}
		})
		// console.log('-------------------');
	})
});