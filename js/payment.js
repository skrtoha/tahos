const preg_int = /^[0-9]+$/;
$(function(){
	$(".method").click(function(event) {
		$(".method").removeClass('selected');
		$(this).addClass('selected');
		block_id ="#" + $(this).attr("data-target");
		$(".pay-block").removeClass("show");
		$(block_id).addClass("show");
	});
	$('#from_bonus').on('submit', function(e){
        const element = $('input[name=bonus_count]')
		if (
            element.val() > $('input[name="bonus_current"]').val() ||
			!preg_int.test(element.val()) ||
			!element.val()
		){
			show_message("Введено неккоректное количество!", 'error');
			e.preventDefault();
		}
	})
    $('#paykeeper form').on('submit', e => {
        e.preventDefault()
        const formData = new FormData(e.target)
        formData.set('act', 'get_link')

        fetch('/admin/ajax/payment.php', {
            method: 'post',
            body: formData
        }).then(response => response.text()).then(response => {
            document.location.href = response
        })
    })

});