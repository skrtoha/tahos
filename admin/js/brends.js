(function($){
	window['brends'] = {
		init: function(){
			$('a.upload_files').on('click', function(){
				$('input[type=file]').get(0).click();
			});
			$('input[type=file]').on('change', function(event){
				event.stopPropagation();
				event.preventDefault();
				if (typeof this.files == 'undefined') return;
				var data = new FormData();
				$.each(this.files, function(key, value){
					data.append(key, value);
				});
				$.ajax({
					url: '/admin/ajax/brends.php?brend_id=' + $('input[name=brend_id]').val(),
					type: 'post',
					data: data,
					cache: false,
					dataType: 'json',
					processData: false,
					contentType: false,
					success: function(respond, status, jqXHR){
						console.log(respond, status, jqXHR);
					}
				})
				console.log(data);
				return false;
			})
		},
	}
})(jQuery)
$(function(){
	brends.init();
})