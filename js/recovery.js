$(function(){
    $('#change-password').on('submit', function(e){
        let formData = {};
        $.each($(this).serializeArray(), function(i, item){
            formData[item.name] = item.value;
        })
        if (formData.password_1 !== formData.password_2){
            e.preventDefault();
            return show_message('Пароли не совпадают', 'error');
        }
    })
})