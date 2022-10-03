function getHtmlOrderIssueValues(array){
    let output = `
        <div id="issue_info">
            <p><b>ВЫДАЧА №${array[0].issue_id}</b></p>
            <table class="mobile_view">
                <thead>
                    <tr>
                        <th>Бренд</th>
                        <th>Артикул</th>
                        <th>Наименование</th>
                        <th>Цена</th> 
                        <th>Выдано</th>
                        <th>Сумма</th>
                    </tr>
                </thead>
                <tbody>
    `;
    array.forEach((value, index) => {
        output += `
            <tr>
                <td label="Бренд">${value.brend}</td>
                <td label="Артикул">${value.article}</td>
                <td label="Наименование">${value.title_full}</td>
                <td label="Цена">${value.price}<i class="fa fa-rub" aria-hidden="true"></i></td>
                <td label="Выдано">${value.issued}</td>
                <td label="Сумма">${value.price * value.issued}<i class="fa fa-rub" aria-hidden="true"></i></td>
            </tr>
        `
    })
    output += `
                </tbody>
            </table>
        </div>
    `;
    return output;
}
$(function(){
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
    $.ionTabs('#account-history-tabs', {
        type: 'none',
        onChange: (obj) => {
            if (obj.tab != 'Tab_3_name') return;

            const url = '/ajax/account.php';
            let formData = new FormData(document.querySelector('.account-history-block form'));
            formData.set('act', 'getDebtList');

            fetch(url, {
                method: 'POST',
                body: formData
            }).then(response => response.json()).then((response) => {
                console.log(response);
            })
            console.log(obj);
        }
    });

    const dataIssueId = document.querySelectorAll('[data-issue-id]');
    for(let d of dataIssueId){
        d.addEventListener('click', (e) => {
            popup.style.display = 'flex';
            let formData = new FormData();
            formData.set('act', 'getOrderIssueInfo');
            formData.set('issue_id', e.target.parentElement.getAttribute('data-issue-id'));
            fetch('/ajax/account.php', {
                method: 'post',
                body: formData
            }).then(response => response.json()).then(response => {
                popup.style.display = 'none';
                $.magnificPopup.open({
                    items: {
                        src: getHtmlOrderIssueValues(response.issue_values),
                        type: 'inline'
                    }
                })
            })
        })
    }
});