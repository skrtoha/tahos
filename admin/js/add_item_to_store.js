(function($){
	window['add_item_to_store'] = {
		storeInfo: {
			store_id: '',
			item_id: '',
			brend: '',
			article: '',
			title: '',
			price: '',
			in_stock: '',
			packaging: 1,
			requiredRemain: 1
		},
		isValidated: true,
		getHtmlForm: function(storeInfo){
			let str =
				'<form class="add_item_to_store">' +
					'<input name="store_id" value="' + storeInfo.store_id + '" type="hidden">' +
					'<input name="item_id" value="' + storeInfo.item_id + '" type="hidden">' +
					'<table>' +
					 	'<tr>' +
						 	'<td>Бренд:</td>' +
						 	'<td>' + storeInfo.brend + '</td>' +
					 	'</tr>' +
					 	'<tr>' +
						 	'<td>Артикул:</td>' +
						 	`<td>
						 		<a target="_blank" href="/admin/?view=items&act=item&id=${storeInfo.item_id}">${storeInfo.article}</a>
						 	</td>` +
					 	'</tr>' +
					 	'<tr>' +
						 	'<td>Название:</td>' +
						 	'<td>' + storeInfo.title_full + '</td>' +
					 	'</tr>' +
				 		'<tr>' +
						 	'<td>Цена:</td>' +
						 	'<td>' +
						 		'<input name="price" value="' + storeInfo.price + '" type="text">' +
						 	 '</td>' +
					 	'</tr>' +
					 	'<tr>' +
						 	'<td>В наличии:</td>' +
						 	'<td>' +
						 		'<input name="in_stock" type="text" value="' + storeInfo.in_stock + '">' +
						 	 '</td>' +
					 	'</tr>' +
					 	'<tr>' +
						 	'<td>Мин. заказ:</td>' +
						 	'<td>' +
						 		'<input name="packaging" value="' + storeInfo.packaging + '" type="text">' +
						 	 '</td>' +
					 	'</tr>';
				if (storeInfo.store_id == '23') str +=
						'<tr>' +
						 	'<td>Минимальное наличие:</td>' +
						 	'<td>' +
						 		'<input name="requiredRemain" value="' + storeInfo.requiredRemain + '" type="text">' +
						 	 '</td>' +
					 	'</tr>';
				str +=
						'<tr>' +
						 	'<td colspan="2">' +
						 		'<input value="Сохранить" type="submit">' +
						 	 '</td>' +
					 	'</tr>';
					'</table>' +
				'</form>';
			return str;
		},
		init: function(){
			let self = this;
			$(document).on('click', 'a.add_item_to_store', function(e){
				e.preventDefault();
				let th = $(this);
				let tr = th.closest('tr');
				let storeInfo = self.storeInfo;
				storeInfo.item_id = th.attr('item_id');
				storeInfo.store_id = th.attr('store_id');
				storeInfo.brend = tr.find('td:nth-child(1)').text();
				storeInfo.article = tr.find('td:nth-child(2)').text();
				storeInfo.title = tr.find('td:nth-child(3)').text();
				modal_show(self.getHtmlForm(storeInfo));
			})
			$(document).on('submit', 'form.add_item_to_store', function(e){
				e.preventDefault(e);
				let th = $(this);
				let output = new Object();
				output.column = 'add_item_to_store';
				self.isValidated = true;
				let formData = th.serializeArray();
				$.each(formData, function(i, d){
					if (!d.value){
						show_message('Все поля формы должны быть заполнены!', 'error');
						self.isValidated = false;
						return 0;
					}
					output[d.name] = d.value;
				})
				if (!self.isValidated) return false;
				$.ajax({
					type: 'post',
					url: '/admin/ajax/store_item.php',
					data: output,
					success: function(response){
						console.log(output);
						$('input[column=packaging][item_id=' + output.item_id + ']').val(output.packaging);
						$('input[column=price][item_id=' + output.item_id + ']').val(output.price);
						$('input[column=in_stock][item_id=' + output.item_id + ']').val(output.in_stock);
						$('input[column=requiredRemain][item_id=' + output.item_id + ']').val(output.requiredRemain);
						$('#contents > table > tbody > tr:nth-child(2) > td:nth-child(7)')
						$('input[column=requiredRemain][item_id=' + output.item_id + ']').closest('tr').find('td:nth-child(7)').text(output.in_stock * output.price);
						$('#modal-container').removeClass('active');
						$('.searchResult_list').hide();
						$('input.intuitive_search').val('');
					}
				})
			})
		}
	};
})(jQuery)
$(function(){
	add_item_to_store.init();
})