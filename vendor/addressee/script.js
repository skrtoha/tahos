let $address_id = $('input[name=address_id]');
var $tooltip = $('#tooltip');
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
function setDefault(address_id){
    $.ajax({
        url: '/ajax/settings.php',
        type: 'post',
        data: {set_default_address: address_id},
        success: function(response){}
    })
}
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
$(function(){
    $(document).on('keyup', function(){
        $('#kladr_autocomplete ul li:first-child').remove();
    })
    $('form.js-form-address').on('submit', function(e){
        e.preventDefault();
    })
    $(document).on('click', 'div.address', function(e){
        let th = $(e.target);
        if (th.hasClass('delete_address')) return deleteAddress(this);
        if (th.hasClass('jq-radio')) return setDefault(th.find('input').val());
        if (th.attr('name') == 'isDefault') return setDefault(th.val());
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
                if (getParams()['view'] != 'users'){
                    $('#set-address input[type=radio]').styler();
                }
            }
        })

        $('input[name=address_id]').val('');
        form.find('input:not([type=button])').val('');
    })

    var $container = $(document.getElementById('address_multiple_fields'));

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
});