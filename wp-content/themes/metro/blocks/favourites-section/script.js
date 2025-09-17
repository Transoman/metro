import { changeWindowHistory } from "../../assets/js/helpers";
(() => {
    const initializeBlock = () => {
        const resultWrapper = document.querySelector('[data-target="result_wrapper"]'),
            paginationWrapper = document.querySelector('[data-target="pagination_wrapper"]'),
            form = document.querySelector('[data-target="form"]'),
            totalElement = document.querySelector('[data-target="total"]');

        if (resultWrapper && form) {
            const pageInput = form.querySelector('input[name="page"]');
            const setEventsForPagination = (wrapper) => {
                const prevButton = wrapper.querySelector('[data-target="pagination_prev"]'),
                    nextButton = wrapper.querySelector('[data-target="pagination_next"]'),
                    paginationLinks = wrapper.querySelectorAll('[data-target="pagination_link"]');

                if (nextButton && prevButton && paginationLinks) {
                    nextButton.addEventListener('click', (e) => {
                        e.preventDefault();
                        pageInput.value = parseInt(pageInput.value) + 1;
                        fetchFavouritesListings(form, true);
                        changeWindowHistory(nextButton);
                    })
                    prevButton.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (pageInput.value >= 1) {
                            pageInput.value = parseInt(pageInput.value) - 1;
                            fetchFavouritesListings(form, true);
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
                                fetchFavouritesListings(form, true);
                                changeWindowHistory(link)
                            })
                        }
                    })
                }
            }
            const setEventsForFavouritesListings = (listings) => {
                listings.forEach(listing => {
                    const addToFavouritesButton = listing.querySelector('[data-target="fetch_favourites"]');
                    const listingID = listing.getAttribute('data-id');
                    if (addToFavouritesButton) {
                        addToFavouritesButton.addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            fetchFavouritesListings(form, false, listingID);
                            addToFavouritesButton.classList.toggle('liked')
                        })
                    }
                })
            }
            if (paginationWrapper) {
                setEventsForPagination(paginationWrapper);
            }

            const fetchFavouritesListings = (target, needScroll, listingID) => {
                const data = new FormData(target);
                if (listingID !== undefined) {
                    data.append('listing_id', listingID);
                }
                fetch(mm_ajax_object.ajaxURL, {
                    method: "POST",
                    credentials: "same-origin",
                    body: data
                })
                    .then(response => response.json())
                    .then(json => {
                        if (json.cards !== '') {
                            resultWrapper.innerHTML = json.cards;
                            const listings = document.querySelectorAll('[data-target="custom_post"]');
                            setEventsForFavouritesListings(listings)
                        } else {
                            const empty = document.querySelector('.empty');
                            resultWrapper.classList.add('hide');
                            empty.classList.remove('hide');
                        }
                        if (json.total) {
                            if (totalElement) {
                                totalElement.innerText = `(${json.total})`
                            }
                        }
                        else {
                            if (totalElement) {
                                totalElement.innerText = `(${0})`
                            }
                        }
                        if (paginationWrapper) {
                            if (json.pagination) {
                                paginationWrapper.innerHTML = json.pagination;
                                setEventsForPagination(paginationWrapper);
                                paginationWrapper.classList.remove('hide');
                            } else {
                                paginationWrapper.classList.add('hide');
                            }
                        }
                        if (needScroll) {
                            setTimeout(() => {
                                window.scrollTo({
                                    top: resultWrapper.offsetTop - MMHeader.offsetHeight,
                                    left: 0,
                                    behavior: 'smooth'
                                })
                            }, 0)
                        }

                    })
            }

            const listings = document.querySelectorAll('[data-target="custom_post"]');

            if (listings) {
                setEventsForFavouritesListings(listings)
            }
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
