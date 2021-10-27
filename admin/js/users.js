(function($){
	window['user_order_add'] = {
		ajaxUrl: '/admin/ajax/user.php',
		mainSelector: '#user_order_add ',
		response: this.mainSelector + 'div.response',
		reg_int: /^\d+$/,
		items: {},
        pageSize: 30,

        head_common_list:
            '<tr class="head">' +
                '<th>Тип</th>' +
                '<th>Поиск</th>' +
                '<th>Дата</th>' +
            '</tr>',
        paginationContainer: $('#pagination-container'),
		init: function(){
			let uoa = this;
			if ($('#history_search').size()) this.history_search();
			$('#actions form').on('submit', function(e){
			    e.preventDefault();
			    let url = '/admin/?view=users&id=' + $('input[name=user_id]').val() + '&ajax=history_search_count';
			    let params = {};
			    let formData = $(this).serializeArray();
			    $.each(formData, function (i, item){
			        if (!item.value) return 1;
			        params[item.name] = item.value;
			        url += '&' + item.name + '=' + item.value;
                })
                $.ajax({
                    url: url,
                    type: 'get',
                    success: function(response){
                        $('input[name=totalNumber]').val(response);
                        user_order_add.history_search(params);
                    }
                })

            });
			$(uoa.getSelector('a.show_form_search')).on('click', function(e){
				e.preventDefault();
				$(this).nextAll('div.item_search').toggleClass('active');
			})
			$(document).on('click', uoa.getSelector('li a.resultItem'), function(e){
				e.preventDefault();
				uoa.getHtmlStores($(this).attr('item_id'));
			})
			$(uoa.getSelector('input[type=submit]')).on('click', function(){
				if ($(this).attr('is_draft')) $('input[name=is_draft]').val(1);
				else $('input[name=is_draft]').val(0);
			})
			$(uoa.getSelector('#added_items')).on('submit', function(e){
				var is_valid = true;
				$(this).find('input[name^=price]').each(function(){
					var val = $(this).val();
					if (!parseInt(val)) is_valid = false;
				})
				$(this).find('input[name^=quan]').each(function(){
					var val = $(this).val();
					if (!parseInt(val)) is_valid = false;
				})
				if (!is_valid){
					show_message('Произошла ошибка', 'error');
					e.preventDefault();
				} 
				else $(this).find('input').prop('disabled', false);
			})
			$(document).on('change', uoa.getSelector('select[name^=store_id]'), function(){
				var tr = $(this).closest('tr');
				var item_id = tr.attr('item_id');
				var store_id = $(this).val();
				var store = uoa.items[item_id].stores[store_id];
				if (typeof store !== 'undefined'){
					let markup = + $('input[name=markup]').val();
					let price = + store.price;
					price += Math.round(price * markup / 100);
					tr.find('input[name^=withoutMarkup]').val(store.price);
					tr.find('input[name^=price]')
						.val(price)
						.prop('disabled', true);
				}
				else{
					tr.find('input[name^=price]')
						.val(0)
						.prop('disabled', false);
				}
				uoa.setTotal();
			})
			$(document).on('change', uoa.getSelector('input[name^=price]'), function(){
				if (!uoa.reg_int.test($(this).val())) return show_message('Значение цены задано неккоректно!', 'error');
				uoa.setTotal();
			})
			$(document).on('change', uoa.getSelector('input[name^=quan]'), function(){
				if (!uoa.reg_int.test($(this).val())) return show_message('Значение количества задано неккоректно!', 'error');
				uoa.setTotal();
			})
			$(document).on('click', uoa.getSelector('span.icon-cancel-circle1'), function(e){
				e.preventDefault();
				if (!confirm('Вы действительно хотите удалить?')) return false;
				let item_id = $(this).closest('tr').attr('item_id');
				$(this).closest('tr').remove();
				if (!$(uoa.getSelector('tr.item')).size()) $(uoa.getSelector('tr.hiddable')).show();
				delete uoa.items[item_id];
				uoa.setTotal();
			})
			$('.users_box').on('click', function(){
				document.location.href = "?view=users&act=funds&id=" + $(this).attr('user_id');
			})
			$('a.return_money').on('click', function(e){
				e.preventDefault();
				let amount = + prompt('Введите сумму:');
				if (!amount) return false;
				if (!/\d+/.test(amount)) return show_message('Сумма указано неверно!', 'error');
				$.ajax({
					type: 'post',
					url: '/admin/ajax/user.php',
					data: {
						act: 'return_money',
						amount: amount,
						user_id: $('input[name=user_id]').val()
					},
					success: function(){
						let $obj = $('div.actions.users > span:nth-child(2) > b > span');
						let currentAmount = +$obj.html();
						$obj.html(currentAmount - amount);
						show_message('Успешно возвращено!');
					}
				})
			})
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
						act: 'items'
					},
					tableName: 'items',
				});
			});
		},
        history_search: function(params = {}){
            let uoa = this;
            let dataSource = '/admin/?view=users&id=' + $('input[name=user_id]').val() + '&ajax=history_search';
            if (params){
                for(let key in params){
                    if (!params[key]) continue;
                    dataSource += '&' + key + '=' + params[key];
                }
            }
            uoa.paginationContainer.pagination({
                pageNumber: $('input[name=page]').val(),
                dataSource: dataSource,
                className: 'paginationjs-small',
                locator: '',
                totalNumber: $('input[name=totalNumber]').val(),
                pageSize: uoa.pageSize,
                ajax: {
                    beforeSend: function(){
                        showGif();
                    }
                },
                callback: function(data, pagination){
                    var str = uoa.head_common_list;
                    let search;
                    for(var key in data){
                        var d = data[key];
                        if (d.item_id){
                            let href = `/admin/?view=items&act=item&id=${d.item_id}`;
                            search = '<a target="_blank" href="'+ href + '">' + d.search + '</a>';
                        }
                        else {
                            let vin = d.search.slice(0, 17);
                            let href = '/original-catalogs/legkovie-avtomobili#/carInfo?q=' + vin;
                            search = '<a target="_blank" href="'+ href + '">' + d.search + '</a>'
                        }
                        str +=
                            '<tr>' +
                                '<td>' + d.type + '</td>' +
                                '<td>' + search + '</td>' +
                                '<td>' + d.date + '</td>' +
                            '</tr>'
                    }
                    $('#history_search').html(str);
                    showGif(false);
                }
            })
        },
		getSelector: function(str){
			return this.mainSelector + str;
		},
		getTableRow: function(item_id, htmlStores){
			let item = this.items[item_id];
			str =
				'<tr class="item" item_id="' + item_id + '">' +
					'<td label="Поставищик">' + htmlStores +  '</td>' +
					'<td label="Бренд">' + item.brend + '</td>' +
					'<td label="Артикул">' + item.article + '</td>' +
					'<td label="Наименование">' + item.title_full + '</td>' +
					`<td label="Цена">
						<input value="0" type="hidden" name="withoutMarkup[${item_id}]">
						<input value="0" type="text" name="price[${item_id}]">
					</td>` +
					'<td label="Количество"><input value="1" type="text" name="quan[' + item_id + ']"></td>' +
					'<td label="Сумма"><span value="0" class="summ">0</span></td>' +
					'<td label="Комментарий"><textarea name="comment[' + item_id + ']"></textarea></td>' +
					`<td>
						<span class="icon-cancel-circle1 delete"></span>
					</td>` +
				'</tr>';
			return str;
		},
		getHtmlStores: function(item_id){
			let uoa = this;
			if (uoa.items[item_id] != undefined) return false;
			$.ajax({
				type: 'post',
				url: '/admin/ajax/store_item.php',
				data: {
					column: 'getStoreItemsByItemID',
					item_id: item_id,
                    user_id: $('div.value input[type=submit]').attr('user_id')
				},
				beforeSend: function(){
					showGif();
				},
				success: function(response){
					showGif(false);
					if (!response) return false;
					
					uoa.items[item_id] = JSON.parse(response);

					let htmlStores = '';
					if (uoa.items[item_id].stores != undefined){
						htmlStores = '<select name="store_id[' + item_id + ']">';
						htmlStores += '<option value="0">без поставщика</option>';
						$.each(uoa.items[item_id].stores, function(i, store){
							htmlStores += `<option value="${i}">${store.cipher} - (${store.price}р.)</option>`;
						})
						htmlStores += '</select>';
					}

					$('#added_items table .hiddable').hide();
					$('#added_items table tbody').append(uoa.getTableRow(item_id, htmlStores));
					$('#user_order_add .searchResult_list').hide();
					setTimeout(function(){
						$('#added_items table tr:last-child input[name^=price]').focus();
					}, 100);
				}
			})
		},
		setTotal: function(){
			var total = 0;
			$(this.getSelector('tr.item')).each(function(){
				var price = $(this).find('input[name^=price]').val();
				var quan = $(this).find('input[name^=quan]').val();
				$(this).find('span.summ').text(price * quan);
				total += price * quan;
			})
			$(this.getSelector('span.total')).text(total);
		}
	}
	user_order_add.init();
})(jQuery)
