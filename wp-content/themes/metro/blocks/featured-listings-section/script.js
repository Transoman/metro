import Swiper from "swiper";
import { Navigation, Grid } from "swiper/modules";

(() => {
    const initializeBlock = () => {
        const featuredListingsSection = document.querySelector('[data-target="featured_listings"]');
        if (featuredListingsSection) {
            const sliderElement = featuredListingsSection.querySelector('.swiper'),
                prevButton = featuredListingsSection.querySelector('[data-target="swiper_left"]'),
                nextButton = featuredListingsSection.querySelector('[data-target="swiper_right"]');
            let options = {
                freeMode: true,
                lazy: true,
            }

            if (nextButton && prevButton) {
                Swiper.use(Navigation);
                options.navigation = {
                    prevEl: prevButton,
                    nextEl: nextButton
                }
            }

            if (featuredListingsSection.classList.contains('standard')) {
                options.breakpoints = {
                    320: {
                        slidesPerView: 1.11,
                        spaceBetween: 20
                    },
                    576: {
                        slidesPerView: 2,
                        spaceBetween: 30,
                    },
                    992: {
                        slidesPerView: 3,
                        spaceBetween: 30,
                    }

                }
            } else {
                Swiper.use(Grid);
                options.breakpoints = {
                    320: {
                        slidesPerView: 1.11,
                        spaceBetween: 20,
                    },
                    577: {
                        slidesPerView: 2,
                        spaceBetween: 0,
                        grid: {
                            fill: 'row',
                            rows: 2,
                        }
                    }
                }
            }

            if (sliderElement) {
                const instance = new Swiper(sliderElement, options)
            }

        }
    }

    if (document.readyState === "interactive" || document.readyState === "complete") {
        if (!window.acf) {
            initializeBlock()
        } else {
            window.acf.addAction('render_block_preview', initializeBlock)
        }
    }
    else {
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.acf) {
                initializeBlock()
            } else {
                window.acf.addAction('render_block_preview', initializeBlock)
            }
        })
    }
})()