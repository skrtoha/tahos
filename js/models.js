$(function(){

	// // range slider
	// $("#volume").ionRangeSlider({
	// 	type: "double",
	// 	min: 1,
	// 	max: 50
	// });


	// view switch in catalogue
	$(".view-switch").click(function(event) {
		$(".view-switch").removeClass("active");
		$(this).addClass("active");
		switch_id = $(this).attr("id");
		if (switch_id == "mosaic-view-switch") {
			$(".mosaic-view").show();
			$(".list-view").hide();
		}else{
			$(".mosaic-view").hide();
			$(".list-view").show();
		}

	});

	// equal height
	$(".item .img-wrap").matchHeight();
	$(".item").matchHeight();

});
