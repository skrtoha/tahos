$(function(){

	// tabs in selected ts-name
	$.ionTabs("#selected-ts-tabs",{
		type: "storage"
	});
	$('button.save_ts_note').on('click', function(){
		$.ajax({
			type: 'post', 
			url: '/ajax/garage.php',
			data: 'act=change_garage&modification_title=' + $('input[name=modification_title]').val() +
							'&comment=' + $('div.note textarea').val() + '&modification_id=' + $('input[name=modification_id]').val(),
			success: function(response){
				// console.log(response); return false;
				if (response == 1) show_message('Изменения успешно сохранены!');
			}
		})
	})

});