import { SearchForm } from "../../assets/js/helpers"

(() => {
    const initializeBlock = () => {
        const headerSearchBarElement = document.querySelector('[data-target="header_search_bar"]');
        if (headerSearchBarElement) {
            new SearchForm(headerSearchBarElement)
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