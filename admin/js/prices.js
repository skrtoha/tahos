var itemInfo = {};
let store = {};
function showStoreInfo(store_id, item_id, self_store = 0){
	$.ajax({
		type: 'post',
		url: '/admin/ajax/item.php',
		data: {
			act: 'getStoreItem',
			store_id: store_id,
			item_id: item_id,
            self_store: self_store
		},
		beforeSend: function(){
			showGif();
		},
		success: function(response){
			let result = JSON.parse(response);
			let storeInfo = add_item_to_store.storeInfo;
			$.each(result, function(key, value){
				storeInfo[key] = value;
			})
            itemInfo.brend = result.brend;
            itemInfo.id = result.item_id;
            itemInfo.title_full = result.title_full;
            itemInfo.article = result.article;

            if (typeof storeInfo.main_store !== 'undefined' && storeInfo.main_store !== null){
                store.store_id = storeInfo.main_store.store_id;
                store.provider_id = storeInfo.main_store.provider_id;
                store.provider = storeInfo.providerList[store.provider_id].title;
                store.cipher = storeInfo.main_store.cipher;
                store.store = storeInfo.main_store.cipher + '-' + storeInfo.main_store.title;
                store.min_price = storeInfo.main_store.min_price;
                store.requiredRemain = storeInfo.main_store.requiredRemain;
            }

			showGif(false);
			modal_show(add_item_to_store.getHtmlForm(storeInfo, store));
            if ($('table.t_table').data('provider-id') == '14') addHtmlMainStore(storeInfo, store);
		}
	})
	return false;
}
function set_intuitive_search(e, tableName){
	let val = $(e.target).val();
	let minLength = 1;
	val = val.replace(/[^\wа-яА-Я]+/gi, '');
	intuitive_search.getResults({
		event: e,
		value: val,
		additionalConditions: {
			store_id: $('table.t_table').attr('store_id'),
            type_search: document.querySelector('input[name=type_search]:checked').value,
            provider_id: document.querySelector('input[name="provider_id"]').value
		},
		minLength: minLength,
		tableName: tableName
	});
}
function deleteStoreItem(item_id, store_id){
	$.ajax({
		type: 'get',
		url: '/admin/?view=prices',
		data: {
			act: 'delete_item',
			item_id: item_id,
			store_id: store_id
		},
		beforeSend: function(){
			showGif();
		},
		success: function(){
			$('a.deleteStoreItem[item_id=' + item_id + ']').closest('tr').remove();
			showGif(false);
			$('#modal-container').removeClass('active');
			$('ul.searchResult_list a[item_id=' + item_id + '][store_id=' + store_id + ']').closest('li').remove();
			show_message('Удачно удалено!');
		}
	})
}
function addHtmlMainStore(storeInfo = {}, mainStore = {}){
    let htmlString = '';
    const $selector = $('.add_item_to_store tbody tr:last-child');
    if (Object.keys(storeInfo).length == 0){
        $.ajax({
            type: 'post',
            dataType: 'json',
            data: {
                'act': 'getAllProviders'
            },
            url: '/admin/ajax/providers.php',
            success: function(response){
                htmlString = getHtmlAdditionStringMainStore(response);
                $selector.before(htmlString);
            }
        })
    }
    else{
        htmlString = getHtmlAdditionStringMainStore(
            storeInfo.providerList,
            storeInfo.providerStoreList,
            storeInfo,
            mainStore
        )
        $selector.before(htmlString);
    }
}
function getHtmlAdditionStringMainStore(providerList, providerStoreList = {}, storeInfo = {}, mainStore = {}){
    let str = `
        <tr class="provider">
            <td>Поставщик</td>
            <td>
                <select id="provider_id">
                    <option value="">...выберите</option>`;
    $.each(providerList, function(i, item){
        let selected = '';
        if (Object.keys(mainStore).length != 0){
            if (item.id == mainStore.provider_id) selected = 'selected';
        }
        str += `<option ${selected} value="${item.id}">${item.title}</option>`;
    })
    str += `</select></td></tr>`;
    if (Object.keys(providerStoreList).length != 0){
        let providerStoreString = '';
        $.each(providerStoreList, function(i, item){
            let selected = '';
            if (item.id == mainStore.store_id){
                selected = 'selected';
            }
            providerStoreString += `<option ${selected} value="${item.id}">${item.cipher}-${item.title}</option>`;
        })

        str += `
            <tr class="provider_store">
                <td>Склад:</td>
                <td>
                    <select name="main_store_id">
                        <option value="">...выберите</option>
                        ${providerStoreString}
                    </select>
                </td>
            </tr>`;
    }

    let min_price = 0;
    if (Object.keys(mainStore).length != 0){
        min_price = mainStore.min_price;
    }
    str +=
        '<tr>' +
            '<td>Закупка:</td>' +
            '<td>' +
                '<input name="min_price" value="' + min_price + '" type="text">' +
            '</td>' +
        '</tr>';

    let requiredRemain = 1;
    if (Object.keys(storeInfo).length != 0){
        requiredRemain = storeInfo.main_store.requiredRemain;
    }
    str +=
        '<tr>' +
            '<td>Минимальное наличие:</td>' +
            '<td>' +
                '<input name="requiredRemain" value="' + requiredRemain + '" type="text">' +
            '</td>' +
        '</tr>';
    return str;
}
$(function(){
	$('tr[store_id]').on('click', function(){
		document.location.href = '?view=prices&act=items&id=' + $(this).attr('store_id');
	})
	$('.store_item').on('blur', function(){
		$.ajax({
			type: "POST",
			url: "/admin/ajax/store_item.php",
			data: 
				'value=' + $(this).val() + 
				'&column=' + $(this).attr('column') + 
				'&store_id=' + $(this).closest('table').attr('store_id') +
				'&item_id=' + $(this).attr('item_id'),
			success: function(msg){
				console.log(msg);
				if (msg == "ok"){
					show_message('Значение успешно изменено!');
				}
			}
		})
	})
	$('input[name=article].intuitive_search').on('keyup focus', function(e){
		set_intuitive_search(e, 'store_items');
	})
	$('input[name=storeItemsForAdding].intuitive_search').on('keyup focus', function(e){
		set_intuitive_search(e, 'storeItemsForAdding');
	})
	$(document).on('click', 'a.showStoreItemInfo', function(){
		let elem = $(this)
		let store_id = elem.attr('store_id')
		let item_id = elem.attr('item_id')
        let self = elem.data('self')
		showStoreInfo(store_id, item_id, self)
	})
	$(document).on('click', 'table.t_table tr', function(e){
		let target = $(e.target);
		if (target.hasClass('store_item')){
			return false;
		}
		if (target.hasClass('item')){
			document.location.href = target.attr('href');
			return false;
		}
		if (target.hasClass('icon-cancel-circle1')){
			if (!confirm('Действительно удалить?')) return false;
			let item_id = $(target).closest('a').attr('item_id');
			let store_id = $(target).closest('table').attr('store_id');
			deleteStoreItem(item_id, store_id);
			return false;
		}

        let self_store = 0;
        if ($('input[name="provider_id"]').val() == 14) self_store = 1
		showStoreInfo(
			$(this).closest('table').attr('store_id'),
			$(this).closest('tr').find('input[column=packaging]').attr('item_id'),
            self_store
		);
	})
	$(document).on('click', 'a.addStoreItem', function(){
		let $a = $(this);
		let item_id = $a.attr('item_id');
		let storeInfo = add_item_to_store.storeInfo;
		showGif();
		$.ajax({
			type: 'post',
			url: '/admin/ajax/item.php',
			data: {
				item_id: item_id,
				act: 'getItemInfo',
                store_id: $('table.t_table').attr('store_id')
			},
			success: function(response){
                const element = $('table.t_table')
				showGif(false);
				itemInfo = JSON.parse(response);
				$.each(itemInfo, function(key, value){
					storeInfo[key] = value;
				}) 
				storeInfo.store_id = element.attr('store_id');
				storeInfo.item_id = item_id;
                storeInfo.priceWithoutMarkup = '';
				modal_show(add_item_to_store.getHtmlForm(storeInfo));

                //добавление возможности выбора поставщика для основного склада
                if (element.data('provider-id') == '14') addHtmlMainStore();
			}
		})
	})
    $(document).on('change', '#provider_id', function(){
        $('.provider_store').remove();
        const $th = $(this);

        store.provider_id = $(this).val();
        store.provider = $('#provider_id option:selected').text();

        $.ajax({
            type: 'post',
            dataType: 'json',
            url: '/admin/ajax/providers.php',
            data: {
                act: 'getProviderStores',
                provider_id: $th.val()
            },
            success: function(response){
                $('tr.provider_store').remove()
                let str = `
                    <tr class="provider_store">
                        <td>Склад</td>
                        <td>
                            <select name="main_store_id">`;
                store.store_id = response[0].id;
                store.store = response[0].cipher + '-' + response[0].title;
                $.each(response, function(i, item){
                    str += `<option value="${item.id}">${item.cipher}-${item.title}</option>`;
                })
                str += `</select></td></tr>`;
                $('.add_item_to_store tr.provider').after(str);
            }
        })
    })
    $(document).on('change', 'select[name=main_store_id]', function(){
        const $th = $(this);
        store.store_id = $th.val();
        store.store = $th.find('option:selected').text();
    })
	$(document).on('submit', 'form.add_item_to_store', function(){
		if (add_item_to_store.isValidated == false) return false;

        let tbody = $('#contents table.t_table tbody')

        let prevTr = $(`a.deleteStoreItem[item_id=${itemInfo.id}]`).closest('tr').prev();
        if (prevTr.length){
            prevTr.next().remove()
        }
        else{
            prevTr = tbody.find('tr:last-child')
        }


		let array = $(this).serializeArray();
		let formData = {};
		$.each(array, function(i, value){
			formData[value.name] = value.value
		});
        formData.price = formData.price.replace(',', '.');

        if (typeof formData.min_price !== 'undefined'){
            formData.min_price = formData.min_price.replace(',', '.');
        }

		let str = `
			<tr>
				<td>${itemInfo.brend}</td>
				<td><a class="item" href="?view=items&id=${itemInfo.id}&act=item">${itemInfo.article}</a></td>
				<td>${itemInfo.title_full}</td>
				<td><input type="text" class="store_item" value="${formData.packaging}" column="packaging" item_id="${itemInfo.id}"></td>
				<td><input type="text" class="store_item" value="${formData.in_stock}" column="in_stock" item_id="${itemInfo.id}"></td>
				<td><input type="text" class="store_item" value="${formData.price}" column="price" item_id="${itemInfo.id}"></td>
			`;
		if ($('input[name="provider_id"]').val() == 14){
            let now = new Date();
            let updated = now.toLocaleDateString();
			str += `
				<td>${formData.price * formData.in_stock}</td>
				<td>
					<input type="text" class="store_item" value="${formData.requiredRemain}" column="requiredRemain" item_id="${itemInfo.id}">
				</td>
                <td>${store.provider}-${store.store}</td>
                <td>
                    <input type="text" class="store_item" value="${formData.min_price}" column="min_price" item_id="${itemInfo.id}">
                </td>
                <td>${updated}</td>`
		}
		str += `<td>
                    <a title="Удалить" item_id="${itemInfo.id}" class="deleteStoreItem" href="">
                        <span class="icon-cancel-circle1"></span>
                    </a>
                </td>
		    </tr>`;
		$('table.t_table tbody').find('tr.empty').remove()
		prevTr.after(str);
	})
	$(document).on('click', 'a.deleteStoreItem', function(){
		if (!confirm('Действительно удалить')) return false;
		let th = $(this);
		let item_id = th.attr('item_id');
		let store_id = $('table.t_table').attr('store_id');
		deleteStoreItem(item_id, store_id);
	})

    const type_search = document.querySelectorAll('input[name=type_search]');
    type_search.forEach((element, key) => {
        element.addEventListener('change', (event) => {
            let text = event.target.closest('label').querySelector('span').innerText;
            event.target.closest('form').querySelector('input.intuitive_search').placeholder = `Поиск по ${text}`;
        })
    })

	$(document).on('click', 'li.show_all a', (e) => {
		let $th = $(e.target);
		showGif();
		$.ajax({
			type: 'get',
			url: '/vendor/intuitive_search/ajax.php',
			data: {
				tableName: 'storeItemsForAdding',
				value: $th.data('article'),
				additionalConditions: {
					store_id: $th.data('store-id'),
				},
				show_all: 1
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
    $('select[name="self_id"]').on('change', e => {
        document.location.href = `/admin/?view=prices&act=items&self=1&id=${e.target.value}`
    })
})