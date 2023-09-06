'use strict'
class User{
    static mainSelector = '#user_order_add '
    static response = this.mainSelector + 'div.response'
    static reg_int = /^\d+$/
    static items = {}
    static pageSize = 30
    static arrangementList = document.querySelector('input[name="arrangement[list]"]')
    static head_common_list =
            '<tr class="head">' +
                '<th>Тип</th>' +
                '<th>Поиск</th>' +
                '<th>Дата</th>' +
            '</tr>'
    static paginationContainer = $('#pagination-container')
    static init(){
        if (typeof items !== 'undefined'){
            User.items = items;
            if (Object.keys(User.items).length){
                $('tr.hiddable').hide();
                $.each(items, (item_id, item) => {
                    let htmlStores = User.getStringHtmlStores(item_id);
                    $('#added_items table tbody').append(User.getTableRow(item_id, htmlStores))
                })
                User.setTotal();
            }
        }

        if ($('#history_search').size()) User.history_search();

        $('#actions form').on('submit', function(e){
            e.preventDefault();
            let url = '/admin/?view=users&id=' + $('input[name=user_id]').val() + '&ajax=history_search_count';
            let params = {};
            let formData = $(this).serializeArray();
            $.each(formData, function (i, item){
                if (!item.value) return 1;
                params[item.name] = item.value;
                url += '&' + item.name + '=' + item.value;
            })
            $.ajax({
                url: url,
                type: 'get',
                success: function(response){
                    $('input[name=totalNumber]').val(response);
                    user_order_add.history_search(params);
                }
            })

        });
        $(User.getSelector('a.show_form_search')).on('click', function(e){
            e.preventDefault();
            $(this).nextAll('div.item_search').toggleClass('active');
        })
        $(document).on('click', User.getSelector('li a.resultItem'), function(e){
            e.preventDefault();
            User.getHtmlStores($(this).attr('item_id'));
        })
        $(User.getSelector('#added_items')).on('submit', function(e){
            let is_valid = true;
            $(this).find('input[name^=price]').each(function(){
                const val = $(this).val();
                if (!parseInt(val)) is_valid = false;
            })
            $(this).find('input[name^=quan]').each(function(){
                const val = $(this).val();
                if (!parseInt(val)) is_valid = false;
            })
            if (!is_valid){
                show_message('Произошла ошибка', 'error');
                e.preventDefault();
            }
            else $(this).find('input').prop('disabled', false);
        })
        $(document).on('change', User.getSelector('select[name^=store_id]'), function(){
            let th = $(this);
            let tr = th.closest('tr');
            let item_id = tr.attr('item_id');
            let store_id = th.val();
            let store = User.items[item_id].stores.find((item, index, array) => {
                if (item.store_id == store_id) return item;
            })

            if ( typeof store === 'undefined'){
                User.items[item_id].store_id = 0;
                User.items[item_id].price = 0;
                User.items[item_id].withoutMarkup = 0;

                tr.find('input[name^=price]')
                    .val(0)
                    .prop('disabled', false);
            }
            else {
                User.items[item_id].store_id = store_id;
                User.items[item_id].price = store.price;
                User.items[item_id].withoutMarkup = store.withoutMarkup;
                tr.find('input[name^=withoutMarkup]').val(store.withoutMarkup);
                tr.find('input[name^=price]')
                    .val(store.price)
                    .prop('disabled', true);
            }
            // User.addToBasket(User.items[item_id])
            User.setTotal();
            User.setChangesExist();
        })
        $(document).on('change', User.getSelector('input[name^=price]'), function(){
            if (!User.reg_int.test($(this).val())) return show_message('Значение цены задано неккоректно!', 'error');
            User.setTotal();
            User.setChangesExist();
        })
        $(document).on('change', User.getSelector('input[name^=quan]'), function(){
            let th = $(this);
            let quan = th.val();
            if (!User.reg_int.test(quan)) return show_message('Значение количества задано неккоректно!', 'error');
            let item_id = th.closest('tr').attr('item_id');
            User.items[item_id].quan = quan;
            // User.addToBasket(User.items[item_id]);
            User.setTotal();
            User.setChangesExist();
        })
        $(document).on('change', User.getSelector('input[name^=toOrder]'), function(){
            User.setChangesExist();
        })
        $(document).on('click', User.getSelector('span.icon-cancel-circle1'), function(e){
            e.preventDefault();
            if (!confirm('Вы действительно хотите удалить?')) return false;
            let item_id = $(this).closest('tr').attr('item_id');
            $(e.target).closest('tr').remove();
            if (!$(User.getSelector('tr.item')).size()) $(User.getSelector('tr.hiddable')).show();

            delete User.items[item_id];
            User.setTotal();
            User.setChangesExist();
        })
        $('.users_box').on('click', function(){
            document.location.href = "?view=users&act=funds&id=" + $(this).attr('user_id');
        })
        $('a.return_money').on('click', function(e){
            e.preventDefault();
            let amount = + prompt('Введите сумму:');
            if (!amount) return false;
            if (!/\d+/.test(amount)) return show_message('Сумма указано неверно!', 'error');
            $.ajax({
                type: 'post',
                url: '/admin/ajax/user.php',
                data: {
                    act: 'return_money',
                    amount: amount,
                    user_id: $('input[name=user_id]').val()
                },
                success: function(){
                    let $obj = $('div.actions.users > span:nth-child(2) > b > span');
                    let currentAmount = +$obj.html();
                    $obj.html(currentAmount - amount);
                    show_message('Успешно возвращено!');
                }
            })
        })
        $('input.intuitive_search').on('keyup focus', function(e){
            e.preventDefault();
            let val = $(this).val();
            let minLength = 1;
            val = val.replace(/[^\wа-яА-Я]+/gi, '');
            delay(() => {
                intuitive_search.getResults({
                    event: e,
                    value: val,
                    minLength: minLength,
                    additionalConditions: {
                        act: 'items'
                    },
                    tableName: 'items',
                });
            }, 1000)
        });
        $('div.set-addresses > button').on('click', function(e) {
            e.preventDefault();
            modal_show();
        })
        $('input.save').on('click', (e) => {
            $('input[name=save_basket]').val(1);
        })

        const get_arrangements = document.querySelector('a.get_arrangements');
        if (get_arrangements !== null){
            get_arrangements.addEventListener('click', (e) => {
                User.getArrangementList().then(response => {});
            })
        }

        document.querySelectorAll('input[name="bill_mode"]').forEach((item, i) => {
            item.addEventListener('change', (e) => {
                if (User.arrangementList.value == ''){
                    User.getArrangementList().then(response => {});
                }
                let arrangement1 = document.querySelector(`select[name="arrangement[1]"]`);
                let arrangement2 = document.querySelector(`select[name="arrangement[2]"]`);
                switch (e.target.value){
                    case '2':
                        arrangement1.setAttribute('disabled', 'disabled');
                        arrangement2.removeAttribute('disabled');
                        break;
                    case '1':
                        arrangement1.removeAttribute('disabled');
                        arrangement2.setAttribute('disabled', 'disbled');
                        break;
                    case '3':
                        arrangement1.removeAttribute('disabled');
                        arrangement2.removeAttribute('disabled');
                        break;
                }
            })
        })

        const dataIssueId = document.querySelectorAll('tr[data-issue-id]');
        if (dataIssueId){
            dataIssueId.forEach((item, i) => {
                item.addEventListener('click', User.eventFundDistribution)
            })
        }
    }
    static eventFundDistribution = (e) => {
        let formData = new FormData;
        const trDataIssueId = e.target.closest(['tr[data-issue-id]']);

        const nextSibling = trDataIssueId.nextElementSibling;
        if (nextSibling && trDataIssueId.nextElementSibling.classList.contains('second')){
            nextSibling.remove();
            return;
        }

        formData.set('act', 'getFundDistribution');
        formData.set('issue_id', trDataIssueId.getAttribute('data-issue-id'));
        showGif();
        fetch('/admin/ajax/user.php', {
            method: 'POST',
            body: formData
        }).then(response => response.json()).then(response => {
            let tdBaseElement = document.createElement('tr');
            tdBaseElement.classList.add('second');
            tdBaseElement.innerHTML = `
                            <td colspan="7">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Дата</th>
                                            <th>Тип операции</th>
                                            <th>Сумма</th>
                                            <th>Комментарий</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>                            
                            </td>
                        `;
            let tbody = tdBaseElement.querySelector('tbody');
            for (let row of response){
                let trElement = document.createElement('tr');
                trElement.innerHTML = `
                                <td>${row.created}</td>
                                <td>Пополнение счета</td>
                                <td>${row.sum}</td>
                                <td>${row.comment}</td>
                            `;
                tbody.append(trElement);
            }
            trDataIssueId.after(tdBaseElement);
            showGif(false);
        })
    }
    static async getArrangementList(){
        showGif();
        let formData = new FormData();
        formData.set('TahosID', document.querySelector('input[name="user_id"]').value);
        formData.set('userType', 'Покупатель');
        formData.set('act', 'getArrangements');
        const response = await fetch('/admin/ajax/user.php', {
            method: 'POST',
            body: formData
        });
        let json = await response.json();
        User.arrangementList.value = JSON.stringify(json);
        let htmlString = '';
        json.forEach((item, i) => {
            htmlString += `<option value="${item.uid}">${item.title}</option>`
        })
        document.querySelectorAll('div.ut_arrangement select').forEach((item, i) => {
            item.innerHTML = htmlString;
        })
        showGif(false);
        return json;
    }
    static setChangesExist(){
        $('input.save').prop('disabled', false);
        $('input.send').prop('disabled', true);
        $('input[name^=price]').each((i, item) => {
            if (item.value == "0") $('input.save').prop('disabled', true);
        })
        $('input[name^=quan]').each((i, item) => {
            if (item.value == "0") $('input.save').prop('disabled', true);
        })
    }
    static history_search(params = {}){
        let dataSource = '/admin/?view=users&id=' + $('input[name=user_id]').val() + '&ajax=history_search';
        if (params){
            for(let key in params){
                if (!params[key]) continue;
                dataSource += '&' + key + '=' + params[key];
            }
        }
        User.paginationContainer.pagination({
            pageNumber: $('input[name=page]').val(),
            dataSource: dataSource,
            className: 'paginationjs-small',
            locator: '',
            totalNumber: $('input[name=totalNumber]').val(),
            pageSize: User.pageSize,
            ajax: {
                beforeSend: function(){
                    showGif();
                }
            },
            callback: function(data, pagination){
                let str = User.head_common_list;
                let search;
                for(var key in data){
                    var d = data[key];
                    if (d.item_id){
                        let href = `/admin/?view=items&act=item&id=${d.item_id}`;
                        search = '<a target="_blank" href="'+ href + '">' + d.search + '</a>';
                    }
                    else {
                        let vin = d.search.slice(0, 17);
                        let href = '/original-catalogs/legkovie-avtomobili#/carInfo?q=' + vin;
                        search = '<a target="_blank" href="'+ href + '">' + d.search + '</a>'
                    }
                    str +=
                        '<tr>' +
                        '<td>' + d.type + '</td>' +
                        '<td>' + search + '</td>' +
                        '<td>' + d.date + '</td>' +
                        '</tr>'
                }
                $('#history_search').html(str);
                showGif(false);
            }
        })
    }
    static getSelector(str){
        return User.mainSelector + str;
    }
    static getTableRow(item_id, htmlStores){
        let item = User.items[item_id];
        let valueWithoutMarkup = typeof item.withoutMarkup === 'undefined' ? 0 : item.withoutMarkup;
        let valuePrice = typeof item.price === 'undefined' ? 0 : item.price;
        let valueQuan = typeof item.quan === 'undefined' ? 0 : item.quan;
        let valueSumm = typeof item.price === 'undefined' ? 0 : item.price * item.quan;
        let priceDisabled = htmlStores ? 'disabled' : '';
        let comment = typeof item.comment === 'undefined' || item.comment == null ? '' : item.comment;
        let checkedToOrder = 'checked';
        if (typeof item.isToOrder !== 'undefined' && item.isToOrder == "0"){
            checkedToOrder = '';
        }

        return '<tr class="item" item_id="' + item_id + '">' +
                    `<td>
                        <input title="Отправлять в заказ" value="1" type="checkbox" name="toOrder[${item_id}]" ${checkedToOrder}>
                    </td>` +
                    '<td label="Поставищик">' + htmlStores + '</td>' +
                    '<td label="Бренд">' + item.brend + '</td>' +
                    '<td label="Артикул">' + item.article + '</td>' +
                    '<td label="Наименование">' + item.title_full + '</td>' +
                    `<td label="Цена">
                        <input value="${valueWithoutMarkup}" type="hidden" name="withoutMarkup[${item_id}]">
                        <input ${priceDisabled} value="${valuePrice}" type="text" name="price[${item_id}]">
                    </td>` +
                    `<td label="Количество"><input value="${valueQuan}" type="text" name="quan[${item_id}]"></td>` +
                    `<td label="Сумма"><span value="0" class="summ">${valueSumm}</span></td>` +
                    `<td label="Комментарий">
                        <textarea name="comment[${item_id}]">${comment}</textarea>
                    </td>` +
                    `<td>
                        <span class="icon-cancel-circle1 delete"></span>
                    </td>` +
            '</tr>';
    }
    static getHtmlStores(item_id){
        if (User.items[item_id] != undefined) return false;
        $.ajax({
            type: 'post',
            url: '/admin/ajax/store_item.php',
            data: {
                column: 'getStoreItemsByItemID',
                item_id: item_id,
                user_id: $('input[name=user_id]').val()
            },
            beforeSend: function(){
                showGif();
            },
            success: function(response){
                if (!response) return false;

                User.items[item_id] = JSON.parse(response);

                let htmlStores = User.getStringHtmlStores(item_id);

                $('#added_items table .hiddable').hide();
                $('#added_items table tbody').append(User.getTableRow(item_id, htmlStores));
                $('#user_order_add .searchResult_list').hide();
                setTimeout(function(){
                    $('#added_items table tr:last-child input[name^=price]').focus();
                }, 100);
                showGif(false);
            }
        })
    }
    static addToBasket(object){
        let formData = new FormData;
        formData.set('user_id', document.querySelector('input[name=user_id]').value);
        formData.set('store_id', object.store_id);
        formData.set('item_id', object.item_id);
        formData.set('quan', typeof object.quan === 'undefined' ? 0 : object.quan);
        formData.set('price', object.price);

        showGif(true);

        fetch('/ajax/to_basket.php', {
            method: 'post',
            body: formData
        }).then(response => response.json()).then(response => {
            popup.style.display = 'none';
            let node = User.getHtmlOrderIssueValues(response.issue_values)
            table.querySelector('[data-issue-id="' + issue_id + '"]').after(node);
            obj.classList.add('active');
            showGif(false);
        })
    }
    static getStringHtmlStores(item_id){
        let htmlStores = '';
        if (User.items[item_id].stores != undefined){
            htmlStores = '<select name="store_id[' + item_id + ']">';
            htmlStores += '<option value="0">без поставщика</option>';
            $.each(User.items[item_id].stores, function(i, store){
                let selected = '';
                if (User.items[item_id].store_id != undefined){
                    if (store.store_id == User.items[item_id].store_id) selected = 'selected';
                }
                htmlStores += `<option ${selected} value="${store.store_id}">${store.cipher} - (${store.price}р.)</option>`;
            })
            htmlStores += '</select>';
        }
        return htmlStores;
    }
    static setTotal(){
        let total = 0;
        $(User.getSelector('tr.item')).each(function(){
            const price = $(this).find('input[name^=price]').val();
            const quan = $(this).find('input[name^=quan]').val();
            $(this).find('span.summ').text(price * quan);
            total += price * quan;
        })
        $(User.getSelector('span.total')).text(total);
    }
}
if (document.readyState !== 'loading') User.init();
else document.addEventListener('DOMContentLoaded', User.init);