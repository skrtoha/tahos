(function($){
	window['category'] = {
		init: function(){
			$('select[name=isShowOnMainPage]').on('change', function(){
				$(this).closest('form').submit();
			})
		}
	}
})(jQuery)
$(function(){
	category.init();
})