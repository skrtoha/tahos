(function($){
	window['index'] = {
		init: function(){
			index.order_funds.init();
		},
		order_funds: {
			init: function(){
				$('select[name=user_id]').chosen({
					disable_search_threshold: 5,
					no_results_text: "не найден",
					allow_single_deselect: true,
					width: "200px"
				});
				$.datetimepicker.setLocale('ru');
				$('#order_funds .datetimepicker[name=dateFrom], #order_funds .datetimepicker[name=dateTo]').datetimepicker({
					format:'d.m.Y H:i',
					onChangeDateTime: function(db, $input){
						index.order_funds.applyFilters();
					},
					closeOnDateSelect: true,
					closeOnWithoutClick: true
				});
				$('#order_funds select[name=user_id]').on('change', function(){
					index.order_funds.applyFilters();
				})
			},
			applyFilters: function(){
				$.ajax({
					type: 'get',
					url: '/admin/ajax/index.php',
					data: $('#order_funds form.filters').serialize() + '&act=getOrderFundsHtmlData',
					success: function(response){
						$('#order_funds table tbody').empty().html(response);
					}
				})
			}
		}
	}
})(jQuery)
$(function(){
	index.init();
})