var preg_int = /^[0-9]+$/;
$(function(){
	$(".method").click(function(event) {
		$(".method").removeClass('selected');
		$(this).addClass('selected');
		block_id ="#" + $(this).attr("data-target");
		$(".pay-block").removeClass("show");
		$(block_id).addClass("show");
	});
	$('#from_bonus').on('submit', function(e){
		// e.preventDefault();
		// console.log(
		// 	$('input[name=bonus_count]').val() > $('input[name=bonus_current').val(),
		// 	preg_int.test($('input[name=bonus_count]').val()),
		// 	$('input[name=bonus_count]').val()
		// );
		if (
			$('input[name=bonus_count]').val() > $('input[name=bonus_current').val() ||
			!preg_int.test($('input[name=bonus_count]').val()) ||
			!$('input[name=bonus_count]').val()
		){
			show_message("Введено неккоректное количество!", 'error');
			e.preventDefault();
		}
	})
});