$(document).ready(function(e){
	$('.messages-page tr').on('click', function(e){
		var t = e.target;
		if (t.className == 'fa fa-times' || t.className == 'delete_all_messages') return false;
		if ($(this).attr('type') == 'new') document.location.href = '/new/' + $(this).attr('id');
		else document.location.href = '/correspond/' + $(this).attr('id');
	})
	$('.delete-message').on('click', function(e){
		if (confirm('Вы действительно хотите удалить данную переписку?')){
				var elem = $(this).closest('tr');
			var type = elem.attr('type');
			var data = '&id=' + elem.attr('id');
			if (elem.attr('type') == 'new') data += '&act=delete_new';
			else data += '&act=delete_message';
			console.log(data);
			$.ajax({
				type: "POST",
				url: "/ajax/message.php",
				data: data,
				success: function(msg){	
					// console.log(msg);
					// return;
					if (msg == "ok"){
						show_message('Успешно удалено!');
						elem.remove();
						if ($('.messages-page table tr').length == 1) $('.messages-page table').append('<tr><td colspan="2">Сообщений не найдено</td></tr>');
					}
				} 
			});
		}
	})
	$('#delete_all_messages').on('click', function(){
		if (!confirm('Вы действительно хотите удалить всю переписку')) return false;
		$.ajax({
			type: "POST",
			url: "/ajax/message.php",
			data: "&act=delete_all_messages",
			success: function(msg){
				if (msg == "ok"){
					show_message('Сообщения успешно удалены!', 'ok');
					$('.messages-page table tr:nth-child(n + 2)').remove();
					if ($('.messages-page table tr').length == 1) $('.messages-page table').append('<tr><td colspan="2">Сообщений не найдено</td></tr>');
				}
			} 
		});
	})
	$('#new_message').on('click', function(){
		window.location.href = "/new_message";
	})
})