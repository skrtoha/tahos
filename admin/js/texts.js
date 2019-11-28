$(function(){
	$.ionTabs("#tabs_1",{
		type: "hash",
		onChange: function(obj){
			var str = '/admin/?view=texts&tab=' + obj.tab;
			if ($('div[data-name=' + obj.tab + '] input[name=act]').val()) str += '&act=' + $('input[name=act]').val();
			if ($('div[data-name=' + obj.tab + '] input[name=id]').val()) str += '&id=' + $('input[name=id]').val();
			str += '#tabs|texts:' + obj.tab;
			window.history.pushState(null, null, str);
		}
	});
	$('tr[text_id]').on('click', function(){
		document.location.href = '?view=texts&tab=help&act=theme_change&id=' + $(this).attr('text_id');
	});
	$('tr[rubric_id]').on('click', function(){
		document.location.href = '?view=texts&tab=help&act=rubric_change&id=' + $(this).attr('rubric_id');
	});
	$('.item_remove').on('click', function(e){
		if (!confirm('Вы действительно хотите удалить?')) e.preventDefault();
	})
})
