(function($){
	window['intuitive_search'] = {
		defaultParams: {
			minLength: 2,
			maxCountResults: 10 
		},
		getResults: function(params){
			let self = this;
			let minLength = typeof params.minLength != 'undefined' ? params.minLength : self.defaultParams.minLength;
			let maxCountResults = typeof params.maxCountResults != 'undefined' ? params.maxCountResults : self.defaultParams.maxCountResults;
			$(params.event.target).next('ul.searchResult_list').remove();
			if (params.value.length <= minLength) return false;
			$.ajax({
				url: '/vendor/intuitive_search/ajax.php',
				type: 'get',
				data: {
					tableName: params.tableName,
					searchField: params.searchField,
					value: params.value,
					maxCountResults: maxCountResults
				},
				beforeSend: function(){
					$(params.event.target).nextAll('.preloader').show();
					$(params.event.target).nextAll('.searchResult_list').empty();
				},
				success: function(response){
					$(params.event.target).nextAll('.preloader').hide();
					let ul = $(params.event.target).nextAll('.searchResult_list');
					if (!response) ul.html('<li>ничего не найдено</li>');
					else ul.html(response);
				}
			})
		},
		init: function(){
			$('input.intuitive_search')
				.wrap('<div class="intuitiveSearch_wrap"></div>')
				.attr('autocomplete', 'off')
				.closest('.intuitiveSearch_wrap')
				.append(`
					<div style="display: none; position: absolute;width:100%;text-align:center; background: white" class="preloader">
						<img src="/vendor/intuitive_search/preload.gif">
					</div>
					<ul class="searchResult_list"></ul>
				`);
			$(document).on('click', function(e){
				let target = $(e.target);
				if (!target.closest('.intuitiveSearch_wrap').size()) target.find('.searchResult_list').hide();
			})
		}
	}
})(jQuery)
$(function(){
	intuitive_search.init();
})
