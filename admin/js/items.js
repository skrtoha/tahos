var reg_integer = /^\d+$/;
$(function(){
	$('form').on('submit', function(e){
		if ($('input[name=type]:checked').val() == 'id' && !reg_integer.test($('input[name=search]').val())){
			e.preventDefault();
			show_message('Поиск по id содержит неккоректные данные!', 'error');
		}
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
		// console.log('initials', initials);
		$.ajax({
			method: 'post',
			url: '/admin/ajax/item.php',
			data: 
				'act=get_filters&item_id=' + item_id +
				'&category_id=' + category_id,
			success: function(res){
				var filters = JSON.parse(res);
				// console.log('filters', filters);
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
							var selected = initials[filters[key].id] == f.filter_values[k].title ? 'selected' : '';
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
			url: '/admin/ajax/item.php',
			data: 
				$('#apply_filter').serialize() + 
				'&act=apply_filter' +
				'&item_id=' + elem.attr('item_id') + '&category_id=' + category_id,
			success: function(res){
				// console.log(res); return;
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
	$('#language_add').on('click', function(e){
		e.preventDefault();
		str = 
			'<label class="item_translate">' +
				'<select name="language_id[]">';
		for(var k in languages) str +=
					'<option value="' + languages[k].id + '">' + languages[k].title + '</option>';
		str +=
				'</select>' +
				'<input type=text name="translate[]" value="">' +
				'<span class="icon-cross translate_delete"></span>' +
			'</label>';
		$('#item_translate').append(str);
	})
	$(document).on('click', 'span.translate_delete', function(){
		$(this).closest('label').remove();
	})
	$('a.analogies_add, a.analogies_delete').on('click', function(e){
		e.preventDefault();
		var href = $(this).attr('href');
		if ($(this).hasClass('analogies_delete') && !confirm('Действительно хотите удалить?')) return false;
		if (confirm('Выполнить действие для всех?')) href += '&all=1';
		document.location.href = href;
	})
	$('input[name=hidden]').on('click', function(){
		var checked = $(this).is(':checked') ? 1 : 0;
		$.ajax({
			type: 'post',
			url: '/admin/ajax/item.php',
			data: 'act=analogy_hide&value=' + $(this).val() + '&item_id=' + $('input[name=item_id]').val() + '&checked=' + checked,
			success: function(response){}
		})
	})
	$('#properties_categories .properties').on('click', function(){
		if (!confirm('Вы действительно хотите удалить?')) return false;
		var th = $(this);
		var category_id = th.attr('category_id');
		$.ajax({
			method: 'post',
			url: '/admin/ajax/item.php',
			data: 
				'&act=category_delete&item_id=' + th.closest('div').attr('item_id') +
				'&category_id=' + category_id,
			success: function(res){
				th.remove();
				$('table[category_id=' + category_id + ']').parent().remove();
			}
		})
	})
	$('a.clearAnalogies').on('click', function(e){
		if (!confirm('Вы действительно хотите очистить список аналогов')) e.preventDefault();
	})
})