'use strict';

class Funds {
    init() {
        const trElementList = document.querySelectorAll("tr:not(.head)");
        if (trElementList) {
            trElementList.forEach(item => {
                item.addEventListener('dblclick', e => {
                    showGif();
                    const tr = e.target.closest('tr');
                    const formData = new FormData();
                    formData.set('act', 'get');
                    formData.set('fund_id', tr.dataset.fundId);
                    fetch('/admin/ajax/funds.php', {
                        method: 'post',
                        body: formData
                    }).then(response => response.json()).then(fund => {
                        showGif(false);

                        modal_show(`
                            <form id="fund_modal">
                                <input type="hidden" name="fund_id" value="${fund.id}">
                                <table>
                                    <tr>
                                        <td>Сумма</td>
                                        <td><input type="text" name="sum" value="${fund.sum}"></td>
                                    </tr>
                                    <tr>
                                        <td>Остаток</td>
                                        <td>
                                            <input type="text" name="remainder" value="${fund.remainder}">
                                        </td>
                                    </tr>
                                     <tr>
                                        <td colspan="2">
                                            <label>
                                                <input type="checkbox" value="1" name="set_for_user">
                                                Установить для пользователя
                                            </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Оплачено</td>
                                        <td><input type="text" name="paid" value="${fund.paid}"></td>
                                    </tr>
                                    <tr>
                                        <td>Счет</td>
                                        <td>
                                            <select name="bill_type">
                                                <option ${fund.bill_type == "1" ? 'selected' : ''} value="1">Наличный</option>
                                                <option ${fund.bill_type == "2" ? 'selected' : ''} value="2">Безналичный</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <input type="submit" value="Сохранить">
                                        </td>
                                    </tr>
                                </table>                            
                            </form>
                            <a class="remove" nohref="" data-fund-id="${fund.id}">Удалить</a>
                        `);
                    }).then(() => {
                        document.querySelector('a.remove').addEventListener('click', e => {
                            if (!confirm('Действительно удалить?')) {
                                return;
                            }

                            showGif();

                            const formData = new FormData;
                            formData.set('act', 'remove');
                            formData.set('fund_id', e.target.dataset.fundId);
                            fetch('/admin/ajax/funds.php', {
                                method: 'post',
                                body: formData
                            }).then(() => {
                                showGif(false);
                                document.querySelector('#modal-container').classList.remove('active');
                                document.querySelector(`tr[data-fund-id="${e.target.dataset.fundId}"]`).remove();
                                show_message('Успешно удалено');
                            })

                        });
                        document.querySelector('#fund_modal').addEventListener('submit', e => {
                            const formData = new FormData(e.target);
                            formData.set('act', 'change');
                            fetch('/admin/ajax/funds.php', {
                                method: 'post',
                                body: formData
                            }).then(() => {
                                document.location.reload();
                            })
                        })
                    })
                })
            })
        }
    }
}

(new Funds()).init();