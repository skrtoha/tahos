(function($){
	window['test_api_providers'] = {
		ajax_url: '/admin/?view=test_api_providers',
		init: function(){
			$('#getCoincidences').on('submit', function(e){
				e.preventDefault();
				$('div.results').empty();
				let th = $(this);
				$.ajax({
					type: 'get',
					url: test_api_providers.ajax_url + '&' + th.serialize(),
					success: function(response){
						if (!response) return;
						let items = JSON.parse(response);
						$.each(items, function(i, item){
							$('div.results').append(
								'<a item_id="' + item.id + '" class="resultItem">' + 
									item.brend + ' ' + item.article + ' ' + item.title_full +
								'</a>'
							);
						})
					}
				})
			})
			$(document).on('click', 'a.resultItem', function(e){
				e.preventDefault();
				let th = $(this);
				$('div.results').empty();
				$('input[name=article]').val('');
				$('#tests button').removeClass('hidden');
				history.pushState(null, null, test_api_providers.ajax_url + '&item_id=' + th.attr('item_id'));
				$('#tests p.title').html(th.html());
				$('input[name=item_id]').val(th.attr('item_id'));
			})
			$('#processTest').on('click', function(){
				$('.removable').empty();
				let providers = $('#tests form').serializeArray();
				let total = 0;
				if (!providers.length) return show_message('Ничего не выбрано', 'error');
				$.each(providers, function(i, provider){
					$.ajax({
						type: 'get',
						url: test_api_providers.ajax_url,
						data: {
							act: 'getResultApi',
							item_id: $('input[name=item_id]').val(),
							provider_id: provider.name,
							providerApiTitle: provider.value
						},
						success: function(response){
							let $input = $('input[name=' + provider.name + ']');
							$input.closest('tr').find('td:nth-child(2)').html(response);
							total += + response;
							$('span.total').html(total);
						}
					})
				})
			})
			$('#tests input[name=checkAll]').on('click', function(){
				if ($(this).is(':checked')) $('#tests input[type=checkbox]').prop('checked', true);
				else $('#tests input[type=checkbox]').prop('checked', false);
			})
		}
	}
})(jQuery)
$(function(){
	test_api_providers.init();
})