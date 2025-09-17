import { SearchForm, addToFavourites, changeWindowHistory, Steps } from '../../assets/js/helpers';
import { MarkerClusterer } from "@googlemaps/markerclusterer";

(() => {
	const addMapScript = () => {
		const apiKey = mm_ajax_object.google_map_api_key;
		const mainScript = document.createElement('script');
		mainScript.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&callback=initiliazeMap&region=EG&language=en`;
		mainScript.async = true;
		document.head.appendChild(mainScript);
		const clusterScript = document.createElement('script');
		clusterScript.src = 'https://unpkg.com/@googlemaps/markerclusterer@latest';
		clusterScript.async = true;
		document.head.appendChild(clusterScript);
	}

	class SearchFunctionality extends SearchForm {
		#isMapInitialized = false;
		constructor(searchBar, searchSection) {
			super(searchBar);
			this.searchBar = searchBar;
			this.searchSection = searchSection;
			this.orderInput = this.searchBar.querySelector('input[name="order"]');
			this.orderField = this.searchSection.querySelector('[data-target="default_custom_select"]');
			this.pageInput = this.searchBar.querySelector('input[name="page"]');
			this.resultTitle = this.searchSection.querySelector('[data-target="result_string"]');
			this.closeMapButton = this.searchSection.querySelector('[data-target="close_map"]');
			this.mobileMapCard = this.searchSection.querySelector('[data-target="mobile_card"]');

			this.mapViewButton = this.searchSection.querySelector('[data-target="map_view"]');
			this.listViewButton = this.searchSection.querySelector('[data-target="list_view"]');
			this.pagination = this.searchSection.querySelector('[data-target="pagination_wrapper"]');
			this.resultWrapper = this.searchSection.querySelector('[data-target="ajax_wrapper"]');

			this.isUserAuthorized = this.searchSection.classList.contains('unauthorized');

			this.initiliazeSearchBar(this.searchBar);
			this.initiliazePagination(this.pagination);

			this.initiliazeOrderField(this.orderField, this.orderInput);
			this.initiliazeToggleButtons(this.mapViewButton, this.listViewButton, this.searchSection);

			this.setEventOnCloseMapButton(this.closeMapButton);

			this.mapElement = this.searchSection.querySelector('[data-target="google_map"]');
			window.initiliazeMap = () => {
				this.initiliazeMap()
			};
			this.jsonMarkers = [];
			this.markerCluster = null;
			this.mapMarkers = [];
			this.mapInfoWindows = [];
			this.mapInstance = null;
			this.isMapView = false;
			this.mobileBreakpoint = 767;
			this.defaultIcon = {
				url: this.mapElement.getAttribute('data-marker')
			}
			this.hoverIcon = {
				url: this.mapElement.getAttribute('data-active-marker'),
			}

			if (this.formFields) {
				this.formFields.forEach(formField => {
					this.behaviorOfPlaceholder(formField);
				})
			}

			if (this.isUserAuthorized) {
				this.setEventOnListingsSection(this.searchSection);
			}

		}

		initiliazeOrderField(orderField, orderInput) {
			if (!orderField || !orderInput) {
				return;
			}

			const placeholder = orderField.querySelector('.placeholder');
			if (placeholder) {
				placeholder.addEventListener('click', (e) => {
					if (!this.isUserAuthorized) {
						orderField.classList.toggle('active');
					}
					else {
						this.scrollToUnathorizedSection();
					}
				})
			}

			const options = orderField.querySelectorAll('li');
			if (options && placeholder) {
				options.forEach(option => {
					option.addEventListener('click', (e) => {
						placeholder.querySelector('span').innerText = option.innerText;
						orderField.classList.toggle('active');
						orderInput.value = option.getAttribute('data-value');
						this.setResults();
					})
				})
			}
		}

		scrollToUnathorizedSection() {
			const section = this.searchSection.querySelector('.unauthorized-section');
			if (section) {
				window.scroll({
					top: section.offsetTop,
					left: 0,
					behavior: 'smooth'
				})
			}
		}

		setEventOnListingsSection(wrapper) {
			if (!wrapper) {
				return
			}
			const sectionOverlay = wrapper.querySelector('.section-overlay');
			if (sectionOverlay) {
				sectionOverlay.addEventListener('click', (e) => {
					this.scrollToUnathorizedSection();
				})
			}
		}

		setEventOnCloseMapButton(button) {
			if (!button) {
				return
			}

			button.addEventListener('click', (e) => {
				this.resetMap();
				this.toggleMap();
			})
		}

		toggleMap() {
			if (!this.mapViewButton || !this.listViewButton || !this.searchSection) {
				return;
			}
			this.searchSection.classList.toggle('with-map');
			this.mapViewButton.classList.toggle('active');
			this.listViewButton.classList.toggle('active');
			this.isMapView = !this.isMapView;
			if (this.mapMarkers) {
				this.mapMarkers.forEach(marker => {
					marker.setIcon(this.defaultIcon)
				})
			}
			if (this.mapInfoWindows) {
				this.mapInfoWindows.forEach(infoWindow => {
					infoWindow.close();
				})
			}
		}

		initiliazeToggleButtons(mapViewButton, listViewButton, searchSection) {
			if (!mapViewButton || !listViewButton || !searchSection) {
				return;
			}

			mapViewButton.addEventListener('click', (e) => {
				if (!this.isUserAuthorized) {
					this.resetMap();
					this.toggleMap();
					if (!this.#isMapInitialized) {
						addMapScript(this.initiliazeMap);
						this.#isMapInitialized = true;
					}
					else {
						this.mapStartPoint();
					}
				}
				else {
					this.scrollToUnathorizedSection();
				}

			});

			listViewButton.addEventListener('click', (e) => {
				if (!this.isUserAuthorized) {
					this.resetMap();
					this.toggleMap();
				}
				else {
					this.scrollToUnathorizedSection();
				}
				this.setResults();

			});
		}

		initiliazeSearchBar(searchBar) {
			if (!searchBar) {
				return;
			}

			const form = searchBar.querySelector('form');
			if (form) {
				form.addEventListener('submit', (e) => {
					e.preventDefault();
					if (!this.isUserAuthorized) {
						this.setResults();
						if (window.innerWidth < this.mobileBreakpoint) {
							this.searchBar.classList.remove('active');
							document.body.classList.remove('freeze');
							document.body.classList.remove('overlay');
						}
					}
					else {
						this.scrollToUnathorizedSection();
					}
				});
			}
		}

		initiliazePagination(pagination) {
			if (!pagination) {
				return;
			}

			const prevButton = pagination.querySelector('[data-target="pagination_prev"]');
			const nextButton = pagination.querySelector('[data-target="pagination_next"]');

			const paginationLinks = pagination.querySelectorAll('ul li');

			if (prevButton && nextButton) {
				prevButton.addEventListener('click', (e) => {
					e.preventDefault();
					this.pageInput.value = parseInt(this.pageInput.value) - 1;
					changeWindowHistory(prevButton);
					window.scrollTo({ top: 0, left: 0, behavior: 'smooth' });
					if (this.isMapView) {
						this.searchSection.querySelector('.listings').scrollTo({ top: 0, left: 0, behavior: 'smooth' })
					}
					this.setResults();
				})
				nextButton.addEventListener('click', (e) => {
					e.preventDefault();
					this.pageInput.value = parseInt(this.pageInput.value) + 1;
					changeWindowHistory(nextButton);
					window.scrollTo({ top: 0, left: 0, behavior: 'smooth' });
					if (this.isMapView) {
						this.searchSection.querySelector('.listings').scrollTo({ top: 0, left: 0, behavior: 'smooth' })
					}
					this.setResults();
				})
			}

			if (paginationLinks) {
				paginationLinks.forEach(paginationLink => {
					const link = paginationLink.querySelector('a');
					if (paginationLink.classList.contains('current-page') && paginationLink.previousElementSibling) {
						paginationLink.previousElementSibling.classList.add('previous');
					}
					if (link) {
						link.addEventListener('click', (e) => {
							e.preventDefault();
							const newPage = e.target.innerText;
							this.pageInput.value = newPage;
							changeWindowHistory(link);
							window.scrollTo({ top: 0, left: 0, behavior: 'smooth' });
							if (this.isMapView) {
								this.searchSection.querySelector('.listings').scrollTo({ top: 0, left: 0, behavior: 'smooth' })
							}
							this.setResults();
						})
					}
				})
			}
		}

		initiliazeMap() {
			if (this.mapElement) {
				this.mapInstance = new google.maps.Map(this.mapElement, {
					mapId: 'c46718ce39aa3b46',
					zoom: 15,
					center: { lat: 40.73354169558694, lng: -73.99224655000002 },
					disableDefaultUI: true,
					clickableIcons: false,
					zoomControl: true,
				});
				this.mapStartPoint();
			}
		}

		resetMap() {
			if (!this.mapInstance) {
				return;
			}
			this.jsonMarkers = [];
			this.mapMarkers.forEach(marker => marker.setMap(null));
			this.mapMarkers = [];
			this.mapInfoWindows = [];
			if (this.markerCluster) {
				this.markerCluster.clearMarkers();
			}
		}

		setMarkersOnMap() {
			if (!this.jsonMarkers) {
				return;
			}
			const splitedMarkers = this.splitMarkers(this.jsonMarkers);
			if (splitedMarkers.uniqueObjects.length > 0) {
				splitedMarkers.uniqueObjects.forEach(marker => {
					if (marker.id !== '' && marker.lat !== '' && marker.lng !== '') {
						this.addMarker(marker.id, { lat: parseFloat(marker.lat), lng: parseFloat(marker.lng) });
					}
				})
			}
			if (splitedMarkers.repeatableGroups.length > 0) {
				splitedMarkers.repeatableGroups.forEach(groupMarkers => {
					const position = { lat: parseFloat(groupMarkers[0].lat), lng: parseFloat(groupMarkers[0].lng) }
					this.addMarker(groupMarkers.map(item => item.id), position, groupMarkers.length);
				})
			}
			if (this.mapMarkers.length > 0) {
				this.mapMarkers.forEach(marker => marker.setMap(this.mapInstance));
			}
		}

		splitMarkers(markers) {
			if (!markers) {
				return;
			}
			const frequencyMap = {};
			const repeatableGroups = [];
			const uniqueObjects = [];

			markers.forEach((element) => {
				const key = ['lat', 'lng'].map(property => element[property]).join(',');

				if (frequencyMap[key]) {
					frequencyMap[key].count++;
					frequencyMap[key].objects.push(element);
				} else {
					frequencyMap[key] = { count: 1, objects: [element] };
				}
			});

			for (const key in frequencyMap) {
				if (frequencyMap[key].count > 1) {
					repeatableGroups.push(frequencyMap[key].objects);
				} else {
					uniqueObjects.push(frequencyMap[key].objects[0]);
				}
			}

			return { repeatableGroups, uniqueObjects };
		}

		addMarker(listingID, { lat, lng }, length = 0) {
			if (!listingID || !lat || !lng) {
				return;
			}
			let icon = this.defaultIcon;
			let position = { lat, lng };
			let map = this.mapInstance
			icon.size = new google.maps.Size(48, 48);
			icon.scaledSize = new google.maps.Size(48, 48);
			icon.anchor = new google.maps.Point(24, 16)
			icon.labelOrigin = new google.maps.Point(24, 20);
			let options = {
				position,
				map,
				icon: icon,
			}
			if (length !== 0) {
				options.label = {
					text: length.toString(),
					color: "#FFF",
				}
			}
			if (!isNaN(position.lat) && !isNaN(position.lng)) {
				const marker = new google.maps.Marker(options);
				this.mapMarkers.push(marker);
				const infoWindow = this.addInfoWindow(position);
				this.setEventsForMarkers(marker, listingID, infoWindow);
			}
		}

		addInfoWindow(position) {
			if (!position) {
				return;
			}
			const windowInfo = new google.maps.InfoWindow({
				ariaLabel: position,
			})
			this.mapInfoWindows.push(windowInfo)
			return windowInfo
		}

		setCenterOfMap() {
			if (!this.mapMarkers) {
				return;
			}
			const bounds = new google.maps.LatLngBounds();
			this.mapMarkers.forEach(marker => {
				bounds.extend({ lat: marker.getPosition().lat(), lng: marker.getPosition().lng() })
			});
			this.mapInstance.fitBounds(bounds);
		}

		mapStartPoint() {
			if (!this.mapInstance) {
				return;
			}
			if (window.innerWidth > this.mobileBreakpoint) {
				this.fetchResult(16)
					.then(response => response.json())
					.then(json => {
						if (json.cards) {
							this.resultWrapper.innerHTML = json.cards;
							this.setAddToFavorites(this.resultWrapper);
						}
						else {
							this.resultWrapper.innerHTML = '<span class="no-results">Oops! We couldn\'t find any results matching your search. Let\'s try expanding your search criteria for better results.</span>'
						}
						if (json.pagination) {
							this.pagination.innerHTML = json.pagination;
							this.initiliazePagination(this.pagination);
							this.pagination.classList.remove('hide');
						}
						else {
							this.pagination.classList.add('hide');
						}
						if (json.coordinates) {
							this.searchSection.classList.remove('fetching');
							this.jsonMarkers = json.coordinates;
							this.setMarkersOnMap();
							this.setCenterOfMap();
						}
						if (json.filters) {
							this.resultTitle.innerText = json.filters;
						}
						if (json.coordinates) {
							this.mapElement.setAttribute('data-info', JSON.stringify(json.coordinates));
						}

					})
					.catch(err => {
						console.error(err);
						this.searchSection.classList.remove('fetching');
					})

			}
			else {
				this.fetchResult('-1')
					.then(response => response.json())
					.then(json => {
						if (json.coordinates) {
							this.searchSection.classList.remove('fetching');
							this.jsonMarkers = json.coordinates;
							this.setMarkersOnMap();
							this.addMarkerClustering();
							this.setCenterOfMap();
						}
					})
					.catch(err => {
						console.error(err);
						this.searchSection.classList.remove('fetching');
					})
			}
		}

		addMarkerClustering() {
			this.markerCluster = new MarkerClusterer({
				map: this.mapInstance,
				markers: this.mapMarkers,
				renderer: {
					render: ({ markers, _position: position }) => {
						let realLength = 0;
						markers.forEach(marker => {
							if (marker.label && marker.label.text) {
								realLength += parseInt(marker.label.text);
							}
							else {
								realLength++;
							}
						})
						return new google.maps.Marker({
							position: {
								lat: position.lat(),
								lng: position.lng(),
							},
							label: {
								text: String(realLength) + ' listings',
								color: "white",
							},
							icon: this.mapElement.getAttribute('data-clustering-marker'),
						});
					},
				},
			});
		}

		setAddToFavorites(wrapper) {
			if (!wrapper) {
				return;
			}

			const listings = wrapper.querySelectorAll('[data-target="custom_post"]');
			if (listings) {
				listings.forEach(listing => {
					const addToFavouriteButton = listing.querySelector('[data-target="add_to_favourites"]');
					const listingID = listing.getAttribute('data-id');
					if (addToFavouriteButton) {
						addToFavouriteButton.addEventListener('click', (e) => {
							e.preventDefault();
							e.stopPropagation();
							addToFavourites(listingID).then(() => {
								addToFavouriteButton.classList.toggle('liked');
							})
						})
					}
				})
			}
		}

		setEventsForMarkers(marker, listingID, infoWindow) {
			if (!marker || !listingID || !infoWindow) {
				return;
			}
			const map = this.mapInstance
			const events = ['drag', 'dragend', 'dragstart', 'click', 'zoom_changed'];
			events.forEach(event => {
				map.addListener(event, (e) => {
					marker.setIcon(this.defaultIcon);
					infoWindow.close();
					if (window.innerWidth < this.mobileBreakpoint) {
						this.mobileMapCard.classList.remove('show');
					}
				})
			})
			marker.addListener('click', (e) => {
				if (this.mapMarkers) {
					this.mapMarkers.forEach(marker => marker.setIcon(this.defaultIcon));
				}
				if (this.mapInfoWindows) {
					this.mapInfoWindows.forEach(infoWindow => infoWindow.close());
				}
				this.fetchListingMapCard(listingID)
					.then(response => response.json())
					.then(response => {
						const html = this.buildListingCard(response);
						this.setAddToFavorites(html);
						if (window.innerWidth > this.mobileBreakpoint) {
							infoWindow.setContent(html);
							infoWindow.open({
								anchor: marker,
								map
							})
						}
						else {
							this.mobileMapCard.innerHTML = '';
							this.mobileMapCard.insertAdjacentElement('afterbegin', html);
							this.mobileMapCard.classList.add('show');
						}
					})
					.catch(err => {
						console.error(err);
					})
			});
			window.addEventListener('resize', () => {
				marker.setIcon(this.defaultIcon);
				infoWindow.close();
				if (window.innerWidth < this.mobileBreakpoint) {
					this.mobileMapCard.classList.remove('show');
				}
			});
			this.setHoverEventOnListings(listingID, marker);
		}

		setHoverEventOnListings(listingID, marker) {
			const events = ['mouseover', 'mouseout', 'mouseenter', 'mouseleave'];
			const eventHandler = (event) => {
				if (event.type === 'mouseenter' || event.type === 'mouseover') {
					this.changeColorOfMarker(marker, true);
				}
				else {
					this.changeColorOfMarker(marker, false);
				}
			}
			if (listingID.length) {
				listingID.forEach(listing => {
					const listingCard = this.resultWrapper.querySelector(`[data-target="custom_post"][data-id="${listing}"]`);
					if (listingCard) {
						events.forEach(event => {
							listingCard.addEventListener(event, eventHandler);
						})
					}
				})
			}
			else {
				const listingCard = this.resultWrapper.querySelector(`[data-target="custom_post"][data-id="${listingID}"]`);
				if (listingCard) {
					events.forEach(event => {
						listingCard.addEventListener(event, eventHandler)
					})
				}
			}
		}

		changeColorOfMarker(marker, hover = false) {
			let icon = (hover) ? this.hoverIcon : this.defaultIcon;
			icon.size = new google.maps.Size(48, 48);
			icon.scaledSize = new google.maps.Size(48, 48);
			icon.anchor = new google.maps.Point(24, 16)
			icon.labelOrigin = new google.maps.Point(24, 20)
			marker.setIcon(icon);
		}

		buildListingCard(json) {
			const wrapper = document.createElement('div');
			wrapper.className = (json.several) ? 'cards-wrapper several' : 'cards-wrapper';
			wrapper.innerHTML = json.card;
			return wrapper;
		}

		fetchListingMapCard(listingID) {
			if (!listingID) {
				return;
			}

			const data = new FormData();
			data.append('action', 'get_map_listing_card');
			data.append('listing_id', listingID);

			return fetch(mm_ajax_object.ajaxURL, {
				method: "POST",
				credentials: "same-origin",
				body: data
			})
		}

		setResults() {
			let numberposts = (this.isMapView && window.innerWidth > this.mobileBreakpoint) ? 16 : 15;
			this.fetchResult(numberposts)
				.then(response => response.json())
				.then(json => {
					this.searchSection.classList.remove('fetching');
					if (json.cards) {
						this.resultWrapper.innerHTML = json.cards;
						this.setAddToFavorites(this.resultWrapper);
					}
					else {
						this.resultWrapper.innerHTML = '<span class="no-results">Oops! We couldn\'t find any results matching your search. Let\'s try expanding your search criteria for better results.</span>'
					}
					if (json.pagination) {
						this.pagination.innerHTML = json.pagination;
						this.initiliazePagination(this.pagination);
						this.pagination.classList.remove('hide');
					}
					else {
						this.pagination.classList.add('hide');
					}
					if (json.paginationLinkTag && json.paginationLinkTag.prev) {
						const parser = new DOMParser();
						const doc = parser.parseFromString(json.paginationLinkTag.prev, "text/html");
						const newPrevLinkTag = doc.querySelector("link[rel='prev']");
						const prevLinkTag = document.querySelector('link[rel="prev"]');

						if (prevLinkTag) {
							prevLinkTag.replaceWith(newPrevLinkTag);
						}
						else {
							document.head.appendChild(newPrevLinkTag);
						}
					} else {
						document.querySelector('link[rel="prev"]').remove();
					}
					if (json.paginationLinkTag && json.paginationLinkTag.next) {
						const parser = new DOMParser();
						const doc = parser.parseFromString(json.paginationLinkTag.next, "text/html");
						const newNextLinkTag = doc.querySelector("link[rel='next']");
						const nextLinkTag = document.querySelector('link[rel="next"]');

						if (nextLinkTag) {
							nextLinkTag.replaceWith(newNextLinkTag);
						}
						else {
							document.head.appendChild(newNextLinkTag);
						}
					} else {
						document.querySelector('link[rel="next"]').remove();
					}
					if (json.filters) {
						this.resultTitle.innerText = json.filters;
					}
					if (json.coordinates) {
						this.mapElement.setAttribute('data-info', JSON.stringify(json.coordinates));
					}
					if (this.isMapView && json.coordinates) {
						this.resetMap();
						this.jsonMarkers = json.coordinates;
						this.setMarkersOnMap();
						this.setCenterOfMap();
					}
				})
				.catch(err => {
					this.searchSection.classList.remove('fetching');
					console.error(err);
				});
		}

		fetchResult(numberposts = 15) {
			const form = this.searchBar.querySelector('form');
			let data = new FormData(form);
			if (numberposts !== 15) {
				data.delete('numberposts');
				data.append('numberposts', numberposts);
			}

			this.searchSection.classList.add('fetching');

			return fetch(mm_ajax_object.ajaxURL, {
				method: "POST",
				credentials: "same-origin",
				body: data,
			});
		}

	}

	const initiliazeBlock = () => {
		const searchBar = document.querySelector('[data-target="header_search_bar_search_page"]');
		const searchSection = document.querySelector('[data-target="search_form_section"]');

		if (searchBar && searchSection) {
			new SearchFunctionality(searchBar, searchSection);
		}

	}

	if (document.readyState === "interactive" || document.readyState === "complete") {
		if (!window.acf) {
			initiliazeBlock()
		}
		else {
			window.acf.addAction('render_block_preview=acf/search-results-section', initiliazeBlock);
		}
	}
	else {
		document.addEventListener('DOMContentLoaded', () => {
			if (!window.acf) {
				initiliazeBlock()
			}
			else {
				window.acf.addAction('render_block_preview=acf/search-results-section', initiliazeBlock);
			}
		})
	}
})()