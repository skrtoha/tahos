var reg_integer = /^\d+$/;
$(function(){
	$('input.intuitive_search').on('keyup focus', function(e){
		e.preventDefault();
		let val = $(this).val();
		let minLength = 1;
		val = val.replace(/[^\wа-яА-Я]+/gi, '');
		intuitive_search.getResults({
			event: e,
			value: val,
			minLength: minLength,
			additionalConditions: {
				act: $(this).attr('name'),
				item_id: $('input[name=item_id]').val()
			},
			tableName: 'items',
		});
	});
	$('.hide').on('click', function(e){
		e.preventDefault();
		if ($(this).html() == "Показать") $(this).html('Скрыть').next().show();
		else $(this).html('Показать').next().hide();
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
			data: 'act=analogy_hide&value=' + $(this).val() + '&item_id=' + $('input[name=item_id]').val() + '&hidden=' + checked,
			success: function(response){}
		})
	})
	$('input[name=checked]').on('click', function(){
		var checked = $(this).is(':checked') ? 1 : 0;
		$.ajax({
			type: 'post',
			url: '/admin/ajax/item.php',
			data: 'act=analogy_checked&value=' + $(this).val() + '&item_id=' + $('input[name=item_id]').val() + '&checked=' + checked,
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
	$('select[filter_id]').on('change', function(){
		let th = $(this);
		let fv_id = th.val();
		let title = th.find('option[value=' + fv_id + ']').text();
		th.find('option[value=' + fv_id + ']').prop('disabled', true);
		th.next().append(
			'<label class="filter_value">' +
				'<input type="hidden" name="fv[]" value="' + fv_id + '">' +
				title +
				' <span class="icon-cross1"></span>' +
			'</label>'
		)
		th.find('option:first-child').prop('selected', true);
	})
	$(document).on('click', 'label.filter_value', function(){
		let th = $(this);
		let title = th.text();
		title = title.trim(title);
		let fv_id = th.find('input').val();
		th.parent().prev().find('option[value=' + fv_id + ']').prop('disabled', false);
		$(this).remove();
	})
	$('#buttonLoadPhoto').on('click', function(e){
		e.preventDefault();
		$('#loadPhoto').click();
	})
	$(document).on('click', '.loop', function(e){
		e.preventDefault();
		let th = $(this);
		let big = th.closest('li').attr('big');
		let ul = th.closest('ul');
		let items = new Array();
		let number = 0;
		let currentNumber = 0;
		ul.find('li').each(function(){
			if ($(this).attr('big') == big) currentNumber = number;
			items.push({src: $(this).attr('big')});
			number++;
		});
		let magnificPopup = $.magnificPopup.instance;
		magnificPopup.open({
			items: items,
			type: 'image',
			gallery:{
				enabled: true,
				navigateByImgClick: true,
				preload: [0, 1]
			}
		});
		magnificPopup.goTo(currentNumber);
	})
	$(document).on('click', 'span.main-photo', function(){
		let th = $(this);
		$('span.main-photo').removeClass('icon-lock').addClass('icon-unlocked');
		th.removeClass('icon-unlocked').addClass('icon-lock');
		$('li[big]').removeClass('main-photo');
		$('li[big]').find('input[name*=is_main]').val(0);
		th.closest('li').addClass('main-photo').find('input[name*=is_main]').val(1);
	})
	$(document).on('change', '#loadPhoto', function(){
		$(this).closest('form').ajaxForm({
			target: '#modal_content',
			beforeSubmit: function(){},
			success: function(response){
				let image = document.getElementById('uploadedPhoto');
				let item_id = $('#item_id').val();
				let cropper = new Cropper(image, {
					autoCropArea: 1,
					aspectRatio: 0.8,
					cropBoxResizable: false
				});
				$('#modal-container').addClass('active');
				$('#modal-container').on('click', function(event){
					var t = $('#modal-container');
					if (t.is(event.target)){
			      	cropper.reset();
						$('#modal_content').empty();
						t.removeClass('active');
						$('#loadPhoto').closest('form').resetForm();
					} 
				})
				$('#savePhoto').on('click', function(){
					cropper
						.getCroppedCanvas({
							'fillColor': '#fff',
							'width': 200,
							height: 250
						})
						.toBlob((blob) => {
							const formData = new FormData();
							formData.append('croppedImage', blob/*, 'example.png' */);
							formData.append('item_id', item_id);
							formData.append('act', 'savePhoto');
							formData.append('initial', $('#uploadedPhoto').attr('src'));
							$.ajax('/admin/ajax/item.php', {
								method: 'POST',
								data: formData,
								processData: false,
								contentType: false,
								success(response) {
									let images = JSON.parse(response);
									let count = $('#photos li').size();
									$('#photos').append(
										'<li big="' + images.big + '">' +
											'<div>' +
												'<a class="loop" href="#">Увеличить</a>' +
												'<a table="fotos" class="delete_foto" href="#">Удалить</a>' +
												'<span class="main-photo icon-unlocked"></span>' +
											'</div>' +
											'<img src="' + images.small + '" alt="">' +
											'<input type="hidden" name="photos[' + count + '][small]" value="' + images.small + '">' +
											'<input type="hidden" name="photos[' + count + '][big]" value="' + images.big + '">' +
											'<input type="hidden" name="photos[' + count + '][is_main]" value="0">' +
										'</li>'
									);
						      	cropper.destroy();
									$('#modal_content').empty();
									$('#modal-container').removeClass('active');
								},
								error() {
									console.log('Upload error');
								},
							});
						});
				})
			}
		}).submit();
	})
	$(document).on('click', 'a.removePhoto', function(e){
		e.preventDefault();
		if (!confirm('Вы действительно хотите удалить?')) return false;
		$(this).closest('li').remove();
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
			url: "/admin/ajax/item.php",
			data: {
				act: 'applyCategory',
				category_id: category_id,
				item_id: $('#item_id').val()
			},
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
	$(document).on('click', 'a.addItem', function(){
		let th = $(this);
		$.ajax({
			type: 'post',
			url: '/admin/ajax/item.php',
			beforeSend: function(){
				$('#pupup').css('display', 'flex');
			},
			data: {
				act: 'addItem',
				type: th.attr('type'),
				item_id: $('input[name=item_id]').val(),
				item_diff: th.attr('item_id')
			},
			success: function(response){
				let itemInfo = JSON.parse(response);
				$('#itemDiff').append(`
					<tr>
						<td label="Бренд">${itemInfo.brend}</td>
						<td label="Артикул">
							<a href="?view=items&act=item&id=${itemInfo.id}">
								${itemInfo.article}
							</a>
						</td>
						<td label="Название">${itemInfo.title_full}</td>
						<td label="Штрих-код">${itemInfo.barcode}</td>
						<td label="Категории">${itemInfo.categories}</td>
						<td label="">
							<a class="delete_item" href="?view=items&act=delete&type=${th.attr('type')}&item_id=${itemInfo.id}&item_diff=${th.attr('item_id')}">Удалить</a>
						</td>
					</tr>
				`);
				$('#popup').css('display', 'none');
			}
		})
	})
})
