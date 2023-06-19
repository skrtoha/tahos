"use strict"
class Marketplaces{
    static ajaxUrl = '/admin/?view=marketplaces'
    static itemAjaxUrl = '/admin/ajax/item.php'
    static marketplaceUrl = '/admin/ajax/marketplaces.php'
    static itemInfo = {}

    static totalSelector(tab){
        return $(`div[data-name=${tab}] .total span`);
    }

    static init(){
        if (!window.location.hash){
            const get = getParams();
            if (typeof get.tab === 'undefined') get.tab = 'avito';
            window.history.pushState(
                null,
                null,
                '/admin/?view=marketplaces&tab=' + get.tab + '#tabs|marketplaces:' + get.tab
            )
        }
        this.setTabs();
        document.querySelectorAll('a[data-type].add').forEach((item, i) => {
            item.addEventListener('click', e => {
                const tabType = e.target.dataset.type;
                switch(tabType){
                }
            })
        })
        $('input[name=itemsForAdding].intuitive_search').on('keyup focus', function(e){
            Marketplaces.set_intuitive_search(e, 'itemsForAdding');
        })
        $(document).on('click', 'a.addItem', e => {
            let tab;
            let ionTab = e.target.closest('.ionTabs__item');
            if (ionTab) tab = ionTab.dataset.name
            else tab = e.target.closest('li').dataset.tab

            const item_id = e.target.getAttribute('item_id');
            switch(tab){
                case 'avito':
                    Marketplaces.showAvitoModal(item_id);
                    break;
                case 'ozon':
                    Marketplaces.showOzonModal(item_id)
                    break;

            }
        })
        $(document).on('click', 'li.show_all a', (e) => {
            showGif();
            $.ajax({
                type: 'get',
                url: '/vendor/intuitive_search/ajax.php',
                data: {
                    tableName: 'itemsForAdding',
                    value: e.target.dataset.article,
                    additionalConditions: {
                        marketplace: 1,
                        store_id: 23
                    },
                    show_all: 1,
                    tab: e.target.closest('div[data-name]').dataset.name
                },
                success: (response) => {
                    modal_show(`
					<ul class="show_all">
						${response}
					</ul>
				`)
                    showGif(false);
                }
            })
        })
        $(document).on('submit', '#modal_avito', e => {
            const $form = $(e.target);
            const $tableBody = $('div[data-name="avito"] table tbody');
            e.preventDefault()
            let formData = new FormData(e.target)
            formData.set('act', 'applyCategory')
            formData.set('isAvito', '1')
            fetch(Marketplaces.itemAjaxUrl, {
                method: 'post',
                body: formData
            }).then(response => response.text()).then(response => {
                let category = $form.find('tr:nth-child(4) select option:selected').text()
                category = category.replace('→', '')
                $(`tr[data-item-id=${formData.get('item_id')}]`).remove()
                $tableBody.append(`
                    <tr data-item-id="${formData.get('item_id')}" data-category-id="${formData.get('category_id')}">
                        <td>${$form.find('tr:nth-child(1) td:nth-child(2)').text()}</td>                    
                        <td>${$form.find('tr:nth-child(2) td:nth-child(2)').text()}</td>                    
                        <td>${$form.find('tr:nth-child(3) td:nth-child(2)').text()}</td>                    
                        <td>${category}</td>                    
                        <td><span class="icon-bin"></span></td>                    
                    </tr>
                `)
                $('#modal-container').removeClass('active')
            })
        })
        $(document).on('click', 'tr[data-item-id]', e => {
            if(e.target.classList.contains('icon-bin')) return
            if(e.target.classList.contains('icon-loop2')) return
            if(e.target.classList.contains('icon-info1')) return
            if(e.target.classList.contains('icon-upload')) return
            let tab = e.target.closest('div[data-name]').dataset.name
            let item_id = e.target.closest('tr').dataset.itemId
            switch(tab){
                case 'avito':
                    Marketplaces.showAvitoModal(
                        item_id,
                        e.target.closest('tr').dataset.categoryId,
                    )
                    break
                case 'ozon':
                    Marketplaces.showOzonModal(item_id, 1)
                    break
            }
        })
        $(document).on('click', 'span.icon-bin', e => {
            if (!confirm('Действительно удалить?')) return
            const tr = e.target.closest('tr')

            showGif()
            const tab = e.target.closest('div[data-name]').dataset.name
            let formData = new FormData()
            formData.set('act', 'delete_item')
            formData.set('tab', tab)
            formData.set('category_id', tr.dataset.categoryId)
            formData.set('item_id', tr.dataset.itemId)

            fetch(Marketplaces.marketplaceUrl, {
                method: 'post',
                body: formData
            }).then(response => response.text()).then(response => {
                tr.remove()
                showGif(false)
            })
        })
        $(document).on('change', '#modal_ozon select[name="category_id"]', e => {
            let formData = new FormData();
            formData.set('act', 'ozon_get_type')
            formData.set('category_id', e.target.value)
            showGif();
            fetch(Marketplaces.marketplaceUrl, {
                method: 'post',
                body: formData
            }).then(response => response.json()).then(response => {
                document.querySelector('#modal_ozon td.type_good').innerHTML = Marketplaces.getTypeHtml(response)
                showGif(false)
            })
        })
        $(document).on('submit', '#modal_ozon', e => {
            e.preventDefault()
            let formData = new FormData(e.target)
            formData.set('act', 'ozon_product_import')
            showGif();
            fetch(Marketplaces.marketplaceUrl, {
                method: 'post',
                body: formData
            }).then(response => response.json()).then(response => {
                showGif(false)
                if (!response.success){
                    show_message(response.errors, 'error')
                    return
                }
                show_message('Успешно добавлено!')
                document.getElementById('modal-container').classList.remove('active')

                Marketplaces.itemInfo.status = response.status

                const toRemove = document.querySelector([`tr[data-item-id="${Marketplaces.itemInfo.offer_id}"]`]);
                if (toRemove) toRemove.remove()

                $(Marketplaces.getTabElement('ozon')).find('tbody').append(
                    Marketplaces.ozonGetElementRow(Marketplaces.itemInfo)
                )
            })
        })
        $(document).on('keyup', 'input[name="ozon_markup"]', e => {
            let elementPrice = document.querySelector('input[name="price"]')
            let currentPrice = + elementPrice.dataset.clearPrice;
            let markup = + e.target.value;
            elementPrice.value = Math.floor(currentPrice + currentPrice * (markup / 100))
        })
        $(document).on('click', 'span.icon-info1', e => {
            showGif()
            let formData = new FormData()
            formData.set('act', 'ozonGetProductInfo')
            formData.set('offer_id', e.target.closest('tr').dataset.itemId)
            fetch(Marketplaces.marketplaceUrl, {
                method: 'post',
                body: formData
            }).then(response => response.json()).then(response => {
                Marketplaces.showModalOzonProductInfo(response)
            })
        })
        $(document).on('click', 'span.icon-upload', e => {
            showGif()
            let formData = new FormData;
            formData.set('act', 'ozonGetInStock')
            formData.set('offer_id', e.target.closest('tr').dataset.itemId)
            showGif()
            fetch(Marketplaces.marketplaceUrl, {
                method: 'post',
                body: formData
            }).then(response => response.json()).then(response => {
                showGif(false)
                let answer = prompt('Укажите количество:', response.result)
                if (!answer) return;
                showGif()
                let formData = new FormData;
                formData.set('act', 'ozonSetInStock')
                formData.set('amount', answer)
                formData.set('offer_id', e.target.closest('tr').dataset.itemId)
                fetch(Marketplaces.marketplaceUrl, {
                    method: 'post',
                    body: formData
                }).then(response => response.json()).then(response => {
                    showGif(false)
                    if (response.success){
                        show_message('Успешно обновлено!')
                    }
                    else{
                        show_message(response.error)
                    }
                })
            })
        })
        $(document).on('keyup', 'input[name="search"]', e => {
            let currentTab = document.querySelector('li.ionTabs__tab_state_active')
            let tabName = currentTab.dataset.target
            let search = e.target.value.toLowerCase()
            let hide
            document.querySelectorAll(`div[data-name="${tabName}"] tbody tr`).forEach((nodeTr, counter) => {
                hide = true
                nodeTr.querySelectorAll('td').forEach((tdNode, i) => {
                    let currentValue = tdNode.innerText.toLowerCase()
                    if (currentValue.indexOf(search) !== -1) hide = false
                })
                if (hide) nodeTr.style.display = 'none'
                else nodeTr.style.display = 'table-row'
            })
        })
    }



