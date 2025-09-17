(() => {
	let initializedMap = false;

	const addMapScript = () => {
		const api_key = mm_ajax_object.google_map_api_key;
		const script = document.createElement('script');
		script.src = `https://maps.googleapis.com/maps/api/js?key=${api_key}&libraries=places&callback=initMap&region=EG&language=en`;
		script.async = true;
		document.head.appendChild(script);
		initializedMap = true;
	}
	const initializeBlock = (section) => {
		let map;
		if (section) {
			const form = section.querySelector('[data-target="form"]');
			const resultForm = section.querySelector('[data-target="result_form"]');
			const resultMap = section.querySelector('[data-target="result_map"]');
			const mapElement = resultMap.querySelector('#result-map');
			const message = section.querySelector('[data-target="error_message"]');
			const closeButtonMessage = message.querySelector('button');
			let markers = [];
			let autocompletes = [];
			let directionsService,
				directionsDisplay;

			const button = form.querySelector('button'),
				homeInput = form.querySelector('#home'),
				workInput = form.querySelector('#work'),
				center = { lat: 40.7504864, lng: -74.0014762 };
			const inputs = [homeInput, workInput];


			closeButtonMessage.addEventListener('click', (e) => {
				message.classList.remove('show');
			})

			const defaultBounds = {
				north: center.lat + 0.1,
				south: center.lat - 0.1,
				east: center.lng + 0.1,
				west: center.lng - 0.1,
			};

			const initMap = () => {
				directionsService = new google.maps.DirectionsService,
					directionsDisplay = new google.maps.DirectionsRenderer;
				map = new google.maps.Map(mapElement, {
					mapId: 'c46718ce39aa3b46',
					disableDefaultUI: true,
					clickableIcons: false,
					zoom: 13,
					center: center
				});
				inputHandler(inputs);
			}

			button.addEventListener('click', (e) => {
				e.preventDefault();
				if (homeInput.value == '') {
					showError(homeInput, 'Please enter your home address');
				}
				if (workInput.value == '') {
					showError(workInput, 'Please enter your work address');
				}
				if (!directionsDisplay) {
					showError(workInput, 'Enter the point');
					return
				}
				directionsDisplay.setMap(map);
				if (validationCalculator()) {
					calculateAndDisplayRoute(directionsService, directionsDisplay, homeInput, workInput)
				}
			})

			inputs.forEach(input => {
				input.addEventListener('focus', (e) => {
					if (!initializedMap) {
						addMapScript()
					}
				});
			})

			const validationCalculator = () => {
				let result = true;
				autocompletes.forEach(item => {
					if (item.field.value === '') {
						showError(item.field, '');
						result = false;
					}
					else {
						hideError(item.field);
					}
				})
				return result;
			}

			const inputHandler = (inputs) => {
				if (inputs) {
					inputs.forEach(input => {
						checkInput(input);
						const options = {
							bounds: defaultBounds,
							componentRestrictions: { country: "us" },
							fields: ["address_components", "geometry", "place_id", "name"],
							strictBounds: false,
						};
						const autocomplete = new google.maps.places.Autocomplete(input, options);
						autocompletes.push({ field: input, instance: autocomplete });
					})
				}
			}

			window.initMap = initMap

			const calculateAndDisplayRoute = (directionsService, directionsDisplay, homeInput, workInput) => {
				const carInput = resultForm.querySelector('#car'),
					trainInput = resultForm.querySelector('#train'),
					footInput = resultForm.querySelector('#foot'),
					bikeInput = resultForm.querySelector('#bike');

				const storage = [
					{
						mode: "DRIVING",
						output: carInput
					},
					{
						mode: "TRANSIT",
						output: trainInput
					},
					{
						mode: "WALKING",
						output: footInput
					},
					{
						mode: "BICYCLING",
						output: bikeInput
					}
				];

				storage.forEach(item => {
					directionsService.route({
						origin: homeInput.value,
						destination: workInput.value,
						travelMode: item.mode
					},
						function (response, status) {
							if (status === 'OK') {
								if (item.mode === 'WALKING') {
									directionsDisplay.setOptions({ suppressMarkers: true });
									directionsDisplay.setDirections(response);
								}
								resultMap.classList.add('show');
								const route = response.routes[0].legs[0];
								if (item.mode === 'WALKING') {
									setMapOnAll(null)
									createMarker(route.start_location, true);
									createMarker(route.end_location, false);
								}
								item.output.value = Math.round(response.routes[0].legs[0].duration.value / 60)
								window.scrollTo({ top: resultMap.offsetTop + resultMap.offsetHeight, left: 0, behavior: 'smooth' });
							}
							else {
								window.scrollTo({
									top: message.offsetTop - MMHeader.offsetHeight,
									left: 0,
									behavior: 'smooth'
								})
								message.classList.add('show')
							}
						});
				})
			}

			const checkInput = (input) => {
				input.addEventListener('input', (e) => {
					if (input.value !== '') {
						hideError(input)
					}
				})
			}

			const showError = (input, message) => {
				if (!input.classList.contains('invalid')) {
					input.classList.add('invalid')
					input.insertAdjacentHTML('afterend', `<span class="error">${message}</span>`)
				}
			}

			const hideError = (input) => {
				input.classList.remove('invalid');
				if (input.nextElementSibling && input.nextElementSibling.className == 'error') {
					input.nextElementSibling.remove();
				}
			}

			const setMapOnAll = (map) => {
				for (let i = 0; i < markers.length; i++) {
					markers[i].setMap(map);
				}
			}

			const createMarker = (position, boolean) => {
				let options = {
					position: position,
					map: map,
				}
				if (boolean) {
					options.icon = '/wp-content/uploads/2023/04/Marker-Home.png'
				} else {
					options.icon = '/wp-content/uploads/2023/04/Marker-Office.png'
				}
				const marker = new google.maps.Marker(options);
				markers.push(marker);
			}
		}
	}

	if (document.readyState === "interactive" || document.readyState === "complete") {
		const commuteCalculatorSection = document.querySelector('[data-target="commute_calculator"]');
		if (!window.acf) {
			if (commuteCalculatorSection) {
				initializeBlock(commuteCalculatorSection);
			}
		}
	}
	else {
		document.addEventListener('DOMContentLoaded', () => {
			const commuteCalculatorSection = document.querySelector('[data-target="commute_calculator"]');
			if (!window.acf) {
				if (commuteCalculatorSection) {
					initializeBlock(commuteCalculatorSection);
				}
			}
		})
	}
})()
