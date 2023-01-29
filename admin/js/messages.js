$(function(){
    $('table.spare_parts_request tr').on('click', (e) => {
        document.location.href = '/admin/?view=messages&act=spare_parts_request&id=' + $(e.target).closest('tr').data('id');
    })
})