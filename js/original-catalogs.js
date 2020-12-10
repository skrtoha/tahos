function apply_items(models){
	var str = '';
	var option = '<option selected=""></option>';
	for (var letter in models){
		// alert();
		str += 
		'<div class="name-block">' +
			'<p class="letter">' + letter + '</p>' +
			'<div class="models">';
		for(var key in models[letter]){
			var m = models[letter][key];
			var href = '/original-catalogs/' + $('#vehicle').val() + '/' + $('#brend').val() +
									'/' + m.model_id + '/' + m.href + '/vin';
			str += '<a href="' + href + '">' + m.title + '</a>';
			option += '<option value="' + href + '">' + m.title + '</option>';
		}
		str +=
								'</div>' +
							'</div>';
	}
	if(!str) str = '<p>Ничего не найдено</p>';
	// console.log(str);
	$('div.mosaic-view').html(str);
	$('div.list-view').html(str);
	$('select.select_model').html(option).trigger('refresh');
}
getUrlString = getParams();
$(".view-switch").click(function(event) {
	$(".view-switch").removeClass("active");
	$(this).addClass("active");
	switch_id = $(this).attr("id");
	if (switch_id == "mosaic-view-switch") {
		$(".mosaic-view").show();
		$(".list-view").hide();
	}
	else{
		$(".mosaic-view").hide();
		$(".list-view").show();
	}
});
$(".item .img-wrap").matchHeight();
$(".item").matchHeight();
$('#vehicle').on('change', function(){
	document.location.href = '/original-catalogs/' + $(this).val();
})
$('#brend').on('change', function(){
	document.location.href = '/original-catalogs/' + $(this).attr('vehicle') + '/' + $(this).val();
})
$('#search').on('blur', function(){
	get_ajax();
});
$('#search').on('keydown', function(e){
	if (e.keyCode == 13){
		e.preventDefault();
		get_ajax();
	}
})
function get_ajax(){
	var data = 'vehicle=' + $('#vehicle').val() +
							'&brend=' + $('#brend').val() + 
							'&year=' + $('select.select_year').val() +
							'&search=' + $('#search').val() +
							'&act=get_models';
	$.ajax({
		type: 'post',
		url: '/ajax/original-catalogs.php',
		data: data,
		success: function(res){
			// console.log(res); return false;
			if (res) var models = JSON.parse(res);
			else models = '';
			// console.log(models);
			apply_items(models);
		}
	})
	return false;
}
$('.select_year').on('change', function(){
	get_ajax($(this));
})
$('.select_model').on('change', function(){
	document.location.href = $(this).val();
	return false;
})
$('.slider').each(function(){
		var e = $(this);
		e.ionRangeSlider({
			type: "double",
			min: e.attr('min'),
			max: e.attr('max'),
			onFinish: function(data){
				e.attr('from', data.from);
				e.attr('to', data.to);
				$('#filters_on').val(1);
				setTimeout(get_items, 1);
			}
		});
	})
let intervalID = setInterval(function(){
	let user_id = + $('input[name=user_id]').val();
	if (!user_id){
		clearInterval(intervalID);
		return false;
	} 
	if($('div._1WlWlHOl9uqtdaoVxShALG').html()){
		let href = document.location.href;
		href = href.replace(/.*\?/, '');
		let selectorH1 = '#app > div:nth-child(1) > div._1WlWlHOl9uqtdaoVxShALG > h1';
		const urlParams = new URLSearchParams(href);
		let data = {
			'user_id': user_id,
			'title': $(selectorH1).text(),
			'catalogId': urlParams.get('catalogId'),
			'modelId': urlParams.get('modelId'),
			'carId': urlParams.get('carId')
		};
		data.act = 'isGaraged';
		$.ajax({
			type: 'post',
			url: '/ajax/parts-catalogs.php',
			data: data,
			success: function(res){
				$(selectorH1).prepend(`
					<div id="to_garage">
						<button class="${res}" title="Добавить / Удалить в гараж"></button>
					</div>
				`);
				$('#to_garage button').on('click', function(){
					let th = $(this);
					if (th.hasClass('is_garaged')) data.act = 'removeFromGarage';
					else data.act = 'addToGarage';
					$.ajax({
						type: 'post',
						url: '/ajax/parts-catalogs.php',
						data: data,
						success: function(response){
							if (data.act == 'addToGarage') $('#to_garage button').addClass('is_garaged');
							else $('#to_garage button').removeClass('is_garaged');
						}
					})
				})
			}
		})
		clearInterval(intervalID);
	}
}, 1000);
