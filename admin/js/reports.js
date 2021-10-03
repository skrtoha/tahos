var tab;
$(function(){
	window['reports'] = {
		tab: null,
		ajaxUrl: '/admin/?view=reports',
		init: function(){
			if (!window.location.hash){
				get = getParams();
				window.history.pushState(null, null,  '/admin/?view=reports&tab=' + get.tab + '#tabs|reports:' + get.tab)
			}
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
			$(document).on('submit', '[data-name=nomenclature] form', function(e){
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
				if (!confirm('Подтвердить удаление?')) return false;
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
            $(document).on('submit', 'form.filter-form', function(e){
                e.preventDefault();
                let th = $(this);
                tab = th.closest('[data-name]').attr('data-name');
                reports.processAjaxQuery(tab);
            })
		},
        processAjaxQuery: function(tab){
		    const form = $('div[data-name=' + tab + ']').find('form.filter-form');
		    let data = {};
		    data.tab = tab;
            $.each(form.serializeArray(), function(i, item){
                if (!item.value) return 1;
                data[item.name] = item.value;
            })
            $.ajax({
                type: 'post',
                url: reports.ajaxUrl,
                data: data,
                success: function(response){
                    $('div[data-name=' + tab + '] table tbody').empty();
                    if (!response) return false;
                    var items = JSON.parse(response);
                    reports.parsePurchaseability(items, tab);
                }
            });
        },
		setDateTimePicker: function(tab){
			$.datetimepicker.setLocale('ru');
			$('[data-name=' + tab + '] .datetimepicker[name=dateFrom], [data-name=' + tab + '] .datetimepicker[name=dateTo]').datetimepicker({
				format:'d.m.Y H:i',
				closeOnDateSelect: true,
				closeOnWithoutClick: true
			});
		},
		setTabs: function(){
			$.ionTabs("#tabs_1", {
				type: "hash",
				onChange: function(obj){
					reports.tab = obj.tab;
					reports.setDateTimePicker(reports.tab);
					if (obj.tab == 'searchHistory' || obj.tab == 'purchaseability') return reports.processAjaxQuery(obj.tab);
					let data = {
                        tab: obj.tab,
                        dateFrom: $('div[data-name=' + obj.tab + '] input[name=dateFrom]').val(),
                        dateTo: $('div[data-name=' + obj.tab + '] input[name=dateTo]').val()
                    }
					$.ajax({
						type: 'post',
						url: reports.ajaxUrl,
						data: data,
						success: function(response){
							switch(obj.tab){
								case 'remainsMainStore':
									let itemRemains = JSON.parse(response);
									$('[data-name=' + obj.tab + '] table tbody').empty();
									$.each(itemRemains, function(i, item){
										$('[data-name=' + obj.tab + '] table tbody').append(
											'<tr>' +
												'<td>' + item.brend + '</td>' +
												'<td><a target="_blank" href="?view=items&act=item&id=' + item.id + '">' + item.article + '</a></td>' +
												'<td>' + item.title_full + '</td>' +
												'<td>' + item.in_stock + '</td>' +
											'</tr>'
										);
									})
									break;
								default:
									$('div[data-name=' + obj.tab + ']').html(response);
							}
						}
					});
					// window.history.pushState(null, null, str)
				}
			});
		},
		parsePurchaseability: function(items, tab){
			if (!items) return false;
			switch(tab){
				case 'purchaseability':
					for(var key in items){
						$('[data-name=purchaseability] table tbody').append(
							'<tr>' +
								'<td>' + items[key].brend + '</td> ' +
								'<td>' + items[key].article + '</td> ' +
								'<td>' + items[key].title_full + '</td> ' +
								'<td>' + items[key].purchases + '</td> ' +
								'<td>' + items[key].tahos_in_stock + '</td> ' +
							'</tr>'
						);
					}
					break;
				case 'searchHistory':
					$.each(items, function(i, item){
						$('[data-name=searchHistory] table tbody').append(`
							<tr>
								<td>
									<a target="_blank" href="/admin/?view=items&id=${item.item_id}&act=item">${item.brend_article}</a>
								</td>
								<td>${item.count}</td>
						`);
					})
					break;
			}
		},
		remainsMainStore: {
			selector: '',
			get: function(){
				$.ajax({
					type: 'post',
					url: reports.ajaxUrl,
					data: {
						tab: 'remainsMainStore',
					},
					success: function(response){
						
					}
				})
			}
		}
	}
	reports.init();
})
