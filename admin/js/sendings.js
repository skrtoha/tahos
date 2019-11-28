(function($){
	window['sendings'] = {
		paginationContainer: $('#pagination-container'),
		head_common_list:
			'<tr class="head">' +
				'<th>№</th>' +
				'<th>Пользователь</th>' +
				'<th>Сумма</th>' +
				'<th>Дата</th>' +
				'<th>Статус</th>' +
			'</tr>',
		pageSize: 30,
		init: function(){
			var oi = this;
			if ($('#common_list').size()) this.common_list();
			$(document).on('click', 'tr[sending_id]', function(){
				document.location.href = '/admin/?view=sendings&id=' + $(this).attr('sending_id');
			})
			$('#sended').on('click', function(e){
				if (!confirm('Вы подтверждаете действие?')) e.preventDefault();
			})
		},
		common_list: function(){
			var oi = this;
			oi.paginationContainer.pagination({
				pageNumber: $('input[name=page]').val(),
				dataSource: document.location.href + '&act=common_list',
				className: 'paginationjs-small',
				locator: '',
				totalNumber: $('input[name=totalNumber]').val(),
				pageSize: oi.pageSize,
				ajax: {
					beforeSend: function(){}
				},
				callback: function(data, pagination){
					var str = oi.head_common_list;
					if (!Object.keys(data).length) str += 
						'<tr>' +
							'<td colspan="5">Ничего не найдено</td>' +
						'</tr>';
					else for(var key in data){
						var s = data[key];
						str += 
							'<tr class="' + s.is_new + '" sending_id="' + s.id + '">' +
								'<td>' + s.id + '</td>' +
								'<td>' + s.fio + '</td>' +
								'<td>' + s.sum + '</td>' +
								'<td>' + s.date + '</td>' +
								'<td>' + s.status + '</td>' +
							'</tr>'
					};
					$('#common_list').html(str);
					// console.log(data, pagination);
				}
			})
		},
	}
})(jQuery)
$(function(){
	sendings.init();
})