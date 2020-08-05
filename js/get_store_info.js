(function($){
	window['get_store_info'] = {
		init: function(){
			$.getScript('/vendor/peity/jquery.peity.js', function(){
				$(document).on('click', 'a[store_id]', function(e){
					// return false;
					e.preventDefault();
					let th = $(this);
					$.ajax({
						type: 'post',
						url: '/admin/ajax/providers.php',
						data: {
							act: 'getStoreInfo',
							store_id: th.attr('store_id')
						},
						success: function(response){
							$.magnificPopup.open({
								items: {
									src: response,
									type: 'inline'
								}
							});
							setInterval(function(){
								$('div.donut span[data-peity]').peity('donut');
							}, 200);
						}
					})
				})
			})
		}
	}
})(jQuery)
$(function(){
	get_store_info.init();
})