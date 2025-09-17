import Swiper from "swiper";
import { Navigation } from "swiper/modules"
import { GoogleRatingRender } from "../../assets/js/helpers";

(() => {
    const initializeBlock = () => {
        const testimonialsSection = document.querySelector('[data-target="testimonials_slider"]');
        const googleRatingElement = testimonialsSection.querySelector('[data-google-rating]')
        GoogleRatingRender(googleRatingElement);

        if (testimonialsSection) {
            let options = {
                freeMode: true,
                breakpoints: {
                    300: {
                        slidesPerView: 1.11,
                        spaceBetween: 20,
                    },
                    577: {
                        slidesPerView: 2,
                        spaceBetween: 30,
                    }
                }
            }

            const sliderElement = testimonialsSection.querySelector('.swiper');
            const prevButton = testimonialsSection.querySelector('[data-target="swiper_left"]');
            const nextButton = testimonialsSection.querySelector('[data-target="swiper_right"]');
            if (nextButton && prevButton) {
                Swiper.use(Navigation)
                options.navigation = {
                    prevEl: prevButton,
                    nextEl: nextButton
                }
            }

            new Swiper(sliderElement, options)
        }
    }

    if (document.readyState === "interactive" || document.readyState === "complete") {
        if (!window.acf) {
            initializeBlock()
        }
        else {
            window.acf.addAction('render_block_preview/type=testimonials-section', initializeBlock);
        }
    }
    else {
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.acf) {
                initializeBlock()
            }
            else {
                window.acf.addAction('render_block_preview/type=testimonials-section', initializeBlock);
            }
        })
    }
})()