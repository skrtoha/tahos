(function($){
	window['settings'] = {
		providers: {
			init: function(){
				$('#providers tr[provider_id]').on('click', function(){
					document.location.href = '/admin/?view=providers&act=provider&id=' + $(this).attr('provider_id');
				})
			}
		}
	}
})(jQuery)
$(function(){
	settings.providers.init();
})