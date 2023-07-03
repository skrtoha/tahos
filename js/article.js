/**
 * hide form if there are no providers
 * @type {Boolean}
 */
var hidable_form = true;
var res;
let tabSelector = '#Tab__search-result-tabs__';

function removeWithoutProviders(){
    if ($('#in_stock_only').is(':checked')){
        $elements = $('td');
        $.each($elements, function(i, item){
            let th = $(this);
            if (th.text() === 'Поставщиков не найдено') th.closest('tr').remove()
        })
        $.each($('#analogies div.mobile-layout div.goods-header'), function(i, item){
            let th = $(this);
            let table = th.next('table');
            if (!table.find('tr td').size()) th.remove();  
        })
    }
}

function setTabType(tab){
    let search_type;
    let $filterForm = $('#offers-filter-form');
    switch(tab){
        case 'Tab_1':
            search_type = "articles";
            $('#price-from').val($('#price_from').val());
            $('#price-to').val($('#price_to').val());
            $('#time-from').val($('#time_from').val());
            $('#time-to').val($('#time_to').val());
            break;
        case 'Tab_2': search_type = "substitutes"; break;
        case 'Tab_3': search_type = "analogies"; break;
        case 'Tab_4': search_type = "complects"; break;
    }
    $filterForm
        .removeClass('hidden')
        .attr('search_type', search_type)
    ;
    if (search_type === 'articles' && hidable_form){
        $filterForm.addClass('hidden');
    }
    if (search_type === 'substitutes' || search_type === 'analogies' || search_type === 'complects'){
        var item_id = $('#item_id').val();
        var data = "item_id=" + item_id + "&search_type=" + search_type;
        $.ajax({
            type: "POST",
            url: "/ajax/article_filter.php",
            data: data,
            beforeSend: function(){
                popup.style.display = 'flex';
                $(tabSelector + ' .articul-table').html(
                    '<tr class="gif">' +
                    '<td colspan="7"></td>' +
                    '</tr>');
                $(tabSelector + ' .mobile-layout').html(
                    '<div class="gif"></div>'
                );
            },
            success: function(msg){
                if (msg){
                    res = JSON.parse(msg);
                    sortStoreItems('brend');
                    var si = store_items(res.store_items, res.user, search_type);
                    if (Object.keys(res.prices).length){
                        $('#offers-filter-form').removeClass('hidden');
                        $('#price-from').val(Math.min.apply(null, res.prices));
                        $('#price-to').val(Math.max.apply(null, res.prices));
                        $('#time-from').val(Math.min.apply(null, res.deliveries));
                        $('#time-to').val(Math.max.apply(null, res.deliveries));
                    }
                    else $('#offers-filter-form').addClass('hidden');
                }
                else{
                    si = {
                        full: '<div>Ничего не найдено.</div>',
                        mobile: '<div>Ничего не найдено.</div>'
                    }
                    $('#offers-filter-form').addClass('hidden');
                }
                $(tabSelector + tab + ' .articul-table').html(si.full);
                $(tabSelector + tab + ' .mobile-layout').html(si.mobile);
                if (tab === 'Tab_3' && si !== '<div>Ничего не найдено</div>') $('#analogies').show();
                $('a.sortable').removeClass('asc');
                $('a.sortable.brend').addClass('asc');
                price_format();
                applyUserMarkup();
                popup.style.display = 'none';
            }
        });
    }
}

function set_tabs(){
    $.ionTabs("#search-result-tabs",{
        type: "none",
        onChange: function(obj){
            setTabType(obj.tab);
            if (obj.tab === 'Tab_1'){
                setTabType('Tab_3');
            }
        }
    });
}
/**
 * checks is there any item in basket
 * @param  {[object]}  store_items_list [description]
 * @return {Boolean}
 */
function isInBasket(store_items_list){
    for(var key in store_items_list){
        var store_item = store_items_list[key].store_item;
        for(var k in store_item.list){
            if (store_item.list[k].in_basket) return true;
        }
        if(typeof store_item.prevails != 'undefined'){
            for(var k in store_item.prevails){
                if (store_item.prevails[k].in_basket) return true;
            }
        }
    }
    return false;
}
/**
 * Sets a new value in current to-stock-button
 * @param {[type]} obj object with parameters (value, store_id, item_id)
 */
function setNewValueCurrentItem(obj){
    $('li[store_id=' + obj.store_id + '][item_id=' + obj.item_id + '] input').val(obj.quan);
    $('i.to-stock-btn[store_id=' + obj.store_id + '][item_id=' + obj.item_id + ']').html('<i class="goods-counter">' + obj.quan + '</i>');
}
/**
 * sets in value inBasket amount of item
 * @param {[type]} store_id [description]
 * @param {[type]} item_id  [description]
 * @param {[type]} amount   [addable value]
 * @param isReplace
 */
function setAmountInBasket(store_id, item_id, amount, isReplace = false){
    if (typeof inBasket[store_id + ':' + item_id] == 'undefined') return inBasket[store_id + ':' + item_id] = amount;
    if (isReplace) return inBasket[store_id + ':' + item_id] = parseInt(amount);
    inBasket[store_id + ':' + item_id] = parseInt(inBasket[store_id + ':' + item_id]);
    return inBasket[store_id + ':' + item_id] += amount;
}
/**
 * [setNewValueCartIcon sets common amount in basket icon]
 */
