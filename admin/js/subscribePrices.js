(function($){
	window['subscribePrices'] = {
		init: function(){
			$('#add').on('click', function(e){
				e.preventDefault();
				subscribePrices.showForm({
					email: '',
					title: '',
					phone: ''
				});
			})
			$('tr.edit:not(.user_id)').on('click', function(e){
				if (
					$(e.target).hasClass('icon-cancel-circle1') ||
					$(e.target).closest('a').hasClass('subscribeHandy')
				) return false;
				let self = $(this);
				let data = {
					email: self.closest('tr').find('td:nth-child(1)').text(),
					title: self.closest('tr').find('td:nth-child(2)').text(),
					phone: self.closest('tr').find('td:nth-child(3)').text(),
				}
				subscribePrices.showForm(data);
			})
			$('.icon-cancel-circle1').on('click', function(){
				if (!confirm('Действительно удалить?')) return false;
				document.location.href = $(this).closest('a').attr('href');
			})
			$('a.subscribeHandy').on('click', function(e){
				e.preventDefault();
				let self = $(this);
				modal_show(`
					<div id="modal_subscribePrice">
						<div class="left">
							<a price="${self.attr('email')}" act="subscribeMainStoresPrice" class="subcribePrice" href="">Отправить полный прайс</a>
							<label>
								<input id="formNew" type="checkbox" name="formNew" value="1">
								<span>Сформировать заново</span>
							</label>
						</div>
						<div class="right">
							<a price="${self.attr('email')}" act="subscribeTahosPrice" class="subcribePrice" href="">Отправить прайс Тахос</a>
						</div>
					</div>
				`);
			})
			$(document).on('click', 'a.subcribePrice', function(e){
				e.preventDefault();
				let self = $(this);
				$.ajax({
					type: 'post',
					url: '/admin/ajax/user.php',
					data: {
						act: self.attr('act'),
						email: self.attr('price'),
						isFormNew: $('#formNew').is(':checked')
					},
					beforeSend: function(){
						$('#modal-container').removeClass('active');
						showGif();
					},
					success: function(response){
						showGif(false);
						if (response == '1') return show_message('Прайс успешно отправлен!');
						else return show_message(response, 'error');
					}
				})
			})
		},
		showForm(data){
			let str = `
				<form name="changeSubscribePrices" method="post" action="/admin/?view=subscribePrices&act=change">
					<table>
						<tr>
							<td>Email:</td>
							<td><input type="email" required value="${data.email}" name="email"></td>
						</tr>
						<tr>
							<td>Название:</td>
							<td><input type="text" required value="${data.title}" name="title"></td>
						</tr>
						<tr>
							<td>Телефон:</td>
							<td><input type="text" name="phone" value="${data.phone}"></td>
						</tr>
						<tr>
							<td colspan="2"><input type="submit" value="Сохранить"></td>
						</tr>
					</table>
				</form>
			`;
			modal_show(str);
		}
	}
})(jQuery)
$(function(){
	subscribePrices.init();
})
