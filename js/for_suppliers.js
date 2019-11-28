$(function(){

	/* mask phone */

	$.mask.definitions['~']='[+-]';
	$("#phone, #additional-phone").mask("+7 (999) 999-99-99");

	//add additional
	$("#add-additional-phone").click(function(event) {
		event.preventDefault();
		$(this).hide();
		$("#additional-phone").show();
	});

	$("#jur-act-same-address").styler();

	$("#jur-act-same-address").change(function(event) {
		if ($(this).prop("checked")) {
			$("#index-act, #region-act, #address-act").prop('disabled', true);
		}else{
			$("#index-act, #region-act, #address-act").prop('disabled', false);
		}
	});

});