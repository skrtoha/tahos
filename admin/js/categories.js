(function($){
	window['categories'] = {
		init: function(){
			$('select[name=parent_id]').on('change', function(){
				$(this).closest('form').submit();
			})
		}
	}
})(jQuery)
$(function(){
	categories.init();
})