    static showModalOzonProductInfo(object){
        modal_show(`
            <div id="modal_ozon">
                <table>
                    <tr>
                        <td>Цена</td>
                        <td>${object.price}</td>
                    </tr>
                    <tr>
                        <td>Цена <br>до скидки</td>
                        <td>${object.old_price}</td>
                    </tr>
                    <tr>
                        <td>В наличии</td>
                        <td>${object.stocks.present}</td>
                    </tr>
                    <tr>
                        <td>Ошибки</td>
                        <td>${Marketplaces.getStringByArray(object.errors)}</td>
                    </tr>
                    <tr>
                        <td>Статус</td>
                        <td>
                            <table>
                                <tr>
                                    <td>Состояние товара</td>
                                    <td>${object.status.state}</td>
                                </tr>
                                <tr>
                                    <td>Состояние товара, на переходе <br>в которое произошла ошибка.</td>
                                    <td>${object.status.state_failed}</td>
                                </tr>
                                <tr>
                                    <td>Статус модерации</td>
                                    <td>${object.status.moderate_status}</td>
                                </tr>
                                <tr>
                                    <td>Причины отклонения товара</td>
                                    <td>${Marketplaces.getStringByArray(object.status.decline_reasons)}</td>
                                </tr>
                                <tr>
                                    <td>Статус валидации</td>
                                    <td>${object.status.validation_state}</td>
                                </tr>
                                <tr>
                                    <td>Название состояния товара</td>
                                    <td>${object.status.state_name}</td>
                                </tr>
                                <tr>
                                    <td>Описание состояния товара</td>
                                    <td>${object.status.state_description}</td>
                                </tr>
                                <tr>
                                    <td>Признак, что при создании<br> товара возникли ошибки</td>
                                    <td>${object.status.is_failed}</td>
                                </tr>
                                <tr>
                                    <td>Признак, что товар создан</td>
                                    <td>${object.status.is_created}</td>
                                </tr>
                                <tr>
                                    <td>Подсказки для<br> текущего состояния товара</td>
                                    <td>${object.status.state_tooltip}</td>
                                </tr>
                                <tr>
                                    <td>Ошибки при загрузке товаров</td>
                                    <td>${Marketplaces.getStringByArray(object.status.item_errors, 'description')}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td>Выставлен<br> на продажу</td>
                        <td>${object.visible}</td>
                    </tr>
                </table>
            </div>
        `)
        showGif(false)
    }

