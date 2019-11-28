$(function(){


	// equal pics height
	$(".item img").matchHeight();
	$(".item").matchHeight();


	// active switch in garage
	$(".switch").click(function (event) {

		id = $(this).attr("id");
		console.log(id);
		event.preventDefault;
		$(".switch").removeClass("active");
		$(this).addClass("active");
		$(".tab").hide();
		switch (id) {
			case "news-switch":
				$(".news-tab").show();
				break;
			case "sales-switch":
				$(".sales-tab").show();
				break;
			case "articles-switch":
				$(".articles-tab").show();
				break;
		}

	});



	// view switch in garage
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

});