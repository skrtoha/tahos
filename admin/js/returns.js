(function($){
	window['returns'] = {
		init: function(){
			$('tr[osi]').on('click', function(){
				document.location.href = '/admin/?view=returns&act=form&osi=' + $(this).attr('osi');
			})
			$('input[type=submit]').on('click', function(e){
				e.preventDefault();
				if ($(e.target).hasClass('is_stay')){
					window.history.pushState(null, null, location.href + '&is_stay=1');
				}
				$(this).closest('form').submit();
			})
			$('#filter .filter').on('change', function(){
				$('#filter').submit();
			})
		},
		setDateTimePicker: function(){
			$.datetimepicker.setLocale('ru');
			$('.datetimepicker[name=dateFrom], .datetimepicker[name=dateTo]').datetimepicker({
				format:'d.m.Y H:i',
				onChangeDateTime: function(db, $input){
					$('#filter').submit();
				},
				closeOnDateSelect: true,
				closeOnWithoutClick: true
			});
		},
	}
})(jQuery)
$(function(){
	returns.init();
	returns.setDateTimePicker();
})