$(function(){

	// tabs in selected ts-name
	$.ionTabs("#selected-ts-tabs",{
		type: "storage"
	});
	$('button.save_ts_note').on('click', function(){
		let data = new Object();
		data.act = 'change_garage';
		data.modification_id = $('input[name=modification_id]').val();
		if ($('input[name=modification_title]').val() !== 'undefined') data.modification_title = $('input[name=modification_title]').val();
		if ($('div.note textarea').val() !== 'undefined') data.comment = $('div.note textarea').val();
		$.ajax({
			type: 'post', 
			url: '/ajax/garage.php',
			data: data,
			success: function(response){
				// console.log(response); return false;
				if (response == 1) show_message('Изменения успешно сохранены!');
			}
		})
	})

});