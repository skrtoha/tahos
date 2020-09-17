var itemInfo;
function showStoreInfo(store_id, item_id){
	$.ajax({
		type: 'post',
		url: '/admin/ajax/item.php',
		data: {
			act: 'getStoreItem',
			store_id: store_id,
			item_id: item_id
		},
		beforeSend: function(){
			$('#popup').css('display', 'flex');
		},
		success: function(response){
			let result = JSON.parse(response);
			let storeInfo = add_item_to_store.storeInfo;
			$.each(result, function(key, value){
				storeInfo[key] = value;
			})
			$('#popup').css('display', 'none');
			modal_show(add_item_to_store.getHtmlForm(storeInfo));
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
			store_id: $('table.t_table').attr('store_id')
		},
		minLength: minLength,
		tableName: tableName
	});
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
	$('input[name=searchArticle].intuitive_search').on('keyup focus', function(e){
		set_intuitive_search(e, 'store_items');
	})
	$('input[name=storeItemsForAdding].intuitive_search').on('keyup focus', function(e){
		set_intuitive_search(e, 'storeItemsForAdding');
	})
	$(document).on('click', 'a.showStoreItemInfo', function(){
		let elem = $(this);
		let store_id = elem.attr('store_id');
		let item_id = elem.attr('item_id');
		showStoreInfo(store_id, item_id);
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
			let tr = $(this).closest('tr');
			$.ajax({
				type: 'get',
				url: '/admin/?view=prices',
				data: {
					act: 'delete_item',
					item_id: tr.find('input[column=packaging]').attr('item_id'),
					store_id: tr.closest('table').attr('store_id')
				},
				success: function(){
					tr.remove();
					show_message('Удачно удалено!');
				}
			})

			return false;
		}
		showStoreInfo(
			$(this).closest('table').attr('store_id'),
			$(this).closest('tr').find('input[column=packaging]').attr('item_id')
		);
		console.log(e.target);
	})
	$(document).on('click', 'a.addStoreItem', function(){
		let $a = $(this);
		let item_id = $a.attr('item_id');
		let storeInfo = add_item_to_store.storeInfo;
		$('#popup').css('display', 'flex');
		$.ajax({
			type: 'post',
			url: '/admin/ajax/item.php',
			data: {
				item_id: item_id,
				act: 'getItemInfo'
			},
			success: function(response){
				$('#popup').css('display', 'none');
				itemInfo = JSON.parse(response);
				$.each(itemInfo, function(key, value){
					storeInfo[key] = value;
				}) 
				storeInfo.store_id = $('table.t_table').attr('store_id');
				storeInfo.item_id = item_id;
				modal_show(add_item_to_store.getHtmlForm(storeInfo));
			}
		})
	})
	$(document).on('submit', 'form.add_item_to_store', function(){
		if (add_item_to_store.isValidated == false) return false;
		let array = $(this).serializeArray();
		let formData = new Object();
		$.each(array, function(i, value){
			formData[value.name] = value.value
		});
		console.log(formData, itemInfo);
		let str = `
			<tr>
				<td>${itemInfo.brend}</td>
				<td><a class="item" href="?view=items&id=${itemInfo.id}&act=item">${itemInfo.article}</a></td>
				<td>${itemInfo.title_full}</td>
				<td><input type="text" class="store_item" value="${formData.packaging}" column="packaging" item_id="${itemInfo.id}"></td>
				<td><input type="text" class="store_item" value="${formData.in_stock}" column="in_stock" item_id="${itemInfo.id}"></td>
				<td><input type="text" class="store_item" value="${formData.price}" column="price" item_id="${itemInfo.id}"></td>
			`;
		if (formData.store_id == '23'){
			str += `
				<td>${formData.price * formData.in_stock}</td>
				<td>
					<input type="text" class="store_item" value="${formData.requiredRemain}" column="requiredRemain" item_id="${itemInfo.id}">
				</td>
			`
		}
		str += `
			<td>
				<a title="Удалить" class="delete_item" href="?view=prices&act=delete_item&item_id=${itemInfo.id}&store_id=${formData.store_id}">
					<span class="icon-cancel-circle1"></span>
				</a>
				</td>
		</tr>
		`;
		$('table.t_table tbody tr.head.sort').after(str);
	})
})