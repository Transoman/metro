import Swiper from "swiper";
import { Manipulation } from "swiper/modules";
import { addToFavourites } from "../../assets/js/helpers";
(() => {
	const initializeBlock = () => {
		class FeaturedSpaces {
			constructor(element) {
				this.filterForm = element.querySelector('[data-target="featured_spaces_form"]');
				this.HTMLWrapper = element.querySelector('[data-target="featured_spaces_wrapper"]');
				this.sliderElement = element.querySelector('[data-target="featured_spaces_slider"]');
				this.sliderInstance = null;
				this.sliderOptions = {
					spaceBetween: 20,
					slidesPerView: 1.11,
					laxzy: true,
					freeMode: true,
				}
				this.minResize = false;
				this.maxResize = true;
			}

			startPoint() {
				this.initializationSlider();
				this.initializationFilterForm()
			}

			initializationFilterForm() {
				this.setEventsForTabs()
			}

			setEventsForTabs() {
				const tabs = this.filterForm.querySelectorAll('a'),
					hiddenInput = this.filterForm.querySelector('input[type="hidden"]'),
					numberposts = hiddenInput.value;
				tabs.forEach(tab => {
					const slug = tab.getAttribute('data-slug');
					tab.addEventListener('click', (e) => {
						e.preventDefault();
						this.fetchPostsForFeaturedSpaces(slug, numberposts)
					})
				})
			}

			fetchPostsForFeaturedSpaces(slug, numberposts) {
				const asyncURL = mm_ajax_object.ajaxURL;
				const formData = new FormData();
				formData.append('action', 'get_featured_spaces_posts');
				formData.append('numberposts', numberposts);
				formData.append('slug', slug);
				if (asyncURL) {
					fetch(asyncURL, {
						method: 'POST',
						body: formData
					})
						.then(response => response.json())
						.then(json => {
							if (json) {
								this.HTMLWrapper.innerHTML = '';
								Swiper.use(Manipulation);
								this.sliderInstance = new Swiper(this.sliderElement, this.sliderOptions);
								json.forEach(item => {
									this.sliderInstance.appendSlide(`<div class="swiper-slide block">${item}</div>`);
								})
								this.sliderInstance.destroy(true, true);
								this.setEventsToListing(this.sliderElement);
							}
						})
				}
			}

			setEventsToListing(wrapper) {
				if (wrapper) {
					const listings = wrapper.querySelectorAll('[data-target="custom_post"]');
					listings.forEach(listing => {
						const addToFavouritesButton = listing.querySelector('[data-target="add_to_favourites"]');
						if (addToFavouritesButton) {
							addToFavouritesButton.addEventListener('click', (e) => {
								e.preventDefault();
								e.stopPropagation();
								addToFavouritesButton.classList.toggle('liked').then(() => {
									addToFavourites(listing);
								})
							})
						}
					})
				}
			}

			initializationSlider() {
				this.resizeWindow(window.innerWidth, 577)
				window.addEventListener('resize', () => {
					this.resizeWindow(window.innerWidth, 577)
				})

			}

			enablingSlider() {
				this.sliderInstance = new Swiper(this.sliderElement, this.sliderOptions);
			}

			destroyingSlider() {
				if (this.sliderInstance !== undefined) {
					this.sliderInstance.destroy(true, true)
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

		const featuredSpacesSection = document.querySelector('[data-target="featured_spaces"]');
		if (featuredSpacesSection) {
			const initFeaturedSpaces = new FeaturedSpaces(featuredSpacesSection).startPoint()
		}
	}

	if (document.readyState === "interactive" || document.readyState === "complete") {
		if (!window.acf) {
			initializeBlock()
		}
		else {
			window.acf.addAction('render_block_preview/type=featured-spaces-section', initializeBlock)
		}
	}
	else {
		document.addEventListener('DOMContentLoaded', () => {
			if (!window.acf) {
				initializeBlock()
			}
			else {
				window.acf.addAction('render_block_preview/type=featured-spaces-section', initializeBlock)
			}
		})
	}
})()