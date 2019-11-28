$(function(){
	var get = window
		.location
		.search
		.replace('?','')
		.split('&')
		.reduce(
			function(p,e){
				var a = e.split('=');
				p[ decodeURIComponent(a[0])] = decodeURIComponent(a[1]);
				return p;
			},
			{}
		);
	pickmeup.defaults.locales['ru'] = {
		days: ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'],
		daysShort: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
		daysMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
		months: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
		monthsShort: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек']
	};
	pickmeup('[data-name=common] input[name=begin]', {
		date: $('[data-name=common] input[name=begin]').val(),
		format  : 'd.m.Y',
		locale : 'ru',
		hide_on_select: true
	});
	pickmeup('[data-name=common] input[name=end]', {
		date: $('[data-name=common] input[name=end]').val(),
		format  : 'd.m.Y',
		locale : 'ru',
		hide_on_select: true
	});
	pickmeup('[data-name=group] input[name=begin]', {
		date: $('[data-name=group] input[name=begin]').val(),
		format  : 'd.m.Y',
		locale : 'ru',
		hide_on_select: true
	});
	pickmeup('[data-name=group] input[name=end]', {
		date: $('[data-name=group] input[name=end]').val(),
		format  : 'd.m.Y',
		locale : 'ru',
		hide_on_select: true
	});
	$("[data-name=common] input[name=period]").change(function(event) {
		if (this.value == 'custom') {
			$(this).closest('[data-name=common]').find(".date-wrap input").prop('disabled', false);
		}
		else{
			$(this).closest('[data-name=common]').find(".date-wrap input").prop('disabled', true);
		}
	});
	$("[data-name=group] input[name=period]").change(function(event) {
		if (this.value == 'custom') {
			$(this).closest('[data-name=group]').find(".date-wrap input").prop('disabled', false);
		}
		else{
			$(this).closest('[data-name=group]').find(".date-wrap input").prop('disabled', true);
		}
	});
	$(".order-filter-form .pseudo-select").click(function(event) {
		$(this).toggleClass("opened").nextAll('.status-form').toggle();
	});
	$(document).mouseup(function (e){
		if (!$(e.target).closest('div.status').size()){
			$(".status-form").hide();
			$(".order-filter-form .pseudo-select").removeClass("opened");
		}
	});
	$(document).on('click', ".comment-btn", function(event) {
		var e = $(this);
		e.next('.comment-block').show();
		$(".h_overlay, .overlay").show();
	});
	$(document).on('click', ".cancel_comment", function(event) {
		$(".comment-block, .overlay, .h_overlay").hide();
	});
	$.ionTabs("#orders_tabs",{
		type: "hash",
	});
	if (get.tab) $.ionTabs.setTab('orders', get.tab);
	$('[data-name=group] tr[order_id]').on('click', function(){
		document.location.href = '/order/' + $(this).attr('order_id');
	})
	$('tr[sending_id]').on('click', function(e){
		document.location.href = '/sending/' + $(this).attr('sending_id');
	})
});