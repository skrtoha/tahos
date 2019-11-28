$(function(){


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

	// item equal height

	$(".item").matchHeight();
	$(".item .img").matchHeight();

	//make tree
	// $(".tree-structure").jstree();
	$(".tree-structure").on('click', '.jstree-anchor', function (e) {
		$(this).jstree(true).toggle_node(e.target);
	})
	.jstree();


});
