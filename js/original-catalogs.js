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

$(function(){
    let isProccessedGarage = false;
    let isProccessedVin = false;
    let isPageVin = false;

    let intervalID = setInterval(function(){
        let user_id = + $('input[name=user_id]').val();
        let result;
        if (!user_id){
            clearInterval(intervalID);
            return false;
        }
        let $partsCatalogsNodes = $('div._1WlWlHOl9uqtdaoVxShALG');
        if (!$('#to_garage').size()) isProccessedGarage = false;
        if($partsCatalogsNodes.size() && !isProccessedGarage){
            let href = document.location.href;
            isPageVin = /carInfo\?q=/.test(href);
            href = href.replace(/.*\?/, '');
            const urlParams = new URLSearchParams(href);

			let $h1 = $partsCatalogsNodes.find('h1');
			let title = $h1.html();
			title = title.replace(/[^\w ]+/g, '');
			title = title.replace(/^ +/g, '');
			title = title.replace(/ +$/g, '');
			let data = {
				'user_id': user_id,
				'title': title,
				'catalogId': urlParams.get('catalogId') ?? '',
				'modelId': urlParams.get('modelId') ?? '',
				'carId': urlParams.get('carId') ?? '',
				'q': urlParams.get('q') ?? ''
			};
			data.act = 'isGaraged';
			$.ajax({
				type: 'post',
				url: '/ajax/parts-catalogs.php',
				data: data,
				success: function(response){
                    if (isProccessedGarage) return;
                    isProccessedGarage = true;
                    result = JSON.parse(response);
                    let added = result.isGaraged === 'is_garaged' ? 'added' : '';
					$h1.prepend(`
						<div id="to_garage" class="${added}">
							<button class="${result.isGaraged}" full_name="${result.userFullName}" title="Добавить / Удалить в гараж"></button>
						</div>
					`);
                    $('#to_garage').on('click',function(e){
                        let title = $h1.text();
                        data.title = title.trim();

                        data.year = '';
                        $.each($('li._1aQVewIy8-bOC25Ti2Wsxl div._1UgSrnp4JyS_GOucnzUvx9'), function(i, item){
                            let string = $(item).text();
                            string = string.trim();
                            if (/^\d{4}$/.test(string)) data.year = string;
                        })

                        let vin = $('#id-vin-frame-search').val();
                        data.vin = vin.trim();

                        data.owner = result.userFullName;

                        eventAddGarage(this, data);
                    })
				}
			})
		}

		if (isPageVin && !isProccessedVin){
		    let data = {};
            $tbody = $('div.LjoJ3TC3QBG0Gj1e1y18m tbody._2s4Zlis3TQ_ERIPo_5CYhW');
            $tr = $tbody.find('tr._2ra47Mt1RDDKqGLLJbQWkG:first-child');
            let brend = $tr.find('td:nth-child(1)').html();
            data.brend = brend.trim();
            data.model = $tr.find('td:nth-child(2)').html();
            data.model = data.model.trim();
            data.year = $tr.find('td:nth-child(3)').html();
            data.year = data.year.trim();
            data.vin = urlParams.get('q');
            data.act = "saveVin";
            data.user_id = $('input[name=user_id]').val();
            $.ajax({
                "type": "post",
                "url": "/ajax/common.php",
                "data": data,
                success: function(response){
                    isProccessedVin = true;
                }
            })
        }

	}, 500);
})
	
