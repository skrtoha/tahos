(function($){
	window['add_item_to_store'] = {
		init: function(){
			$(document).on('click', 'a.add_item_to_store', function(e){
				e.preventDefault();
				let th = $(this);
				let tr = th.closest('tr');
				let item_id = th.attr('item_id');
				let store_id = th.attr('store_id');
				let str =
					'<form class="add_item_to_store">' +
						'<input name="store_id" value="' + store_id + '" type="hidden">' +
						'<input name="item_id" value="' + item_id + '" type="hidden">' +
						'<table>' +
						 	'<tr>' +
							 	'<td>Бренд:</td>' +
							 	'<td>' + tr.find('td:nth-child(1)').text() + '</td>' +
						 	'</tr>' +
						 	'<tr>' +
							 	'<td>Артикул:</td>' +
							 	'<td>' + tr.find('td:nth-child(2)').text() + '</td>' +
						 	'</tr>' +
						 	'<tr>' +
							 	'<td>Название:</td>' +
							 	'<td>' + tr.find('td:nth-child(3)').text() + '</td>' +
						 	'</tr>' +
					 		'<tr>' +
							 	'<td>Цена:</td>' +
							 	'<td>' +
							 		'<input name="price" value="" type="text">' +
							 	 '</td>' +
						 	'</tr>' +
						 	'<tr>' +
							 	'<td>В наличии:</td>' +
							 	'<td>' +
							 		'<input name="in_stock" type="text">' +
							 	 '</td>' +
						 	'</tr>' +
						 	'<tr>' +
							 	'<td>Мин. заказ:</td>' +
							 	'<td>' +
							 		'<input name="packaging" value="1" type="text">' +
							 	 '</td>' +
						 	'</tr>';
					if (store_id == '23') str +=
							'<tr>' +
							 	'<td>Минимальное наличие:</td>' +
							 	'<td>' +
							 		'<input name="requiredRemain" value="1" type="text">' +
							 	 '</td>' +
						 	'</tr>';
					str +=
							'<tr>' +
							 	'<td colspan="2">' +
							 		'<input value="Добавить" type="submit">' +
							 	 '</td>' +
						 	'</tr>';
						'</table>' +
					'</form>';
				modal_show(str);
			})
			$(document).on('submit', 'form.add_item_to_store', function(e){
				e.preventDefault(e);
				let th = $(this);
				let output = new Object();
				let isValidated = true;
				output.column = 'add_item_to_store';
				let formData = th.serializeArray();
				$.each(formData, function(i, d){
					if (!d.value){
						show_message('Все поля формы должны быть заполнены!', 'error');
						isValidated = false;
						return 0;
					}
					output[d.name] = d.value;
				})
				if (!isValidated) return false;
				console.log(output);
				$.ajax({
					type: 'post',
					url: '/admin/ajax/store_item.php',
					data: output,
					success: function(response){
						document.location.href = '/admin/?view=prices&act=items&id=' + output.store_id;
					}
				})
			})
		}
	}
})(jQuery)
$(function(){
	add_item_to_store.init();
})