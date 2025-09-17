(() => {
    let captchaLoaded = false;

    const initiliazeScript = () => {
        let head = document.getElementsByTagName('head')[0];
        let recaptchaScript = document.createElement('script');
        let cf7script = document.createElement('script');

        let recaptchaKey = mm_ajax_object.wpcf7_sitekey;

        if (!recaptchaKey) {
            return
        }

        recaptchaScript.src = 'https://www.google.com/recaptcha/api.js?render=' + recaptchaKey + '&ver=3.0';

        cf7script.text = `(()=>{var e;wpcf7_recaptcha={...null!==(e=wpcf7_recaptcha)&&void 0!==e?e:{}};const c=wpcf7_recaptcha.sitekey,{homepage:n,contactform:a}=wpcf7_recaptcha.actions,o=t=>{const{action:e,func:n,params:a}=t;grecaptcha.execute(c,{action:e}).then((t=>{const c=new CustomEvent("wpcf7grecaptchaexecuted",{detail:{action:e,token:t}});document.dispatchEvent(c)})).then((()=>{"function"==typeof n&&n(...a)})).catch((t=>console.error(t)))};if(grecaptcha.ready((()=>{o({action:n})})),document.addEventListener("change",(t=>{o({action:a})})),"undefined"!=typeof wpcf7&&"function"==typeof wpcf7.submit){const t=wpcf7.submit;wpcf7.submit=function(e){let c=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{};o({action:a,func:t,params:[e,c]})}}document.addEventListener("wpcf7grecaptchaexecuted",(t=>{const e=document.querySelectorAll('form.wpcf7-form input[name="_wpcf7_recaptcha_response"]');for(let c=0;c<e.length;c++)e[c].setAttribute("value",t.detail.token)}))})()`;

        head.appendChild(recaptchaScript);

        function insertValidateRecaptcha() {
            if (typeof grecaptcha === "undefined") {
                setTimeout(insertValidateRecaptcha, 200);
            }
            else {
                head.appendChild(cf7script);
            }
        }

        setTimeout(insertValidateRecaptcha, 200);

        captchaLoaded = true;
    }

    const initializeBlock = () => {
        const formInputs = document.querySelectorAll('.wpcf7-form [name]');
        if (formInputs) {
            formInputs.forEach(formInput => {
                formInput.addEventListener('focus', (e) => {
                    if (captchaLoaded) {
                        return;
                    }
                    initiliazeScript();
                })
            })
        }
    }

    if (document.readyState === "interactive" || document.readyState === "complete") {
        initializeBlock()
    }
    else {
        document.addEventListener('DOMContentLoaded', () => {
            initializeBlock()
        })
    }

})()