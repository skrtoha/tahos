(function($){
	window['order_issues'] = {
		paginationContainer: $('#pagination-container'),
		head_common_list:
			'<tr class="head">' +
				'<th>№</th>' +
				'<th>Пользователь</th>' +
				'<th>Сумма</th>' +
				'<th>Дата</th>' +
			'</tr>',
		pageSize: 30,
		init: function(){
			var oi = this;
			if ($('#common_list').size()) this.common_list();
			if ($('#user_issue_values').size()) this.user_issue_values();
			
			// $('#user_order_issues input[type=checkbox]').on('change', function(){
			// 	var th = $(this);
			// 	if (th.attr('name') == 'all') return oi.checkAll(th);
			// 	if (th.is(':checked')){
			// 		if (th.val() > 1){
			// 			var new_value = prompt('Введите значение для выдачи', th.val());
			// 			if (new_value === null){
			// 				th.prop('checked', false);
			// 				return false;
			// 			}
			// 			th.val(new_value);
			// 		}
			// 	}
			// 	else{
			// 		th.val(th.prev().val());
			// 	} 
			// })
			$(document).on('click', 'tr[issue_id]', function(){
				document.location.href = '/admin/?view=order_issues&issue_id=' + $(this).attr('issue_id') + 
					'&page=' + oi.paginationContainer.pagination('getSelectedPageNum');
			})
		},
		checkAll: function(obj){
			if (obj.is(':checked')){
				$('input[name^=income]').each(function(){
					$(this)
						.prop('checked', true)
						.val($(this).prev().val());
				});
			} 
			else $('input[name^=income]').prop('checked', false);
		},
		common_list: function(){
			var oi = this;
			oi.paginationContainer.pagination({
				pageNumber: $('input[name=page]').val(),
				dataSource: '/admin/?view=order_issues&ajax=common_list',
				className: 'paginationjs-small',
				locator: '',
				totalNumber: $('input[name=totalNumber]').val(),
				pageSize: oi.pageSize,
				ajax: {
					beforeSend: function(){}
				},
				callback: function(data, pagination){
					var str = oi.head_common_list;
					for(var key in data){
						var d = data[key];
						str += 
							'<tr issue_id="' + d.issue_id + '">' +
								'<td>' + d.issue_id + '</td>' +
								'<td>' + d.user + '</td>' +
								'<td>' + d.sum + '</td>' +
								'<td>' + d.created + '</td>' +
							'</tr>'
					};
					$('#common_list').html(str);
					// console.log(data, pagination);
				}
			})
		},
		user_issue_values: function(){
			var oi = this;
			oi.paginationContainer.pagination({
				pageNumber: $('input[name=page]').val(),
				dataSource: '/admin/?view=order_issues&ajax=user_issue_values&user_id=' + $('input[name=user_id]').val(),
				className: 'paginationjs-small',
				locator: '',
				totalNumber: $('input[name=totalNumber]').val(),
				pageSize: oi.pageSize,
				ajax: {beforeSend: function(){}},
				callback: function(data, pagination){
					console.log(pagination);
					var str = 
						'<tr class="head">' +
							'<th>№ выдачи</th>' +
							'<th>№ заказа</th>' +
							'<th>Бренд</th>' +
							'<th>Артикул</th>' +
							'<th>Выдано</th>' +
							'<th>Коментарий</th>' +
						'</tr>';
					for(var key in data){
						var d = data[key];
						str += 
							'<tr issue_value="' + d.issue_id + ':' + d.order_id + ':' + d.item_id + '">' +
								'<td>' +
									'<a href="/admin/?view=order_issues&issue_id=' + d.issue_id + '">' + d.issue_id + '</td>' +
								'<td>' +
									'<a href="/admin/?view=orders&id=' + d.order_id + '&act=change">' + d.order_id + '</a>' +
								'</td>' +
								'<td>' + d.brend + '</td>' +
								'<td>' +
									'<a href="http://tahos/admin/?view=item&id=' + d.item_id + '">' + d.article + '</a>' +
								'</td>' +
								'<td>' + d.issued + '</td>' +
								'<td>' + d.comment + '</td>' +
							'</tr>'
					};
					$('#user_issue_values').html(str);
					// console.log(data, pagination);
				}
			})
		},
	}
})(jQuery)
$(function(){
	order_issues.init();
})