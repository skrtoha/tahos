function getParams(obj, act){
	return {
		act: act,
		order_id: + obj.attr('order_id'),
		store_id: + obj.find('div.count-block').attr('store_id'),
		item_id: + obj.find('div.count-block').attr('item_id'),
		packaging: + obj.find('div.count-block').attr('packaging'),
		summand: + obj.find('div.count-block').attr('summand')
	}
}
function editComment(obj){
	var data =
		'act=comment' +
		'&order_id=' + obj.closest('tr').attr('order_id') + 
		'&store_id=' + obj.closest('tr').attr('store_id') + 
		'&item_id=' + obj.closest('tr').attr('item_id') + 
		'&text=' + obj.find('textarea').val();
	$.ajax({
		type: "POST",
		url: "/ajax/order.php",
		data: data,
		success: function(msg){
			// console.log(msg);
			if ($(document).width() > 700){
				$('.overlay').click();
				show_message('Комментарий успешно изменен', 'ok');
			}
		} 
	});
}
function setDateIssueDelivery(date){
    const selector = 'input[name=date_issue]';
    pickmeup.defaults.locales['ru'] = {
        days: ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'],
        daysShort: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
        daysMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
        months: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
        monthsShort: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек']
    };
    pickmeup(selector).destroy();
    pickmeup('input[name=date_issue]', {
        min: date,
        date: date,
        format: 'd.m.Y',
        locale: 'ru',
        hide_on_select: true
    });
}
$(function(){
    setDateIssueDelivery($('#min_date').val());
    $('input[name=entire_order]').on('change', function(){
        const $th = $(this);
        if ($th.is(':checked')) setDateIssueDelivery($('#max_date').val());
        else setDateIssueDelivery($('#min_date').val())
    })
	$(".count-block .minus, .count-block .plus").click(function(event) {
		var th = $(this);
		var params = getParams(th.closest('tr'), th.attr('class'));
		if (params.act == 'minus' && +th.parent().find('input').val() - params.packaging == 0) return false;
		$.ajax({
			type: "POST",
			url: "/ajax/order.php",
			data: params,
			success: function(msg){
				var newVal;
				if (params.act == 'plus'){
					newVal = + th.parent().find('input').val() + params.packaging;
					$('#basket_basket').html(+$('#basket_basket').unmask() + params.packaging * params.summand);
				} 
				if (params.act == 'minus'){
					newVal = + th.parent().find('input').val() - params.packaging;
					$('#basket_basket').html(+$('#basket_basket').unmask() - params.packaging * params.summand);
				} 
				th.parent().find('input').val(newVal);
				th.closest('tr').find('span.price_format').text(newVal * params.summand);
			}
		});
	});
	$('div.count-block input').on('blur', function(){
		var count = $(this).val();
		var th = $(this).closest('div.count-block');
		var packaging = th.attr('packaging');
		var reg = /^\d+$/;
		var currTotalSum = 0;
		var totalCount = 0;
		var store_id = th.attr('store_id');
		var item_id = th.attr('item_id');
		// console.log(count % packaging);
		if (!reg.test(count) || count < 1){
			show_message("Введите целое число отличное от нуля!", 'error');
			$(this).focus();
			return false;
		}
		if (count % packaging != 0){
			show_message('Значение должно нацело делиться на ' + packaging + '!', 'error');
			$(this).focus();
			return false;
		}
		var subtotal = th.find('input').val() * th.attr('summand');
		th.closest('tr').find('.subtotal .price_format').html(subtotal);
		$('.cart-popup-table tr[store_id=' + store_id + '][item_id=' + item_id + '] td:nth-child(2)').html(count + ' шт.');
		$('.cart-popup-table tr[store_id=' + store_id + '][item_id=' + item_id + '] span.price_format').html(subtotal);
		$('.basket-table .subtotal .price_format').each(function(){
			currTotalSum = +$(this).html() + currTotalSum;
		});
		$('.basket-table div[store_id]').each(function(){
			totalCount = + $(this).find('input').val() + totalCount;
		})
		$('a.cart span').html(totalCount);
		$('#total_quan').html(totalCount);
		$('#basket_basket').html(currTotalSum);
		$('#total_basket').html(currTotalSum);
		$.ajax({
			type: 'post',
			url: '/ajax/basket.php',
			data: 'act=computing&store_id=' + store_id + 
						'&item_id=' + item_id + 
						'&packaging=' + packaging +
						'&summand=' + th.attr('summand') +
						'&value=' + th.find('input').val(),
			success: function(response){
				// console.log(response); return false;
			}
		})
	})
	$(".cancel_comment").click(function(event) {
		$(".comment-block, .overlay, .h_overlay").hide();
	});
	$(".overlay, .h_overlay").click(function(event) {
		$(this).hide();
		$(".cart-popup, .comments-block").hide();
	});
	$(".comment-btn").click(function(event) {
		var e = $(this);
		e.next('.comment-block').show();
		$(".h_overlay, .overlay").show();
	});
	$(".comment-block button").on('click', function(){
		editComment($(this).closest('.comment-block'));
	})
	$('.comment-block textarea').on('blur', function(){
		editComment($(this).closest('.comment-block'));
	})
	$('span[act=delete]').on('click', function(){
		if (!confirm('Вы действительно хотите удалить?')) return false;
		console.log()
		var th = $(this).closest('tr');
		var params = getParams(th, 'delete');
		// console.log($('table tr[order_id][store_id][item_id]').size()); return false;
		$.ajax({
			type: "POST",
			url: "/ajax/order.php",
			data: params,
			success: function(response){
				th.remove();
				if (!$('table tr[order_id][store_id][item_id]').size()) document.location.href = '/orders';
				// console.log(response); return false;

			}
		})
	})

    $('input[type=checkbox]').styler();

    $('a.button.edit').on('click', function(e){
        let data = {"act": "changeOrderInfo"};
        data.order_id = $('table.orders-table tr[order_id]').attr('order_id');
        data.values = {};
        $.each($('#orderInfo').serializeArray(), function(i, item){
            data.values[item.name] = item.value;
        })
        console.log(data);
        $.ajax({
            type: 'post',
            url: '/ajax/order.php',
            data: data,
            success: function(){}
        })
    })
})