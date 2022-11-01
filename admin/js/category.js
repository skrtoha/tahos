(function($){
	window['category'] = {
		init: function(){
			$(document).on('change', 'input[name=isShowOnMainPage]', function(){
				$(this).closest('form').submit();
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
						beforeSend: function(){
							showGif();
			  			},
						success: function(msg){
							var res = JSON.parse(msg);
							if (res.error) show_message(res.error, 'error');
							else{
								$('[colspan=4]').remove();
                                let tr = document.createElement('tr');
                                tr.classList.add('subcategory');
                                tr.setAttribute('data-id', res.category_id);
								tr.innerHTML =
								'<td title="Нажмите, чтобы изменить" class="category" data-id="' + res.id + '">' + 
									res.title +
								'</td>' + 
								'<td class="pos" data-id="' + res.id + '">0</td>' + 
								'<td title="Нажмите, чтобы изменить" class="href" data-id="' + res.id + '">' +
									res.href +
								'</td>' + 
								`<td>
									<form>
										<input type="hidden" name="view" value="category">
										<input type="hidden" name="act" value="changeIsShowOnMainPage">
										<input type="hidden" name="id" value="${res.id}">
										<input type="checkbox" name="isShowOnMainPage" value="1">
									</form>
								</td>` +
                                `<td>
								    <input type="checkbox" name="hidden" value="1">
                                </td>` +
								'<td>' + 
									'<a href="?view=category&act=items&id=' + res.category_id + '">Товаров (0)</a> ' +
									'<a href="?view=category&act=filters&id=' + res.category_id + '">Фильров (1)</a> ' +
									'<a href="?view=category&id=' + res.category_id + '">Подкатегории (0)</a>' +
								'</td>' +
								'<td>' + 
									'<a class="delete_category" href="?view=category&act=delete&id=' + res.id + '&parent_id=' + parent_id + '">Удалить</a>' +
								'</td>' +
								'</tr>';
                                document.querySelector('.t_table').append(tr);
                                category.eventDeleteItem(tr);
                                category.eventChangeHidden(tr);
								show_message("Подкатегория '" + new_value + "' успешно добавлена!");
							}
							showGif(false);
						}
					})
				}
			})
			$(document).on('click', '.subcategory td.href, .subcategory td.category, .subcategory td.pos', function(){
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
			$('#add_filter_value').on('click', function(e){
				e.preventDefault();
				var title = prompt('Введите название нового свойства:');
				if (!title) return false;
				var elem = $(this);
				category.sendAjaxAddFilterValue({
					filter_id: $('input[name=filter_id]').val(),
					title: title
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
			$('input.intuitive_search').on('keyup focus', function(e){
				e.preventDefault();
				let val = $(this).val();
				let minLength = 1;
				intuitive_search.getResults({
					event: e,
					value: val,
					minLength: minLength,
					tableName: 'brends',
				});
			});
			$(document).on('click', 'a.resultBrend', function(){
				let th = $(this);
				let title = th.text();
				category.sendAjaxAddFilterValue({
					filter_id: $('input[name=filter_id]').val(),
					title: title.trim()
				});
			})

            const delete_item = document.querySelectorAll('.delete_category');
            if (delete_item){
                delete_item.forEach((element, key) => {
                    this.eventDeleteItem(element.closest('tr'));
                })
            }

            const hidden = document.querySelectorAll('input[name=hidden]');
            if (hidden){
                hidden.forEach((element, key) => {
                    this.eventChangeHidden(element.closest('tr'));
                })
            }
		},
        eventChangeHidden: obj => {
            const selector = obj.querySelector('input[name=hidden]');
            selector.addEventListener('change', e => {
                let formData = new FormData();
                formData.set('act', 'setHidden');
                formData.set('id', obj.getAttribute('data-id'));
                formData.set('hidden', selector.checked ? 1 : 0);
                fetch('/admin/ajax/item.php', {
                    method: 'post',
                    body: formData
                }).then(response => response.text()).then(response => {})
            })
        },
		sendAjaxAddFilterValue: function(obj){
			$.ajax({
				type: "POST",
				url: "/ajax/add_filter_value.php",
				data: 'filter_id=' + obj.filter_id + '&title=' + obj.title,
				success: function(msg){
					if (!msg) document.location.reload();
					else show_message(msg, 'error');
				}
			})
		},
        eventDeleteItem: function(obj){
            obj.querySelector('.delete_category').addEventListener('click', e => {
                e.preventDefault();

                if (!confirm('Действительно удалить?')) return;

                let formData = new FormData;
                formData.set('act', 'deleteCategory');
                formData.set('id', obj.getAttribute('data-id'));
                showGif();
                fetch('/admin/ajax/item.php', {
                    method: 'post',
                    body: formData
                }).then(response => response.text()).then(response => {
                    obj.remove();
                    showGif(false);
                })
            })
        }
	}
})(jQuery)
$(function(){
	category.init();
})