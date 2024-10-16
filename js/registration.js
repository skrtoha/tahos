let myMap2;

function createSubMenu(item, collection, submenu) {
    const submenuItem = $('<li value="' + item.id + '">' + item.title + '</li>');
    let placemark = new ymaps.Placemark(
        [+item.coord_1, +item.coord_2],
        {
            balloonContentBody: item.balloon,
            placemark_id: item.id
        },
        {
            balloonCloseButton: true,
        }
    );
    collection.add(placemark);

    submenuItem
        .appendTo(submenu)
        .on('click', function() {
            if (!placemark.balloon.isOpen()) {
                myMap2.setCenter([+item.coord_1, +item.coord_2], 15);
                placemark.balloon.open();
            }
            else placemark.balloon.close();
        });
}
function init() {
    const $issue = $('select[name=issue_id]');
    $('#map').removeClass('loading');
    myMap2 = new ymaps.Map('map2', {
        center: [50.8636, 74.6111],
        zoom: 10,
        controls: []
    }, {
        searchControlProvider: 'yandex#search'
    });
    const menu = $('<ul name="issue"></ul>');
    const collection = new ymaps.GeoObjectCollection(null);

    $.ajax({
        type: "POST",
        url: "/ajax/get_groups.php",
        success: function(msg) {
            const groups = JSON.parse(msg);
            for (const key in groups) createSubMenu(groups[key], collection, menu);
            $('#div_issue').after(menu);
            $issue.on('change', function(){
                const issue_id = $(this).val();
                if (issue_id) $('ul[name=issue] li[value=' + $(this).val() + ']').click();
                else myMap2.setBounds(myMap2.geoObjects.getBounds());
            })

            $issue.prop('disabled', false).trigger('refresh')

            collection.events.add('click', function(e){
                const placemark = e.get('target');
                const issue_id = placemark.properties.get('placemark_id');
                $('select[name=issue_id] option').prop('selected', false);
                $('select[name=issue_id] option[value=' + issue_id + ']').prop('selected', true).trigger('refresh');
            })

            myMap2.geoObjects.add(collection);
            myMap2.setBounds(myMap2.geoObjects.getBounds());
        }
    });

}
$(function(){
    if ($(document).width() >= 925){
        $.getScript('https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=9c8a8b41-54f2-48ab-993b-1b8384cda4eb', function(){
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
    document.addEventListener('captchaSuccessed_registration', e => {
        $('#registration > button').prop('disabled', false)
      })
});