import { isElementInViewport } from "../../assets/js/helpers";

(() => {
    let initializedMap = false

    const AddScript = () => {
        const api_key = mm_ajax_object.google_map_api_key;
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${api_key}&callback=initMap`;
        script.async = true;
        document.head.appendChild(script);
    }

    const initializeBlock = () => {
        const addressSection = document.querySelector('[data-target="mm-address"]');

        if (addressSection) {

            const addressMap = addressSection.querySelector('[data-target="address_map"]');
            const mapElement = addressMap.querySelector('#address-map');

            window.addEventListener('scroll', (e) => {
                if (isElementInViewport(mapElement) && !initializedMap) {
                    AddScript()
                    initializedMap = true;
                }
            })

            const initMap = () => {
                const center = { lat: parseFloat(mapElement.dataset.lat), lng: parseFloat(mapElement.dataset.lng) };
                const map = new google.maps.Map(mapElement, {
                    mapId: 'c46718ce39aa3b46',
                    disableDefaultUI: true,
                    clickableIcons: false,
                    zoom: 15,
                    center: center,
                    zoomControl: true,
                    fullscreenControl: true
                });


                new google.maps.Marker({
                    position: center,
                    map: map,
                    icon: {
                        url: addressMap.getAttribute('data-marker'),
                        size: new google.maps.Size(48, 48),
                        scaledSize: new google.maps.Size(48, 48),
                    }
                });
            }
            window.initMap = initMap
        }
    }

    if (document.readyState === "interactive" || document.readyState === "complete") {
        if (!window.acf) {
            initializeBlock()
        }
        else {
            window.acf.addAction('render_block_preview/type=acf/address-block', initializeBlock)
        }
    }
    else {
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.acf) {
                initializeBlock()
            }
            else {
                window.acf.addAction('render_block_preview/type=acf/address-block', initializeBlock)
            }
        })
    }
})()