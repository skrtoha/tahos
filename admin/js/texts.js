$(function(){
	$.ionTabs("#tabs_1",{
		type: "hash",
		onChange: function(obj){
			let str = '/admin/?view=texts&tab=' + obj.tab;
            const params = getParams();
            if (typeof params['act'] !== 'undefined') str += '&act=' + params['act'];
            if (typeof params['id'] !== 'undefined') str += '&id=' + params['id'];
			str += '#tabs|texts:' + obj.tab;
			window.history.pushState(null, null, str);
		}
	})
    $(document).on('dblclick', '.ionTabs__tab', function(e){
        let formData = {};
        const $th = $(this);
        formData.title = prompt('Введите новое название:', $th.html());
        if (formData.title.length == 0) return show_message('Название слишком короткое!', 'error');
        formData.number = $th.attr('data-target');
        formData.act = 'changeColumn';
        $.ajax({
            type: 'get',
            url: '/admin/ajax/texts.php',
            data: formData,
            success: function(){
                $th.html(formData.title);
            }
        })
    })
    $('tr[data-href]').on('click', function (){
        document.location.href = $(this).data('href');
    })
    $('a.delete').on('click', function(e){
        if (!confirm('Действительно удалить')) e.preventDefault();
    })
})
