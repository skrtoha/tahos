'use strict'
class Account{
    static designation = '<i class="fa fa-rub" aria-hidden="true"></i>';
    static init(){
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

                const objTab = document.querySelector([`div[data-name=${obj.tab}]`]);

                let innerHtml = objTab.innerHTML;
                innerHtml = innerHtml.trim();
                if (innerHtml.length > 0) return;

                const url = '/ajax/account.php';
                let formData = new FormData(document.querySelector('.account-history-block form'));
                formData.set('act', 'getDebtList');

                popup.style.display = 'flex';

                fetch(url, {
                    method: 'POST',
                    body: formData
                }).then(response => response.json()).then((response) => {
                    objTab.append(this.getHtmlOrderIssues(response));
                    popup.style.display = 'none';
                })
            }
        });

        const dataIssueId = document.querySelectorAll('[data-issue-id]');
        for(let d of dataIssueId){
            d.addEventListener('click', (e) => {
                let issue_id = e.target.closest('tr').getAttribute('data-issue-id');
                this.dataIssueIdEvent(issue_id)
            })
        }
    }
    static dataIssueIdEvent(issue_id){
        popup.style.display = 'flex';
        let formData = new FormData();
        formData.set('act', 'getOrderIssueInfo');
        formData.set('issue_id', issue_id);
        fetch('/ajax/account.php', {
            method: 'post',
            body: formData
        }).then(response => response.json()).then(response => {
            popup.style.display = 'none';
            $.magnificPopup.open({
                items: {
                    src: this.getHtmlOrderIssueValues(response.issue_values),
                    type: 'inline'
                }
            })
        })
    }
    static getHtmlOrderIssues(array){
        let table = document.createElement('table');
        table.classList.add('mobile_view');
        table.innerHTML = `
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Дата</th>
                        <th>Сумма</th>
                        <th>Оплачено</th> 
                    </tr>
                </thead>
                <tbody>
                </tbody>
        `;
        let tbody = table.querySelector('tbody');

        if (array.length) array.forEach((v, index) => {
            let tr = document.createElement('tr');
            tr.setAttribute('data-issue-id', v.issue_id);
            tr.innerHTML = `
                <td label="№">${v.issue_id}</td>
                <td label="Дата">${v.created}</td>
                <td label="Сумма">${v.sum + this.designation}</td>
                <td label="Оплачено">${v.paid + this.designation}</td>
            `;
            tr.addEventListener('click', (event) => {
                this.dataIssueIdEvent(v.issue_id);
            })
            tbody.append(tr);
        })
        else{
            let tr = document.createElement('tr');
            tr.innerHTML = `
                <tr>
                    <td colspan="4">Ничего не найдено</td>
                </tr>
            `;
            tbody.append(tr)
        }

        return table;
    }
    static getHtmlOrderIssueValues(array){
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
                <td label="Цена">${value.price}${this.designation}</td>
                <td label="Выдано">${value.issued}</td>
                <td label="Сумма">${value.price * value.issued}${this.designation}</td>
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
}
if (document.readyState !== 'loading') Account.init();
else document.addEventListener('DOMContentLoaded', Account.init);