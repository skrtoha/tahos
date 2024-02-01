$(function(){
    $.ajax({
        type: 'get',
        url: '/admin/ajax/texts.php',
        data: {},
        success: function(){
        }
    })
	  $.ionTabs("#tabs_1",{
		type: "hash",
		onChange: function(obj){
      console.log('changed')
			let str = '/admin/?view=texts&tab=' + obj.tab;
            const params = getParams();
            if (typeof params['act'] !== 'undefined') str += '&act=' + params['act'];
            if (typeof params['id'] !== 'undefined') str += '&id=' + params['id'];
            if (typeof params['parent_id'] !== 'undefined') str += '&parent_id=' + params['parent_id'];
			str += '#tabs|texts:' + obj.tab;
      $.ajax({
          type: 'get',
          url: str,
          success: function (response){
              $(`div[data-name="${obj.tab}"]`).html(response);
              window.history.pushState(null, null, str);
              tinymce.init(tinymceInitParams)
          }
      })
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
    $(document).on('dblclick', 'tr[data-href *= "text_rubric"]', function(e){
        const $th = $(this);
        document.location.href = `/admin/?view=texts&tab=2&act=text_rubric_change&id=${$th.data('id')}#tabs|texts:2`;
    })
    $(document).on('click', 'tr[data-href]', function (){
        document.location.href = $(this).data('href');
    })
    $(document).on('click', 'a.delete', function(e){
        if (!confirm('Действительно удалить')) e.preventDefault();
    })
    $(document).on('submit', 'div[data-name] form', function(e){
        e.preventDefault();
        const $th = $(this);
        let formData = {};
        $.each($th.serializeArray(), function(i, item){
            formData[item.name] = item.value;
        })
        $.ajax({
            type: 'post',
            url: document.location.href,
            data: formData,
            success: function (response){
                $(`div[data-name="${$th.closest('div[data-name]').data('name')}"]`).html(response);
                show_message('Успешно сохранено!');
            }
        })
    })
})
