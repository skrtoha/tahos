var ajax_url = '/admin/ajax/orders.php';
var reg_integer = /^\d+$/;
var is_reload = true;
const getQuery = getParams();
function first_option(obj){
	obj.find('option').prop('selected', false);
	obj.find('option:first-child').prop('selected', true);
}
function set_items_to_order(){
    if (typeof getQuery.act === 'undefined' && typeof getQuery.id === 'undefined'){
        $.ajax({
            type: 'post',
            url: '/admin/ajax/orders.php',
            data: {
                status_id: 'getItemsToOrder'
            },
            success: function (response){
                $('#itemsToOrder').html(response)
            }
        })
    }
}
function arrived_new(obj){
    const th = obj.closest('#arrived_change');
    const type = th.find('input[name=new_value_radio]:checked').val();
    const current = +th.find('input[name=value]').val();
    const store_id = +th.attr('store_id');
    const item_id = +th.attr('item_id');
    const pay_type = th.attr('pay_type');
    const tr = $('tr[store_id=' + store_id + '][item_id=' + item_id + ']');
    const arrived = +tr.find('input[name=arrived]').val();
    const order_id = +tr.find('input[name=order_id]').val();
    const quan = +tr.find('input[name=quan]').val();
    if (type == 'arrived_new'){
		// console.log(current, arrived);
		if (current + arrived > quan || !reg_integer.test(current) || !current){
			show_message('Значение задано неккоректно!', 'error');
			$('#arrived_change input[name=value]').focus();
			return false;
		}
	}
	$.ajax({
		type: 'post',
		url: '/admin/ajax/orders.php',
		data: 'status_id=' + type + '&order_id=' + order_id + '&store_id=' + store_id + 
					'&item_id=' + item_id + '&current=' + current + '&user_id=' + tr.find('input[name=user_id]').val() +
                    '&pay_type=' + pay_type +
					'&price=' + tr.find('input[name=price]').val(),
		success: function(response){
			// console.log(response); return false;
			$('#modal-container').removeClass('active');
			if (is_reload) document.location.reload();
		}
	})
}
function issued_new(obj){
	var th = obj.closest('#issued_change');
	var provider_id = + th.attr('provider_id');
	var item_id = + th.attr('item_id');
	var tr = $('tr[provider_id=' + provider_id + '][item_id=' + item_id + ']');
	var order_id = + tr.find('input[name=order_id]').val();
	var user_id = + tr.find('input[name=user_id]').val();
	var price = + tr.find('input[name=price]').val();
	var issued = tr.find('input[name=issued]').val();
	var arrived = tr.find('input[name=arrived]').val();
	var issued_new = th.find('input[name=value]').val();
	if (!reg_integer.test(issued_new) || issued_new > arrived - issued){
		show_message('Значение задано неккоректно!', 'error');
		return false;
	}
	$.ajax({
		type: 'post',
		url: '/admin/ajax/orders.php',
		data: 'status_id=issued_new' + '&order_id=' + order_id + '&provider_id=' + provider_id + 
					'&item_id=' + item_id + '&issued_new=' + issued_new + '&user_id=' + user_id +
					'&price=' + price + '&bill=' + tr.find('input[name=bill]').val(),
		success: function(response){
			// console.log(response); return false;
			$('#modal-container').removeClass('active');
			if (is_reload) document.location.reload();
		}
	})
}
function check_value(th, value, compared){
	if (value === null){
		first_option(th);
		return false;
	} 
	if (value == 0 || !reg_integer.test(value)){
		show_message('Количество указано неккоректно!', 'error');
		first_option(th);
		return false;
	}
	if (value > compared){
		show_message('Количество указано неккоректно!', 'error');
		first_option(th);
		return false;
	}
	return true;
}
function setTotalSumm(){
	var total = 0;
	$('tr[store_id][item_id]').each(function(){
		var th = $(this);
		summ = th.find('input[name=price]').val() * th.find('input[name=quan]').val();
		th.find('td.sum').html(summ);
		total += summ;
	})
	$('td.total').html(total);
}
$(function(){
    set_items_to_order();
    $('select[name=delivery]').on('change', function(){
        const $th = $(this);
        let disabled
        switch ($th.val()){
            case 'Доставка': disabled = false; break;
            case 'Самовывоз': disabled = true; break;
        }
        $('select[name=address_id]').prop('disabled', disabled);
    })
	$('#changeStatus select[name=status_id]').on('change', function(){
		$(this).closest('form').submit();
	})
	$('a.show_stringLog').on('click', function(e){
		e.preventDefault();
		$(this).next().toggleClass('active');
	});
	$(document).on('submit', 'form[name=store_change]', function(e){
		return false;
	})
	$('.orders_box').on('click', function(){
		document.location.href = $(this).attr('href');
	});
	$('a.allInWork').on('click', function(){
		if (!confirm('Вы уверены?')) return false;
	})
    $('.datetimepicker[name=date_issue]').datetimepicker({
        format:'d.m.Y',
        closeOnDateSelect: true,
        closeOnWithoutClick: true,
        mask: true
    });
	$(document).on('submit', '#askForQuanAndPrice', function(e){
		e.preventDefault();
        const $th = $(this)
        const array = $th.serializeArray();
        const formData = {}
        for(let a of array){
            formData[a.name] = a.value
        }
        const form = $('tr[store_id=' + formData.store_id + '][item_id=' + formData.item_id + '] td.change_status form');
        formData.order_id = form.find('input[name=order_id]').val()
        const items = []
        items.push(formData)

        $.ajax({
			type: 'post',
			url: '/admin/ajax/return.php',
			data: {
                act: 'createReturn',
                items: items
            },
            beforeSend: () => {
                showGif()
            },
			success: function(response){
                showGif(false)
                show_message('Заявка успешно создана')
                $('#modal-container').removeClass('active')
			}
		})
	})
	$('select.change_status')
        .on('change', function(){
            const th = $(this);
            const status_id = +th.val();
            if (status_id != 13) return false;
            if (!confirm('Подтверждаете действие?')) return false;
            const form = th.closest('form');
            const issued = form.find('input[name=issued]').val();
            const returned = form.find('input[name=returned]').val();
            const tr = form.closest('tr');

            $.ajax({
                type: 'get',
                url: '/admin/ajax/return.php',
                data: {
                    act: 'get_reasons'
                },
                success: response => {
                    let strReason = '';
                    const reasons = JSON.parse(response)
                    for(let r of reasons){
                        strReason += `<option value="${r.id}">${r.title}</option>`
                    }
                    modal_show(`
                        <form id="askForQuanAndPrice">
                            <input type="hidden" name="store_id" value="${tr.attr('store_id')}">
                            <input type="hidden" name="item_id" value="${tr.attr('item_id')}">
                            <table>
                                <tr>
                                    <td>Количество:</td>
                                    <td><input type="text" name="quan" value="${issued - returned}"></td>
                                </tr>
                                 <tr>
                                    <td>Причина:</td>
                                    <td>
                                        <select name="reason_id">
                                            ${strReason}
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Цена:</td>
                                    <td><input readonly type="text" name="price" value="${form.find('input[name=price]').val()}"></td>
                                </tr>
                                <tr>
                                    <td colspan="2"><input type="submit" value="Отправить"></td>
                                </tr>
                            </table>
                        </form>
                    `)
                }
            })
        })
        .on('change', function(){
            var th = $(this);
            var status_id = + th.val();
            //добавлено, т.к. обработка идет в другом месте
            if (status_id == 13) return false;
            if (status_id == 6 || status_id == 8){
                if (!confirm('Вы подтверждаете действие?')) return false;
            }
            th = th.closest('form');

            if (!th.find('input[name=pay_type]').val()){
                show_message('Не указан способ оплаты!', 'error');
                return false;
            }

            var order_id = + th.find('input[name=order_id]').val();
            var store_id = + th.find('input[name=store_id]').val();
            var item_id = + th.find('input[name=item_id]').val();
            var data = 'status_id=' + status_id + '&order_id=' + order_id + '&store_id=' + store_id + '&item_id=' + item_id;
            data += '&user_id=' + th.find('input[name=user_id]').val();
            data += '&price=' + th.find('input[name=price]').val();
            data += '&bill_cash=' + th.find('input[name=bill_cash]').val();
            data += '&bill_cashless=' + th.find('input[name=bill_cashless]').val();
            data += '&reserved_cash=' + th.find('input[name=reserved_cash]').val();
            data += '&reserved_cashless=' + th.find('input[name=reserved_cashless]').val();
            data += '&pay_type=' + th.find('input[name=pay_type]').val();
            switch(status_id){
                case 1:
                    data += '&arrived=' + th.find('input[name=arrived]').val();
                    break;
                //выделено в отдельную функцию
                case 2:break;
                case 3:
                    ordered = + th.find('input[name=ordered]').val();
                    if (ordered == 1) arrived = ordered;
                    else arrived = prompt('Укажите количество:', ordered);
                    if (!check_value(th, arrived, ordered)) return false;
                    data += '&arrived=' + arrived;
                    break;
                case 8:
                    ordered = + th.find('input[name=ordered]').val();
                    data += '&ordered=' + ordered;
                    break;
                case 11:
                    var quan = + th.find('input[name=quan]').val();
                    if (quan > 1) var ordered = prompt('Укажите количество:', quan);
                    else ordered = quan;
                    if (!check_value(th, ordered, quan)) return false;
                    data += '&ordered=' + ordered;
                    break;
            }
            $.ajax({
                type: 'post',
                url: ajax_url,
                data: data,
                success: function(response){
                    // console.log(response); return false;
                    if (is_reload) document.location.reload();
                }
            })
        })
	$('a.arrived_change').on('click', function(e){
		e.preventDefault();
		var th = $(this);
		modal_show(
			'<div ' +
            '           id="arrived_change" ' +
            '           store_id="' + th.closest('tr').attr('store_id') + '" ' +
            '           item_id="' + th.closest('tr').attr('item_id') + '"' +
                        `pay_type="${th.closest('tr').find('input[name=pay_type]').val()}"` +
            '       >' +
				'<label>' +
					'<input checked type="radio" name="new_value_radio" value="arrived_new">' +
					'Еще пришло: <input type="text" name="value" value="' + th.html() + '"/>' +
				'</label>' +
				'<label>' +
					'<input type="radio" name="new_value_radio" value="declined">' +
					'Отказ поставщика'  +
				'</label>' +
				'<input type="submit" value="Применить">' +
			'</div>'
		);
		setTimeout(function(){
			$('#arrived_change input[name=value]').focus();
		}, 100);
	});
	$('a.issued_change').on('click', function(e){
		e.preventDefault();
		var th = $(this);
		var arrived = + th.closest('tr').find('input[name=arrived]').val();
		var issued = + th.html();
		modal_show(
			'<div id="issued_change" store_id="' + th.closest('tr').attr('store_id') + '" item_id="' + th.closest('tr').attr('item_id') + '">' +
				'Еще выдано: <input type="text" name="value" value="' + (arrived - issued) + '"/>' +
				'<input type="submit" value="Применить">' +
			'</div>'
		);
		setTimeout(function(){
			$('#issued_change input[name=value]').focus();
		}, 100);
	});
	$(document).on('click', '#issued_change input[type=submit]', function(){
		issued_new($(this));
	})
	$(document).on('keyup', '#issued_change input[name=value]', function(e){
		if (e.keyCode != 13) return false;
		issued_new($(this));
	})
	$(document).on('change', '#arrived_change input[type=radio]', function(){
		var type = $('input[name=new_value_radio]:checked').val();
		if (type == 'declined'){
			$('#arrived_change input[name=value]').prop('disabled', true);
		}
		else{
			$('#arrived_change input[name=value]').prop('disabled', false);
		}
	})
	$(document).on('click', '#arrived_change input[type=submit]', function(){
		arrived_new($(this));
	})
	$(document).on('keyup', '#arrived_change input[name=value]', function(e){
		if (e.keyCode != 13) return false;
		arrived_new($(this));
	})
	$('#to_basket').on('click', function(){

        let formData = {
            status_id: 'return_to_basket',
            data: []
        };
        $('input[name=return_to_basket]').each(function(){
			if (!$(this).is(':checked')) return true;

            const form = $(this).closest('tr').find('td.change_status form');

            const store_id = form.find('input[name=store_id]').val();
            const item_id = form.find('input[name=item_id]').val();

            $('tr[store_id=' + store_id + '][item_id=' + item_id + ']').remove();

            formData.data.push({
                order_id: form.find('input[name=order_id]').val(),
                store_id: store_id,
                item_id: item_id,
                user_id: form.find('input[name=user_id]').val(),
                quan: form.find('input[name=quan]').val(),
                price: form.find('input[name=price]').val()
            })
		});

        if (formData.data.length == 0) return show_message('Нечего возвращать!', 'error');

		$.ajax({
			type: 'post',
			url: '/admin/ajax/orders.php',
			data: formData,
			success: function(response){
                show_message('Успешно возвращено', 'ok');
                if (response == 0){
                    document.location.href = '/admin/?view=orders';
                }
			}
		})
	})
	$('textarea[name=comment]').on('change', function(){
		var form = $(this).closest('tr').find('td.change_status form');
		$.ajax({
			type: "post",
			url: '/admin/ajax/orders.php',
			data: 'status_id=comment&text=' + $(this).val() + 
				'&store_id=' + form.find('input[name=store_id]').val() + 
				'&item_id=' + form.find('input[name=item_id]').val() +
				'&order_id=' + form.find('input[name=order_id]').val(),
			success: function(response){
				console.log(response);
			}
		})
	})
	$('.icon-cancel-circle').on('click', function(){
		if (!confirm('Вы действительно хотите удалить?')) return false;
		var th = $(this);
		$.ajax({
			type: 'post',
			url: '/admin/ajax/orders.php',
			data: {
				status_id: 'remove',
				order_id: $('input[name=order_id]').val(),
				item_id: $(this).closest('tr').attr('item_id'),
				store_id: $(this).closest('tr').attr('store_id')
			},
			success: function(response){
				if (!parseInt(response)) document.location.href = '/admin/?view=orders';
				th.closest('tr').remove();
				setTotalSumm();
			}
		})
	})
	$('input[type=text]').on('change', function(){
		var th = $(this);
		switch (th.attr('name')){
			case 'price':
				if(!reg_integer.test(th.val())){
					show_message ('Ошибка в значении!', 'error');
					return false;
				}
				break;
			case 'quan':
				if(!reg_integer.test(th.val())){
					show_message ('Ошибка в значении!', 'error');
					return false;
				}
				break;
		}
		$.ajax({
			type: 'post',
			url: '/admin/ajax/orders.php',
			data: {
				status_id: 'change_draft',
				name: th.attr('name'),
				value: th.val(),
				order_id: $('input[name=order_id]').val(),
				item_id: $(this).closest('tr').attr('item_id'),
				store_id: $(this).closest('tr').attr('store_id')
			},
			success: function(response){
				// console.log(response); return;
				setTotalSumm();
			}
		})
	})
	$('a.editOrderValue').on('click', function(e){
		e.preventDefault();
        const th = $(this);
        let status_id = + th.closest('tr').data('status-id');
        let inputDisable = status_id == 1 ? 'readonly' : '';
        $.ajax({
			type: 'post',
			url: '/admin/ajax/orders.php',
            beforeSend: function () {
                showGif();
            },
			data: {
				status_id: 'getOrderValue',
				osi: th.attr('osi')
			},
			success: function(response){
                showGif(false);

				let ov = JSON.parse(response);
				ov.comment = ov.comment ? ov.comment : '';
                let storesString =
                    `<tr>
                        <td>Склад:</td>
                        <td>
                            <select name="store_id">
                    `;
                for(let item of ov.stores){
                    const selected = item.store_id == ov.store_id ? 'selected' : '';
                    storesString += `<option
                                      data-price="${item.price}" 
                                      data-without-markup="${item.withoutMarkup}"
                                      ${selected} 
                                      value="${item.store_id}"
                                    >
                                        ${item.cipher} - ${item.price} руб.
                                    </option>`
                }
                storesString += `</select>
                                  <td>
                                  </tr>`;
                let orderStatuses = `<select name="order_status_id">`;
                ov.order_statuses.forEach(item => {
                    const selected = item.id == ov.status_id ? 'selected': '';
                    orderStatuses += `<option ${selected} value="${item.id}">${item.title}</option>`;
                })
                orderStatuses += `</select>`;
                modal_show(
                    '<form class="editOrderValue">' +
                                '<input type="hidden" name="status_id" value="editOrderValue">' +
                                '<input type="hidden" name="osi" value="' + th.attr('osi') + '">' +
                                `<input type="hidden" name="withoutMarkup" value="${ov.withoutMarkup}">` +
                                '<table>' +
                                    '<tr>' +
                                        '<td>Цена:</td>' +
                                        '<td><input ' + inputDisable + ' type="text" name="price" value="' + ov.price + '"></td>' +
                                    '</tr>' +
                                    storesString +
                                    `<tr>
                                        <td>Статус:</td>
                                        <td>${orderStatuses}</td>
                                    </tr>` +
                                    '<tr>' +
                                        '<td>Количество:</td>' +
                                        '<td><input ' + inputDisable + ' type="text" name="quan" value="' + ov.quan + '"></td>' +
                                    '</tr>' +
                                    `<tr>
                                        <td>Заказано:</td>
                                        <td><input type="text" ${inputDisable} name="ordered" value="${ov.ordered}"></td>
                                    </tr>
                                    <tr>
                                        <td>Пришло:</td>
                                        <td><input type="text" ${inputDisable} name="arrived" value="${ov.arrived}"></td>
                                    </tr>
                                    <tr>
                                        <td>Выдано:</td>
                                        <td><input type="text" ${inputDisable} name="issued" value="${ov.issued}"></td>
                                    </tr>
                                    <tr>
                                        <td>Отказано:</td>
                                        <td><input type="text" ${inputDisable} name="declined" value="${ov.declined}"></td>
                                    </tr>
                                    <tr>
                                        <td>Возврат:</td>
                                        <td><input type="text" ${inputDisable} name="returned" value="${ov.returned}"></td>
                                    </tr>` +
                                    '<tr>' +
                                        '<td>Комментарий:</td>' +
                                        '<td><input type="text" name="comment" value="' + ov.comment + '"></td>' +
                                    '</tr>' +
                                    '<tr>' +
                                        '<td colspan="2"><input type="submit"  value="Сохранить"></td>' +
                                    '</tr>' +
                                '</table>' +
                            '</form>'
                );
                $(document).on('submit', 'form.editOrderValue', function(e){
                    e.preventDefault();
                    let form = $(this);
                    $.ajax({
                        type: 'post',
                        url: '/admin/ajax/orders.php',
                        data: form.serialize(),
                        beforeSend: () => {
                          showGif()
                        },
                        success: function(response){
                            document.location.reload();
                        }
                    })
                })
			}
		})
	})
    $(document).on('change', 'form.editOrderValue select[name=store_id]', e => {
        const $th = $(e.target)
        const option = $th.find('option:selected')
        $th.closest('form').find('input[name=price]').val(option.data('price'))
        $th.closest('form').find('input[name=withoutMarkup]').val(option.data('without-markup'))
  })
})
