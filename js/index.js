$(function(){
	/* actions slider */
	$(".actions_slider").owlCarousel({
		items: 1,
		smartSpeed: 500
	});
	$(".prev").click(function(){
		$(".actions_slider").trigger("prev.owl.carousel");
	});
	$(".next").click(function(){
		$(".actions_slider").trigger("next.owl.carousel");
	});
	$('.vehicle_select').on('change', function(){
		var th = $(this);
		$.ajax({
			method: 'post',
			url: '/ajax/index.php',
			data: 'act=get_brends&vehicle_id=' + th.val(),
			success: function(res){
				// console.log(res);
				var brends = JSON.parse(res);
				// console.log(brends);
				var str = '<option value=""></option>';
				for(var key in brends) str += '<option href="' + brends[key].href + '" value="' + brends[key].brend_id + '">' + brends[key].title + '</option>';
				$('select.brend_select').prop('disabled', false).html(str).trigger('refresh');
			}
		})
		return false;
	})
	$('select.brend_select').on('change', function(){
		var th = $(this);
		$.ajax({
			method: 'post',
			url: '/ajax/index.php',
			data: 'act=get_years&vehicle_id=' + $('select.vehicle_select').val() + '&brend_id=' + $('select.brend_select').val(),
			success: function(res){
				// console.log(res); return false;
				var years = JSON.parse(res);
				var str = '<option value=""></option>';
				for (var k in years) str += '<option value="' + years[k].id + '">' + years[k].title + '</option>';
				$('select.year_select').prop('disabled', false).html(str).trigger('refresh');
			}
		})
	})
	$('select.year_select').on('change', function(){
		var th = $(this);
		$.ajax({
			type: 'post',
			url: '/ajax/index.php',
			data: 'act=get_models&vehicle_id=' + $('select.vehicle_select').val() + 
						'&brend_id=' + $('select.brend_select').val() + '&year_id=' + $('select.year_select').val(),
			success: function(response){
				// console.log(response); return false;
				var models = JSON.parse(response);
				var str = '<option value=""></option>';
				for (var k in models) str += '<option href="' + models[k].href + '" value="' + models[k].model_id + '">' + models[k].title + '</option>';
				$('select.model_select').prop('disabled', false).html(str).trigger('refresh');
			}
		})
	})
	$('select.model_select, select.year_select').on('change', function(){
		var model = $('select.model_select option:selected').attr('href');
		var model_id = $('select.model_select').val();
		var year = $('select.year_select option:selected').html();
		var vehicle = $('select.vehicle_select option:selected').attr('href');
		var brend = $('select.brend_select option:selected').attr('href');
		if (!model || !year || !vehicle || !brend) return false;
		document.location.href = 
			'original-catalogs' +
			'/' + vehicle +
			'/' + brend +
			'/' + model_id +
			'/' + model +
			'/' + year;
	})
});