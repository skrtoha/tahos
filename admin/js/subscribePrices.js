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
			$('tr.edit').on('click', function(e){
				if ($(e.target).hasClass('icon-cancel-circle1')) return false;
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
