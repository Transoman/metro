import Swiper from "swiper";
import { Navigation, Grid } from "swiper/modules";
import { changeWindowHistory } from "./helpers";
(() => {
    const heightOfHeader = (header) => {
        let result = 0
        if (header) {
            Array.from(header.children).forEach(item => result += item.clientHeight);
        }
        return result;
    }
    const initializeBlock = () => {
        const scrollMenus = document.querySelectorAll('[data-target="scroll_menu"]');
        if (scrollMenus) {
            scrollMenus.forEach(scrollMenu => {
                const links = scrollMenu.querySelectorAll('a');
                const menuData = [];
                if (links.length > 0) {
                    links.forEach(link => {
                        const selector = link.hash;
                        const section = document.querySelector(`${selector}`);
                        if (section !== null) {
                            menuData.push({ link: link, section: section });
                        }
                        link.addEventListener('click', (e) => {
                            e.preventDefault();
                            window.scrollTo({
                                top: section.offsetTop - heightOfHeader(MMHeader),
                                left: 0,
                                behavior: 'smooth'
                            });
                        })
                    })

                    window.addEventListener('scroll', (e) => {
                        let current;
                        menuData.forEach(menuItem => {
                            let sectionTop = menuItem.section.offsetTop;
                            let sectionHeight = menuItem.section.offsetHeight;
                            if (pageYOffset > sectionTop - MMHeader.offsetHeight && pageYOffset < (sectionTop + sectionHeight) - MMHeader.offsetHeight) {
                                current = menuItem.section.getAttribute('id');
                            }
                        })

                        links.forEach(link => {
                            link.classList.remove('active');
                            if (link.hash.includes(current)) {
                                link.classList.add('active')
                            }
                        })
                    })
                }
            })
        }

        const levelsMenus = document.querySelectorAll('[data-target="levels_menu"]');
        if (levelsMenus) {
            levelsMenus.forEach(parentMenu => {
                const moreButton = parentMenu.querySelector('[data-target="more_button"]');
                const secondLevelMenu = parentMenu.querySelector('[data-target="second_level"]');

                if (moreButton && secondLevelMenu) {
                    moreButton.addEventListener('click', (e) => {
                        moreButton.classList.toggle('active');
                        secondLevelMenu.classList.toggle('active');
                    })
                }
            })
        }

        const prominentBuildings = document.querySelector('[data-target="prominent_buildings"]');

        if (prominentBuildings) {
            const sliderElement = prominentBuildings.querySelector('.swiper'),
                prevButton = prominentBuildings.querySelector('[data-target="swiper_left"]'),
                nextButton = prominentBuildings.querySelector('[data-target="swiper_right"]');

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

        const featuredListingsSection = document.querySelector('[data-target="featured_listings"]');
        if (featuredListingsSection) {
            const sliderElement = featuredListingsSection.querySelector('.swiper'),
                gridStyle = featuredListingsSection.getAttribute('data-grid-style'),
                prevButton = featuredListingsSection.querySelector('[data-target="swiper_left"]'),
                nextButton = featuredListingsSection.querySelector('[data-target="swiper_right"]');

            if (sliderElement) {
                let options = {
                    freeMode: true,
                    lazy: true,
                }

                if (prevButton && nextButton) {
                    Swiper.use(Navigation);
                    options.navigation = {
                        prevEl: prevButton,
                        nextEl: nextButton
                    }

                }

                if (gridStyle == 'true') {
                    Swiper.use(Grid);

                    options.breakpoints = {
                        300: {
                            slidesPerView: 1.2,
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
                else {
                    options.breakpoints = {
                        320: {
                            slidesPerView: 1.2,
                        },
                        577: {
                            slidesPerView: 2,
                        },
                        992: {
                            slidesPerView: 3,
                        }
                    }
                }

                const instance = new Swiper(sliderElement, options)
            }

        }

        const moreNewsSection = document.querySelector('[data-target="more_posts"]');
        if (moreNewsSection) {
            const sliderElement = moreNewsSection.querySelector('.swiper');

            if (sliderElement) {
                const instance = new Swiper(sliderElement, {
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
                })
            }
        }

        const listingsSection = document.querySelector('[data-target="listings_with_pagination"]');
        if (listingsSection) {
            const resultWrapper = listingsSection.querySelector('[data-target="result_wrapper"]');
            const paginationWrapper = listingsSection.querySelector('[data-target="pagination_wrapper"]');
            if (resultWrapper && paginationWrapper) {
                const form = listingsSection.querySelector('[data-target="form"]');
                const pageInput = form.querySelector('input[name="page"]');
                const setEventsForPagination = (wrapper) => {
                    const prevButton = wrapper.querySelector('[data-target="pagination_prev"]'),
                        nextButton = wrapper.querySelector('[data-target="pagination_next"]'),
                        paginationLinks = wrapper.querySelectorAll('[data-target="pagination_link"]');

                    if (nextButton && prevButton && paginationLinks) {
                        nextButton.addEventListener('click', (e) => {
                            e.preventDefault();
                            pageInput.value = parseInt(pageInput.value) + 1;
                            fetchListings(form)
                            changeWindowHistory(nextButton);
                        })
                        prevButton.addEventListener('click', (e) => {
                            e.preventDefault();
                            if (pageInput.value > 1) {
                                pageInput.value = parseInt(pageInput.value) - 1;
                                fetchListings(form)
                                changeWindowHistory(prevButton);
                            }
                        })
                        paginationLinks.forEach(paginationLink => {
                            const link = paginationLink.querySelector('a');
                            if (paginationLink.classList.contains('current-page') && paginationLink.previousElementSibling) {
                                paginationLink.previousElementSibling.classList.add('previous');
                            }
                            if (link) {
                                link.addEventListener('click', (e) => {
                                    e.preventDefault();
                                    const newPage = e.target.innerText;
                                    pageInput.value = newPage;
                                    fetchListings(form)
                                    changeWindowHistory(link)
                                })
                            }
                        })
                    }
                }
                setEventsForPagination(paginationWrapper);
                const fetchListings = (target) => {
                    const data = new FormData(target)
                    fetch(mm_ajax_object.ajaxURL, {
                        method: "POST",
                        credentials: "same-origin",
                        body: data
                    })
                        .then(response => response.json())
                        .then(json => {
                            if (json.cards !== '') {
                                resultWrapper.innerHTML = json.cards
                            }
                            if (paginationWrapper) {
                                if (json.pagination) {
                                    paginationWrapper.innerHTML = json.pagination;
                                    setEventsForPagination(paginationWrapper);
                                    paginationWrapper.classList.remove('hide')
                                } else {
                                    paginationWrapper.classList.add('hide')
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
                            }
                            setTimeout(() => {
                                window.scrollTo({
                                    top: listingsSection.offsetTop,
                                    left: 0,
                                    behavior: 'smooth'
                                })
                            }, 0)
                        })
                }
            }

        }

        const typesBlocks = document.querySelectorAll('[data-target="type_block"]');
        if (typesBlocks) {
            typesBlocks.forEach(typesBlock => {
                const title = typesBlock.querySelector('.title');
                typesBlock.style.setProperty('--element-height', `${title.clientHeight + 1}px`)
                window.addEventListener('resize', () => {
                    typesBlock.style.setProperty('--element-height', `${title.clientHeight + 1}px`)
                })
            })
        }

        const readMoreElements = document.querySelectorAll('.columns-3 [data-target="read_more_text"]');
        if (readMoreElements) {

            const modifyString = (text, boolean, length) => {
                return (boolean && text.length > length) ? text.substring(0, length) + '...' : text;
            }
            const showButton = (text, button) => {
                window.addEventListener('resize', (e) => {
                    if (window.innerWidth < 576 && text.length > 20 && button.classList.contains('hide')) {
                        button.classList.remove('hide')
                    }
                })
            }

            readMoreElements.forEach(readMoreElement => {
                if (readMoreElement.getAttribute('data-init') === 'false') {
                    const text = readMoreElement.getAttribute('data-text');
                    let toggleState = false;
                    const paragraph = readMoreElement.querySelector('p');
                    if (paragraph) {
                        paragraph.innerText = modifyString(text, true, (window.innerWidth < 576) ? 20 : 105);

                        window.addEventListener('resize', (e) => {
                            paragraph.innerText = modifyString(text, true, (window.innerWidth < 576) ? 20 : 105);
                            button.innerText = 'Read more';
                            toggleState = false
                        })
                    }

                    const button = readMoreElement.querySelector('button[type="button"]');
                    if (window.innerWidth < 576 && text.length > 20 && button.classList.contains('hide')) {
                        button.classList.remove('hide')
                    }

                    showButton(text, button)

                    if (button) {
                        button.addEventListener('click', () => {
                            if (!toggleState) {
                                button.innerText = 'Read less';
                            } else {
                                button.innerText = 'Read more'
                            }
                            paragraph.innerText = modifyString(text, toggleState, (window.innerWidth < 576) ? 20 : 105);
                            readMoreElement.classList.toggle('show');
                            toggleState = !toggleState
                        })
                    }

                    readMoreElement.setAttribute('data-init', true)
                }
            })
        }


        const mobileSelector = document.querySelector('[data-target="mobile_sidebar"]');
        if (mobileSelector) {
            const placeholder = mobileSelector.querySelector('.placeholder');
            placeholder.addEventListener('click', () => {
                mobileSelector.classList.toggle('active');
            })

            window.addEventListener('click', (e) => {
                if (mobileSelector.classList.contains('active') && !mobileSelector.contains(e.target)) {
                    mobileSelector.classList.remove('active')
                }
            })
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