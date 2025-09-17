import Swiper from "swiper";
import { GoogleRatingRender } from "../../assets/js/helpers";
(() => {
	class ClientSay {
		constructor(element) {
			this.clientsSayElement = element.querySelector('[data-target="clients_say_element"]');
			this.googleReviewsElement = element.querySelector('[data-target="google_review_element"]');
			this.clientsSaySliderInstance = null;
			this.googleReviewsSliderInstance = null;
			this.minResize = false;
			this.maxResize = true;
		}

		startPoint() {
			this.initializationClientSaySlider();
			this.initializationGoogleReviews();
		}

		initializationClientSaySlider() {
			this.clientsSaySliderInstance = new Swiper(this.clientsSayElement, {
				spaceBetween: 1,
				allowTouchMove: true,
				grabCursor: true,
				pagination: {
					el: '.pagination',
					clickable: true
				},
			})
		}

		initializationGoogleReviews() {
			this.resizeWindow(window.innerWidth, 769)
			window.addEventListener('resize', () => {
				this.resizeWindow(window.innerWidth, 769)
			})
		}

		enablingSlider() {
			this.googleReviewsSliderInstance = new Swiper(this.googleReviewsElement, {
				spaceBetween: 20,
				slidesPerGroup: 2.5,
				slidesPerView: 'auto',
				// cssMode: true,
				freeMode: true,
			})
		}

		destroyingSlider() {
			if (this.googleReviewsSliderInstance !== undefined) {
				this.googleReviewsSliderInstance.destroy(true, true)
			}
		}


		resizeWindow(width, breakpoint) {
			if (width <= breakpoint && !this.minResize) {
				this.minResize = true;
				this.maxResize = false;
				this.enablingSlider()
			}
			if (width >= breakpoint && !this.maxResize) {
				this.maxResize = true;
				this.minResize = false;
				this.destroyingSlider()
			}
		}

	}
	const initializeBlock = () => {
		const clientsSaySection = document.querySelector('[data-target="clients_say"]');
		const ratingsElements = clientsSaySection.querySelectorAll('[data-google-rating]');
		if (ratingsElements) {
			ratingsElements.forEach(element => {
				GoogleRatingRender(element)
			})
		}
		if (clientsSaySection) {
			const initClientSay = new ClientSay(clientsSaySection).startPoint()
		}
	}

	if (document.readyState === "interactive" || document.readyState === "complete") {
		if (!window.acf) {
			initializeBlock()
		}
		else {
			window.acf.addAction('render_block_preview/type=clients-say-section', initializeBlock);
		}
	}
	else {
		document.addEventListener('DOMContentLoaded', () => {
			if (!window.acf) {
				initializeBlock()
			}
			else {
				window.acf.addAction('render_block_preview/type=clients-say-section', initializeBlock);
			}
		})
	}
})()