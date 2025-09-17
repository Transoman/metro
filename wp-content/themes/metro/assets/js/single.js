import Swiper from "swiper"
import { Navigation, Grid } from 'swiper/modules';
import { YoutubeVideos } from "./helpers";

(() => {
    const initializeBlock = () => {
        const featuredListingsSection = document.querySelector('[data-target="featured_listings"]');
        if (featuredListingsSection) {
            const sliderElement = featuredListingsSection.querySelector('.swiper'),
                prevButton = featuredListingsSection.querySelector('[data-target="swiper_left"]'),
                nextButton = featuredListingsSection.querySelector('[data-target="swiper_right"]');

            Swiper.use([Navigation, Grid]);
            if (sliderElement) {
                const instance = new Swiper(sliderElement, {
                    freeMode: true,
                    lazy: true,
                    navigation: {
                        prevEl: prevButton,
                        nextEl: nextButton
                    },
                    breakpoints: {
                        300: {
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
                })
            }
        }

        const featuredBuildings = document.querySelector('[data-target="featured_buildings"]');

        if (featuredBuildings) {
            const sliderElement = featuredBuildings.querySelector('.swiper'),
                prevButton = featuredBuildings.querySelector('[data-target="swiper_left"]'),
                nextButton = featuredBuildings.querySelector('[data-target="swiper_right"]');

            if (sliderElement) {
                let options = {
                    freeMode: true,
                    lazy: true,
                    breakpoints: {
                        300: {
                            slidesPerView: 1.11,
                        },
                        577: {
                            slidesPerView: 2,
                        },
                        992: {
                            slidesPerView: 3,
                        }
                    }
                }
                if (prevButton && nextButton) {
                    Swiper.use(Navigation)
                    options.navigation = {
                        prevEl: prevButton,
                        nextEl: nextButton
                    }
                }

                new Swiper(sliderElement, options);
            }
        }

        const videos = document.querySelectorAll('[data-target="youtube_video"]');

        if (videos) {
            new YoutubeVideos(videos);
        }
    }

    if (document.readyState === "interactive" || document.readyState === "complete") {
        initializeBlock()
    }
    else {
        document.addEventListener('DOMContentLoaded', () => {
            initializeBlock()
        })
    }
})()