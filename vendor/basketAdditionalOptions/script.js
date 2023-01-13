function showAdditionalOptions(){
    $.magnificPopup.open({
        items: {
            src: '#additional_options'
        }
    });
}
function eventClickToOrder(e){
    var noReturn = [];
    if ($(document).width() >= 880){
        $('table.basket-table input[name=toOrder]:checked').each(function () {
            const th = $(this).closest('tr');
            if (!th.find('.noReturn').size()) return 1;
            noReturn.push({
                brend: th.find('b.brend_info').html(),
                article: th.find('a.articul').html(),
                title: th.find('span.title').html()
            });
        });
    }
    else{
        $('div.mobile-view input[name=toOrder]:checked').each(function () {
            const th = $(this).closest('div.good');
            if (!th.find('.noReturn').size()) return 1;
            noReturn.push({
                brend: th.find('b.brend_info').html(),
                article: th.find('a.articul').html(),
                title: th.find('p.title').html()
            });
        });
    }
    if (noReturn.length){
        $.magnificPopup.open({
            type: 'inline',
            preloader: false,
            mainClass: 'product-popup-wrap',
            callbacks: {
                open: function(){
                    var str =
                        '<thead>' +
                        '<tr>' +
                        '<th>Бренд</th>' +
                        '<th>Артикул</th>' +
                        '<th>Название</th>' +
                        '</tr>' +
                        '</thead>' +
                        '<tbody>'
                    ;
                    for(var k in noReturn){
                        str +=
                            '<tr>' +
                            '<td>' + noReturn[k].brend + '</td>' +
                            '<td>' + noReturn[k].article + '</td>' +
                            '<td>' + noReturn[k].title + '</td>' +
                            '</tr>';
                    }
                    str += '</tbody>';
                    $('#mgn_popup table.basket-table').html(str);
                }
            },
            items: {
                src: '#mgn_popup'
            }
        });
        return false;
    }
    else e.preventDefault();
    if (!noReturn.length) showAdditionalOptions();
}
$(function(){
    $('input[type=checkbox], input[type=radio], select').styler();
})