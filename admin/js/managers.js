(function($){
	window['managers'] = {
		init: function(){
			$('[data-manager-id]').on('click', function(e){
                if (e.target.classList.contains('icon-cross1')) {
                    return;
                }
				document.location.href = '/admin/?view=managers&act=manager&id=' + e.target.closest('tr').dataset.managerId;
			})
			$('[group_id]').on('click', function(){
				document.location.href = '/admin/?view=managers&act=group&id=' + $(this).attr('group_id');
			})
			$('input[type=checkbox]').on('click', function(){
				if ($(this).closest('li').hasClass('parent')){
					if ($(this).prop('checked')){
						$(this).closest('li').find('input[type=checkbox]').prop('checked', true);
					}
					else $(this).closest('li').find('input[type=checkbox]').prop('checked', false);
					return;
				}
				else{
					$(this).closest('li.parent').children('label').find('input[type=checkbox]').prop('checked', true);
				}
			})
            const icons = document.querySelectorAll('span.icon-cross1');
            if (icons) {
                icons.forEach(function(item){
                    item.addEventListener('click', e => {
                        if (!confirm('Уверены, что хотите удалить?')) {
                            return;
                        }

                        const formData = new FormData;
                        formData.set('act', 'remove');
                        formData.set('id', item.closest('tr').dataset.managerId);
                        fetch('/admin/ajax/managers.php', {
                            method: 'post',
                            body: formData
                        }).then(() => {
                            e.target.closest('tr').remove();
                            show_message('Успешно удалено')
                        })

                    })
                })
            }

		}
	}
})(jQuery)
$(function(){
	managers.init();
})