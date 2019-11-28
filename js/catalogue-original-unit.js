$(function(){

	// range slider
	$("#volume").ionRangeSlider({
		type: "double",
		min: 1,
		max: 50
	});

	$('.unit-pic a').magnificPopup({
		type: 'image',
		closeOnContentClick: true,
		mainClass: 'mfp-img-mobile',
		image: {
			verticalFit: true
		}

	});

	$(".unit-pic img").elevateZoom({
		scrollZoom : true
	});

});
