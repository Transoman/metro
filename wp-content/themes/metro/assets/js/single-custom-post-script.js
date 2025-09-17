import Swiper from "swiper";
import { Navigation, Thumbs } from "swiper/modules";
import { addToFavourites, isElementInViewport } from "../../assets/js/helpers";
import flatpickr from "flatpickr";

(() => {
	let initializedMap = false;
	let initializedMatterport = false;

	const AddScript = (mapElement) => {
		const api_key = mapElement.getAttribute("data-api-key");
		const script = document.createElement("script");
		script.src = `https://maps.googleapis.com/maps/api/js?key=${api_key}&callback=initMap`;
		script.async = true;
		document.head.appendChild(script);
	};

	const initializeBlock = () => {
		Swiper.use([Navigation, Thumbs]);
		const mainSliderElement = document.querySelector(
			'[data-target="single_main_slider"]'
		),
			thumbsSliderElement = document.querySelector(
				'[data-target="single_thumbs_slider"]'
			),
			mainSliderNextButton = document.querySelector(
				'[data-target="main_slider_next_button"]'
			),
			mainSliderPrevButton = document.querySelector(
				'[data-target="main_slider_prev_button"]'
			),
			fullSizeButton = document.querySelector(
				'[data-target="full_size_button"]'
			),
			closeFullSizeButton = document.querySelector(
				'[data-target="close_full_size"]'
			),
			slidersWrapper = document.querySelector(".listing-sliders");
		let thumbsSliderInstance = null,
			mainSliderInstance = null;

		if (thumbsSliderElement) {
			thumbsSliderInstance = new Swiper(thumbsSliderElement, {
				slidesPerView: "auto",
				watchSlidesProgress: true,
				loop: true,
			});
		}

		let optionsForMainSlider = {
			slidesPerView: 1.1078,
			loop: true,
			longSwipes: false,
			speed: 800,
			breakpoints: {
				320: {
					spaceBetween: 10,
				},
				577: {
					spaceBetween: 20,
				},
			},
		};

		if (thumbsSliderInstance !== null) {
			optionsForMainSlider.thumbs = {
				swiper: thumbsSliderInstance,
			};
		}

		if (mainSliderNextButton && mainSliderPrevButton) {
			optionsForMainSlider.navigation = {
				nextEl: mainSliderNextButton,
				prevEl: mainSliderPrevButton,
			};
		}

		if (mainSliderElement) {
			mainSliderInstance = new Swiper(mainSliderElement, optionsForMainSlider);
		}

		if (fullSizeButton && closeFullSizeButton) {
			fullSizeButton.addEventListener("click", () => {
				slidersWrapper.classList.add("full-size");
				mainSliderInstance.params.slidesPerView = 1;
				document.body.classList.add("freeze");
				MMOverlay.classList.add("show");
			});
			closeFullSizeButton.addEventListener("click", (e) => {
				slidersWrapper.classList.remove("full-size");
				document.body.classList.remove("freeze");
				mainSliderInstance.params.slidesPerView = 1.1078;
				MMOverlay.classList.remove("show");
			});
		}

		const mapElement = document.querySelector('[data-target="google_map"]');

		if (mapElement) {
			window.addEventListener("scroll", (e) => {
				if (isElementInViewport(mapElement) && !initializedMap) {
					AddScript(mapElement);
					initializedMap = true;
				}
			});

			const initMap = () => {
				const coordinates = {
					lat: parseFloat(mapElement.getAttribute("data-lat")),
					lng: parseFloat(mapElement.getAttribute("data-lng")),
				};
				window.mapInstance = new google.maps.Map(mapElement, {
					mapId: "c46718ce39aa3b46",
					zoom: 15,
					center: coordinates,
					disableDefaultUI: true,
					clickableIcons: false,
					zoomControl: true,
				});
				const marker = new google.maps.Marker({
					position: coordinates,
					icon: {
						path: "M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z",
						fillColor: "#0961af",
						fillOpacity: 1,
						strokeColor: "#0961af",
						scale: 0.075,
					},
				});
				marker.setMap(mapInstance);
			};
			window.initMap = initMap;
		}

		const backToTopButton = document.querySelector(
			'[data-target="back_to_top"]'
		);

		if (backToTopButton) {
			backToTopButton.addEventListener("click", (e) => {
				window.scrollTo({ top: 0, left: 0, behavior: "smooth" });
			});
		}

		const contactButton = document.querySelector(
			'[data-target="open_contact_button"]'
		),
			closeContactButton = document.querySelector(
				'[data-target="close_contact_button"]'
			),
			modal = document.querySelector(".inqury");

		if (modal && contactButton && closeContactButton) {
			contactButton.addEventListener("click", (e) => {
				modal.classList.add("show");
				document.body.classList.add("freeze");
			});
			closeContactButton.addEventListener("click", (e) => {
				e.preventDefault();
				e.stopPropagation();
				modal.classList.remove("show");
				document.body.classList.remove("freeze");
			});
			window.addEventListener("resize", () => {
				if (
					modal.classList.contains("show") &&
					document.body.classList.contains("freeze") &&
					window.innerWidth > 992
				) {
					modal.classList.remove("show");
					document.body.classList.remove("freeze");
				}
			});
		}

		const similarListingsSections = document.querySelectorAll(
			'[data-target="similar_listings"]'
		);

		if (similarListingsSections.length > 0) {
			similarListingsSections.forEach((section) => {
				let options = {
					breakpoints: {
						320: {
							slidesPerView: 1.2,
						},
						576: {
							slidesPerView: 2,
						},
						992: {
							slidesPerView: 3,
						},
					},
				};
				const prevButton = section.querySelector('[data-target="swiper_left"]');
				const nextButton = section.querySelector(
					'[data-target="swiper_right"]'
				);
				if (prevButton && nextButton) {
					options.navigation = {
						prevEl: prevButton,
						nextEl: nextButton,
					};
				}
				const swiperElement = section.querySelector(".swiper");
				const instance = new Swiper(swiperElement, options);
			});
		}

		const addToFavouritesButton = document.querySelector(
			'[data-target="add_to_favourites"]'
		);

		if (addToFavouritesButton) {
			const listingId = addToFavouritesButton.getAttribute("data-id");
			addToFavouritesButton.addEventListener("click", (e) => {
				e.preventDefault();
				e.stopPropagation();
				addToFavourites(listingId).then(() => {
					addToFavouritesButton.classList.toggle("liked");
				});
			});
		}

		const shareButton = document.querySelector('[data-target="share_listing"]'),
			shareList = document.querySelector('[data-target="share_list"]'),
			copyLinkButton = document.querySelector('[data-target="copy_link"]');

		if (shareButton && shareList && copyLinkButton) {
			shareButton.addEventListener("click", (e) => {
				if (window.innerWidth > 577) {
					shareList.classList.toggle("show");
				} else {
					navigator.share({
						title: document.title,
						url: window.location.href,
					});
				}
			});

			window.addEventListener("click", (e) => {
				if (
					shareList.classList.contains("show") &&
					!shareList.contains(e.target) &&
					!shareButton.contains(e.target)
				) {
					shareList.classList.remove("show");
					copyLinkButton.querySelector('span').innerText = 'Copy Link'
				}
			});

			const copyContent = async (content) => {
				const el = document.createElement("textarea");
				el.value = content;
				el.setAttribute("readonly", "");
				el.style.position = "absolute";
				el.style.left = "-9999px";
				document.body.appendChild(el);
				el.select();
				document.execCommand("copy");
				document.body.removeChild(el);
			};

			if (copyLinkButton) {
				copyLinkButton.addEventListener("click", (e) => {
					copyContent(window.location.href).then(() => {
						copyLinkButton.querySelector('span').innerText = 'Copied to clipboard ✓'
					});
				});
			}
		}

		const inqurySection = document.querySelector('[data-target="inqury"]');
		if (inqurySection) {
			const formSection = inqurySection.querySelector(
				'[data-target="inqury_form"]'
			),
				messageSection = inqurySection.querySelector(
					'[data-target="inqury_message"]'
				),
				form = formSection.querySelector("form");

			form.addEventListener("wpcf7mailsent", (e) => {
				formSection.classList.add("hide");
				messageSection.classList.add("show");
				setTimeout(() => {
					formSection.classList.remove("hide");
					messageSection.classList.remove("show");
				}, 10000);
			});
		}

		const iniFlatpickrInput = (input) => {
			const datePicker = flatpickr(input, {
				minDate: input.min,
				altInput: true,
				altFormat: "j M Y",
				dateFormat: "Y-m-d",
				disableMobile: true,
			});
		};

		const replaceDateInputToFlatpickrInput = (replacedInput) => {
			const newInput = replacedInput.cloneNode(true);
			newInput.type = "text";
			const parent = replacedInput.parentNode;
			parent.classList.add("flatpickr");
			parent.removeChild(replacedInput);
			newInput.dataset.name = replacedInput.name;
			parent.insertAdjacentElement("afterbegin", newInput);
			return newInput;
		};

		const changeDateInputAndSetEventOnButton = (oldInput, button) => {
			if (!oldInput || !button) {
				return false;
			}

			const newInput = replaceDateInputToFlatpickrInput(oldInput);
			iniFlatpickrInput(newInput);
			button.addEventListener("click", (e) => {
				e.preventDefault();
				newInput.nextElementSibling.dispatchEvent(new Event("focus"));
			});
		};

		const scheduleButton = document.querySelectorAll(
			'[data-target="schedule_button"]'
		),
			scheduleWrapper = document.querySelector(
				'[data-target="schedule_wrapper"]'
			),
			schedulePopup = document.querySelector('[data-target="schedule_popup"]'),
			closeScheduleButton = document.querySelector(
				'[data-target="close_schedule_popup"]'
			),
			scheduleMessage = document.querySelector(
				'[data-target="schedule_message"]'
			),
			closeScheduleMessage = scheduleMessage.querySelector(
				'[data-target="close_schedule_message"]'
			);
		let initFlatPicker = false;

		if (
			scheduleButton &&
			schedulePopup &&
			closeScheduleButton &&
			scheduleMessage &&
			closeScheduleMessage
		) {
			const form = schedulePopup.querySelector("form");
			const openCalendarBtn = schedulePopup.querySelector("button.icon");
			const oldDateInput = form.querySelector('input[type="date"]');
			scheduleButton.forEach((button) => {
				button.addEventListener("click", (e) => {
					scheduleWrapper.classList.add("show");
					scheduleWrapper.classList.add("modal");
					schedulePopup.classList.add("open");
					document.body.classList.add("freeze");
					document.body.classList.add("overlay-grey");
					if (oldDateInput && !initFlatPicker) {
						changeDateInputAndSetEventOnButton(oldDateInput, openCalendarBtn);
						initFlatPicker = true;
					}
				});
			});

			closeScheduleButton.addEventListener("click", (e) => {
				schedulePopup.classList.remove("open");
				document.body.classList.remove("freeze");
				document.body.classList.remove("overlay-grey");
				scheduleWrapper.classList.remove("show");
				scheduleWrapper.classList.remove("modal");
			});

			form.addEventListener("wpcf7mailsent", () => {
				schedulePopup.classList.remove("open");
				document.body.classList.remove("freeze");
				document.body.classList.remove("overlay-grey");
				scheduleMessage.classList.add("show");
				scheduleWrapper.classList.remove("modal");

				window.scrollTo({
					top: 0,
					left: 0,
					behavior: "smooth",
				});

				setTimeout(() => {
					scheduleMessage.classList.remove("show");
				}, 10000);
			});

			closeScheduleMessage.addEventListener("click", (e) => {
				scheduleMessage.classList.remove("show");
			});
		}

		const tenantsSection = document.querySelector(
			'[data-target="building_tenants"]'
		);
		if (tenantsSection) {
			const sliderElement = tenantsSection.querySelector(".swiper"),
				nextButton = tenantsSection.querySelector(
					'[data-target="swiper_right"]'
				),
				prevButton = tenantsSection.querySelector(
					'[data-target="swiper_left"]'
				);

			new Swiper(sliderElement, {
				slidesPerView: 4,
				spaceBetween: 22,
				navigation: {
					nextEl: nextButton,
					prevEl: prevButton,
				},
				breakpoints: {
					300: {
						slidesPerView: 1.11,
					},
					577: {
						slidesPerView: 3,
					},
					768: {
						slidesPerView: 4,
						freeMode: false,
					},
				},
			});
		}

		const availableSpaceAccordions = document.querySelectorAll(
			'[data-target="accordion"]'
		);
		if (availableSpaceAccordions) {
			availableSpaceAccordions.forEach((accordion) => {
				const header = accordion.querySelector(".header-accordion");
				if (header) {
					header.addEventListener("click", (e) => {
						accordion.classList.toggle("active");
					});
				}
			});
		}

		const replaceAndCreateIframe = (element) => {
			if (element) {
				const url = element.getAttribute("data-url");
				const iframe = document.createElement("iframe");
				iframe.src = url;
				element.insertAdjacentElement("afterend", iframe);
				element.remove();
			}
		};

		const matterPortContainer = document.querySelector("#matterport");
		if (matterPortContainer) {
			window.addEventListener("scroll", (e) => {
				if (
					isElementInViewport(matterPortContainer) &&
					!initializedMatterport
				) {
					replaceAndCreateIframe(matterPortContainer);
					initializedMatterport = true;
				}
			});
		}

		const setGtagEventOnForms = () => {
			const inquiryForm = document.querySelector('[data-target="inqury"] form');
			const page = window.location.pathname.includes("listing")
				? "listing"
				: window.location.pathname.includes("building")
					? "building"
					: null;
			if (inquiryForm) {
				inquiryForm.addEventListener("wpcf7mailsent", (e) => {
					window.dataLayer.push({
						event: page + "_page_inquiry_form",
						listing_name: document.title,
					});
				});
			}
			const scheduleForm = document.querySelector(
				'[data-target="schedule_popup"] form'
			);
			if (scheduleForm) {
				scheduleForm.addEventListener("wpcf7mailsent", (e) => {
					window.dataLayer.push({
						event: page + "_page_schedule_tour_form",
						listing_name: document.title,
					});
				});
			}
		};

		if (typeof window.dataLayer !== "undefined") {
			let object = {
				page_title: document.title,
			};
			if (window.location.pathname.includes("listing")) {
				object.event = "listing_page_click";
			}
			window.dataLayer.push(object);
			setGtagEventOnForms();
		}

		const inquiryFocusBtns = document.querySelectorAll(
			'[data-target="table_btn"]'
		);
		if (inquiryFocusBtns) {
			const input = document.querySelector(
				'[data-target="inqury"] form input:not([type="hidden"])'
			);
			inquiryFocusBtns.forEach((button) => {
				button.addEventListener("click", (e) => {
					input.focus();
				});
			});
		}
	};

	if (
		document.readyState === "interactive" ||
		document.readyState === "complete"
	) {
		initializeBlock();
	} else {
		document.addEventListener("DOMContentLoaded", () => {
			initializeBlock();
		});
	}
})();
