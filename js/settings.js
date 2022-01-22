var myMap;
let htmlCloseButton = `<button title="Close (Esc)" type="button" class="mfp-close">×</button>`;
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
    let center;
    let groups = get_groups();
    if (groups.length == 1){
        center = [+groups[0]['coord_1'], +groups[0]['coord_2']]
    }
    else center = [55.751574, 37.573856];

	myMap = new ymaps.Map('issue_check', {
        center: center,
        zoom: 14,
		controls: []
	}, {
		searchControlProvider: 'yandex#search'
	});
	var menu = $('<ul name="issue" class="hidden"></ul>');
	var collection = new ymaps.GeoObjectCollection(null);

    myMap.geoObjects.add(collection);
    for (var key in groups){
        createSubMenu(groups[key], collection, menu);
    }
	$('#div_issue').append(menu);
	$('select[name=issue_id]').on('change', function(){
		var issue_id = $(this).val();
		if (issue_id) $('ul[name=issue] li[value=' + $(this).val() + ']').click();
		else{
			myMap.setBounds(myMap.geoObjects.getBounds());
			$('#show_map #apply').attr('issue_id', '');
		} 
	})
    if (groups.length > 1) myMap.setBounds(myMap.geoObjects.getBounds());
}
$(function() {
	$("#same-address").styler();
	$("input[name='delivery-select']").styler();
	$.mask.definitions['~'] = '[+-]';
	$("#phone, #additional-phone").mask("+7 (999) 999-99-99");
	//additional-functions show
	$("#additional-functions").change(function(event) {
		if ($(this).prop("checked") == true) {
			$("#additional .additional-functions").show();
		} else {
			$("#additional .additional-functions").hide();
		}
	});
    $('div.set-addresses > button').on('click', function(e) {
        e.preventDefault();
        $('#overlay').css('display', 'flex');
        $('#set-address').css('display', 'flex');
        $('input[type=radio]').styler();
    })
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
        data += '&pay_type=' + $('#pay_type').val();
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
    $('span.icon-mail2:not(.applied)').on('click', function(){
        const $th = $(this);
        const email = $th.prev().val();
        $.magnificPopup.open({
            items: {
                src: `
                    <div id="email-confirmation">
                        <h3>Подтверждение электронной почты</h3>
                        <p>На ваш email <b>${email}</b> будет отправлена ссылка для подтверждения</p>
                        <button>Отправить</button>
                    </div>
                `,
                type: 'inline'
            }
        });
    })
    $('span.icon-phone:not(.applied)').on('click', function(){
        const $th = $(this);
        const phoneNumber = $th.closest('.input-wrap').find('input[name="data[phone]"]').val();
        $.magnificPopup.open({
            items: {
                src: `
                    <div id="phone-confirmation">
                        <h3>Подтверждение номера телефона</h3>
                        <p>На номер телефона <b>${phoneNumber}</b> будет отпрвлено сообщение с кодом подтверждения</p>
                        <button class="send">Отправить</button>
                    </div>
                `
            }
        })
    })
    $(document).on('click', '#phone-confirmation button.send', function(){
        const $th = $(this);
        $.ajax({
            type: 'post',
            data: {
                act: 'send_sms_confirmation',
                phone: $th.closest('div').find('b').text()
            },
            url: '/ajax/common.php',
            success: function(response){
                $('#phone-confirmation').html(`
                    <form id="enter_code" action="">
                        <label for="input_code">Введите код подтверждения</label>
                        <input type="number" required name="code" placeholder="Код подтверждения">
                        <input type="submit" value="Подтвердить">
                    </form>
                    ${htmlCloseButton}
                `)
            }
        })
    })
    $(document).on('submit', '#enter_code', function(e){
        e.preventDefault();
        let formData = {};
        $.each($(this).serializeArray(), function(i, item){
            formData[item.name] = item.value;
        })
        $.ajax({
            url: '/ajax/common.php',
            data: {
                act: 'confirm_phone_number',
                code: formData.code
            },
            type: 'post',
            success: function(response){
                if (response === 'ok'){
                    $.magnificPopup.close();
                    show_message('Номер успешно подтвержден');
                    const $phone = $('input[name="data[phone]"]');
                    $phone.next().remove();
                    $phone.after(`
                        <span class="icon-checkmark1"></span>
                    `);

                }
                else show_message('Неверный номер', 'error');
            }
        })
    })
    $(document).on('click', '#email-confirmation', function (){
        const $th = $(this);
        $.ajax({
            type: 'post',
            data: {
                act: 'send_email_confirmation',
                email: $th.closest('div').find('b').text()
            },
            url: '/ajax/common.php',
            success: function(response){
                $.magnificPopup.close();
                if (response === 'ok') show_message('Подтверждение отправлено на ваш email');
                else show_message('Произошла ошибка отправки', 'error');
            }
        })
    })
});