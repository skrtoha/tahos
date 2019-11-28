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
})