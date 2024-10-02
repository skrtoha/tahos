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

const getHtml = (data, address_id = '', isDefault = 0) => {
    const checked = isDefault ? 'checked' : '';
    const default_address = isDefault ? 1 : 0;
    let output = `
         <div class='address' id='${address_id}'>
            <input ${checked} type='radio' name='isDefault' value='${address_id}'>
    `;
    $.each(data, (i, item) => {
        output += `
            <span kladr_id='${item.kladr_id}' name='${item.name}'>${item.value}</span>
        `;
    })
    output += `
        <i class="fa fa-times delete_address" aria-hidden="true"></i>
        <input form="${form}" type="hidden" name="addressee[]" value='${JSON.stringify(data)}'>
        <input form="${form}" type="hidden" name="default_address[]" value="${default_address}">
        <input type="hidden" form="${form}" name="address_id[]" value="${address_id}">
        </div>
    `;
    return output;
}

function deleteAddress(obj){
    if (!confirm('Вы уверены?')) return false;
    let th = $(obj).closest('div');
    th.remove();
}
function setDefault(obj){
    const selector = 'input[name="default_address[]"]';
    $('div.address').find(selector).val(0);
    obj.closest('div.address').find(selector).val(1)
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
        if (th.hasClass('jq-radio')) return setDefault(th);
        if (th.attr('name') == 'isDefault') return setDefault(th);
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

        if (address_id){
            $('.address[id=' + address_id + ']').remove()
        }

        $('#set-address .right').append(getHtml(formData));
        const selector = '#set-address-wrapper .right div.address';
        if ($(selector).length == 1){
            const obj = $(selector + ':first-child input[name=isDefault]');
            obj.prop('checked', true);
            setDefault(obj);
        }
        $('.address input[type=radio]').styler();

        $('input[name=address_id]').val('');
        form.find('input:not([type=button])').val('');
    })

    $('div.set-addresses > button').on('click', function(e) {
        e.preventDefault();
        $('#overlay').css('display', 'flex');
        $('#set-address').css('display', 'flex');
        $('.address input[type=radio]').styler();
    })
    $('.bt_close').on('click', function() {
        $(this).closest('div.popup').css('display', 'none');
        $('#overlay').css('display', 'none');
        $('.popup_selected').empty();
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