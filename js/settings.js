var myMap;
function get_groups() {
	var result;
	$.ajax({
		type: "POST",
		url: "/ajax/get_groups.php",
		data: "",
		async: false,
		success: function(msg) {
			result = JSON.parse(msg);
		}
	});
	return result;
}
function createSubMenu(item, collection, submenu) {
	var submenuItem = $('<li value="' + item.id + '">' + item.title + '</li>'),
	placemark = new ymaps.Placemark(
		[item.coord_1, item.coord_2], 
		{
			balloonContent: item.balloon,
			placemark_id: item.id
		},
		{
			balloonCloseButton: false,
		}
	);
	collection.add(placemark);
	collection.events.add('click', function(e){
		var placemark = e.get('target');
		var issue_id = placemark.properties.get('placemark_id');
		$('select[name=issue_id] option').prop('selected', false);
		$('select[name=issue_id] option[value=' + issue_id + ']').prop('selected', true).trigger('refresh');
		$('#show_map #apply').attr('issue_id', issue_id);
	})
	submenuItem
		.appendTo(submenu)
		.on('click', function() {
			if (!placemark.balloon.isOpen()) {
				myMap.setCenter([item.coord_1, item.coord_2], 14);
				placemark.balloon.open();
				$('#show_map #apply').attr('issue_id', item.id);
			} 
			else placemark.balloon.close();
		});
}
function init() {
	myMap = new ymaps.Map('issue_check', {
		center: [50.8636, 74.6111],
		zoom: 12,
		controls: []
	}, {
		searchControlProvider: 'yandex#search'
	});
	var menu = $('<ul name="issue" class="hidden"></ul>');
	var collection = new ymaps.GeoObjectCollection(null);
	myMap.geoObjects.add(collection);
	var groups = get_groups();
	for (var key in groups) createSubMenu(groups[key], collection, menu);
	$('#div_issue').append(menu);
	$('select[name=issue_id]').on('change', function(){
		var issue_id = $(this).val();
		if (issue_id) $('ul[name=issue] li[value=' + $(this).val() + ']').click();
		else{
			myMap.setBounds(myMap.geoObjects.getBounds());
			$('#show_map #apply').attr('issue_id', '');
		} 
	})
	// Выставляем масштаб карты чтобы были видны все группы.
	myMap.setBounds(myMap.geoObjects.getBounds());
}
$(function() {
	$("#same-address").styler();
	$("input[name='delivery-select']").styler();
	$.mask.definitions['~'] = '[+-]';
	$("#phone, #additional-phone").mask("+7 (999) 999-99-99");
	//additional-functions show
	$("#additional-functions").change(function(event) {
		if ($(this).prop("checked") == true) {
			$("form#additional .additional-functions").show();
		} else {
			$("form#additional .additional-functions").hide();
		}
	});
	$('.bt_close').on('click', function() {
		$('#show_map').css('display', 'none');
		$('#overlay').css('display', 'none');
		$('.popup_selected').empty();
	})
	$('#bt_show_map').on('click', function(e) {
		e.preventDefault();
		$('#issue_check').html('<div id="image_map"><img src="/images/preload.gif" alt=""></div>');
		$('#overlay').css('display', 'flex');
		$('#show_map').css('display', 'flex');
		$.getScript('https://api-maps.yandex.ru/2.1/?lang=ru_RU&amp;apikey=64b4b12b-f136-4cc3-bfe2-3418e1c7b59a', function(){
			$('#image_map').remove();
			$('#div_issue select[name=issue_id] option').prop('selected', false);
			$('#div_issue select[name=issue_id] option:first-child').prop('selected', true);
			$('#div_issue select[name=issue_id]').trigger('refresh');
			ymaps.ready(init);
		})
	})
	$('.text_selected').on('click', function() {
		$(this).next().toggleClass('opened');
	})
	$('#apply').on('click', function() {
		if (!$(this).attr('issue_id')){
			show_message('Выберите пункт выдачи!', 'error');
			return false;
		}
		$('#show_map').css('display', 'none');
		$('#overlay').css('display', 'none');
		$('#pickup-points option').prop('selected', false);
		$('#pickup-points option[value=' + $(this).attr('issue_id') + ']').prop('selected', true);
		$('#pickup-points').trigger('refresh');
	})
	$('#save_form').on('click', function(e) {
		e.preventDefault();
		if ($('#new_password').val()) {
			if ($('#new_password').val() != $('#repeat_new_password').val()) {
				show_message('Пароли не совпадают!', 'error');
				return false;
			}
			if ($('#new_password').val() == $('#old_password').val()) {
				show_message('Старый и новый пароли не должны совпадать!', 'error');
				return false;
			}
			if ($('#new_password').val().length < 5) {
				show_message('Длина пароля должна быть не менее 5 символов!', 'error');
				return false;
			}
			var data = "old_password=" + $('#old_password').val();
			data += "&new_password=" + $('#new_password').val();
		}
		if ($('#delivery-way').val() == 'pickup' && !$('#pickup-points').val()) {
			show_message('Выберите пункт выдачи!', 'error');
			return false;
		}
		var delivery_type = $('#delivery-way').val() == 'pickup' ? 'Самовывоз' : 'Доставка';
		if (data) data += '&delivery_type=' + delivery_type;
		else data = 'delivery_type=' + delivery_type;
		data += '&issue_id=' + $('#pickup-points').val();
		if ($('#get_news').is(':checked')) data += '&get_news=1';
		else data += '&get_news=0';
		if ($('#hide_analogies').is(':checked')) data += '&show_all_analogies=1';
		else data += '&show_all_analogies=0';
		if ($('#get_notifications').is(':checked')) data += '&get_notifications=1';
		else data += '&get_notifications=0';
		if ($('#bonus_program').is(':checked')) data += '&bonus_program=1';
		else data += '&bonus_program=0';
		data += '&email=' + $('#email').val();
		data += '&telefon=' + $('#phone').val();
		data += '&adres=' + $('#address').val();
		if (!$('#additional-functions').is(':checked')) {
			data += '&currency_id=1';
			data += '&markup=0';
		} else {
			data += '&currency_id=' + $('#currency').val();
			data += '&markup=' + $('#markup').val();
		}
		// console.log(data); e.preventDefault(); 
		$.ajax({
			type: "POST",
			url: "/ajax/settings.php",
			data: data,
			async: false,
			success: function(msg) {
				console.log(msg);
				switch (+msg) {
					case 1:
						show_message('Неверный текущий пароль!', 'error');
						break;
					case 2:
						show_message('Такой e-mail уже занят!', 'error');
						break;
					case 3:
						$.cookie('message', 'Изменения успешно сохранены!', cookieOptions);
						document.location.reload();
						break;
					case 4:
						show_message('Такой номер телефона уже существует!', 'error');
						break;

				}
			}
		});
	})
	$('.binded').on('click', function() {
		document.location.href = '/?act=unbind&id=' + $(this).attr('social_id');
	})
});