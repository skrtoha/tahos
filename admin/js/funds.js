(function($){
	window['funds'] = {
		init: function(){
			$('select[name=is_payed]').on('change', function(){
				$(this).closest('form').submit();
			})
		}
	}
})(jQuery)
$(function(){
	funds.init();
})