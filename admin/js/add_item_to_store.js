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
		getHtmlForm: function(storeInfo, main_store = {}){
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
						 		'<input name="price" value="' + storeInfo.priceWithoutMarkup + '" type="text">' +
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
                if (storeInfo.store_id == 23){
                    str +=
                        '<tr>' +
                            '<td>Минимальное наличие:</td>' +
                            '<td>' +
                                '<input name="requiredRemain" value="' + storeInfo.requiredRemain + '" type="text">' +
                            '</td>' +
                        '</tr>';
                }
				if (typeof storeInfo.providerList !== 'undefined'){
                    let providerString = '';
                    $.each(storeInfo.providerList, function(i, item){
                        let selected = '';
                        if (storeInfo.main_store !== null && item.id == storeInfo.main_store.provider_id){
                            selected = 'selected';
                            main_store.provider = item.title;
                            main_store.provider_id = item.id;
                        }
                        providerString += `<option ${selected} value="${item.id}">${item.title}</option>`;
                    })
                    str += `
                        <tr class="provider">
                            <td>Поставщик:</td>
                            <td>
                                <select id="provider_id">
                                    <option value="">...выберите</option>
                                    ${providerString}
                                </select>
                            </td>
                        </tr>`;

                    if (typeof storeInfo.providerStoreList !== 'undefined'){
                        let providerStoreString = '';
                        $.each(storeInfo.providerStoreList, function(i, item){
                            let selected = '';
                            if (item.id == storeInfo.main_store.store_id){
                                selected = 'selected';
                                main_store.store_id = item.id;
                                main_store.store = item.cipher + '-' + item.title;
                            }
                            providerStoreString += `<option ${selected} value="${item.id}">${item.cipher}-${item.title}</option>`;
                        })

                        str += `
                            <tr class="provider_store">
                                <td>Склад:</td>
                                <td>
                                    <select name="main_store_id">
                                        <option value="">...выберите</option>
                                        ${providerStoreString}
                                    </select>
                                </td>
                            </tr>`;
                    }
                }

				str +=
                    '<tr>' +
                        '<td colspan="2">' +
                            '<input value="Сохранить" type="submit">';
				if (storeInfo.price) str += `
                            <a class="deleteStoreItem" onClick="return false;"  href="#" item_id="${storeInfo.item_id}">
                                Удалить
                            </a>`;
				str +=
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
				let output = {};
				output.column = 'add_item_to_store';
				self.isValidated = true;
				let formData = th.serializeArray();
				$.each(formData, function(i, d){
					if (!d.value ){
						show_message('Все поля формы должны быть заполнены!', 'error');
						self.isValidated = false;
						return 0;
					}
					output[d.name] = d.value;
				})
				if (!self.isValidated) return false;

                output.is_main = $('table[store_id]').attr('store_id') === '23' ? 1 : 0;

				$.ajax({
					type: 'post',
					url: '/admin/ajax/store_item.php',
					data: output,
					success: function(response){
						$('input[column=packaging][item_id=' + output.item_id + ']').val(output.packaging);
						$('input[column=price][item_id=' + output.item_id + ']').val(output.price);
						$('input[column=in_stock][item_id=' + output.item_id + ']').val(output.in_stock);
						$('input[column=requiredRemain][item_id=' + output.item_id + ']')
                            .val(output.requiredRemain)
						    .closest('tr').find('td:nth-child(7)')
                            .text(output.in_stock * output.price);
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