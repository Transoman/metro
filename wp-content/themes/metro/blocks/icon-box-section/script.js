import Swiper from "swiper"
(() => {
	const initiliazeBlock = () => {
		const iconBoxSections = document.querySelectorAll('[data-target="icon-box"]');
		if (iconBoxSections) {
			iconBoxSections.forEach(section => {
				let sliderInstance = null;
				let sliderOptions = {
					spaceBetween: 20,
					slidesPerView: 1.11,
					freeMode: true,
				}
				let minResize = false;
				let maxResize = true;
				let element = section.querySelector('.swiper');

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
			})
		}
	}

	if (document.readyState === "interactive" || document.readyState === "complete") {
		initiliazeBlock()
	}
	else {
		document.addEventListener('DOMContentLoaded', () => {
			if (!window.acf) {
				initiliazeBlock()
			}
			else {
				window.acf.addAction('render_block_preview/type=icon-box', initiliazeBlock);
			}
		})
	}
})()
