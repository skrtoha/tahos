(function($){
	window['brends'] = {
		providers: new Object(),
		init: function(){
			$('a.upload_files').on('click', function(){
				$('input[type=file]').get(0).click();
			});
			$('input[type=file]').on('change', function(event){
				event.stopPropagation();
				event.preventDefault();
				if (typeof this.files == 'undefined') return;
				var data = new FormData();
				$.each(this.files, function(key, value){
					data.append(key, value);
				});
				$.ajax({
					url: '/admin/ajax/brends.php?brend_id=' + $('input[name=brend_id]').val(),
					type: 'post',
					data: data,
					cache: false,
					dataType: 'json',
					processData: false,
					contentType: false,
					success: function(respond, status, jqXHR){
						show_message('Операция завершена. Подробности в консоли');
						console.log(respond);
					}
				})
				return false;
			})
			$('#addProviderBrend').on('click', function(e){
				e.preventDefault();
				modal_show(
					'<form name="addProviderBrend">' +
						'<table>' +
							'<tr>' +
								'<td>Поставщик:</td>' +
								'<td>' + window.brends.getProvidersHtml() + '</td>' +
							'</tr>' +
							'<tr>' +
								'<td>Название:</td>' +
								'<td><input type="text" name="title"></td>' +
							'</tr>' +
							'<tr>' +
								'<td colspan="2"><input type="submit" value="Отправить"></td>' +
							'</tr>' +
						'</table>' +
					'</form>'
				)
			})
			$(document).on('submit', 'form[name=addProviderBrend]', function(e){
				e.preventDefault();
				var formData = $(this).serializeArray();
				if (!formData[1].value) return show_message('Укажите название!', 'error');
				formData = {
					act: 'setProviderBrend',
					brend_id: $('input[name=brend_id]').val(),
					data: formData
				}
				$.ajax({
					url: 'ajax/providers.php',
					data: formData,
					type: 'post',
					success: function(response){
						if (+response !== 1) return show_message(response, 'error');
						$('#addProviderBrend').after(
							'<span class="subbrend" provider_id="' + formData.data[0].value + '">' +
								'<b>' + window.brends.providers[formData.data[0].value] + ':</b> ' + formData.data[1].value +
								'<span class="providerBrendDelete"></span>' +
							'</span>'
						);
						$('#modal-container').removeClass('active');
					}
				})
			})
			$('div.value.subbrends').on('click', '.providerBrendDelete', function(e){
				if (!confirm('Вы действительно хотите удалить?')) return false;
				var th = $(this);
				$.ajax({
					type: 'post',
					url: 'ajax/providers.php',
					data: {
						act: 'providerBrendDelete',
						brend_id: $('input[name=brend_id]').val(),
						provider_id: $(th).closest('[provider_id]').attr('provider_id')
					},
					success: function(response){
						th.closest('[provider_id]').remove();
					}
				})
			})
			$('input[name=brendItems].intuitive_search').on('keyup focus', function(e){
				e.preventDefault();
				let val = $(this).val();
				let minLength = 1;
				val = val.replace(/[^\wа-яА-Я]+/gi, '');
                delay(() => {
                    intuitive_search.getResults({
                        event: e,
                        value: val,
                        minLength: minLength,
                        additionalConditions: {
                            brend_id: $('input[name=id]').val(),
                        },
                        tableName: 'brendItems',
                    });
                }, 1000)
			});
			$('div.value.subbrends').on('click', '.subbrend_delete', function(){
				var elem = $(this).parent();
				var subbrend_id = elem.attr('subbrend_id');
				if (!confirm('Вы действительно хотите удалить?')) return false;
				$.ajax({
					type: "POST",
					url: "/ajax/subbrend.php",
					data: 'act=subbrend_delete&subbrend_id=' + subbrend_id,
					success: function(msg){
						// alert(msg);
						if (msg == 'ok'){
							show_message('Подбренд успешно удален!');
							elem.remove();
							if (!$('.subbrend').length) $('#add_subbrend').before('<span id="no_brends">Подбрендов не найдено</span>');
						}
					}
				})
			})
			$('select[name=brend_from], select[name=brend_to]').chosen({
				disable_search_threshold: 5,
				no_results_text: "не найден",
				allow_single_deselect: true,
				width: "200px"
			});
		},
		getProvidersHtml: function(e){
			var output = '<select name="provider_id">';
			$.ajax({
				type: 'post',
				url: '/admin/ajax/providers.php',
				async: false,
				data: {act: 'getAllProviders'},
				success: function(response){
					window.brends.providers = JSON.parse(response);
					var providers = new Object();
					$.each(window.brends.providers, function(index, value){
						providers[value.id] = value.title
					});
					window.brends.providers = providers;
					$.each(window.brends.providers, function(index, value){
						output += '<option value="' + index + '">' + value + '</option>';
					})
				}
			})
			output += '</select>';
			return output;
		}
	}
})(jQuery)
$(function(){
	brends.init();
})