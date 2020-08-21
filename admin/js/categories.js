(function($){
	window['categories'] = {
		init: function(){
			$('select[name=parent_id]').on('change', function(){
				$(this).closest('form').submit();
			})
			$('#buttonLoadPhoto').on('click', function(e){
				e.preventDefault();
				$('#loadPhoto').click();
			})
			$(document).on('change', '#loadPhoto', function(){
				$(this).closest('form').ajaxForm({
					target: '#modal_content',
					beforeSubmit: function(){},
					success: function(response){
						let image = document.getElementById('uploadedPhoto');
						let item_id = $('#item_id').val();
						let cropper = new Cropper(image, {
							autoCropArea: 1,
							aspectRatio: 0.8,
							cropBoxResizable: false
						});
						$('#modal-container').addClass('active');
						$('#modal-container').on('click', function(event){
							var t = $('#modal-container');
							if (t.is(event.target)){
					      	cropper.reset();
								$('#modal_content').empty();
								t.removeClass('active');
								$('#loadPhoto').closest('form').resetForm();
							} 
						})
						$('#savePhoto').on('click', function(){
							cropper
								.getCroppedCanvas({
									'fillColor': '#fff',
									'width': 200,
									height: 250
								})
								.toBlob((blob) => {
									const formData = new FormData();
									formData.append('croppedImage', blob/*, 'example.png' */);
									formData.append('item_id', item_id);
									formData.append('act', 'savePhoto');
									formData.append('initial', $('#uploadedPhoto').attr('src'));
									$.ajax('/admin/ajax/item.php', {
										method: 'POST',
										data: formData,
										processData: false,
										contentType: false,
										success(response) {
											let images = JSON.parse(response);
											let count = $('#photos li').size();
											$('#photos').append(
												'<li big="' + images.big + '">' +
													'<div>' +
														'<a table="fotos" class="delete_foto" href="#">Удалить</a>' +
													'</div>' +
													'<img src="' + images.small + '" alt="">' +
													'<input type="hidden" name="photo" value="' + images.small + '">' +
												'</li>'
											);
								      	cropper.destroy();
											$('#modal_content').empty();
											$('#modal-container').removeClass('active');
											$('#buttonLoadPhoto').addClass('hidden');
										},
										error() {
											console.log('Upload error');
										},
									});
								});
						})
					}
				}).submit();
			})
			$('a.delete_foto').on('click', function(){
				$(this).closest('li').remove();
				$('#buttonLoadPhoto').removeClass('hidden');
 			})
		}
	}
})(jQuery)
$(function(){
	categories.init();
})