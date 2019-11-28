var cookieOptions = {path: '/'};
$(document).ready(function(){
	$('#send-message button').on('click', function(e){
		e.preventDefault();
		var sendeble = true;
		var text_message = $('#send-order-text').val();
		if (text_message.length < 10){
			show_message('Длина сообщения должна быть не менее 10 символов!', 'error');
			sendeble = false;
		}
		var department = $('#department').val();
		if (!department){
			show_message('Выберите тему для сообщения!', 'error');
			sendeble = false;
		}
		if (sendeble){
			fotos = [];
			$('#fotos li').each(function(){
				fotos.push($(this).attr('foto_name'));
			});
			$('#send-message input[name=json_fotos]').val(JSON.stringify(fotos));
			$.cookie('message', 'Сообщение успешно отправлено!');
			$.cookie('message_type', 'ok');
			$('#send-message').submit();
		}
	})
	$('#click_image').on('click', function(){
		$('#image').click();
	})
	$(document).on('click', '#fotos li', function(){
		var foto_name = $(this).attr('foto_name');
		var elem = $(this);
		$.ajax({
			type: "POST",
			url: "/ajax/delete_msg_foto.php",
			data: "name=" + foto_name,
			success: function(msg){
				if (msg){
					elem.remove();
					show_message('Фото успешно удалено!');
				} 
				else show_message('')
			} 
		});
	})
	$(document).on('change', '#image', function(){
		$('#upload_image').ajaxForm({
			target: '#temp_foto',
			beforeSubmit: function(e){
				// $('.uploading').show();
			},
			success:function(msg){
				cookie_message();
				$('#fotos').prepend($('#temp_foto').html());
				$('#temp_foto').empty();					
			},
			error:function(e){
			}
		}).submit();	
	})
	$("select").styler();
	$(".info_btn").click(function(event) {
		$(this).next().show();
		$(".overlay").show();
	});
	$(document).mouseup(function (e)	{
		var container = $(".info");
		if (!container.is(e.target) // if the target of the click isn't the container...
				&& container.has(e.target).length === 0) // ... nor a descendant of the container
		{
			container.hide();
			$(".overlay").hide();
		}
	});
	$('.attachment').magnificPopup({
		delegate: 'a',
		type: 'image',
		tLoading: 'Загрузка #%curr%...',
		mainClass: 'mfp-img-mobile',
		gallery: {
			enabled: true,
			navigateByImgClick: true,
			preload: [0,1] // Will preload 0 - before current, and 1 after the current image
		},
		image: {
			tError: 'Не удалось загрузить <a href="%url%">изображение #%curr%</a>'
		}
	});
})