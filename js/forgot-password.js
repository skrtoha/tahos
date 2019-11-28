$(function(){

		/* forgot password */

	$(".forgot_password_form form").submit(function(event) {
		event.preventDefault();
		$(".forgot_password_form .message").fadeIn(300);
	});


});