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
            if(e.target.classList.contains('icon-bin')) return;
            Marketplaces.showAvitoModal(
                e.target.closest('tr').dataset.itemId,
                e.target.closest('tr').dataset.categoryId,
            )
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
                let htmlString = '';
                for(let v of response){
                    htmlString += `
                        <label class="type_good">
                            <input name="type[]" type="checkbox" value="${v.id}">
                            ${v.value}
                        </label>
                    `
                }
                document.querySelector('#modal_ozon td.type_good').innerHTML = htmlString
                showGif(false)
            })
        })
        $(document).on('submit', '#modal_ozon', e => {
            e.preventDefault()
            let formData = new FormData(e.target)
            formData.set('act', 'ozon_product_import')
            fetch(Marketplaces.marketplaceUrl, {
                method: 'post',
                body: formData
            }).then(response => response.json()).then(response => {

            })
        })
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

    static showOzonModal(item_id, category_id = null){
        showGif();

        let formData = new FormData();
        formData.set('act', 'getItemInfo')
        formData.set('item_id', item_id)
        formData.set('category_id', category_id)
        formData.set('marketplace_description', 1)
        formData.set('additional_options', 1)
        formData.set('store_id', 23)
        fetch(Marketplaces.itemAjaxUrl, {
            method: 'post',
            body: formData
        }).then(response => response.json()).then(itemInfo => {
            formData.set('act', 'getCategoryOzon')

            fetch(Marketplaces.marketplaceUrl, {
                method: 'post',
                body: formData
            }).then(response => response.text()).then(categoryTree => {
                modal_show(`
                    <form id="modal_ozon">
                        <input type="hidden" name="offer_id" value="${item_id}">
                        <table>
                            <tr>
                                <td>Бренд</td>
                                <td><input type="text" name="brend" readonly value="${itemInfo.brend}"></td>
                            </tr>
                            <tr>
                                <td>Артикул</td>
                                <td>
                                    <input type="hidden" name="article" value="${itemInfo.article}">
                                    <a target="_blank" href="/admin/?view=items&act=item&id=${itemInfo.id}">${itemInfo.article}</a>
                                </td>
                            </tr>
                            <tr>
                                <td>Название</td>
                                <td><input type="text" readonly name="name" value="${itemInfo.title_full}"></td>
                            </tr>
                             <tr>
                                <td>Цена, руб</td>
                                <td><input type="text" name="price" value="${itemInfo.price}"></td>
                            </tr>
                            <tr>
                                <td>Категория</td>
                                <td>${categoryTree}</td>
                            </tr>
                            <tr>
                                <td>Тип</td>
                                <td class="type_good"></td>
                            </tr>
                            <tr>
                                <td>Размер НДС</td>
                                <td>
                                    <label class="type_good">
                                        <input checked type="radio" name="vat" value="0">
                                        0%
                                    </label>   
                                    <label class="type_good">
                                        <input type="radio" name="vat" value="0.1">
                                        10%
                                    </label> 
                                    <label class="type_good">
                                        <input type="radio" name="vat" value="0.2">
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
                                <td><input type="text" name="weight" value="${itemInfo.weight * 1000}"></td>                            
                            </tr>
                            <tr>
                                <td>Описание</td>
                                <td>
                                    <input type="hidden" name="marketplace_description" value="${itemInfo.marketplace_description}">
                                    ${itemInfo.marketplace_description}
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
                        switch(obj.tab){
                            case 'avito':
                                let $tbody = $('[data-name=' + obj.tab + '] table tbody');
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
                                });
                                Marketplaces.totalSelector(obj.tab).html(response.totalCount);
                                break;
                        }
                    }
                });
            }
        });
    }
}

$(function(){
    Marketplaces.init();
})