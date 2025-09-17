import { addToFavourites, Steps, togglePasswordField, wpcf7CustomValidation } from "../../assets/js/helpers"

(() => {
    let stepsInstance = null;
    const documentHeight = () => {
        const doc = document.documentElement
        doc.style.setProperty('--doc-height', `${window.innerHeight}px`);
    }
    window.addEventListener('resize', documentHeight);

    const setHeightOfHeader = (header) => {
        document.documentElement.style.setProperty('--header-height', `${header.offsetHeight}px`);
    }

    const headerBehavior = () => {
        const mainHeader = document.querySelector('.front-header');
        if (!mainHeader) {
            return;
        }
        const burger = document.querySelector('[data-target="burger"]');
        const menu = document.querySelector('[data-target="header-menu"]');
        const backMenuButton = document.querySelector('[data-target="back-menu"]');
        const menuLists = menu.querySelectorAll('nav > ul:first-of-type > li');
        const authorizationButtons = document.querySelectorAll('[data-target="authorization_button"]');
        const overlay = document.querySelector('[data-target="overlay"]');
        const headerSearchForm = document.querySelector('[data-target="header_search_bar"]') || document.querySelector('[data-target="header_search_bar_search_page"]') || document.querySelector('[data-target="search_form_section"]');
        const authorizedButton = document.querySelector('[data-target="authorized_button"]');
        const authorizedMenu = document.querySelector('[data-target="authorized_menu"]');
        const headerSearchBarButtons = document.querySelectorAll('[data-target="show_search_bar"]');
        const authorizionPopup = document.querySelector('[data-target="authorization_poup"]');
        window.MMOverlay = overlay;
        window.MMMenu = menu;
        window.MMHeader = mainHeader
        if (burger && menu) {
            burger.addEventListener('click', () => {
                menu.classList.toggle('active');
                burger.classList.toggle('active');
                document.body.classList.toggle('freeze');
                if (headerSearchForm) {
                    headerSearchForm.classList.toggle('hide');
                }
            })
            window.addEventListener('resize', () => {
                if (window.innerWidth > 1200 && menu.classList.contains('active') && document.body.classList.contains('freeze') && burger.classList.contains('active')) {
                    menu.classList.remove('active');
                    document.body.classList.remove('freeze');
                    burger.classList.remove('active');
                    backMenuButton.classList.remove('show');
                    if (headerSearchForm) {
                        headerSearchForm.classList.remove('hide');
                        headerSearchForm.classList.remove('active');
                    }
                }
            })
        }
        const linkEventHandler = (e) => {
            const menuList = e.target.menuList;
            if (!menuList.classList.contains('active')) {
                e.preventDefault();
                e.target.menuList.classList.add('active');
                backMenuButton.classList.add('show');
            }
        }

        if (menuLists) {
            menuLists.forEach(menuList => {
                const link = menuList.querySelector('a');
                const subMenu = menuList.querySelector('ul');
                if (link.href.includes('#') && subMenu) {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        menuList.classList.add('active');
                        backMenuButton.classList.add('show');
                        window.addEventListener('resize', () => {
                            if (menuList.classList.contains('active')) {
                                menuList.classList.remove('active');
                                backMenuButton.classList.remove('show');
                            }
                        })
                    })
                }
                if (window.innerWidth < 1200 && link && subMenu) {
                    link.subMenu = subMenu
                    link.menuList = menuList
                    link.addEventListener('click', linkEventHandler);
                }
                window.addEventListener('resize', (e) => {
                    if (window.innerWidth < 1200 && link && subMenu) {
                        link.subMenu = subMenu;
                        link.menuList = menuList
                        link.addEventListener('click', linkEventHandler)
                    } else {
                        link.removeEventListener('click', linkEventHandler);
                    }
                })
            })
        }

        if (authorizationButtons && authorizionPopup) {
            authorizationButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    authorizionPopup.classList.add('show');
                    overlay.classList.add('show');
                    document.body.classList.add('overlay', 'freeze');
                })
            })

            const closePopupButton = authorizionPopup.querySelector('[data-target="close_popup"]');
            if (closePopupButton) {
                closePopupButton.addEventListener('click', (e) => {
                    authorizionPopup.classList.remove('show');
                    overlay.classList.remove('show');
                    document.body.classList.remove('overlay', 'freeze');
                    stepsInstance.clearAllFormFields();
                })
            }

            window.addEventListener('click', (e) => {
                if (authorizionPopup.classList.contains('show') && !authorizionPopup.contains(e.target) && !authorizionPopup.classList.contains('popup-inline')) {
                    authorizionPopup.classList.remove('show');
                    overlay.classList.remove('show');
                    document.body.classList.remove('overlay', 'freeze');
                    stepsInstance.clearAllFormFields();
                }
            })
        }

        if (headerSearchBarButtons) {
            headerSearchBarButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    menu.classList.toggle('active');
                    burger.classList.toggle('active');
                    if (headerSearchForm) {
                        headerSearchForm.classList.remove('hide');
                        headerSearchForm.classList.add('active');
                    }
                    document.body.classList.add('overlay');
                })
            })
        }

        if (authorizedButton && authorizedMenu) {
            authorizedButton.addEventListener('click', (e) => {
                authorizedMenu.classList.toggle('active');
                backMenuButton.classList.toggle('show')
            })
            window.addEventListener('resize', () => {
                if (window.innerWidth > 1200 && authorizedMenu.classList.contains('active')) {
                    authorizedMenu.classList.remove('active');
                    document.body.classList.remove('freeze');
                }
            })
        }

        backMenuButton.addEventListener('click', () => {
            backMenuButton.classList.remove('show');
            menuLists.forEach(menuList => menuList.classList.remove('active'))
            if (authorizedMenu) {
                authorizedMenu.classList.remove('active')
            }
        })

        window.addEventListener('scroll', (e) => {
            if (window.scrollY > 0) {
                mainHeader.classList.add('high-z-index');
            } else {
                mainHeader.classList.remove('high-z-index');
            }
        })
        setHeightOfHeader(mainHeader);
        window.addEventListener('resize', (e) => {
            setHeightOfHeader(mainHeader);
        })

    }

    const initializeBlock = () => {
        const accessibilityPlusButton = document.querySelector('[data-action="resize-plus"]');
        const accessibilityMinusButton = document.querySelector('[data-action="resize-plus"]');
        if (accessibilityPlusButton) {
            accessibilityPlusButton.addEventListener('click', (e) => {
                setTimeout(() => {
                    if (document.body.className.includes('pojo-a11y-resize-font')) {
                        document.documentElement.classList.add('resized');
                    }
                    else {
                        document.documentElement.classList.remove('resized');
                    }
                }, 0)
            })
        }

        if (accessibilityMinusButton) {
            accessibilityMinusButton.addEventListener('click', (e) => {
                if (document.body.className.includes('pojo-a11y-resize-font')) {
                    document.documentElement.classList.add('resized');
                }
                else {
                    document.documentElement.classList.remove('resized');
                }
            })
        }


        documentHeight()

        if (!window.acf) {
            headerBehavior();
        }

        const formsWithPasswords = document.querySelectorAll('[data-target="password_input"]');

        if (formsWithPasswords) {

            formsWithPasswords.forEach(form => {
                const passwordInput = form.querySelector('input[data-field="password"]'),
                    showPasswordButton = form.querySelector('button[type="button"]');

                if (!passwordInput || !showPasswordButton) {
                    return
                }

                togglePasswordField(passwordInput, showPasswordButton);
            })

        }

        const listings = document.querySelectorAll('[data-target="custom_post"]');

        if (listings) {
            listings.forEach(listing => {
                const addToFavouritesButton = listing.querySelector('[data-target="add_to_favourites"]');

                if (addToFavouritesButton) {
                    addToFavouritesButton.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        addToFavourites(listing)
                            .then(() => {
                                addToFavouritesButton.classList.toggle('liked');
                            })
                    })
                }
            })
        }

        document.addEventListener('load', function() {
            const phoneFields = document.querySelectorAll('.wpcf7 input[type="tel"]');
            if (phoneFields) {
                phoneFields.forEach(field => {
                    field.value = (mm_ajax_object.hasOwnProperty('user_phone')) ? mm_ajax_object.user_phone : '';
                })
            }
        });

        const simpleContactForm = document.querySelector('[data-target="simple_contact_form"]');
        if (simpleContactForm) {
            const closeButton = simpleContactForm.querySelector('[data-target="close_popup"]');
            if (closeButton) {
                closeButton.addEventListener('click', (e) => {
                    window.MMOverlay.classList.remove('show');
                    simpleContactForm.classList.remove('show');
                    document.body.classList.remove('freeze');
                })
            }

            const openSimpleFormLinks = document.querySelectorAll('a[href="#simple_contact_form"]');
            if (openSimpleFormLinks) {
                openSimpleFormLinks.forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        window.MMOverlay.classList.add('show');
                        simpleContactForm.classList.add('show');
                        document.body.classList.add('freeze');
                    })
                })
            }
        }

        const steps = document.querySelectorAll('[data-target="steps"]');
        if (steps) {
            steps.forEach(step => {
                if (step.classList.contains('footer')) {
                    stepsInstance = new Steps(step);
                }
                else {
                    new Steps(step);
                }
            });
        }

        const registrationNotification = document.querySelector('[data-target="global_notification"]');
        if (registrationNotification) {
            let called = false;

            const clearNotification = () => {
                registrationNotification.remove();
                const data = new FormData();
                data.append('action', 'clear_notification');
                fetch(mm_ajax_object.ajaxURL, {
                    method: "POST",
                    body: data,
                    credentials: 'same-origin'
                })
            }

            const closeBtn = registrationNotification.querySelector('button');
            closeBtn.addEventListener('click', (e) => {
                called = true;
                clearNotification()
            })

            setTimeout(() => {
                if (!called) {
                    clearNotification()
                }
            }, 10000);
        }

        document.querySelectorAll('form.wpcf7-form').forEach(form => {
            let initialized = false;
            form.addEventListener('wpcf7submit', (e) => {
                if (!initialized) {
                    new wpcf7CustomValidation(form);
                    initialized = true;
                }
            })
        })
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