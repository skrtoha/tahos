$(function(){
	// $('.unit-pic a').magnificPopup({
	// 	type: 'image',
	// 	closeOnContentClick: true,
	// 	mainClass: 'mfp-img-mobile',
	// 	image: {
	// 		verticalFit: true
	// 	}
	// });
	$(".unit-pic img").on('click', function(e){
		e.preventDefault();
	})
	if ($(document).width ()>= 928){
		$(".unit-pic img").elevateZoom({
			scrollZoom : true
		});
	}
});
