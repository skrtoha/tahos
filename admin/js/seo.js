// 'use strict'

class Seo {
    constructor () {}
    init() {
        const trElementList = document.querySelectorAll('table.t_table tr');
        if (trElementList) {
            trElementList.forEach(trElement => {
                trElement.addEventListener('click', e => {
                    document.location.href = `/admin/?view=seo&act=edit&id=${e.target.closest('tr').dataset.id}`;
                })
            })
        }

        const type_content = document.querySelector('select[name="type_content"]');
        if (type_content) {
            type_content.addEventListener('change', e => {
                let href = '/admin/?';
                const get = getParams();

                if (typeof get.type_content === 'undefined') {
                    document.location.href = document.location.href + `&type_content=${e.target.value}`
                }
                else {
                    Object.keys(get).forEach(key => {
                        switch (key) {
                            case 'type_content':
                                href += `${key}=${e.target.value}&`
                                break;
                            default:
                                href += `${key}=${get[key]}&`;
                        }
                    })
                    href = href.slice(0, -1);
                    document.location.href = href;
                }
            })
        }
    }
}

if (document.readyState !== 'loading') {
    (new Seo()).init();
}
else {
    document.addEventListener('DOMContentLoaded', (new Seo()).init);
}