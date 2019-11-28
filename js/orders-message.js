$(function(){

	$("select").styler();

	// info show
	$(".info_btn").click(function(event) {
		$(this).next().show();
		$(".overlay").show();
	});

	//info-hide
	// hide status form by clicking outside
	$(document).mouseup(function (e)	{
		var container = $(".info");

		if (!container.is(e.target) // if the target of the click isn't the container...
				&& container.has(e.target).length === 0) // ... nor a descendant of the container
		{
			container.hide();
			$(".overlay").hide();
		}

	});

});