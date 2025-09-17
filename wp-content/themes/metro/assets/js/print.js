import QRCode  from "qrcode";
(() => {

    const generateQRcode = (element) => {
        let opts = {
            errorCorrectionLevel: 'H',
            type: 'image/jpeg',
            quality: 0.3,
            margin: 1,
            color: {
                dark: "#000",
                light: "#fff"
            }
        }

        QRCode.toDataURL(window.location.origin + window.location.pathname, opts, function (err, url) {
            if (err) throw err
            element.querySelector('img').src = url;
        })
    }

    document.addEventListener('DOMContentLoaded', () => {
        const qrcodeElement = document.querySelector('.qrcode');
        if (qrcodeElement) {
            generateQRcode(qrcodeElement);
        }
    })
})()