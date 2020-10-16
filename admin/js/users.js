(function($){
	window['user_order_add'] = {
		ajaxUrl: '/admin/ajax/user.php',
		mainSelector: '#user_order_add ',
		response: this.mainSelector + 'div.response',
		reg_int: /^\d+$/,
		items: null,
		init: function(){
			var uoa = this;
			$(uoa.getSelector('a.show_form_search')).on('click', function(e){
				e.preventDefault();
				$(this).nextAll('div.item_search').toggleClass('active');
			})
			$(uoa.getSelector('form[name=search_items]')).on('submit', function(e){
				e.preventDefault();
				$(uoa.getSelector('#found_items')).empty();
				var th = $(this);
				$.ajax({
					method: 'post',
					url: uoa.ajaxUrl,
					data: th.serialize() + '&act=item_search',
					success: function(response){
						// console.log(response); return false;
						if (!response) {
							$(uoa.response).html('Поиск не дал результатов');
							return false;
						}
						uoa.items = JSON.parse(response);
						var str = ''
						for (id in uoa.items){
							var i = uoa.items[id];
							str +=
								'<li>' +
									'<a item_id="' + id + '" class="item" title="Кликните для применения">' +
										i.brend + ' - ' + i.article + ' - ' + i.title_full +
									'</a>' +
								'</li>';
						}
						$(uoa.getSelector('ul.found_items')).html(str);
					}
				});
			}),
			$(document).on('click', uoa.getSelector('li a.item'), function(e){
				var item_id = $(this).attr('item_id');
				$(uoa.getSelector('tr[item_id=' + item_id + ']')).remove();
				$(uoa.getSelector('form.added_items table tr.hiddable'))
					.hide()
					.after(uoa.getTableRow($(this).attr('item_id')));
			})
			$(uoa.getSelector('input[type=submit]')).on('click', function(){
				if ($(this).attr('is_draft')) $('input[name=is_draft]').val(1);
				else $('input[name=is_draft]').val(0);
			})
			$(uoa.getSelector('form.added_items')).on('submit', function(e){
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
					tr.find('input[name^=price]')
						.val(store.price)
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
				// console.log(uoa.reg_int, $(this).val(), uoa.reg_int.test($(this).val()));
				if (!uoa.reg_int.test($(this).val())) return show_message('Значение цены задано неккоректно!', 'error');
				uoa.setTotal();
			})
			$(document).on('change', uoa.getSelector('input[name^=quan]'), function(){
				if (!uoa.reg_int.test($(this).val())) return show_message('Значение количества задано неккоректно!', 'error');
				uoa.setTotal();
			})
			$(document).on('click', uoa.getSelector('a.delete'), function(e){
				e.preventDefault();
				if (!confirm('Вы действительно хотите удалить?')) return false;
				else $(this).closest('tr').remove();
				if (!$(uoa.getSelector('tr.item')).size()) $(uoa.getSelector('tr.hiddable')).show();
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
						$obj.html(currentAmount + amount);
						show_message('Успешно возвращено!');
					}
				})
			})
		},
		getSelector: function(str){
			return this.mainSelector + str;
		},
		getTableRow: function(item_id){
			var count = $(this.getSelector('form.added_items tr.item')).size();
			var i = this.items[item_id];
			str =
				'<tr class="item" item_id="' + item_id + '">' +
					'<td label="Поставищик">' + this.getHtmlStores(item_id) +  '</td>' +
					'<td label="Бренд">' + i.brend + '</td>' +
					'<td label="Артикул">' + i.article + '</td>' +
					'<td label="Наименование">' + i.title_full + '</td>' +
					'<td label="Цена"><input value="0" type="text" name="price[' + item_id + ']"</td>' +
					'<td label="Количество"><input value="1" type="text" name="quan[' + item_id + ']"</td>' +
					'<td label="Сумма"><span value="0" class="summ">0</span></td>' +
					'<td label="Комментарий"><textarea name="comment[' + item_id + ']"></textarea></td>' +
					'<td><a href="#" class="delete">Удалить</a></td>' +
				'</tr>';
			return str;
		},
		getHtmlStores: function(item_id){
			if (typeof this.items[item_id].stores === undefined) return;
			str = '<select name="store_id[' + item_id + ']">';
			str += '<option value="0">без поставщика</option>'
			for (var id in this.items[item_id].stores){
				var store = this.items[item_id].stores[id];
				str +=
					'<option value="' + id + '">' + store.title + '</option>';
			} 
			str += '</select>';
			return str;
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