function setNewValueCartIcon(){
    var summ = 0;
    $('.cart span').remove();
    for(var k in inBasket){
        inBasket[k] = parseInt(inBasket[k]);
        summ += inBasket[k];
    }
    return $('.cart').append('<span>' + summ + '</span>');
}
function setNewValueAjax(obj){
    $.ajax({
        type: "POST",
        url: "/ajax/to_basket.php",
        data: obj,
        success: function(msg){
            get_basket(JSON.parse(msg));
            show_popup_basket();
            setTimeout(function(){
                if (!$('.cart-popup').is(':hover')) $('.overlay').click();
            }, 2500);
            applyUserMarkup();
        }
    });
}
function setNewValue(obj){
    obj.quan = inBasket[obj.store_id + ':' + obj.item_id];
    setNewValueCurrentItem(obj);
    setNewValueCartIcon();
    setNewValueAjax(obj);
}
function store_items(store_items, user, search_type = null){
    const isInBasket = window.isInBasket(store_items);
    /**
     * adds to class if no items exists in basket
     * @type {[string]}
     */
    const hidden = isInBasket ? '' : 'hidden';
    const c_si = Object.keys(store_items).length;
    if (!c_si) return '<div>Ничего не найдено</div>';
    // console.log(Object.keys(si.list).length);
    let priceTh;
    let deliveryTh;
    if (search_type === 'analogies'){
        priceTh = '';
        deliveryTh = '';
    }
    else{
        priceTh = '<th>Цена</th>';
        deliveryTh = '<th>Срок</th>';
    }
    let full =
        '<tr class="shown">' +
        '<th><a class="sortable brend" href="">Бренд</a></th>' +
        '<th>Наименование</th>' +
        '<th>Поставщик</th>' +
        '<th><a class="sortable in_stock" href="">В наличии</a></th>' +
        '<th><a class="sortable delivery" href="">Срок</a></th>' +
        '<th><a class="sortable price" href="">Цена</a></th>' +
        '<th class="quan ' + hidden + '">К заказу</th>' +
        '<th><i class="fa fa-cart-arrow-down" aria-hidden="true"></i></th>' +
        '</tr>';
    let mobile = '';
    let i;
    length = store_items.length;
    for (i = 0; i < length; i++){
        var id = store_items[i].item_id;
        var si = store_items[i].store_item;
        /**
         * counts amount of items in list
         */
        var csi;
        // console.log('item_id=' + si.item_id, typeof si.list);
        if (typeof si.list !== null && typeof si.list !== 'undefined') csi = Object.keys(si.list).length;
        else csi = false;
        /**
         * shows is there any prevail in item list
         * @type {[string]}
         */
        var count_prevails = typeof si.prevails !== 'undefined' ? Object.keys(si.prevails).length : false;
        /**
         * for displaying additional items if csi > 2
         * @type {[string]}
         */
        var button_row = (csi <= 2) ? 'button-row' : '';
        var empty = '';
        var si_price = si.min_price;
        if (csi > 1) var si_delivery = si.min_delivery;
        else si_delivery = false;
        full +=
            '<tr class="' + button_row + ' ' + empty + ' shown first-full">' +
            '<td style="padding: 20px 0;text-align:left">' +
            '<b class="brend_info" brend_id="' + si.brend_id + '">' +
                si.brend +
            '</b> ' +
            '<a href="/article/' + si.item_id + '-' + si.article + '" class="articul">' +
                si.article +
            '</a>' +
            '</td>' +
            '<td class="name-col" style="padding-top: 18px;text-align:left">';
        mobile +=
            '<div class="goods-header">' +
            '<p>' +
            '<b class="brend_info" brend_id="' + si.brend_id + '"> ' + si.brend + '</b> ' +
            '<a href="/article/' + si.item_id + '-' + si.article + '" class="articul">' + si.article + '</a>' +
            '</p>' +
            '<p>' + si.title_full + '</p>';
        if (
            search_type === 'analogies' &&
            typeof user !== 'undefined' &&
            typeof user.id !== 'undefined' &&
            user.allow_request_delete_item === '1' &&
            si.status == '0'
        ){
            var selector = 'item_id="' + $('#item_id').val() + '" item_diff="' + si.item_id + '" user_id="' + user.id + '"';
            mobile += '<span ' + selector + ' title="Сообщить о неверном аналоге" class="icon-tab wrongAnalogy"></span>';
            full += '<span ' + selector + ' title="Сообщить о неверном аналоге" class="icon-tab wrongAnalogy"></span>'
        };
        if (si.status == '1'){
            mobile += '<span title="Проверенный аналог" class="icon-checkmark1"></span>';
            full += '<span title="Проверенный аналог" class="icon-checkmark1"></span>';
        }
        if (+si.is_desc || si.photo){
            var stringClass = '';
            if (si.is_desc) stringClass = 'fa-cog';
            if (si.photo) stringClass = 'fa-camera';
            full +=
                '<a title="Информация о товаре" href="#">' +
                '<i item_id="' + si.item_id + '" class="fa ' + stringClass + ' product-popup-link" aria-hidden="true"></i>' +
                '</a>';
            mobile +=
                '<a title="Информация о товаре" href="#"><i item_id="' + si.item_id + '" class="fa ' + stringClass + ' product-popup-link" aria-hidden="true"></i></a>';
        }
        mobile +=
            '</div>' +
            '<table class="small-view">' +
            '<tr class="first-mobile shown">';
        full +=
            si.title_full +
            '</td>';
        if (!csi && typeof si.prevails == 'undefined'){
            full +=
                '<td colspan="5" style="padding: 20px 0">Поставщиков не найдено</td>';
            mobile +=
                '<tr class="shown first-mobile empty">' +
                '<td colspan="5">Поставщиков не найдено</td>' +
                '</tr>' +
                '</table>';
            continue;
        }
        //cipher start
        full +=
            '<td>';
        mobile +=
            '<td>';
        if (count_prevails){
            full +=
                '<ul class="prevail">';
            mobile +=
                '<ul class="prevail">';
            for (var p in si.prevails){
                full +=
                    '<li ' + si.prevails[p].noReturn + '>';
                mobile +=
                    '<li ' + si.prevails[p].noReturn + '>'; + si.prevails[p].cipher + '</li>';
                full +=
                    '<a href="" store_id="' + si.prevails[p].store_id + '">' + si.prevails[p].cipher + '</a>';
                mobile +=
                    '<a href="" store_id="' + si.prevails[p].store_id + '">' + si.prevails[p].cipher + '</a>';
                if (+si.prevails[p].checked){
                    full += `<span title="Проверенный поставщик" class="icon-shield"></span>`
                    mobile += `<span title="Проверенный поставщик" class="icon-shield"></span>`
                }
                full += '</li>';
                mobile += '</li>';
            }
            full +=
                '</ul>';
            mobile +=
                '</ul>'
        }
        if (csi){
            full +=
                '<ul>' +
                '<li ' + si_price.noReturn + '>';
            mobile +=
                '<ul>' +
                '<li ' + si_price.noReturn + '>';
            full += '<a href="" store_id="' + si_price.store_id + '">' + si_price.cipher + '</a>';
            mobile += '<a href="" store_id="' + si_price.store_id + '">' + si_price.cipher + '</a>';
            if (+si_price.checked){
                full += `<span title="Проверенный поставщик" class="icon-shield"></span>`
                mobile += `<span title="Проверенный поставщик" class="icon-shield"></span>`
            }
            full += '</li>';
            mobile += '</li>';
            if (si_delivery){
                full +=
                    '<li ' + si_delivery.noReturn + '>';
                full += '<a href="" store_id="' + si_delivery.store_id + '">' + si_delivery.cipher + '</a>';
                mobile += '<a href="" store_id="' + si_delivery.store_id + '">' + si_delivery.cipher + '</a>';
                if (+si_delivery.checked){
                    full += `<span title="Проверенный поставщик" class="icon-shield"></span>`
                    mobile += `<span title="Проверенный поставщик" class="icon-shield"></span>`
                }
                full += '</li>';
                mobile += '</li>';
            }
            full +=
                '</ul>';
            mobile +=
                '</ul>';
        }
        full +=
            '</td>';
        mobile +=
            '</td>';
        //cipher end

        //packaging start
        full +=
            '<td>';
        mobile +=
            '<td>';
        if (count_prevails){
            full +=
                '<ul class="prevail">';
            mobile +=
                '<ul class="prevail">';
            for (var p in si.prevails){
                full +=
                    '<li>' +
                    si.prevails[p].in_stock +
                    si.prevails[p].packaging_text
                '</li>';
                mobile +=
                    '<li>' +
                    si.prevails[p].in_stock +
                    si.prevails[p].packaging_text
                '</li>';
            }
            full +=
                '</ul>';
            mobile +=
                '</ul>';
        }
        if (csi){
            full +=
                '<ul>' +
                '<li>' +
                si_price.in_stock +
                si_price.packaging_text +
                '</li>';
            mobile +=
                '<ul>' +
                '<li>' +
                si_price.in_stock +
                si_price.packaging_text +
                '</li>';
            if (si_delivery){
                full +=
                    '<li>' +
                    si_delivery.in_stock +
                    si_delivery.packaging_text +
                    '</li>';
                mobile +=
                    '<li>' +
                    si_delivery.in_stock +
                    si_delivery.packaging_text +
                    '</li>';
            }
            full +=
                '</ul>';
            mobile +=
                '</ul>';
        }
        full +=
            '</td>';
        mobile +=
            '</td>';
        // packaging end;

        //delivery start;
        full +=
            '<td>';
        mobile +=
            '<td>';
        if (count_prevails){
            full +=
                '<ul class="prevail">';
            mobile +=
                '<ul class="prevail">';
            for (var p in si.prevails){
                full +=
                    '<li>'  + si.prevails[p].delivery_date + '</li>';
                mobile +=
                    '<li>' + si.prevails[p].delivery_date + '</li>';
            }
            full +=
                '</ul>';
            mobile +=
                '</ul>';
        }
        if (csi){
            full +=
                '<ul>' +
                '<li>' + si_price.delivery_date + '</li>';
            mobile +=
                '<ul>' +
                '<li>' + si_price.delivery_date + '</li>';
            if (si_delivery){
                full +=
                    '<li>' + si_delivery.delivery_date + '</li>';
                mobile +=
                    '<li>' + si_delivery.delivery_date + '</li>';
            };
            full +=
                '</ul>';
            mobile +=
                '</ul>';
        }
        full +=
            '</td>';
        mobile +=
            '</td>';
        // delivery end

        //price start
        full +=
            '<td>';
        mobile +=
            '<td>';
        if (count_prevails){
            full +=
                '<ul class="prevail">';
            mobile +=
                '<ul class="prevail">';
            for (var p in si.prevails){
                full +=
                    '<li>' + si.prevails[p].user_price + '</li>';
                mobile +=
                    '<li>' + si.prevails[p].user_price + '</li>';
            }
            full +=
                '</ul>';
            mobile +=
                '</ul>'
        }
        if (csi){
            full +=
                '<ul>' +
                '<li>' + si_price.user_price + '</li>';
            mobile +=
                '<ul>' +
                '<li>' + si_price.user_price + '</li>';
            if (si_delivery){
                full +=
                    '<li>' + si_delivery.user_price + '</li>';
                mobile +=
                    '<li>' + si_delivery.user_price + '</li>';
            }
            full +=
                '</ul>';
            mobile +=
                '</ul>';
        };
        full +=
            '</td>';
        mobile +=
            '</td>';
        //price end

        //quan start
        full +=
            '<td class="quan ' + hidden + '">';
        mobile +=
            '<td class="quan ' + hidden + '">';
        if (count_prevails){
            full +=
                '<ul class="prevail">';
            mobile +=
                '<ul class="prevail">';
            for (var p in si.prevails){
                full +=
                    '<li packaging="' + si.prevails[p].packaging + '" store_id="' + si.prevails[p].store_id + '" item_id="' + id + '" class="count-block">';
                mobile +=
                    '<li packaging="' + si.prevails[p].packaging + '" store_id="' + si.prevails[p].store_id + '" item_id="' + id + '" class="count-block">';
                if (si.prevails[p].in_basket){
                    full +=
                        '<input value="' + si.prevails[p].in_basket + '">';
                    mobile +=
                        '<input value="' + si.prevails[p].in_basket + '">';
                }
                full +=
                    '</li>';
                mobile +=
                    '</li>';
            }
            full +=
                '</ul>';
            mobile +=
                '</ul>';
        }
        if (csi){
            full +=
                '<ul>' +
                '<li packaging="' + si_price.packaging + '" store_id="' + si_price.store_id + '" item_id="' + si.item_id + '" class="count-block">';
            mobile +=
                '<ul>' +
                '<li packaging="' + si_price.packaging + '" store_id="' + si_price.store_id + '" item_id="' + si.item_id + '" class="count-block">';
            if (si_price.in_basket){
                full +=
                    '<input value="' + si_price.in_basket + '">';
                mobile +=
                    '<input value="' + si_price.in_basket + '">';
            }
            full +=
                '</li>';
            mobile +=
                '</li>';
            if (si_delivery){
                full +=
                    '<li packaging="' + si_delivery.packaging + '" store_id="' + si_delivery.store_id + '" item_id="' + si.item_id + '" class="count-block">';
                if (si_delivery.in_basket){
                    full +=
                        '<input value="' + si_delivery.in_basket + '">';
                    mobile +=
                        '<input value="' + si_delivery.in_basket + '">';
                }
                full +=
                    '</li>';
                mobile +=
                    '</li>';
            }
            full +=
                '</ul>';
            mobile +=
                '</ul>';
        }
        full +=
            '</td>';
        mobile +=
            '</td>';
        //quan end

        //stock start
        full +=
            '<td>';
        mobile +=
            '<td>';
        if (count_prevails){
            full +=
                '<ul class="prevail to-cart-list">';
            mobile +=
                '<ul class="prevail to-cart-list">';
            for (var p in si.prevails){
                full +=
                    '<li>' +
                    '<i price="' + si.prevails[p].price + '" store_id="' + si.prevails[p].store_id + '" ' +
                    'item_id="' + si.item_id + '" ' +
                    'packaging="' + si.prevails[p].packaging + '"' +
                    ' class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">';
                mobile +=
                    '<li>' +
                    '<i price="' + si.prevails[p].price + '" store_id="' + si.prevails[p].store_id + '" ' +
                    'item_id="' + si.item_id + '" ' +
                    'packaging="' + si.prevails[p].packaging + '"' +
                    ' class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">';
                if (si.prevails[p].in_basket){
                    full +=
                        '<i class="goods-counter">' + si.prevails[p].in_basket + '</i> ';
                    mobile +=
                        '<i class="goods-counter">' + si.prevails[p].in_basket + '</i> ';
                }
                full +=
                    '</i>' +
                    '</li>';
                mobile +=
                    '</i>' +
                    '</li>';
            }
            full +=
                '</ul>';
            mobile +=
                '</ul>'
        }
        if (csi){
            full +=
                '<ul>' +
                '<li>' +
                '<i price="' + si_price.price + '" store_id="' + si_price.store_id + '" ' +
                'item_id="' + si.item_id + '" ' +
                'packaging="' + si_price.packaging + '"' +
                ' class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">';
            mobile +=
                '<ul>' +
                '<li>' +
                '<i price="' + si_price.price + '" store_id="' + si_price.store_id + '" ' +
                'item_id="' + si.item_id + '" ' +
                'packaging="' + si_price.packaging + '"' +
                ' class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">';
            if (si_price.in_basket){
                full +=
                    '<i class="goods-counter">' + si_price.in_basket + '</i> ';
                mobile +=
                    '<i class="goods-counter">' + si_price.in_basket + '</i> ';
            }
            full +=
                '</i>' +
                '</li>';
            mobile +=
                '</i>' +
                '</li>';
            if (si_delivery){
                full +=
                    '<li>' +
                    '<i price="' + si_delivery.price + '" store_id="' + si_delivery.store_id + '" ' +
                    'item_id="' + si.item_id + '" ' +
                    'packaging="' + si_delivery.packaging + '"' +
                    ' class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">';
                mobile +=
                    '<li>' +
                    '<i price="' + si_delivery.price + '" store_id="' + si_delivery.store_id + '" ' +
                    'item_id="' + si.item_id + '" ' +
                    'packaging="' + si_delivery.packaging + '"' +
                    ' class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">';
                if (si_delivery.in_basket){
                    full +=
                        '<i class="goods-counter">' + si_delivery.in_basket + '</i> ';
                    mobile +=
                        '<i class="goods-counter">' + si_delivery.in_basket + '</i> ';
                }
                full +=
                    '</i>' +
                    '</li>';
                mobile +=
                    '</i>' +
                    '</li>';
            }
            full +=
                '</ul>';
            mobile +=
                '</ul>';
        };
        full +=
            '</td>';
        mobile +=
            '</td>';
        //stock end

        mobile +=
            '</tr>';

        if (csi > 2){
            full +=
                '<tr class="hidden-row second-full">' +
                '<td style="padding: 20px 0 0 0;text-align:left">' +
                '<b class="brend_info" brend_id="' + si.brend_id + '">' +
                si.brend +
                '</b> ' +
                '<a href="' + si.article + '" class="articul">' +
                si.article +
                '</a>' +
                '</td>' +
                '<td class="name-col" style="padding-top: 18px;text-align:left">';
            if (search_type == 'analogies' && typeof user.id !== 'undefined' && user.allow_request_delete_item == '1' && si.status == '0'){
                var selector = 'item_id="' + $('#item_id').val() + '" item_diff="' + si.item_id + '" user_id="' + user.id + '"';
                mobile += '<span ' + selector + ' title="Сообщить о неверном аналоге" class="icon-tab wrongAnalogy"></span>';
                full += '<span ' + selector + ' title="Сообщить о неверном аналоге" class="icon-tab wrongAnalogy"></span>'
            };
            if (si.checked == '1'){
                full += '<span title="Проверенный аналог" class="icon-checkmark1"></span>';
            }
            if (+si.is_desc || si.photo){
                var stringClass = '';
                if (si.is_desc) stringClass = 'fa-cog';
                if (si.photo) stringClass = 'fa-camera';
                full +=
                    '<a title="Информация о товаре" href="#">' +
                    '<i item_id="' + si.item_id + '" class="fa ' + stringClass + ' product-popup-link" aria-hidden="true"></i>' +
                    '</a>';
            }
            full +=
                si.title_full +
                '</td>' +

                //cipher start
                '<td>';
            if (count_prevails){
                full +=
                    '<ul class="prevail">';
                for (var p in si.prevails){
                    full +=
                        '<li ' + si.prevails[p].noReturn + '>';
                    full += '<a href="" store_id="' + si.prevails[p].store_id + '">' + si.prevails[p].cipher + '</a>';
                    if (+si.prevails[p].checked){
                        full += `<span title="Проверенный поставщик" class="icon-shield"></span>`
                    }
                    full += '</li>'
                }
                full +=
                    '</ul>';
            }
            full +=
                '<ul>';
            mobile +=
                '<tr class="second-mobile">' +
                '<td>';
            if (count_prevails){
                mobile +=
                    '<ul class="prevail">';
                for (var p in si.prevails){
                    mobile +=
                        '<li ' + si.prevails[p].noReturn + '>';
                    mobile += '<a href="" store_id="' + si.prevails[p].store_id + '">' + si.prevails[p].cipher + '</a>';
                    if (+si.prevails[p].checked){
                        mobile += `<span title="Проверенный поставщик" class="icon-shield"></span>`
                    }
                    mobile += '</li>';
                }
                mobile +=
                    '</ul>';
            }
            mobile +=
                '<ul>';
            for (var k in si.list){
                full +=
                    '<li ' + si.list[k].noReturn + '>';
                mobile +=
                    '<li ' + si.list[k].noReturn + '>';
                full += '<a href="" store_id="' + si.list[k].store_id + '">' + si.list[k].cipher + '</a>';
                mobile += '<a href="" store_id="' + si.list[k].store_id + '">' + si.list[k].cipher + '</a>';
                if (+si.list[k].checked){
                    full += `<span title="Проверенный поставщик" class="icon-shield"></span>`
                    mobile += `<span title="Проверенный поставщик" class="icon-shield"></span>`
                }
            }
            full +=
                '</ul>' +
                '</td>';
            mobile +=
                '</ul>' +
                '</td>';

            //in_stock start
            full +=
                '<td>';
            mobile +=
                '<td>';
            if (count_prevails){
                full +=
                    '<ul class="prevail">';
                mobile +=
                    '<ul class="prevail">';
                for (var p in si.prevails){
                    full +=
                        '<li>' +
                        si.prevails[p].in_stock +
                        si.prevails[p].packaging_text +
                        '</li>';
                    mobile +=
                        '<li>' +
                        si.prevails[p].in_stock +
                        si.prevails[p].packaging_text +
                        '</li>';
                }
                full +=
                    '</ul>';
                mobile +=
                    '</ul>'
            }
            full +=
                '<ul>';
            mobile +=
                '<ul>';
            for (var k in si.list){
                full +=
                    '<li>' +
                    si.list[k].in_stock +
                    si.list[k].packaging_text +
                    '</li>';
                mobile +=
                    '<li>' +
                    si.list[k].in_stock +
                    si.list[k].packaging_text +
                    '</li>';
            }
            full +=
                '</ul>' +
                '</td>';
            mobile +=
                '</ul>' +
                '</td>';
            //in_stock end

            //delivery start
            full +=
                '<td>';
            mobile +=
                '<td>';
            if (count_prevails){
                full +=
                    '<ul class="prevail">';
                mobile +=
                    '<ul class="prevail">';
                for(var p in si.prevails){
                    full +=
                        '<li>' + si.prevails[p].delivery_date + '</li>';
                    mobile +=
                        '<li>' + si.prevails[p].delivery_date + '</li>';
                }
                full +=
                    '</ul>';
                mobile +=
                    '</ul>';
            }
            full +=
                '<ul>';
            mobile +=
                '<ul>';
            for (var k in si.list){
                full +=
                    '<li>' +  si.list[k].delivery_date+ '</li>';
                mobile +=
                    '<li>' + si.list[k].delivery_date + '</li>';
            }
            full +=
                '</ul>' +
                '</td>';
            mobile +=
                '</ul>' +
                '</td>';
            //delivery end

            //price start
            full +=
                '<td>';
            mobile +=
                '<td>';
            if (count_prevails){
                full +=
                    '<ul class="prevail">';
                mobile +=
                    '<ul class="prevail">';
                for (var p in si.prevails){
                    full +=
                        '<li>' + si.prevails[p].user_price + '</li>';
                    mobile +=
                        '<li>' + si.prevails[p].user_price + '</li>';
                }
                full +=
                    '</ul>';
                mobile +=
                    '</ul>';
            }
            full +=
                '<ul>';
            mobile +=
                '<ul>';
            for (var k in si.list){
                full +=
                    '<li>' + si.list[k].user_price + '</li>';
                mobile +=
                    '<li>' + si.list[k].user_price + '</li>';
            }
            full +=
                '</ul>' +
                '</td>';
            mobile +=
                '</ul>' +
                '</td>';
            //price end

            //quan start
            full +=
                '<td class="quan ' + hidden + '">';
            mobile +=
                '<td class="quan ' + hidden + '">';
            if (count_prevails){
                full +=
                    '<ul class="prevail">';
                mobile +=
                    '<ul class="prevail">';
                for (var p in si.prevails){
                    full +=
                        '<li packaging="' + si.prevails[p].packaging + '" store_id="' + si.prevails[p].store_id + '" item_id="' + si.item_id + '" class="count-block">';
                    mobile +=
                        '<li packaging="' + si.prevails[p].packaging + '" store_id="' + si.prevails[p].store_id + '" item_id="' + si.item_id + '" class="count-block">';
                    if (si.prevails[p].in_basket){
                        full +=
                            '<input value="' + si.prevails[p].in_basket + '">';
                        mobile +=
                            '<input value="' + si.prevails[p].in_basket + '">';
                    }
                    full +=
                        '</li>';
                    mobile +=
                        '</li>';
                }
                full +=
                    '</ul>';
                mobile +=
                    '</ul>';
            }
            full +=
                '<ul>';
            mobile +=
                '<ul>';
            for(var k in si.list){
                full +=
                    '<li packaging="' + si.list[k].packaging + '" store_id="' + si.list[k].store_id + '" item_id="' + si.item_id + '" class="count-block">';
                mobile +=
                    '<li packaging="' + si.list[k].packaging + '" store_id="' + si.list[k].store_id + '" item_id="' + si.item_id + '" class="count-block">';
                if (si.list[k].in_basket){
                    full +=
                        '<input value="' + si.list[k].in_basket + '">';
                    mobile +=
                        '<input value="' + si.list[k].in_basket + '">';
                }
                full +=
                    '</li>';
                mobile +=
                    '</li>';
            }
            full +=
                '</ul>';
            mobile +=
                '</ul>';
            full +=
                '</ul>';
            mobile +=
                '</ul>';
            //quan end

            //to_stock start
            full +=
                '<td>';
            mobile +=
                '<td>';
            if (count_prevails){
                full +=
                    '<ul class="prevail to-cart-list">';
                mobile +=
                    '<ul class="prevail to-cart-list">';
                for (var p in si.prevails){
                    full +=
                        '<li>' +
                        '<i price="' + si.prevails[p].price + '" store_id="' + si.prevails[p].store_id + '" ' +
                        'item_id="' + si.item_id + '" ' +
                        'packaging="' + si.prevails[p].packaging + '"' +
                        ' class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">';
                    if (si.prevails[p].in_basket) full +=
                        '<i class="goods-counter">' + si.prevails[p].in_basket + '</i> ';
                    mobile +=
                        '<li>' +
                        '<i price="' + si.prevails[p].price + '" store_id="' + si.prevails[p].store_id + '" ' +
                        'item_id="' + si.item_id + '" ' +
                        'packaging="' + si.prevails[p].packaging + '"' +
                        ' class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">';
                    if (si.prevails[p].in_basket) mobile +=
                        '<i class="goods-counter">' + si.prevails[p].in_basket + '</i> ';
                    full +=
                        '</i> ' +
                        '</li>';
                    mobile +=
                        '</i> ' +
                        '</li>';
                }
                full +=
                    '</ul>';
                mobile +=
                    '</ul>';
            }
            full +=
                '<ul class="to-cart-list">';
            mobile +=
                '<ul class="to-cart-list">';
            for (var k in si.list){
                full +=
                    '<li>' +
                    '<i price="' + si.list[k].price + '" store_id="' + si.list[k].store_id + '" ' +
                    'item_id="' + si.item_id + '" ' +
                    'packaging="' + si.list[k].packaging + '"' +
                    ' class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">';
                if (si.list[k].in_basket) full +=
                    '<i class="goods-counter">' + si.list[k].in_basket + '</i> ';
                mobile +=
                    '<li>' +
                    '<i price="' + si.list[k].price + '" store_id="' + si.list[k].store_id + '" ' +
                    'item_id="' + si.item_id + '" ' +
                    'packaging="' + si.list[k].packaging + '"' +
                    ' class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">';
                if (si.list[k].in_basket) mobile +=
                    '<i class="goods-counter">' + si.list[k].in_basket + '</i> ';
                full +=
                    '</i> ' +
                    '</li>';
                mobile +=
                    '</i> ' +
                    '</li>';
            }
            full +=
                '</ul>' +
                '</td>' +
                '</tr>';
            mobile +=
                '</ul>' +
                '</td>' +
                '</tr>';
            if (csi > 2){
                full +=
                    '<tr class="button-row active shown">' +
                    '<td colspan="7" style="padding-top: 0px !important; text-align:center">' +
                    '<button type="full"></button>' +
                    '</td>' +
                    '</tr>';
                mobile +=
                    '<tr class="button-row active shown">' +
                    '<td colspan="7" style="padding-top: 0px !important;text-align:center">' +
                    '<button></button>' +
                    '</td>' +
                    '</tr>';
            }
        }
        mobile += '</table>';
    }
    return{
        full: full,
        mobile: mobile
    }
}
function sortStoreItems(sortType){
    var sortableObject = new Array();
    var i; length = res.store_items.length;
    for(i = 0; i < length; i++){
        var si = res.store_items[i].store_item;
        if (sortType == 'brend'){
            res.store_items[i].min = si.brend;
            continue;
        }
        var min = 10000000;
        for(var k in si.list){
            if (si.list[k][sortType] < min) min = + si.list[k][sortType];
        }
        if (typeof si.prevails !== 'undefined'){
            for(var k in si.prevails){
                if (si.prevails[k][sortType] < min) min = + si.prevails[k][sortType];
            }
        }
        res.store_items[i].min = min;
    }
    if (sortType == 'brend') res.store_items.sort(function(a, b){
        if (typeof a.min === 'undefined') return 0;
        if (typeof b.min === 'undefined') return 0;
        // console.log(typeof b, typeof a);
        var nameA = a.min.toLowerCase();
        var nameB = b.min.toLowerCase();
        if (nameA < nameB) return -1;
        if (nameA > nameB) return 1;
        return 0;
    });
    else res.store_items.sort(function(a, b){
        return a.min - b.min;
    });
}
function applyAjaxUserMarkup(params){
    let data = {};
    data.params = params;
    data.act = 'applyUserMarkup';
    data.user_id = $('input[name=user_id]').val();
    $.ajax({
        type: 'post',
        url: '/admin/ajax/user.php',
        data: data,
        success: function(response){
        }
    })
}
function applyUserMarkup(){
    let params = {};
    $('span.subprice').remove();
    $('li.withSubprice').removeClass('withSubprice');
    $.each($('#user_markup form').serializeArray(), function(i, item){
        params[item.name] = item.value;
    })

    applyAjaxUserMarkup(params);

    if (params.withUserMarkup === 'on'){
        $('div.ionTabs__body span.price_format').each(function(){
            let th = $(this);
            let basePrice = + th.prev('input').val();
            let price =  Math.floor(basePrice + basePrice * params.markup / 100);
            if (params.showType === 'double'){
                th.text(price);
                th.closest('li').addClass('withSubprice');
                th.next().after('<span class="subprice">' + Math.floor(basePrice) + '</span>');
            }
            if (params.showType === 'single'){
                th.text(Math.floor(price));
            }
        })
    }
    else{
        $('div.ionTabs__body span.price_format').each(function(){
            let th = $(this);
            let basePrice = + th.prev('input').val();
            th.text(basePrice);
        })
        $('table.cart-popup-table span.price_format').each(function(){
            let th = $(this);
            let quan = th.prevAll('input[name=quan]').val();
            let price = th.prevAll('input[name=price]').val();
            th.text(quan * price);
        })
    }

    let totalInBasket = 0;
    $('table.cart-popup-table tr[store_id][item_id] span.price_format').each(function(){
        let th = $(this);
        let quan = th.prevAll('input[name=quan]').val();
        let price = th.prevAll('input[name=price]').val();
        if (params.showInBasket === 'on'){
            let additionalPrice = Math.floor(price * quan + price * quan * params.markup / 100);
            totalInBasket += additionalPrice;
            th.text(additionalPrice);
        }
        else{
            totalInBasket += quan * price;
            th.text(price * quan);
        }
    })
    $('#total_basket').text(totalInBasket);

    if (params.showInBasket == 'on'){
    }
    $('#user_markup').hide();
}
function checkThroughAPI(item_id){
    $.ajax({
        type: 'get',
        url: document.location.href,
        success: function(){
            let href = document.location.href + '/noUseAPI';
            document.location.href = href;
        }
    })
}
$(function(){
    if (!$('#offers-filter-form').hasClass('hidden')) hidable_form = false;
    set_tabs();
    applyUserMarkup();
    if ($('input[name=noUseAPI]').val() == '0'){
        checkThroughAPI();
    }
    $(document).on('click', '.cart-popup-table .delete-btn', function(){
        let store_id = $(this).closest('tr').attr('store_id');
        let item_id = $(this).closest('tr').attr('item_id');
        delete inBasket[store_id + ':' + item_id];
        applyUserMarkup();
    })
    $('li.user_markup a').on('click', function(e){
        e.preventDefault();
        $('#user_markup').show();
    })
    $('#user_markup input[name=withUserMarkup]').on('change', function(){
        let th = $(this);
        if (th.is(':checked')){
            th.closest('form').find('input:not([name=withUserMarkup])').prop('disabled', false).trigger('refresh');
        }
        else{
            th.closest('form').find('input:not([name=withUserMarkup])').prop('disabled', true).trigger('refresh');
        }
    })
    $('#user_markup form').on('submit', function(e){
        e.preventDefault();
        applyUserMarkup();
    })
    $('#offers-filter-form button').on('click', function(e){
        e.preventDefault();
        let tabSelector;
        var elem = $(this);
        var search_type = elem.closest('form').attr('search_type');
        var item_id = elem.attr('item_id');
        var data = "item_id=" + item_id + "&search_type=" + search_type + '&filters_on=1';
        data += "&price_from=" + $('#price-from').val();
        data += "&price_to=" + $('#price-to').val();
        data += "&time_from=" + $('#time-from').val();
        data += "&time_to=" + $('#time-to').val();

        if ($('#in_stock_only').is(':checked')) data += '&in_stock=1';

        switch (search_type){
            case 'articles':
                tabSelector = '#Tab__search-result-tabs__Tab_1';
                break;
            case 'substitutes':
                tabSelector = '#Tab__search-result-tabs__Tab_2';
                break;
            case 'analogies':
                tabSelector = '#Tab__search-result-tabs__Tab_3';
                break;
            case 'complects':
                tabSelector = '#Tab__search-result-tabs__Tab_4';
                break;
        }

        $.ajax({
            type: "POST",
            url: "/ajax/article_filter.php",
            data: data,
            beforeSend: function(){
                $(tabSelector + ' .articul-table').html(
                    '<tr class="gif">' +
                    '<td colspan="7"></td>' +
                    '</tr>');
            },
            success: function(msg){
                // console.log(msg);
                // return;
                res = JSON.parse(msg);
                // console.log(res);
                var pi = store_items(res.store_items, res.user, search_type);
                $('.gif').remove();

                $(tabSelector + ' .articul-table').html(pi.full);
                $(tabSelector + ' .mobile-layout').html(pi.mobile);

                $('a.sortable.brend').addClass('asc');

                price_format();
                applyUserMarkup();

                removeWithoutProviders();
            }
        });
    })
    $(document).on('click', 'a.sortable', function(e){
        e.preventDefault();
        var th = $(this);
        var sortType;
        var ionTab = th.closest('.itemInsertable');
        $('a.sortable').removeClass('asc');
        if (th.hasClass('delivery')) sortType = 'delivery';
        if (th.hasClass('price')) sortType = 'price';
        if (th.hasClass('in_stock')) sortType = 'in_stock';
        if (th.hasClass('brend')) sortType = 'brend';
        sortStoreItems(sortType);
        var si = store_items(res.store_items, res.user, 'analogies');
        ionTab.find('.articul-table').html(si.full);
        ionTab.find('.mobile-layout').html(si.mobile);
        ionTab.find('a.sortable.' + sortType).addClass('asc');
        removeWithoutProviders();
        applyUserMarkup();
    })
    $("input[type=checkbox], input[type=radio]").styler();
    $(document).on('click', ".button-row button", function(event){
        var elem = $(this);
        var type = elem.attr('type');
        if (type == 'full'){
            if (!elem.hasClass('active')){
                elem.
                addClass('active').
                parent().
                parent().
                prev('.second-full').
                addClass('shown').
                prev('.first-full').
                removeClass('shown');
            }
            else{
                elem.
                removeClass('active').
                parent().
                parent().
                prev('.second-full').
                removeClass('shown').
                prev('.first-full').
                addClass('shown');
            }
        }
        else{
            if (!elem.hasClass('active')){
                elem.
                addClass('active').
                parent().
                parent().
                prevAll('.second-mobile').
                addClass('shown').
                prev('.first-mobile').
                removeClass('shown');
            }
            else{
                elem.
                removeClass('active').
                parent().
                parent().
                prevAll('.second-mobile').
                removeClass('shown').
                prev('.first-mobile').
                addClass('shown');
            }
        }
    });
    $(document).on('click', '.to-stock-btn', function(){
        $('.mfp-wrap').click();
        var e = $(this);
        var store_id = +e.attr('store_id');
        var price = +e.attr('price');
        var packaging = +e.attr('packaging');
        var item_id = e.attr('item_id');

        if ($('.login_btn span').html() === 'Войти'){
            $('.login_btn').click();
            show_message('Для добавления товара в корзину необходимо авторизоваться!', 'error');
            return false;
        }
        $('button[type=full]').closest('td').attr('colspan', 8);
        $('.quan.hidden').removeClass('hidden');
        $('.quan li[store_id=' + store_id + '][item_id=' + item_id + ']').html('<input value="' + packaging + '">');

        setAmountInBasket(store_id, item_id, packaging);
        setNewValue({
            store_id: store_id,
            item_id: item_id,
            price: + $('i.to-stock-btn[store_id= ' + store_id + '][item_id=' + item_id + ']').attr('price')
        });
    });
    $(document).on('blur', '.quan li[store_id][item_id] input', function(){
        var isValidated = true;
        var th = $(this);
        var val = + th.val();
        var packaging = + th.closest('li').attr('packaging');
        var message = 'Количество должно быть кратно ' + packaging + '!';
        if(val % packaging){
            isValidated = false;
            show_message(message, 'error');
        }
        if (!val){
            isValidated = false;
            show_message(message, 'error');
        }
        if (!isValidated) return th.focus();

        var store_id = + th.closest('li').attr('store_id');
        var item_id = + th.closest('li').attr('item_id');
        setAmountInBasket(
            + store_id,
            + item_id,
            + val,
            true
        );
        setNewValue({
            store_id: store_id,
            item_id: item_id,
            price: + $('i.to-stock-btn[store_id= ' + store_id + '][item_id=' + item_id + ']').attr('price'),
        });
    })
    $(document).on('click', '#add-to-favorits', function(){
        var e = $(this);
        var data = 'item_id=' + e.attr('item_id');
        if (!e.hasClass('added')) data += '&act=add';
        else data += '&act=delete';
        // console.log(data);
        $.ajax({
            type: "POST",
            url: "/ajax/favorite.php",
            data: data,
            success: function(msg){
                e.toggleClass('added');
                if (e.hasClass('added')) show_message('Успешно добавлено в избранное!');
                else show_message('Успешно удалено из избранного!');
            }
        })
    })
    $(document).on('click', '#search-same', function(){
        document.location.href = $(this).attr('href');
    })
    $(document).on('click', '.wrongAnalogy', function(){
        if (!confirm('Вы уверены?')) return false;
        var th = $(this);
        $.ajax({
            url: '/admin/ajax/user.php',
            type: 'post',
            data: {
                act: 'wrongAnalogy',
                user_id: th.attr('user_id'),
                item_id: th.attr('item_id'),
                item_diff: th.attr('item_diff')
            },
            success: function(response){
                // console.log(response); return false;
                return show_message('Сообщение успешно отправлено!');
            }
        })
    })
    $(document).on('click', 'a[store_id]', function(e){
        // return false;
        e.preventDefault();
        let th = $(this);
        $.ajax({
            type: 'post',
            url: '/admin/ajax/providers.php',
            data: {
                act: 'getStoreInfo',
                store_id: th.attr('store_id')
            },
            success: function(response){
                $.magnificPopup.open({
                    items: {
                        src: response,
                        type: 'inline'
                    }
                });
            }
        })
    })
    $(document).on('click', function(e){
        let target = $(e.target).closest('#user_markup').attr('id');
        if (
            target != 'user_markup' &&
            $(e.target).attr('class') != 'user_markup'
        ){
            $('#user_markup').hide();
        }
    })
});