import Swiper from "swiper"
import { Navigation, Manipulation } from "swiper/modules";
(() => {
    const initializeBlock = () => {
        const calculator = document.querySelector('[data-target="office_space_calculator"]');
        const sliderSection = document.querySelector('[data-target="slider_section"]')
        if (calculator && sliderSection) {
            const sliderResult = sliderSection.querySelector('[data-target="slider_element"]');
            const employeesInput = calculator.querySelector('input[name="employees"]');
            const fields = calculator.querySelectorAll('input:not([name="employees"], [type="hidden"])');
            const resultList = document.querySelector('[data-target="result_list"]');
            const rangeElement = document.querySelector('[data-target="range_result"]');
            const fromRange = rangeElement.querySelector('[data-target="from"]');
            const toRange = rangeElement.querySelector('[data-target="to"]');
            const firstAdd = calculator.querySelector('[name="first_add"]').value;
            const secondAdd = calculator.querySelector('[name="second_add"]').value;
            const range = calculator.querySelector('[name="range"]').value;
            const nextButton = document.querySelector('[data-target="swiper_right"]');
            const prevButton = document.querySelector('[data-target="swiper_left"]');
            const textWithList = document.querySelector('[data-target="with_list"]');
            const textWithoutList = document.querySelector('[data-target="without_list"]');
            let sliderInstance;
            let options = {
                spaceBetween: 0,
                breakpoints: {
                    320: {
                        slidesPerView: 1.11,
                    },
                    992: {
                        slidesPerView: 3,
                        grid: {
                            rows: 2,
                            fill: 'row'
                        },
                    }
                }
            }

            if (nextButton && prevButton) {
                Swiper.use(Navigation)
                options.navigation = {
                    nextEl: nextButton,
                    prevEl: prevButton
                }
            }

            employeesInput.addEventListener('input', (e) => {
                fields.forEach(field => {
                    if (field.type === 'radio') {
                        field.checked = false
                    }
                    else {
                        field.value = '';
                    }
                    const listItems = resultList.querySelectorAll('li');
                    listItems.forEach(item => {
                        item.remove()
                    })
                })
            })

            fields.forEach(field => {
                const text = field.getAttribute('data-text');
                field.addEventListener('input', (e) => {
                    if (employeesInput.value !== '') {
                        employeesInput.classList.remove('invalid')
                        textWithList.classList.add('show');
                        textWithoutList.classList.remove('show');
                        const value = getValue(calculator, field.name);
                        let listItem = buildListItem(employeesInput.value, value, field.name, text);
                        if (value !== '') {
                            insertListItem(resultList, listItem, field.name, false);
                        }
                        else {
                            insertListItem(resultList, listItem, field.name, true);
                        }
                        let result = getResultRange(employeesInput, fields, firstAdd, secondAdd, range);
                        rangeElement.classList.add('show');
                        fromRange.innerText = (result[0]).toLocaleString('en');
                        toRange.innerText = (result[1]).toLocaleString('en');
                        fetchListingsByRange(result);
                    }
                    else {
                        rangeElement.classList.remove('show');
                        employeesInput.classList.add('invalid');
                        window.scrollTo({
                            top: calculator.offsetTop - MMHeader.offsetHeight,
                            left: 0,
                            behavior: 'smooth'
                        })
                    }
                })
            })

            const insertListItem = (resultList, listItem, selector, remove) => {
                const element = resultList.querySelector(`[data-target="${selector}"]`);
                if (!remove) {
                    if (!element) {
                        resultList.insertAdjacentHTML('beforeend', listItem);
                    } else {
                        element.insertAdjacentHTML('afterend', listItem);
                        element.remove();
                    }
                }
                else {
                    element.remove()
                }

            }

            const buildListItem = (employeesValue, value, name, text) => {
                let string, html;
                if (name === 'seating_places') {
                    string = `${employeesValue} employees ${text}`
                } else {
                    if (text) {
                        if (value > 1) {
                            text += 's';
                        }
                        string = `${value} ${text}`;
                    } else {
                        string = `${value}`
                    }
                }
                html = `<li data-target="${name}">${string}</li>`;
                return html;
            }

            const getValue = (target, key) => {
                for (const pair of new FormData(target).entries()) {
                    if (pair[0] === key) {
                        return pair[1];
                    }
                }
            }

            const getResultRange = (employeesInput, fields, firstAdd, secondAdd, range) => {
                if (employeesInput.value !== '') {
                    let result = 0;
                    const employees = employeesInput.value
                    fields.forEach(field => {
                        if (field.value !== '') {
                            if (field.name === 'seating_places' && field.checked) {
                                result += parseInt(employees) * parseInt(field.getAttribute('data-square'));
                            }
                            if (field.name !== 'seating_places') {
                                result += parseInt(field.value) * parseInt(field.getAttribute('data-square'));
                            }
                        }
                    })
                    result = result + (result * (firstAdd / 100));
                    result = result + (result * (secondAdd / 100));
                    return [Math.round(result - (result * (range / 100))), Math.round(result + (result * (range / 100)))];
                }
            }

            const fetchListingsByRange = (range) => {
                let data = new FormData();
                data.append('range', range);
                data.append('action', 'get_calculated_offices');


                fetch(mm_ajax_object.ajaxURL, {
                    method: "POST",
                    credentials: "same-origin",
                    body: data
                })
                    .then(response => response.json())
                    .then(json => {
                        if (json.listings.length > 0) {
                            sliderInstance.removeAllSlides();
                            json.listings.forEach(item => {
                                sliderInstance.appendSlide(`<div class="swiper-slide">${item}</div>`);
                            })
                            sliderInstance.update();
                            sliderSection.classList.remove('hide');
                            const listings = document.querySelectorAll('[data-target="custom_post"]');
                            if (listings) {
                                listings.forEach(listing => {
                                    const addToFavouritesButton = listing.querySelector('[data-target="add_to_favourites"]');
                                    if (addToFavouritesButton) {
                                        addToFavouritesButton.addEventListener('click', (e) => {
                                            addToFavourites(listing).then(() => {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                addToFavouritesButton.classList.toggle('liked')
                                            })
                                        })
                                    }
                                })
                            }
                        }
                        else {
                            sliderSection.classList.add('hide')
                        }
                    })
            }
            Swiper.use(Manipulation);
            sliderInstance = new Swiper(sliderResult, options);
        }
    }

    if (document.readyState === "interactive" || document.readyState === "complete") {
        if (!window.acf) {
            initializeBlock()
        }
        else {
            window.acf.addAction('render_block_preview/type=office-space-calculator-section', initializeBlock);
        }
    }
    else {
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.acf) {
                initializeBlock()
            }
            else {
                window.acf.addAction('render_block_preview/type=office-space-calculator-section', initializeBlock);
            }
        })
    }
})()
