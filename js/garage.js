$(function(){
	$('.add_ts').magnificPopup({
		type: 'inline',
		preloader: false,
		focus: '#name',

		// When elemened is focused, some mobile browsers in some cases zoom in
		// It looks not nice, so we disable it:
		callbacks: {
			beforeOpen: function() {
				if($(window).width() < 700) {
					this.st.focus = false;
				} else {
					this.st.focus = '#name';
				}
			}
		}
	});
	$(".item img").matchHeight();
	$(".item").matchHeight();
	$(".active-switch").click(function (event) {
		event.preventDefault;
		$(".not-active-switch").removeClass("active");
		$(this).addClass("active");
		$(".active-tab").show();
		$(".not-active-tab").hide();
	});
	$(".not-active-switch").click(function (event) {
		event.preventDefault;
		$(".active-switch").removeClass("active");
		$(this).addClass("active");
		$(".not-active-tab").show();
		$(".active-tab").hide();
	});
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
	$('select[name=vehicle_id]').on('change', function(){
		var th = $(this);
		var vehicle = th.val();
		if (!vehicle) return false;
		$.ajax({
			type: "post",
			url: "/ajax/garage.php",
			data: "act=get_brends&vehicle=" + vehicle,
			success: function(response){
				// console.log(response); return;
				if (!response) return false;
				var brends = JSON.parse(response);
				// console.log(brends);
				var str = '';
				for (var k in brends) str += '<option value="' + brends[k].href + '">' + brends[k].title + '</option>'
				$('select[name=brend_id]')
					.append(str)
					.prop('disabled', false)
					.trigger('refresh');
			}
		})
	})
	$('select[name=brend_id]').on('change', function(){
		var th = $(this);
		var brend = th.val();
		var vehicle = $('select[name=vehicle_id]').val();
		if (!brend) return false;
		$.ajax({
			type: 'post',
			url: '/ajax/garage.php',
			data: 'act=get_models_and_years&vehicle=' + vehicle + '&brend=' + brend,
			success: function(response){
				// console.log(response); return;
				if (!response) return false;
				var res = JSON.parse(response);
				var str = '';
				// console.log(res.models);
				for(var k in res.models) str += '<option model_id="' + res.models[k].id + '" value="' + res.models[k].href + '">' + res.models[k].title + '</option>';
				$('select[name=model_id]')
					.append(str)
					.prop('disabled', false)
					.trigger('refresh');
				var str = '';
				for(var k in res.years) str += '<option value="' + res.years[k].title + '">' + res.years[k].title + '</option>';
				$('select[name=year_id]')
					.append(str)
					.prop('disabled', false)
					.trigger('refresh');
			}
		})
	})
	$('div.filter-form form select').on('change', function(e){
		var f = get_form_data();
		if (f.vehicle && f.brend && f.model && f.year){
			document.location.href = '/original-catalogs/' + f.vehicle + '/' + f.brend + '/' + f.model_id + '/' + f.model + '/vin/' + f.year + '/to_garage';
		}
	})
	$('div.filter-form form').on('submit', function(e){
		e.preventDefault();
		var f = get_form_data();
		// console.log(f); return false;
		if (!f.vehicle){
			show_message('Выберите тип транспорта!', 'error');
			return false;
		}
		if (!f.brend){
			show_message('Выберите марку!', 'error');
			return false;
		}
		if (!f.model && !f.year){
			show_message('Наличие модели или года обязательно!', 'error');
			return false;
		}
		if (!f.model && f.year){
			document.location.href = '/original-catalogs/' + f.vehicle + '/' + f.brend + '/' + f.year
		}
		if (f.model && !f.year){
			document.location.href = '/original-catalogs/' + f.vehicle + '/' + f.brend + '/' + f.model_id + '/' + f.model + '/to_garage'
		}
	})
	$('select[name=year_id]').on('change', function(){
		var f = get_form_data();
		$.ajax({
			type: 'post',
			url: '/ajax/garage.php',
			data: 'act=get_models' + '&vehicle=' + f.vehicle + '&brend=' + f.brend + '&year=' + f.year,
			success: function(response){
				// console.log(response); return false;
				var m = JSON.parse(response);
				// console.log(m);
				var str = '<option selected></option>';
				for(var k in m) str += '<option model_id="' + m[k].model_id + '" value="' + m[k].href + '">' + m[k].title + '</option>';
				$('select[name=model_id]')
					.html(str)
					.trigger('refresh');
			}
		})
	})
	$(document).on('click', 'div.active-tab a.remove-item', function(e){
		e.preventDefault();
		var th = $(this).closest('[modification_id]');
		var img = th.find('div.img').html();
		var model_name = th.find('a.model-name').html();
		if(!confirm('Вы действительно хотите удалить?')) return false;
		var modification_id = th.attr('modification_id');
		$.ajax({
			type: 'post',
			url: '/ajax/garage.php',
			data: 'act=modification_delete&modification_id=' + modification_id,
			success: function(response){
				// console.log(response); return false;
				var m = JSON.parse(response);
				// console.log(m);
				$('[modification_id=' + modification_id + ']').each(function(){
					$(this).remove();
				})
				$('.not-active-tab .removable').remove();
				if (!$('div.mosaic-view > div.active-tab [modification_id]').size()){
					$('div.mosaic-view > div.active-tab div.clearfix').before(
						'<p class="removable">Активных моделей не найдено</p>'
					);
				};
				if (!$('div.list-view div.active-tab .wide-view [modification_id]').size()){
					$('div.list-view div.active-tab .wide-view').append(
						'<tr class="removable"><td colspan="4">Активных моделей не найдено</td></tr>'
					)
				};
				if (!$('div.list-view div.active-tab .middle-view [modification_id]').size()){
					$('div.list-view div.active-tab .middle-view').append(
						'<tr class="removable"><td colspan="2">Активных моделей не найдено</td></tr>'
					)
				};
				if (!$('div.list-view div.active-tab .small-view [modification_id]').size()){
					$('div.list-view div.active-tab .small-view').append(
						'<tr class="removable"><td colspan="2">Активных моделей не задано</td></tr>'
					)
				};
				$('div.mosaic-view > div.not-active-tab > div.clearfix').before(
					'<div class="item" modification_id="' + m.modification_id + '" style="">' +
						'<a class="model-name" href="#">' + model_name + '</a>' +
						'<div class="clearfix"></div>' +
						'<div class="img" style="">' + img + '</div>' +
						'<div class="description">' +
							'<div class="parametrs">' +
								'<p>Имя: ' + m.title_garage + '</p>' +
								'<p>VIN: ' + m.vin + '</p>' +
								'<p>Год выпуска: ' + m.year + '</p>' +
							'</div>' +
						'</div>' +
						'<div class="clearfix"></div>' +
						'<a href="#" class="remove-item">Удалить из гаража</a>' +
						'<a href="#" class="restore-item">Восстановить</a>' +
					'</div>'
				);
				$('div.list-view > div.not-active-tab > table.wide-view').append(
					'<tr modification_id="' + m.modification_id + '">' +
						'<td><a href="#">' + m.title_modification + '</a></td>' +
						'<td><p>' + m.vin + ' </p></td>' +
						'<td><p>' + m.year + '</p></td>' +
						'<td><a href="#" class="restore-item">Восстановить</a></td>' +
						'<td><a href="#" class="remove-item">Удалить</a></td>' +
					'</tr>'
				);
				$('div.list-view > div.not-active-tab > table.middle-view').append(
					'<tr modification_id="' + m.modification_id + '">' +
						'<td><p>' +
							'<a href="#">' + m.title_modification + '</a> ' + m.year + ' г.в. </p></td>' +
						'<td><a href="#" class="restore-item">Восстановить</a></td>' +
						'<td><a href="#" class="remove-item">Удалить</a></td>' +
					'</tr>'
				);
				$('div.list-view > div.not-active-tab > table.small-view').append(
					'<tr modification_id="' + m.modification_id + '">' +
						'<td>' +
							'<p>' +
								'<a href="#">' + m.title_modification + '</a> ' + m.year + ' г.в. </p>' +
							'<a href="#" class="restore-item">Восстановить</a>' +
							'<a href="#" class="remove-item">Удалить</a>' +
						'</td>' +
					'</tr>'
				);
				show_message('Успешно удалено!');
			}
		})
	})
	$(document).on('click', 'a.restore-item', function(e){
		e.preventDefault();
		var th = $(this).closest('[modification_id]');
		var img = th.find('div.img').html();
		var model_name = th.find('a.model-name').html();
		var modification_id = th.attr('modification_id');
		$.ajax({
			type: 'post',
			url: '/ajax/garage.php',
			data: 'act=modification_restore&modification_id=' + modification_id,
			success: function(response){
				// console.log(response); return false;
				var m = JSON.parse(response);
				// console.log(m);
				$('[modification_id=' + modification_id + ']').each(function(){
					$(this).remove();
				})
				$('.active-tab .removable').remove();
				if (!$('div.mosaic-view > div.not-active-tab [modification_id]').size()){
					$('div.mosaic-view > div.not-active-tab div.clearfix').before(
						'<p class="removable">Неактивных моделей не найдено</p>'
					);
				};
				if (!$('div.list-view div.not-active-tab .wide-view [modification_id]').size()){
					$('div.list-view div.not-active-tab .wide-view').append(
						'<tr class="removable"><td colspan="4">Неактивных моделей не найдено</td></tr>'
					)
				};
				if (!$('div.list-view div.not-active-tab .middle-view [modification_id]').size()){
					$('div.list-view div.not-active-tab .middle-view').append(
						'<tr class="removable"><td colspan="2">Неактивных моделей не найдено</td></tr>'
					)
				};
				if (!$('div.list-view div.not-active-tab .small-view [modification_id]').size()){
					$('div.list-view div.not-active-tab .small-view').append(
						'<tr class="removable"><td colspan="2">Неактивных моделей не задано</td></tr>'
					)
				};
				$('div.mosaic-view > div.active-tab > div.clearfix').before(
					'<div class="item" modification_id="' + m.modification_id + '" style="">' +
						'<a class="model-name" href="#">' + model_name + '</a>' +
						'<div class="clearfix"></div>' +
						'<div class="img" style="">' + img + '</div>' +
						'<div class="description">' +
							'<div class="parametrs">' +
								'<p>Имя: ' + m.title_garage + '</p>' +
								'<p>VIN: ' + m.vin + '</p>' +
								'<p>Год выпуска: ' + m.year + '</p>' +
							'</div>' +
						'</div>' +
						'<div class="clearfix"></div>' +
						'<a href="" class="remove-item">Удалить</a>' +
					'</div>'
				);
				$('div.list-view > div.active-tab > table.wide-view').append(
					'<tr modification_id="' + m.modification_id + '">' +
						'<td><a href="#">' + m.title_modification + '</a></td>' +
						'<td><p>' + m.vin + ' </p></td>' +
						'<td><p>' + m.year + '</p></td>' +
						'<td><a href="#" class="remove-item">Удалить</a></td>' +
					'</tr>'
				);
				$('div.list-view > div.active-tab > table.middle-view').append(
					'<tr modification_id="' + m.modification_id + '">' +
						'<td><p>' +
							'<a href="#">' + m.title_modification + '</a> ' + m.year + ' г.в. </p></td>' +
						'<td><a href="#" class="remove-item">Удалить</a></td>' +
					'</tr>'
				);
				$('div.list-view > div.not-active-tab > table.small-view').append(
					'<tr modification_id="' + m.modification_id + '">' +
						'<td>' +
							'<p>' +
								'<a href="#">' + m.title_modification + '</a> ' + m.year + ' г.в. </p>' +
							'<a href="#" class="remove-item">Удалить</a>' +
						'</td>' +
					'</tr>'
				);
				show_message('Успешно восстановлено!');
			}
		})
	})
	$(document).on('click', 'div.not-active-tab a.remove-item', function(e){
		e.preventDefault();
		if (!confirm('Вы действительно хотите удалить из гаража?')) return false;
		var th = $(this).closest('[modification_id]');
		var modification_id = th.attr('modification_id');
		$.ajax({
			type: 'post',
			url: '/ajax/garage.php',
			data: 'act=modification_delete_fully&modification_id=' + modification_id,
			success: function(response){
				// console.log(response); return;
				$('div.not-active-tab [modification_id=' + modification_id + ']').remove();
				show_message('Успешно удалено из гаража');
			}
		})
	})
	$(document).on('click', '.active-tab [modification_id]', function(event){
		var t = $('a.remove-item');
		if (t.is(event.target)) return false;
		document.location.href = '/garage/' + $(this).attr('modification_id');
	})
})
function get_form_data(){
	var form = $('div.filter-form form');
	var vehicle = form.find('select[name=vehicle_id]').val();
	var brend = form.find('select[name=brend_id]').val();
	var model = form.find('select[name=model_id]').val();
	if (model) var model_id = form.find('select[name=model_id] option[value=' + model + ']').attr('model_id');
	else model = false;
	var year = form.find('select[name=year_id]').val();
	return {
		vehicle: vehicle,
		brend: brend,
		model_id: model_id,
		model: model,
		year: year
	}
};