    static getStringByArray(array, field = null){
        let output = '';
        array.forEach((item, i) => {
            let val
            if (field) val = item[field]
            else val = item
            output += `<span class="string">${val}</span>`
        })
        return output
    }

    static getTabElement(type){
        return document.querySelector(`div[data-name="${type}"]`)
    }

    static showAvitoModal(item_id, category_id = null){
        showGif();

        let formData = new FormData();
        formData.set('act', 'getItemInfo')
        formData.set('item_id', item_id)
        formData.set('category_id', category_id)
        formData.set('marketplace_description', 1)
        fetch(Marketplaces.itemAjaxUrl, {
            method: 'post',
            body: formData
        }).then(response => response.json()).then(itemInfo => {
            formData.set('act', 'getSubCategory')
            formData.set('parent_id', 132)

            fetch(Marketplaces.itemAjaxUrl, {
                method: 'post',
                body: formData
            }).then(response => response.text()).then(categoryTree => {
                modal_show(`
                    <form id="modal_avito">
                        <input type="hidden" name="item_id" value="${item_id}">
                        <table>
                            <tr>
                                <td>Бренд</td>
                                <td>${itemInfo.brend}</td>
                            </tr>
                            <tr>
                                <td>Артикул</td>
                                <td>
                                    <a target="_blank" href="/admin/?view=items&act=item&id=${itemInfo.id}">${itemInfo.article}</a>
                                </td>
                            </tr>
                            <tr>
                                <td>Название</td>
                                <td>${itemInfo.title_full}</td>
                            </tr>
                            <tr>
                                <td>Категория</td>
                                <td>${categoryTree}</td>
                            </tr>
                            <tr>
                                <td>Описание</td>
                                <td>
                                    <textarea required name="marketplace_description" cols="30" rows="10">
                                        ${itemInfo.marketplace_description}
                                    </textarea>
                                </td>
                            </tr>
                        </table>
                        <input type="submit" value="Сохранить">
                    </form>                    
                `)
                showGif(false)
            })
        })
    }

