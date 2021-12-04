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
	})
	submenuItem
		.appendTo(submenu)
		.on('click', function() {
			if (!placemark.balloon.isOpen()) {
				myMap.setCenter([item.coord_1, item.coord_2], 15);
				placemark.balloon.open();
			} 
			else placemark.balloon.close();
		});
}
function init() {
	$('#map').removeClass('loading');
	myMap = new ymaps.Map('map', {
		center: [50.8636, 74.6111],
		zoom: 12,
		controls: []
	}, {
		searchControlProvider: 'yandex#search'
	});
	var menu = $('<ul name="issue"></ul>');
	var collection = new ymaps.GeoObjectCollection(null);
	myMap.geoObjects.add(collection);
	var groups = get_groups();
	for (var key in groups) createSubMenu(groups[key], collection, menu);
	$('#div_issue').after(menu);
	$('select[name=issue_id]').on('change', function(){
		var issue_id = $(this).val();
		if (issue_id) $('ul[name=issue] li[value=' + $(this).val() + ']').click();
		else myMap.setBounds(myMap.geoObjects.getBounds());
	})
	// Выставляем масштаб карты чтобы были видны все группы.
	myMap.setBounds(myMap.geoObjects.getBounds());
	$('select[name=issue_id]').prop('disabled', false).trigger('refresh')
}
$(function(){
	if ($(document).width() >= 925){
		$.getScript('https://api-maps.yandex.ru/2.1/?lang=ru_RU&amp;apikey=64b4b12b-f136-4cc3-bfe2-3418e1c7b59a', function(){
			ymaps.ready(init);
		})
	}
	$(".user_type label").click(function(){
		if ($(this).attr("for") == "type_user_1") {
			$(".registration .company_name").hide();
		}
		if ($(this).attr("for") == "type_user_2") {
			$(".registration .company_name").show();
		}
	});
	$.mask.definitions['~']='[+-]';
	$(".input_phone input[type='text']").mask("+7 (999) 999-99-99");
	$(".h_overlay, .overlay").click(function(){
		$(".input_phone .info").hide();
		$(".input_email .info").hide();
	});
	$(".input_email .info_btn").click(function(){
		$(".h_overlay, .overlay, .email_overlay, .input_email .info").show();
	});
	$(".input_phone .info_btn").click(function(){
		$(".h_overlay, .overlay, .phone_overlay, .input_phone .info").show();
	});
	$('.text_selected').on('click', function(){
		$(this).next().toggleClass('opened');
	})
	$("select[name=delivery_type]").on('change', function(){
		const th = $(this);
        if (th.val() == 'Доставка'){
            $('#div_issue').hide();
            $('div.set-addresses').show();
        }
        else{
            $('#div_issue').show();
            $('div.set-addresses').hide();
        }
	});
});