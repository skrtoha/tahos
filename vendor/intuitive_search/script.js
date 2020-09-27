(function($){
	window['intuitive_search'] = {
		defaultParams: {
			minLength: 2,
			maxCountResults: 10 
		},
		selectItemByKey: function(event){
			$input = $(event.target);
			let ul = $input.nextAll('.searchResult_list');
			let activeLi = ul.find('li.active');
			if (event.keyCode == 40){
				if (activeLi.next().size() == 0) return false;
				ul.find('li').removeClass('active');
				activeLi.next().addClass('active');
			} 
			if (event.keyCode == 38){
				if (activeLi.prev().size() == 0) return false;
				ul.find('li').removeClass('active');
				activeLi.prev().addClass('active');
			} 
		},
		applyEnterPressing: function(event){
			$input = $(event.target);
			$input.nextAll('.searchResult_list').find('li.active a').get(0).click();
		},
		getResults: function(params){
			let self = this;
			if (params.event.keyCode == 38 || params.event.keyCode == 40){
				return self.selectItemByKey(params.event);
			}
			if (params.event.keyCode == 13){
				return self.applyEnterPressing(params.event);
			}
			let minLength = typeof params.minLength != 'undefined' ? params.minLength : self.defaultParams.minLength;
			let maxCountResults = typeof params.maxCountResults != 'undefined' ? params.maxCountResults : self.defaultParams.maxCountResults;
			let additionalConditions = typeof params.additionalConditions != 'undefined' ? params.additionalConditions : {};
			$(params.event.target).next('ul.searchResult_list').remove();
			if (params.value.length <= minLength) return false;
			$.ajax({
				url: '/vendor/intuitive_search/ajax.php',
				type: 'get',
				data: {
					tableName: params.tableName,
					additionalConditions: additionalConditions,
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
					else{
						ul.html(response);
						ul.find('li:first-child').addClass('active');
					} 
					ul.show();
				}
			})
		},
		init: function(){
			let intuitiveSearch_number = 0;
			$('input.intuitive_search').each(function(){
				$(this).wrap('<div class="intuitiveSearch_wrap"></div>')
				.attr('autocomplete', 'off')
				.attr('intuitiveSearch_number', intuitiveSearch_number++)
				.closest('.intuitiveSearch_wrap')
				.append(`
					<div style="display:none;position:absolute;width:100%;text-align:center;background:white" class="preloader">
						<img src="/vendor/intuitive_search/preload.gif">
					</div>
					<ul class="searchResult_list"></ul>
				`)
			})
			$(document).on('click', function(e){
				let target = $(e.target);
				let mainNumber = $(e.target).closest('.intuitiveSearch_wrap').find('input.intuitive_search').attr('intuitiveSearch_number');
				if (mainNumber == 'undefined'){
					$(document).find('.searchResult_list').hide();
					$(document).find('input.intuitive_search').val('')
				}
				else{
					$('input.intuitive_search').each(function(){
						let number = $(this).attr('intuitiveSearch_number');
						if(number == mainNumber) return 1;
						let wrap = $(this).closest('.intuitiveSearch_wrap');
						wrap.find('.searchResult_list').hide();
						wrap.find('input.intuitive_search').val('')
					})
				}
			})
		}
	}
})(jQuery)
$(function(){
	intuitive_search.init();
})
