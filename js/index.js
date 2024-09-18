class Index {
    constructor() {}
    static yandexCaptchaActivated = false;
    init() {
        const _self = this;
        $('div.selection.spare_parts_request > div.left > form').on('submit', (e) => {
            e.preventDefault();
            document.location.href = "/original-catalogs/legkovie-avtomobili#/carInfo?q=" + $('input[name=q]').val();
        })

        document.querySelector('#spare_parts_request').addEventListener('submit', e => {
            const captchaInput = document.querySelector('input[name="smart-token"]')
            if (!captchaInput.value) {
                e.preventDefault()
            }
        })

        document.addEventListener('captchaSuccessed_spare_parts_request', e => {
            $('#spare_parts_request input[type=submit]').prop('disabled', false)
        })

        const sparePartsRequestForm = _self.getSparePartsRequestForm();
        sparePartsRequestForm.querySelectorAll('input, textarea').forEach(element => {
            element.addEventListener('keyup', (e) => {
                _self.checkSparePartsForm();
            });
            element.addEventListener('input', (e) => {
                _self.checkSparePartsForm();
            })
        })
    }

    getSparePartsRequestForm() {
        return document.querySelector('#spare_parts_request');
    }

    setYandexCaptcha() {
        const _self = this;
        const script = document.createElement('script');
        script.src = "https://captcha-api.yandex.ru/captcha.js?render=onload&onload=yaChecker"
        document.head.append(script)

        script.onload = () => {
            const widgetId = window.smartCaptcha.render('spare-parts-request-captcha', {
                sitekey: window.captcha_sitekey,
                invisible: false
            })

            Index.yandexCaptchaActivated = true;

            window.smartCaptcha.subscribe(widgetId, 'success', () => {
                const form = _self.getSparePartsRequestForm();
                form.querySelector('input[type="submit"]').disabled = false;
            })
        }
    }

    checkSparePartsForm() {
        const _self = this;
        let disabled = false;
        const inputNameList = ['vin', 'phone', 'name', 'description'];
        const form = _self.getSparePartsRequestForm();

        inputNameList.forEach(name => {
            if (!!!form.querySelector(`[name="${name}"]`).value){
                disabled = true;
            }
        })

        if (!disabled && !Index.yandexCaptchaActivated) {
            _self.setYandexCaptcha();
        }
    }
}
$(function(){
   new Index().init();
});