(function($){
	window['goods_arrival'] = {
		init: function(){
			if ($('#store .store_id').size()) $('#store .intuitiveSearch_wrap').css({display: 'none'});
			$('#store input.intuitive_search').on('keyup focus', function(e){
				e.preventDefault();
				let val = $(this).val();
				let minLength = 1;
				intuitive_search.getResults({
					event: e,
					value: val,
					minLength: minLength,
					additionalConditions: {},
					tableName: 'provider_stores',
				});
			});
			$(document).on('click', '[store_id].provider_store', function(e){
				e.preventDefault();
				let th = $(this);
				$('#store .intuitiveSearch_wrap').hide();
				$('#store').prepend(`
					<div class="store_id">
						<input type="hidden" name="store_id" readonly value="${th.attr('store_id')}">
						<span>${th.text()}</span>
						<span class="icon-cross1"></span>
					</div>
				`)
			})
			$('#goods input.intuitive_search').on('keyup focus', function(e){
				e.preventDefault();
				let val = $(this).val();
				let minLength = 1;
				val = val.replace(/[^\wа-яА-Я]+/gi, '');
				intuitive_search.getResults({
					event: e,
					value: val,
					minLength: minLength,
					additionalConditions: {
						act: 'items'
					},
					tableName: 'items',
				});
			});
			$(document).on('click', '.store_id .icon-cross1', function(){
				let th = $(this);
				th.closest('.store_id').remove();
				$('#store .intuitiveSearch_wrap').show();
			})
			$(document).on('click', 'a.resultItem', function(e){
				e.preventDefault();
				let th = $(this);
				$('#added_goods')
					.append(`
						<tr class="item_id">
							<td>
								<input type="hidden" name="items[${th.attr('item_id')}]" readonly value="${th.attr('item_id')}">
								<span>${th.text()}</span>
							</td>
							<td>
								<input type="text" name="items[${th.attr('item_id')}][in_stock]">
							</td>
							<td>
								<input type="text" name="items[${th.attr('item_id')}][price]">
							</td>
							<td class="summ">0</td>
							<td>
								<input type="text" name="items[${th.attr('item_id')}][packaging]" value="1">
							</td>
							<td><span class="icon-cross1"></span></td>
						</tr>
					`)
				.show()
				$('#goods .searchResult_list').hide();
			})
			$(document).on('change', '#added_goods input[name*=in_stock], #added_goods input[name*=price]', function(){
				let tr = $(this).closest('tr');
				let quan = + tr.find('input[name*=in_stock]').val();
				let price = + tr.find('input[name*=price]').val();
				let summ = quan * price;
				tr.find('td.summ').html(summ);
			})
			$(document).on('click', '#added_goods .icon-cross1', function(){
				$(this).closest('tr').remove();
			})
			$('form').on('submit', function(e){
				let th = $(this);
				let formData = th.serializeArray();
				let isExistsStoreID = false;
				let isValidated = true;
				let isExistItems = false;
				$.each(formData, function(i, v){
					if (v.name == 'store_id') isExistsStoreID = true;
					if (/items/.test(v.name)) isExistItems = true;
					if (!v.value) isValidated = false;
				})
				if (!isExistItems){
					e.preventDefault();
					return show_message('Добавьте хоть один товар!', 'error');
				}
				if (!isExistsStoreID){
					e.preventDefault();	
					return show_message('Укажите склад!', 'error');
				} 
				if (!isValidated){
					e.preventDefault();
					return show_message('Заполните все поля!', 'error');
				}
			})
			$('#commonList tbody tr').on('click', function(){
				window.location.href = $(this).attr('href');
			})
		}
	}
})(jQuery)
$(function(){
	goods_arrival.init();
})