$(function(){
	$.ionTabs("#account-history-tabs",{
		type: "storage"
	});
	pickmeup.defaults.locales['ru'] = {
		days: ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'],
		daysShort: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
		daysMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
		months: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
		monthsShort: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек']
	};
	pickmeup('#data-pic-beg', {
		date: $('#data-pic-beg').val(),
		format  : 'd.m.Y',
		locale : 'ru',
		hide_on_select: true
	});
	pickmeup('#data-pic-end', {
		date: $('#data-pic-end').val(),
		format  : 'd.m.Y',
		locale : 'ru',
		class_name: 'end-calendar',
		hide_on_select: true
	});
	$("input[name=period]").change(function(event) {
		if (this.value == 'selected') {
			$(".date-wrap input").prop('disabled', false);
		}
		else if (this.value == 'all') {
			$(".date-wrap input").prop('disabled', true);
		}
	});
	$('#payment').on('click', function(e){
		document.location.href = '/payment';
	})

});