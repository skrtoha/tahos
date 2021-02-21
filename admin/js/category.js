(function($){
	window['category'] = {
		init: function(){
			$(document).on('change', 'select[name=isShowOnMainPage]', function(){
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
							// console.log(msg);
							var res = JSON.parse(msg);
							if (res.error) show_message(res.error, 'error');
							else{
								$('[colspan=4]').remove();
								var str = '<tr class="subcategory">' +
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
										<select name="isShowOnMainPage">
											<option selected value="0">нет</option>
											<option value="1">да</option>
										</select>
									</form>
								</td>` +
								'<td>' + 
									'<a href="?view=category&act=items&id=' + res.category_id + '">Товаров (0)</a> ' + 
									'<a href="?view=category&act=filters&id=' + res.category_id + '">Фильров (1)</a>' +
								'</td>' +
								'<td>' + 
									'<a class="delete_item" href="?view=category&act=delete&id=' + res.id + '&parent_id=' + parent_id + '">Удалить</a>' + 
								'</td>' +
								'</tr>';
								$('.t_table').append(str);
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
		}
	}
})(jQuery)
$(function(){
	category.init();
})