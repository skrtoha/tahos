var reg_integer = /^\d+$/;
function addItemDiffHtml(type, items){
	console.log(items, type);
	let htmlAnalogies = '';
	let strHtml = '';
	$.each(items, function(key, itemInfo){
		itemInfo.barcode = itemInfo.barcode != null ? itemInfo.barcode : '';
		itemInfo.categories = itemInfo.categories != null ? itemInfo.categories : '';
		strHtml += `
			<tr class="analogyStatus_${itemInfo.status}">
				<td label="Бренд">${itemInfo.brend}</td>
				<td label="Артикул">
					<a target="blank" href="?view=items&act=item&id=${itemInfo.item_id}">
						${itemInfo.article}
					</a>
				</td>
				<td label="Кат. номер">
					<a target="blank" href="?view=items&act=item&id=${itemInfo.item_id}">
						${itemInfo.article_cat}
					</a>
				</td>
				<td label="Название">${itemInfo.title_full}</td>
				<td label="Штрих-код">${itemInfo.barcode}</td>
		`;
		if (type == 'analogies'){
			let checkedHidden = itemInfo.hidden == '1' ? 'checked' : '';
			let checkedChecked = itemInfo.checked == '1' ? 'checked' : '';
			let selected0 = itemInfo.status == '0' ? 'selected' : '';
			let selected1 = itemInfo.status == '1' ? 'selected' : '';
			let selected2 = itemInfo.status == '2' ? 'selected' : '';
			strHtml += `
				<td label="Статус">
					<form class="status">
						<input type="hidden" name="act" value="analogies">
						<input type="hidden" name="view" value="items">
						<input type="hidden" name="item_id" value="${$('#clearItemDiff').attr('item_id')}">
						<input type="hidden" name="item_diff" value="${itemInfo.item_id}">
						<select name="status">
							<option ${selected0} value="0">не выбрано</option>
							<option ${selected1} value="1">проверен</option>
							<option ${selected2} value="2">скрыт</option>
						</select>
					</form>
				</td>
			`;
		} 
		strHtml += `
				<td label="Категории">${itemInfo.categories}</td>
				<td label="">
					<a class="deleteItemDiff" href="act=deleteItemDiff&type=${type}&item_id=${$('input[name=item_id]').val()}&item_diff=${itemInfo.item_id}">Удалить</a>
				</td>
			</tr>
		`;
	})
	$('#itemDiff tr:not(.head)').remove();
	$('#itemDiff').append(strHtml);
}
function eventRemoveCategory(obj){
    obj.querySelector('.icon-cancel-circle1').addEventListener('click', event => {
        if (!confirm('Вы уверены?')) return;
        obj.closest('.category').remove();
    })
}
function eventChangeMainCategory(obj){
    const select = obj.querySelector('select.main_category');

    select.addEventListener('change', event => {
        showGif();

        const objects = obj.querySelectorAll('select[name="category_id[]"]');
        if (objects){
            objects.forEach((element, key) => {
                element.closest('div').querySelector('span').remove();
                element.remove();
            })
        }

        let formData = new FormData();
        formData.set('act', 'getSubCategory');
        formData.set('parent_id', select.value);

        fetch('/admin/ajax/item.php', {
            method: 'POST',
            body: formData
        }).then(response => response.text()).then(response => {
            select.insertAdjacentHTML('afterend', response);
            eventRemoveCategory(obj);
            showGif(false);
        })
    })
}
$(function(){
	let get = getParams();
	$('input.intuitive_search').on('keyup focus', function(e){
		e.preventDefault();
		let val = $(this).val();
		let minLength = 1;
		val = val.replace(/[^\wа-яА-Я]+/gi, '');
        delay(() => {
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
        }, 1000)
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
		var initials = [];
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
	$(document).on('click', 'input[name=hidden]', function(){
		var checked = $(this).is(':checked') ? 1 : 0;
		$.ajax({
			type: 'post',
			url: '/admin/ajax/item.php',
			data: 'act=analogy_hide&value=' + $(this).val() + '&item_id=' + $('input[name=item_id]').val() + '&hidden=' + checked,
			success: function(response){}
		})
	})
	$(document).on('click', 'input[name=checked]', function(){
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
	$('#clearItemDiff').on('click', function(e){
		e.preventDefault();
		if (!confirm('Подтверждаете действие?')) return false;
		let self = $(this);
		$.ajax({
			type: 'post',
			url: '/admin/ajax/item.php',
			data: {
				act: 'clearItemDiff',
				item_id: self.attr('item_id'),
				type: self.attr('type')
			},
			beforeSend: function(){
				showGif();
			},
			success: function(response){
				$('#itemDiff tr:not(.head)').remove();
				show_message('Успешно удалено!');
				showGif(false);
			}
		})
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
		$('li[big]').removeClass('main-photo').find('input[name*=is_main]').val(0);
		th.closest('li').addClass('main-photo').find('input[name*=is_main]').val(1);
	})
	$(document).on('change', '#loadPhoto', function(){
		$(this).closest('form').ajaxForm({
			target: '#modal_content',
			beforeSubmit: function(){
				showGif();
			},
			success: function(response){
				showGif(false);
				let image = document.getElementById('uploadedPhoto');
				let item_id = $('#item_id').val();
				let cropper = new Cropper(image, {
					autoCropArea: 1,
					aspectRatio: 0.8,
					cropBoxResizable: false
				});
				$('#modal-container').addClass('active').on('click', function(event){
					var t = $('#modal-container');
					if (t.is(event.target)){
			      	cropper.reset();
						$('#modal_content').empty();
						t.removeClass('active');
						$('#loadPhoto').closest('form').resetForm();
					} 
				})
				$('#savePhoto').on('click', function(){
					showGif();
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
									showGif(false);
									let images = JSON.parse(response);
									let count = $('#photos li').length;
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
	$(document).on('click', 'a.addItem', function(){
		let th = $(this);
		let addAllAnalogies = 0;
		if (th.attr('type') == 'analogies'){
			if (confirm('Выполнить действие для всех?')) addAllAnalogies = 1;
		}
		$.ajax({
			type: 'post',
			url: '/admin/ajax/item.php',
			beforeSend: function(){
				showGif();
				$('tr.empty').remove();
			},
			data: {
				act: 'addItem',
				type: th.attr('type'),
				item_id: $('input[name=item_id]').val(),
				item_diff: th.attr('item_id'),
				addAllAnalogies: addAllAnalogies
			},
			success: function(response){
				let items = JSON.parse(response);
				addItemDiffHtml(th.attr('type'), items);			
				showGif(false);
				th.closest('ul').find('li:first-child').addClass('active');
				th.closest('li').remove();
			}
		})
	})
	$(document).on('click', 'a.deleteItemDiff', function(e){
		e.preventDefault();
		if (!confirm('Удалить?')) return false;
		let addAllAnalogies = 0;
		let th = $(this);
		let data = getParams(th.attr('href'));
		if (data.type == 'analogies'){
			if (confirm('Выполнить действие для всех?')) data.addAllAnalogies = 1;
		}
		$.ajax({
			type: 'post',
			url: '/admin/ajax/item.php',
			data: data,
			beforeSend: function(){
				showGif();
			},
			success: function(response){
				let items = JSON.parse(response);
				addItemDiffHtml(data.type, items);
				showGif(false);
				th.closest('tr').remove();
				show_message('Успешно удалено!');
			}
		})
	})
	$(document).on('change', '#itemDiff select[name=status]', function(){
		$(this).closest('form').submit();
	})
  $(document).on('submit', 'form.status', function(e){
      e.preventDefault();
      const $form = $(this).closest('form');
      $.ajax({
          url: document.location.href,
          data: $form.serialize(),
          success: function(){
              $form.closest('tr').attr('class', 'analogyStatus_' + $form.find('select[name=status]').val());
              show_message('Успешно сохранено');
          }
      });
  })
  document.querySelector('#add_category').addEventListener('click', (event) => {
      showGif();
      event.preventDefault();
      let formData = new FormData();
      formData.set('act', 'getMainCategory');
      fetch('/admin/ajax/item.php', {
          method: 'post',
          body: formData
      }).then(response => response.text()).then(response => {
          let div = document.createElement('div');
          div.classList.add('category');
          div.innerHTML = response;
          document.querySelector('#categories').prepend(div);
          eventChangeMainCategory(div);
          showGif(false);
      })
  })

  const category = document.querySelectorAll('#categories div.category');
  if (category) category.forEach((element, key) => {
      eventRemoveCategory(element);
  })

  $('#add_to_ozon').on('click', e => {
      e.preventDefault()
      const item_id = document.querySelector('a.delete_item').getAttribute('item_id')
      let tahos_category_id = document.querySelector('select[name="category_id[]"]')

      if (!tahos_category_id){
          show_message('Укажите категорию!', 'error')
          return
      }

      Marketplaces.methods.ozon.showModal(item_id, 1, {
          tahos_category_id: tahos_category_id.value
      })
      Marketplaces.methods.ozon.setChosen('select[name="category_id"]')
      Marketplaces.methods.ozon.setChosen('select[name="tahos_category_id"]')
  })

})
