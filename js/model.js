function get_str_filter_data(){
	var str = '';
	var f = '';
	$('select.select_filter').each(function(){
		var e = $(this);
		if (e.val()) f += e.val() + ',';
	})
	if (f) str += '&filter_values=' + f.slice(0, -1);
	// console.log($('#search').val());
	if ($('#search').val()) str += '&search=' + $('#search').val();
	$('.slider').each(function(){
		var e = $(this);
		str += '&years=' + e.attr('from') + ',' + e.attr('to');
	})
	// console.log(str);
	if (!str) return false;
	return str;
}
function get_items(){
	var data = get_str_filter_data();
	if (!data) return false;
	data += '&filters=' + $('.filter-form form').attr('filters') + '&act=apply_filter';
	data += '&model_id=' + $('input[name=model_id]').val();
	// console.log(data);
	$.ajax({
		method: 'post',
		url: '/ajax/original-catalogs.php',
		data: data,
		beforeSend: function(){
			$('table.wide-view tr:nth-child(n+2)').remove();
			$('table.wide-view').append(
				'<tr class="gif">' + 
					'<td colspan="' + $('.mosaic-view table.wide-view').find('th').size() + '"></td>' +
				'</tr>'
			)
				
		},
		success: function(res){
			// console.log(res); return;
			$('.gif').remove();
			if (!res){
				str = 
					'<tr>' + 
						'<td colspan="' + $('.mosaic-view table.wide-view').find('th').size() + '">Ничего не найдено</td>' +
					'</tr>';
			}
			else{
				var modifications = JSON.parse(res);
				// console.log(modifications);
				var str = '';
				for(var key in modifications){
					var m = modifications[key];
					str += 
						'<tr modification_id="' + key + '">' +
							'<td class="name-col">' +
								'<a href="' + document.location.href + '/' + key + '">' +
									m.title +
								'</a>' +
							'</td>';
					for(var k in m.filter_values){
						var s = m.filter_values[k] ? m.filter_values[k] : '';
						str +=
							'<td>' + s + '</td>';
					} 
					str += 
						'</tr>';
				}
			}
			// console.log(str);
			$('table.wide-view').append(str);
			// console.log(str);
		}
	})
}
$(function(){
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
	$('#vehicle').on('change', function(){
		document.location.href = '/original-catalogs/' + $(this).val();
	})
	$('#brend').on('change', function(){
		document.location.href = '/original-catalogs/' + $(this).attr('vehicle') + '/' + $(this).val();
	})
	$('#search').on('blur', function(){
		get_items();
	});
	$('#search').on('keydown', function(e){
		if (e.keyCode == 13){
			e.preventDefault();
			get_items();
		}
	})
	$('.select_model').on('change', function(){
		// console.log($(this).val());
		document.location.href = '/original-catalogs/' + $(this).val();
		return false;
	})
	$(document).on('click', 'tr.clickable', function(){
		document.location.href = $(this).find('td:first-child a').attr('href');
	})
});
