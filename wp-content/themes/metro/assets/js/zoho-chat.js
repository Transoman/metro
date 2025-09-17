(() => {
    let zohoLoaded = false;

    const initiliazeScript = async () => {
        const inlineScript = document.createElement('script');
        inlineScript.id = 'zsiqchat'
        inlineScript.innerText = `var $zoho = $zoho || {};$zoho.salesiq = $zoho.salesiq || {widgetcode: "siq2d579520ef0e7c01170d72ca82851d584727961c1f41a526882d2e89686ce7dcc3f428d3b1aa763f6d500c1e98f90570",values: {},ready: function() {document.body.classList.add('zoho-chat');}};`;
        document.body.insertAdjacentElement('beforeend', inlineScript);

        const script = document.createElement('script');
        script.id = 'zsiqscript'
        script.defer = true
        script.src = 'https://salesiq.zohopublic.com/widget';
        const element = document.getElementsByTagName('script')[0];
        element.insertAdjacentElement('afterend', script);
        zohoLoaded = true;
    }

    const setEventsOnChat = () => {
        if (typeof $zoho !== 'undefined' && typeof window.dataLayer !== 'undefined') {
            $zoho.salesiq.ready = () => {
                const object = {
                    event: 'zoho_chat_completion'
                }
                $zoho.salesiq.chat.complete(function (visitid, data) {
                    window.dataLayer.push(object)
                    console.log('zoho sended')
                });

                $zoho.salesiq.visitor.offlineMessage(function (visitid, data) {
                    window.dataLayer.push(object);
                    console.log('zoho sended')
                });
            }
        }
    }

    const initializeBlock = () => {
        const events = ['scroll', 'mousemove'];

        events.forEach(event => {
            window.addEventListener(event, (e) => {
                if (!zohoLoaded) {
                    initiliazeScript().then(() => {
                        setEventsOnChat()
                    })
                }
            })
        })
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