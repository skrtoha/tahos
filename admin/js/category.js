"use strict"
class Category{
    static init(){
        $(document).on('change', 'input[name=isShowOnMainPage]', function(){
            $(this).closest('form').submit();
        })
        $('#add_subcategory').on('click', function(e){
            e.preventDefault();
            const new_value = prompt('Введите название новой подкатегории:');
            if (new_value){
                const parent_id = $(this).attr('category_id');
                $.ajax({
                    type: "POST",
                    url: "/ajax/category.php",
                    data: 'table=add&parent_id=' + parent_id + '&new_value=' + new_value,
                    beforeSend: function(){
                        showGif();
                    },
                    success: function(msg){
                        const res = JSON.parse(msg);
                        if (res.error) show_message(res.error, 'error');
                        else{
                            $('[colspan=4]').remove();
                            let tr = document.createElement('tr');
                            tr.classList.add('subcategory');
                            tr.setAttribute('data-id', res.category_id);
                            tr.innerHTML =
                                '<td title="Нажмите, чтобы изменить" class="category" data-id="' + res.id + '">' +
                                res.title +
                                '</td>' +
                                '<td class="pos" data-id="' + res.id + '">0</td>' +
                                '<td title="Нажмите, чтобы изменить" class="href" data-id="' + res.id + '">' +
                                res.href +
                                '</td>' +
                                `<td>
									<form>
										<input type="hidden" name="view" value="category">
										<input type="hidden" name="act" value="changeIsShowOnMainPage">
										<input type="hidden" name="id" value="${res.id}">
										<input type="checkbox" name="isShowOnMainPage" value="1">
									</form>
								</td>` +
                                `<td>
								    <input type="checkbox" name="hidden" value="1">
                                </td>` +
                                '<td>' +
                                '<a href="?view=category&act=items&id=' + res.category_id + '">Товаров (0)</a> ' +
                                '<a href="?view=category&act=filters&id=' + res.category_id + '">Фильров (1)</a> ' +
                                '<a href="?view=category&id=' + res.category_id + '">Подкатегории (0)</a>' +
                                '</td>' +
                                '<td>' +
                                '<a class="delete_category" href="?view=category&act=delete&id=' + res.id + '&parent_id=' + parent_id + '">Удалить</a>' +
                                '</td>' +
                                '</tr>';
                            document.querySelector('.t_table').append(tr);
                            Category.eventDeleteItem(tr);
                            Category.eventChangeHidden(tr);
                            show_message("Подкатегория '" + new_value + "' успешно добавлена!");
                        }
                        showGif(false);
                    }
                })
            }
        })
        $(document).on('click', '.subcategory td.href, .subcategory td.category, .subcategory td.pos', function(){
            const elem = $(this);
            const id = elem.closest('tr').data('id');
            const table = elem.attr('class');
            let old_value = elem.html();
            old_value = old_value.trim();
            const new_value = prompt('Введите новое значение:', old_value);
            if (!new_value) return false;
            if (new_value == old_value) return false;
            $.ajax({
                type: "POST",
                url: "/ajax/category.php",
                data: 'id=' + id + '&table=' + table + '&old_value=' + old_value + '&new_value=' + new_value,
                success: function(msg){
                    // console.log(msg);
                    // alert(msg);
                    const res = JSON.parse(msg);
                    if (res.error) show_message(res.error, 'error');
                    else{
                        if (table == 'category'){
                            if (res.href) elem.next().html(res.href);
                        }
                        elem.html(new_value);
                        show_message('Изменения успешно сохранены!');
                    }
                }
            })
        })
        $('#add_filter_value').on('click', function(e){
            e.preventDefault();
            const title = prompt('Введите название нового свойства:');
            if (!title) return false;
            Category.sendAjaxAddFilterValue({
                filter_id: $('input[name=filter_id]').val(),
                title: title
            })
        })
        $('.change_filter_value').on('click', function(e){
            e.preventDefault();
            const elem = $(this);
            const current_value = elem.parent().parent().find('td:first-child').html();
            const new_value = prompt('Введите новое название значения фильтра:', current_value);
            if (current_value == new_value) return false;
            if (!new_value) return false;
            $.ajax({
                type: "POST",
                url: "/ajax/change_filter_value.php",
                data: 'filter_value_id=' + $(this).attr('filter_value_id') + '&title=' + new_value,
                success: function(msg){
                    if (msg) return show_message(msg, 'error');
                    else{
                        elem.closest('tr').find('td:first-child').html(new_value);
                        show_message('Успешно изменено!');
                    }
                }
            })
        })
        $('.delete_filter_value').on('click', function(e){
            e.preventDefault();
            if (confirm('Вы действительно хотите удалить данное значение фильтра?')){
                $('#popup').css('display', 'flex');
                const elem = $(this);
                $.ajax({
                    type: "POST",
                    url: "/ajax/delete_filter_value.php",
                    data: 'filter_value_id=' + $(this).attr('filter_value_id'),
                    success: function(msg){
                        if (msg == "ok"){
                            show_message('Свойство фильтра успешно удалено!');
                            $('#popup').css('display', 'none');
                            elem.parent().parent().remove();
                        }
                    }
                })
            }
        })
        $('input.intuitive_search').on('keyup focus', function(e){
            e.preventDefault();
            let val = $(this).val();
            let minLength = 1;
            delay(() => {
                intuitive_search.getResults({
                    event: e,
                    value: val,
                    minLength: minLength,
                    tableName: 'brends',
                });
            }, 1000)
        });
        $(document).on('click', 'a.resultBrend', function(){
            let th = $(this);
            let title = th.text();
            Category.sendAjaxAddFilterValue({
                filter_id: $('input[name=filter_id]').val(),
                title: title.trim()
            });
        })

        const delete_item = document.querySelectorAll('.delete_category');
        if (delete_item){
            delete_item.forEach((element) => {
                Category.eventDeleteItem(element.closest('tr'));
            })
        }

        const hidden = document.querySelectorAll('input[name=hidden]');
        if (hidden){
            hidden.forEach((element) => {
                Category.eventChangeHidden(element.closest('tr'));
            })
        }

        $(document).on('click', 'td.ozon > span.icon-checkbox-unchecked', e => {
            Category.showModalMatchCategory(e)
        })
        $(document).on('click', 'span.icon-checkbox-checked', e => {
            const category_id = e.target.dataset.categoryId
            Category.showModalMatchCategory(e, category_id)
        })
        $(document).on('submit', '#set-match-category', e => {
            e.preventDefault()
            showGif()

            const selectElement = e.target.closest('form').querySelector('select[name="category_id"]')
            const category_id = selectElement.value

            if (!parseInt(category_id)){
                Category.removeMatchCategory(e.target)
                return
            }

            const ozon_category_title = selectElement.querySelector(`option[value="${category_id}"]`).innerText
            const formData = new FormData(e.target)
            formData.set('act', 'ozonSetMatchCategory')
            formData.set('title_category_id', ozon_category_title)
            formData.set('check_unique', '1')
            fetch(Marketplaces.marketplaceUrl, {
                method: 'post',
                body: formData
            }).then(response => response.json()).then(response => {
                showGif(false)
                if (response.error){
                    show_message(response.error, 'error')
                    return
                }

                show_message('Успешно сохранено')
                document.getElementById('modal-container').classList.remove('active')
                const spanElement = document
                    .querySelector(`table td[data-id="${formData.get('tahos_category_id')}"]`)
                    .closest('tr')
                    .querySelector('td.ozon > span')

                spanElement.classList.remove('icon-checkbox-unchecked')
                spanElement.classList.add('icon-checkbox-checked')
                spanElement.dataset.categoryId = category_id


            })
        })
    }
    static removeMatchCategory(form){
        showGif()

        const formData = new FormData
        const tahos_category_id = form.querySelector('input[name="tahos_category_id"]').value
        formData.set('act', 'ozonDeleteMatchCategory')
        formData.set('tahos_category_id', tahos_category_id)

        fetch(Marketplaces.marketplaceUrl, {
            method: 'post',
            body: formData
        }).then(response => response.json()).then(() => {
            const spanElement = document
                .querySelector(`table td.category[data-id="${tahos_category_id}"]`)
                .closest('tr')
                .querySelector('td.ozon > span')

            spanElement.classList.remove('icon-checkbox-checked')
            spanElement.classList.add('icon-checkbox-unchecked')
            show_message('Успешно удалено!')
            showGif(false)
            document.querySelector('#modal-container').classList.remove('active')
        })
    }
    static eventChangeHidden(obj){
        const selector = obj.querySelector('input[name=hidden]');
        selector.addEventListener('change', () => {
            let formData = new FormData();
            formData.set('act', 'setHidden');
            formData.set('id', obj.getAttribute('data-id'));
            formData.set('hidden', selector.checked ? 1 : 0);
            fetch('/admin/ajax/item.php', {
                method: 'post',
                body: formData
            }).then(response => response.text()).then(() => {})
        })
    }
    static sendAjaxAddFilterValue(obj){
        $.ajax({
            type: "POST",
            url: "/ajax/add_filter_value.php",
            data: 'filter_id=' + obj.filter_id + '&title=' + obj.title,
            success: function(msg){
                if (!msg) document.location.reload();
                else show_message(msg, 'error');
            }
        })
    }
    static eventDeleteItem(obj){
        obj.querySelector('.delete_category').addEventListener('click', e => {
            e.preventDefault();

            if (!confirm('Действительно удалить?')) return;

            let formData = new FormData;
            formData.set('act', 'deleteCategory');
            formData.set('id', obj.getAttribute('data-id'));
            showGif();
            fetch('/admin/ajax/item.php', {
                method: 'post',
                body: formData
            }).then(response => response.text()).then(() => {
                obj.remove();
                showGif(false);
            })
        })
    }
    static showModalMatchCategory(e, category_id = null){
        showGif()
        const tahos_category_id = e.target.closest('tr').querySelector('td.category').dataset.id
        const formData = new FormData
        formData.set('act', 'getCategoryOzon')

        if (category_id){
            formData.set('category_id', category_id)
        }

        fetch(Marketplaces.marketplaceUrl, {
            method: 'post',
            body: formData
        }).then(response => response.text()).then(ozonCategoryTree => {
            modal_show(`
                <h3>Выберите категорию Озон</h3>
                <form id="set-match-category">
                    <input type="hidden" name="tahos_category_id" value="${tahos_category_id}">
                    ${ozonCategoryTree}
                    <input type="submit" value="Сохранить">
                </form>
            `)
            Marketplaces.methods.ozon.setChosen('select[name="category_id"]')
            showGif(false)
        })
    }
}
$(function(){
	Category.init();
})