import Swiper from "swiper";
(() => {
	const initializeBlock = () => {
		const recommendedListings = document.querySelector('[data-target="recommended_listings"]');
		if (recommendedListings) {
			let sliderInstance = null;
			let sliderOptions = {
				spaceBetween: 20,
				slidesPerView: 1.11,
				freeMode: true,
			}
			let minResize = false;
			let maxResize = true;
			let element = recommendedListings.querySelector('.swiper');

			const resizeWindow = (width, breakpoint) => {
				if (width <= breakpoint && !minResize) {
					minResize = true;
					maxResize = false;
					enablingSlider()
				}
				if (width >= breakpoint && !maxResize) {
					maxResize = true;
					minResize = false;
					destroyingSlider()
				}
			}

			const enablingSlider = () => {
				sliderInstance = new Swiper(element, sliderOptions)
			}

			const destroyingSlider = () => {
				if (sliderInstance !== undefined) {
					sliderInstance.destroy(true, true)
				}
			}

			resizeWindow(window.innerWidth, 577)
			window.addEventListener('resize', () => {
				resizeWindow(window.innerWidth, 577)
			})
		}
	}

	if (document.readyState === "interactive" || document.readyState === "complete") {
		if (!window.acf) {
			initializeBlock()
		}
		else {
			window.acf.addAction('render_block_preview/type=text-with-listings-section', initializeBlock);
		}
	}
	else {
		document.addEventListener('DOMContentLoaded', () => {
			if (!window.acf) {
				initializeBlock()
			}
			else {
				window.acf.addAction('render_block_preview/type=text-with-listings-section', initializeBlock);
			}
		})
	}
})()