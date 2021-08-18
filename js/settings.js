var myMap;
let $address_id = $('input[name=address_id]');
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
function setAddress(formData){
    let container = $('form.js-form-address');

    $.each(formData, function(i, item){
        input = container.find('input[name=' + i + ']');
        input.fias({
            type: $.fias.type[i],
            withParents: true
        });
        switch(i){
            case 'zip':
            case 'building':
                input.val(item.value);
                break;
            default:
                input.fias('controller').setValueById(item.kladr_id);
        }
    })
}
function deleteAddress(obj){
    if (!confirm('Вы уверены?')) return false;
    let th = $(obj).closest('div');
    $.ajax({
        type: 'post',
        url: '/admin/ajax/user.php',
        data: {
            act: 'deleteAddress',
            address_id: th.attr('id')
        },
        success: function(){
            th.remove();
        }
    })
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
	    $(this).closest('div.popup').css('display', 'none');
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
    $('div.set-addresses > button').on('click', function(e) {
        e.preventDefault();
        $('#overlay').css('display', 'flex');
        $('#set-address').css('display', 'flex');
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
		} else {
			data += '&currency_id=' + $('#currency').val();
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
    $('#delivery-way').on('change', function(){
        $('div.pickup-addreses').toggleClass('hidden');
        $('div.set-addresses').toggleClass('hidden');
        $(document).trigger('refresh');
    })
    $(document).on('keyup', function(){
        $('#kladr_autocomplete ul li:first-child').remove();
    })
    $('form.js-form-address').on('submit', function(e){
        e.preventDefault();
    })

    $(document).on('click', 'div.address', function(e){
        let th = $(e.target);
        if (th.hasClass('delete_address')) return deleteAddress(this);
        let formData = {};
        th.closest('div.address').find('span').each(function(){
            let th = $(this);
            formData[th.attr('name')] = {};
            formData[th.attr('name')].kladr_id = +th.attr('kladr_id');
            formData[th.attr('name')].value = th.html();
        })
        setAddress(formData);
        let address_id = th.closest('div.address').attr('id');
        $address_id.val(address_id);
    })
    $('form.js-form-address input[type=button]').on('click', function (e){
        let formData = [];
        let form = $(this).closest('form');
        let countFilledFields = 0;
        let address_id = $address_id.val() ? $address_id.val() : null;
        form.find('input').each(function(item){
            let th = $(this);
            if (!th.val()) return 1;
            if (typeof th.attr('name') === 'undefined') return 1;
            let obj = {};
            obj.name = th.attr('name');
            if (typeof th.attr('data-kladr-id') !== 'undefined'){
                obj.kladr_id = th.attr('data-kladr-id');
            }
            else obj.kladr_id = null;
            obj.value = th.val();
            obj.label = th.prev().html();
            formData.push(obj);
            countFilledFields++;
        })

        if (countFilledFields < 4) return show_message('Слишком мало данных для сохранения!', 'error');

        $.ajax({
            type: 'post',
            url: '/admin/ajax/user.php',
            data: {
                act: 'changeAddress',
                address_id: address_id,
                data: formData
            },
            success: function(response){
                if (address_id){
                    $('.address[id=' + address_id + ']').remove()
                }
                $('#set-address .right').append(response);
            }
        })

        $('input[name=address_id]').val('');
        form.find('input:not([type=button])').val('');
    })
});

// Form example
(function () {
    var $container = $(document.getElementById('address_multiple_fields'));

    var $tooltip = $('#tooltip');

    var $zip = $container.find('[name="zip"]'),
        $region = $container.find('[name="region"]'),
        $district = $container.find('[name="district"]'),
        $city = $container.find('[name="city"]'),
        $street = $container.find('[name="street"]'),
        $building = $container.find('[name="building"]');
    $()
        .add($region)
        .add($district)
        .add($city)
        .add($street)
        .add($building)
        .fias({
            parentInput: $container.find('.js-form-address'),
            verify: true,
            select: function (obj) {
                if (obj.zip) $zip.val(obj.zip);//Обновляем поле zip
                setLabel($(this), obj.type);
                $tooltip.hide();
            },
            check: function (obj) {
                var $input = $(this);

                if (obj) {
                    setLabel($input, obj.type);
                    $tooltip.hide();
                }
                else {
                    showError($input, 'Ошибка');
                }
            },
            checkBefore: function () {
                var $input = $(this);

                if (!$.trim($input.val())) {
                    $tooltip.hide();
                    return false;
                }
            }
        });

    $region.fias('type', $.fias.type.region);
    $district.fias('type', $.fias.type.district);
    $city.fias('type', $.fias.type.city);
    $street.fias('type', $.fias.type.street);
    $building.fias('type', $.fias.type.building);

    $district.fias('withParents', true);
    $city.fias('withParents', true);
    $street.fias('withParents', true);

    // Отключаем проверку введённых данных для строений
    $building.fias('verify', false);

    // Подключаем плагин для почтового индекса
    $zip.fiasZip($container);

    function setLabel($input, text) {
        text = text.charAt(0).toUpperCase() + text.substr(1).toLowerCase();
        $input.parent().find('label').text(text);
    }

    function showError($input, message) {
        $tooltip.find('span').text(message);

        var inputOffset = $input.offset(),
            inputWidth = $input.outerWidth(),
            inputHeight = $input.outerHeight();

        var tooltipHeight = $tooltip.outerHeight();
        var tooltipWidth = $tooltip.outerWidth();

        $tooltip.css({
            left: (inputOffset.left + inputWidth - tooltipWidth) + 'px',
            top: (inputOffset.top + (inputHeight - tooltipHeight) / 2 - 1) + 'px'
        });

        $tooltip.fadeIn();
    }
})();