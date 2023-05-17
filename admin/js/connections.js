(function($){
	window['connections'] = {
		tab: null,
		pageSize: 30,
		commonListFilters: [],
		commonListPageNumber: 1,
		init: function(){
			connections.commonListFilters.isHiddenAdminPages = 1;
			window.connections.setTabs();
			$(document).on('keyup change', 'div[data-name=common_list] .filter', function(e){
				if (e.type == 'keyup' && e.keyCode != 13) return false;
				connections.commonListFilters = [];
				if ($(this).attr('name') == 'manager_id') {
					$(this).closest('.actions').find('input[name=isHiddenAdminPages]').prop('checked', false);
				}
				$('div[data-name=common_list] .filter').each(function(){
					var th = $(this);
					switch(th.attr('name')){
						case 'isHiddenAdminPages':
							if (th.is(':checked')) connections.commonListFilters.isHiddenAdminPages = 1;
							break;
						default:
							if (th.val()) connections.commonListFilters[th.attr('name')] = th.val();
					}
				});
				$('#common_list tbody').empty();
				connections.common_list();
			})
			$(document).on('click', '#add_denied_addresses, #add_allowed_ip', function(e){
				let text = prompt('Введите ip-адрес:');
				if (!text) return false;
				connections.add_ip(text, e.target.id);
				return false;
			})
			$(document).on('click', 'span.denied_addresses, span.allowed_ip', function(e){
				if (!confirm('Вы действительно хотите удалить?')) return false;
				let $th = $(e.target);
				let classText = $th.attr('class');
				classText = classText.replace(' icon-cross1', '');
				classText = classText.trim();
				$.ajax({
					type: 'get',
					url: '/admin/?view=connections&tab=remove_' + classText,
					data: {
						text: $th.text()
					},
					success: function(response){
						$th.remove();
						show_message('Успешно удалено!');
					}
				})
			})
			$(document).on('click', '#add_forbidden_page', function(){
				var page = prompt('Введите страницу:');
				if (!page) return false;
				$.ajax({
					type: 'get',
					url: '/admin/?view=connections&tab=add_forbidden_page',
					data: {
						page: page
					},
					success: function(response){
						if (response != 'ok') return show_message(response, 'error');
						$('div[data-name=forbidden_pages]').append(
							'<span class="forbidden_page icon-cross1">' + page + '</span>'
						);
						show_message('Успешно добавлено!');
					}
				})
			})
			$(document).on('click', 'span.forbidden_page', function(){
				var th = $(this);
				if (!confirm('Вы действительно хотите удалить?')) return false;
				$.ajax({
					type: 'get',
					url: '/admin/?view=connections&tab=remove_forbidden_page',
					data: {
						page: th.text()
					},
					success: function(response){
						th.remove();
						show_message('Успешно удалено!');
					}
				})
			})
			$.datetimepicker.setLocale('ru');
			$('[data-name=statistics] .datetimepicker[name=dateFrom], [data-name=statistics] .datetimepicker[name=dateTo]').datetimepicker({
				format:'d.m.Y H:i',
				onChangeDateTime: function(db, $input){
					connections.statistics();
				},
				closeOnDateSelect: true,
				closeOnWithoutClick: true
			});
			$('[data-name=common_list] .datetimepicker[name=dateFrom], [data-name=common_list] .datetimepicker[name=dateTo]').datetimepicker({
				format:'d.m.Y H:i',
				onChangeDateTime: function(db, $input){
					connections.common_list();
				},
				closeOnDateSelect: true,
				closeOnWithoutClick: true
			});
			$(document).on('click', 'a.addToBlockedIP', function(){
				var th = $(this);
				if (!confirm('Заблокировать это IP?')) return false;
				connections.add_denied_address(th.text());
				return false;
			})
			$(document).on('click', '#statistics span[class^=icon-circle]', function(){
				let th = $(this);
				th.toggleClass('icon-circle-up').toggleClass('icon-circle-down');
				let act = th.attr('class');
				if (th.attr('class') == 'icon-circle-up'){
					let dateFrom = th.closest('div[data-name=statistics]').find('input[name=dateFrom]').val();
					let dateTo = th.closest('div[data-name=statistics]').find('input[name=dateTo]').val();
					let ip = th.next().text();
					th.closest('tr').after(
						'<tr class="detailedInformation">' + 
							'<td colspan="3">' +
								'<div class="ionTabs__preloader"></div>' +
							'</td>' +
						'</tr>'
					);
					connections.setDetailedInformationAboutIP(th.closest('tr').next(), ip, dateFrom, dateTo);
				}
				else th.closest('tr').next().remove();
			})
		},
		setDetailedInformationAboutIP: function($obj, ip, dateFrom, dateTo){
			$.ajax({
				type: 'get',
				url: '/admin/?view=connections&tab=getDetailedInformationAboutIP',
				data: {
					ip: ip,
					dateFrom: dateFrom,
					dateTo: dateTo
				},
				success: function(response){
					let items = JSON.parse(response);
					let str = '<table class="detailedInformationAboutIP">';
					$.each(items, function(i, item){
						str += 
							'<tr>' +
								'<td>' + item.url + '</td>' +
								'<td>' + item.created + '</td>' +
							'<tr>'
					})
					str += '</table>';
					$obj.find('.ionTabs__preloader').remove();
					$obj.find('td').html(str);
				}
			})
		},
		add_ip: function(text, type){
			if (!/^\d+\.\d+\.\d+\.\d+$/.test(text)) return show_message('Адрес введен некоректно!', 'error');
			type = type.replace('add_', '');
			$.ajax({
				type: 'get',
				url: '/admin/?view=connections&tab=add_' + type,
				data: {
					text: text
				},
				success: function(response){
					if (response != 'ok') return show_message(response, 'error');
					$('div[data-name=' + type + ']').append(
						`<span class="${type} icon-cross1">${text}</span>`
					);
					show_message('Успешно добавлено!');
				}
			})
		},
		setTabs: function(){
			$.ionTabs("#tabs_1",{
				type: "hash",
				beforeSend: () => {
					showGif();
				},
				onChange: function(obj){
					window.connections.tab = obj.tab;
					var str = '/admin/?view=connections&tab=' + obj.tab;
					str += '#tabs|connections:' + obj.tab;
					window.history.pushState(null, null, str);
					const $tab = $('div[data-name=' + obj.tab + ']');
					if ($tab.hasClass('viewed')) return false;
					switch(obj.tab){
						case 'common_list': connections.common_list(); break;
						case 'denied_addresses': connections.get_list_ip('denied_addresses'); break;
						case 'allowed_ip': connections.get_list_ip('allowed_ip'); break;
						case 'forbidden_pages': connections.forbidden_pages(); break;
						case 'statistics': connections.statistics(); break;
					}
					$tab.addClass('viewed');
					showGif(false);
				}
			});
		},
		common_list: function(){
			var $tab = $('div[data-name=common_list]');
			var dataSource = '/admin/?view=connections&tab=common_list';
			connections.commonListFilters.dateFrom = $tab.find('input[name=dateFrom]').val();
			connections.commonListFilters.dateTo = $tab.find('input[name=dateTo]').val();
			var strFilters = connections.getHttpStiringFromObject(connections.commonListFilters);
			if (strFilters) dataSource += strFilters;
			$tab.find('.pagination-container').pagination({
				pageNumber: 1,
				dataSource: dataSource,
				className: 'paginationjs-small',
				locator: '',
				totalNumber: connections.getCommonListTotalNumber(connections.commonListFilters),
				pageSize: connections.pageSize,
				ajax: {
					beforeSend: function(){
						$('#common_list tbody').empty();
					},
				},
				callback: function(data, pagination){
					for(var key in data) $('#common_list tbody').append(
						'<tr connection_id="' + data[key].id + '">' +
							'<td label="ip">' + data[key].ip + '</td>' +
							'<td label="Страница">' + data[key].url + '</td>' +
							'<td label="Пользователь">' + data[key].name + '</td>' +
							'<td label="Комментарий">' + data[key].comment + '</td>' +
							'<td label="Запрещено">' + data[key].isDeniedAccess + '</td>' +
							'<td label="Дата">' + data[key].created + '</td>' +
						'<tr>'
					)
					$('select[name=user_id]').chosen({
						disable_search_threshold: 5,
						no_results_text: "не найден",
						allow_single_deselect: true,
						width: "200px"
					});
				}
			})
		},
		statistics: function(){
			var $tab = $('div[data-name=statistics]');
			var dataSource = '/admin/?view=connections&tab=statistics';
			var dateFrom = $tab.find('input[name=dateFrom]').val();
			var dateTo = $tab.find('input[name=dateTo]').val();
			dataSource += '&dateFrom=' + dateFrom;
			dataSource += '&dateTo=' + dateTo;
			$tab.find('.pagination-container').pagination({
				pageNumber: 1,
				dataSource: dataSource,
				className: 'paginationjs-small',
				locator: '',
				totalNumber: connections.getStatisticsTotalNumber(dateFrom, dateTo),
				pageSize: connections.pageSize,
				ajax: {
					beforeSend: function(){
						$('#statistics tbody').empty();
					},
				},
				callback: function(data, pagination){
					for(var key in data) $('#statistics tbody').append(
						'<tr class="' + data[key].is_blocked + '">' +
							'<td label="ip">' +
								'<span class="icon-circle-down"></span>' +
								'<a class="addToBlockedIP" href="">' + data[key].ip + '</a>' + 
							'</td>' +
							'<td label="Количество">' + data[key].cnt + '</td>' +
							'<td label="Коментарий">' + data[key].comment + '</td>' +
						'<tr>'
					)
				}
			})
		},
		getStatisticsTotalNumber: function(dateFrom, dateTo){
			$.ajax({
				type: 'get',
				url: '/admin/?view=connections&tab=getStatisticsTotalNumber',
				data: {
					dateFrom: dateFrom,
					dateTo: dateTo
				},
				async: false,
				success: function(response){
					output = response;
				}
			});
			$('div[data-name=statistics] .total').html('Всего: ' + output);
			return output;
		},
		getHttpStiringFromObject: function(array){
			if (!Object.keys(array).length) return false;
			var output = '';
			for(var key in array) output += '&' + key + '=' + array[key];
			return output;
		},
		getCommonListTotalNumber(filters){
			var output;
			var url = '/admin/?view=connections&tab=getCommonListTotalNumber';
			var strFilters = connections.getHttpStiringFromObject(filters);
			if (strFilters) url += strFilters;
			$.ajax({
				type: 'get',
				url: url,
				async: false,
				success: function(response){
					output = response;
				}
			})
			$('div[data-name=common_list] input[name=totalNumber]').val(output);
			return output;
		},
		get_list_ip: function(type){
			var $tab = $(`div[data-name=${type}]`);
			$tab.find(`.${type}`).remove();
			$.ajax({
				type: 'get',
				url: '/admin/?view=connections&tab=' + type,
				data: '',
				success: function(response){
					if (!response) return false;
					let list = JSON.parse(response);
					for(let key in list){
						$tab.append(
							`<span class="${type} icon-cross1">${list[key].ip}</span>`
						);
					}
				}
			})
		},
		forbidden_pages: function(){
			$.ajax({
				type: 'get',
				url: '/admin/?view=connections&tab=forbidden_pages',
				data: '',
				success: function(response){
					if (!response) return false;
					var pages = JSON.parse(response);
					for(var key in pages){
						$('div[data-name=forbidden_pages]').append(
							'<span class="forbidden_page icon-cross1">' + pages[key].page + '</span>'
						);
					}
				}
			})
		}
	}
})(jQuery)
$(function(){
	connections.init();
})