    static showOzonModal(item_id, ozonProductInfo = 0){
        showGif();

        let formData = new FormData();
        formData.set('act', 'getItemInfo')
        formData.set('item_id', item_id)
        formData.set('marketplace_description', 1)
        formData.set('additional_options', 1)
        formData.set('ozon_product_info', ozonProductInfo)
        formData.set('ozon_markup', 1)
        fetch(Marketplaces.itemAjaxUrl, {
            method: 'post',
            body: formData
        }).then(response => response.json()).then(itemInfo => {
            Marketplaces.itemInfo = itemInfo
            formData.set('act', 'getCategoryOzon')
            if (typeof itemInfo.category_id !== 'undefined'){
                formData.set('category_id', itemInfo.category_id)
            }

            fetch(Marketplaces.marketplaceUrl, {
                method: 'post',
                body: formData
            }).then(response => response.text()).then(categoryTree => {
                let htmlType = '';
                if (formData.get('category_id')) htmlType = Marketplaces.getTypeHtml(itemInfo.types);

                let vat = {
                    null: 'checked',
                    ten: '',
                    twenty: ''
                }
                if (typeof itemInfo.vat !== 'undefined'){
                    vat.null = '';
                    switch(parseFloat(itemInfo.vat)){
                        case 0: vat.null  = 'checked'; break
                        case 0.1: vat.ten  = 'checked'; break
                        case 0.2: vat.twenty  = 'checked'; break
                    }
                }
                modal_show(`
                    <form id="modal_ozon">
                        <input type="hidden" name="offer_id" value="${item_id}">
                        <input type="hidden" name="barcode" value="${itemInfo.barcode ? itemInfo.barcode : ''}">
                        <textarea style="visibility: hidden; height: 0" type="hidden" name="marketplace_description">
                            ${itemInfo.marketplace_description}
                        </textarea>
                        <table>
                            <tr>
                                <td>Бренд</td>
                                <td><input type="text" name="brend" readonly value="${itemInfo.brend}"></td>
                            </tr>
                            <tr>
                                <td>Артикул</td>
                                <td>
                                    <input type="hidden" name="article" value="${itemInfo.article}">
                                    <a target="_blank" href="/admin/?view=items&act=item&id=${itemInfo.offer_id}">${itemInfo.article}</a>
                                </td>
                            </tr>
                            <tr>
                                <td>Название</td>
                                <td><input type="text" readonly name="name" value="${itemInfo.title_full}"></td>
                            </tr>
                            <tr>
                                <td>Цена до скидки, руб</td>
                                <td><input type="text" name="old_price" value="${Math.floor(itemInfo.old_price)}"></td>
                            </tr>
                            <tr>
                                <td>Наценка, %</td>
                                <td><input type="text" name="ozon_markup" value="${itemInfo.ozon_markup}"></td>
                            </tr>
                            <tr>
                                <td>Цена, руб</td>
                                <td><input type="text" name="price" data-clear-price="${itemInfo.price}" value="${Math.floor(+itemInfo.price + itemInfo.price * (itemInfo.ozon_markup / 100))}"></td>
                            </tr>
                            <tr>
                                <td>Категория</td>
                                <td>${categoryTree}</td>
                            </tr>
                            <tr>
                                <td>Тип</td>
                                <td class="type_good">${htmlType}</td>
                            </tr>
                            <tr>
                                <td>Размер НДС</td>
                                <td>
                                    <label class="type_good">
                                        <input ${vat.null} type="radio" name="vat" value="0">
                                        0%
                                    </label>   
                                    <label class="type_good">
                                        <input ${vat.ten} type="radio" name="vat" value="0.1">
                                        10%
                                    </label> 
                                    <label class="type_good">
                                        <input ${vat.twenty} type="radio" name="vat" value="0.2">
                                        20%
                                    </label>                                  
                                </td>
                            </tr>
                            <tr>
                                <td>Размеры, мм</td>
                                <td>
                                    <label class="type_good">
                                        Ширина
                                        <input type="text" name="width" value="${itemInfo.width}">
                                    </label>
                                    <label class="type_good">
                                        Высота
                                        <input type="text" name="height" value="${itemInfo.height}">
                                    </label>
                                    <label class="type_good">
                                        Глубина
                                        <input type="text" name="depth" value="${itemInfo.depth}">
                                    </label>
                                </td>                            
                            </tr>
                            <tr>
                                <td>Вес, г</td>                            
                                <td><input type="text" name="weight" value="${itemInfo.weight}"></td>                            
                            </tr>
                        </table>
                        <input type="submit" value="Сохранить">
                    </form>                    
                `)
                showGif(false)
                $('select[name="category_id"]').chosen({
                    disable_search_threshold: 5,
                    no_results_text: "не найден",
                    allow_single_deselect: true,
                    width: "100%"
                });
            })
        })
    }

