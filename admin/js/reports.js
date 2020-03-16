var tab;
$(function(){
	window['reports'] = {
		tab: null,
		ajaxUrl: '/admin/?view=reports',
		init: function(){
			this.setTabs();
			$(document).on('click', 'a.clearLog', function(e){
				e.preventDefault();
				var th = $(this);
				if (!confirm('Вы уверены?')) return false;
				$.ajax({
					type: 'post',
					url: reports.ajaxUrl,
					data: {
						tab: reports.tab,
						act: 'clear'
					},
					success: function(response){
						// console.log(response); return false;
						$('div[data-name=' + reports.tab + '] tr:not(.head)').remove();
						$('div[data-name=' + reports.tab + '] table').append(
							'<tr>' +
								'<td colspan="4">Ничего не найдено</td>' +
							'<tr>'
						);
					}
				})
			})
			$(document).on('submit', 'form', function(e){
				e.preventDefault();
				var data = $(this).serializeArray();
				$.ajax({
					type: 'post',
					url: reports.ajaxUrl,
					data: {
						tab: reports.tab,
						act: 'hide',
						values: data
					},
					success: function(response){
						for(var k in data) $('input[name=' + data[k].name + '][value=' + data[k].value + ']').remove();
					}
				})
			})
			$(document).on('click', '.removeWrongAnalogy', function(e){
				e.preventDefault();
				if (!confirm('Вы уверены?')) return false;
				var th = $(this);
				$.ajax({
					method: 'post',
					url: reports.ajaxUrl,
					data:{
						tab: reports.tab,
						act: 'removeWrongAnalogy',
						item_id: th.attr('item_id'),
						item_diff: th.attr('item_diff')
					},
					success: function(response){
						// console.log(response); return false;
						th.closest('tr').remove();
					}
				})
			})
			$(document).on('click', '.clear_request_delete_item', function(e){
				if (!confirm('Уверены, что хотите очистить список?')) return false;
				$.ajax({
					url: reports.ajaxUrl,
					type: 'post',
					data: {
						tab: 'clear_request_delete_item'
					},
					success: function(){
						document.location.reload();
					}
				})
				return false;
			})
			$(document).on('click', '.icon-cross1', function(){
				if (!confirm('Подтвердить удаление? Внимание! Номенклатура будет удалена окончательно!')) return false;
				var th = $(this);
				var user_id = th.closest('tr').attr('user_id');
				var item_id = th.closest('tr').attr('item_id');
				$.ajax({
					url:reports.ajaxUrl,
					type: 'post',
					data: {
						tab: 'delete_item',
						user_id: user_id,
						item_id: item_id
					},
					success: function(){
						th.closest('tr').remove();
						show_message('Успешно удалено!');
					}
				})
			})
		},
		setTabs: function(){
			$.ionTabs("#tabs_1",{
				type: "hash",
				onChange: function(obj){
					reports.tab = obj.tab;
					var str = '/admin/?view=reports&tab=' + obj.tab;
					str += '#tabs|reports:' + obj.tab;
					$.ajax({
						type: 'post',
						url: reports.ajaxUrl,
						data: {
							tab: obj.tab,
						},
						success: function(response){
							// console.log(response); return false;
							$('div[data-name=' + obj.tab + ']').html(response);
						}
					});
					window.history.pushState(null, null, str)
				}
			});
		}
	}
	reports.init();
})
