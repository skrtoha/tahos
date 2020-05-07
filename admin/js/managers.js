(function($){
	window['managers'] = {
		init: function(){
			$('[manager_id]').on('click', function(){
				document.location.href = '/admin/?view=managers&act=manager&id=' + $(this).attr('manager_id');
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
		}
	}
})(jQuery)
$(function(){
	managers.init();
})