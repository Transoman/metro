(() => {
    const initializeBlock = () => {
        let initializedMap = false
        const mapElements = document.querySelectorAll('[data-target="polygon_map"]');

        const addMapScript = () => {
            if (typeof mm_ajax_object !== 'undefined' && typeof google !== 'object') {
                const api_key = mm_ajax_object.google_map_api_key;
                if (!initializedMap) {
                    const script = document.createElement('script');
                    script.src = `https://maps.googleapis.com/maps/api/js?key=${api_key}&callback=initMap&region=EG&language=en`;
                    script.async = true;
                    document.head.appendChild(script);
                    initializedMap = true;
                }
            }
        }

        const normalizeCoordinates = (coordinates) => {
            let output = [];
            coordinates.forEach(coordinate => {
                output.push({ lat: parseFloat(coordinate.lat), lng: parseFloat(coordinate.lng) })
            })
            return output
        }

        const initMap = () => {
            mapElements.forEach(mapElement => {
                let coordinates = mapElement.getAttribute('data-coordinates');

                coordinates = JSON.parse(coordinates);
                coordinates = normalizeCoordinates(coordinates)

                let map = new google.maps.Map(mapElement, {
                    mapId: 'c46718ce39aa3b46',
                    zoom: 15,
                    center: { lat: -34.397, lng: 150.644 },
                    disableDefaultUI: true,
                    clickableIcons: false,
                });

                let polygon = new google.maps.Polygon({
                    paths: coordinates,
                    strokeColor: "#023a6c",
                    strokeWeight: 2,
                    fillColor: "#023a6c",
                    fillOpacity: 0.35,
                })

                polygon.setMap(map);


                const bounds = new google.maps.LatLngBounds();

                coordinates.forEach(coordinate => {
                    bounds.extend({ lat: coordinate.lat, lng: coordinate.lng })
                })

                map.fitBounds(bounds)

            })

        }
        window.initMap = initMap
        if (mapElements) {
            addMapScript()
        }
    }

    if (document.readyState === "interactive" || document.readyState === "complete") {
        initializeBlock()
    }
    else {
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.acf) {
                initializeBlock()
            }
        })
    }
})()