    static set_intuitive_search(e, tableName){
        let val = e.target.value;
        let minLength = 1;
        val = val.replace(/[^\wа-яА-Я]+/gi, '');
        intuitive_search.getResults({
            event: e,
            value: val,
            additionalConditions: {
                store_id: 23,
                marketplace: 1
            },
            minLength: minLength,
            tableName: tableName
        });
    }

    static getTypeHtml(types){
        let htmlString = '';
        for(let v of types){
            htmlString += `
                <label class="type_good">
                    <input ${v.checked} name="type[]" type="checkbox" value="${v.id}">
                    ${v.name}
                </label>
                    `
        }
        return htmlString
    }

    static setTabs(){
        $.ionTabs("#tabs_1", {
            type: "hash",
            onChange: function(obj){
                let data = {
                    tab: obj.tab
                }
                $.ajax({
                    type: 'post',
                    url: Marketplaces.ajaxUrl,
                    data: data,
                    dataType: "json",
                    success: function(response){
                        let $tbody = $('[data-name=' + obj.tab + '] table tbody')
                        $tbody.empty()
                        switch(obj.tab){
                            case 'avito':
                                $.each(response.items, (i, item) => {
                                    $tbody.append(`
                                            <tr data-item-id="${item.item_id}" data-category-id="${item.category_id}">
                                                <td>${item.brend}</td>
                                                <td>${item.article}</td>
                                                <td>${item.title_full}</td>
                                                <td>${item.category}</td>
                                                <td><span class="icon-bin"></span></td>
                                            </tr>                                        
                                        `);
                                })
                                Marketplaces.totalSelector(obj.tab).html(response.totalCount);
                                break;
                            case 'ozon':
                                $.each(response.items, (i, item) => {
                                    $tbody.append(Marketplaces.ozonGetElementRow(item));
                                })
                                Marketplaces.totalSelector(obj.tab).html(response.totalCount);
                                break
                        }
                    }
                });
            }
        });
    }

    static ozonGetElementRow(item){
        return `
            <tr data-item-id="${item.offer_id}">
                <td>${item.brend}</td>
                <td>${item.article}</td>
                <td>${item.title_full}</td>
                <td>
                    <span title="Удалить" class="icon-bin"></span>
                    <span title="Получение информации" class="icon-info1"></span>
                    <span title="Загрузить остатки" class="icon-upload"></span>
                </td>
            </tr>                                        
        `
    }
}

$(function(){
    Marketplaces.init();
})