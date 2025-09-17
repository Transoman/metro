(() => {
    class A11y {
        constructor(tool) {
            this.tool = tool;

            if (this.tool) {
                this.setEvents(this.tool);
            }

            this.sizeCounter = 13;
            this.minSize = 12;
            this.maxSize = 20;
            this.initialBodyClass = document.body.className;
        }

        setEvents(tool) {
            const toggleButton = tool.querySelector('[data-target="toggle"]');
            if (toggleButton) {
                this.toggleEvent(toggleButton);
            }
            const increaseButton = tool.querySelector('[data-target="increase_size"]');
            if (increaseButton) {
                this.increaseEvent(increaseButton);
            }
            const decreaseButton = tool.querySelector('[data-target="decrease_size"]');
            if (decreaseButton) {
                this.decreaseEvent(decreaseButton);
            }
            const grayscaleButton = tool.querySelector('[data-target="grayscale"]');
            if (grayscaleButton) {
                this.toggleBody(grayscaleButton, 'a11y-grayscale');
            }
            const highContrastButton = tool.querySelector('[data-target="high_contrast"]');
            if (highContrastButton) {
                this.toggleBody(highContrastButton, 'a11y-high-contrast');
            }
            const negativeContrastButton = tool.querySelector('[data-target="negative_contrast"]');
            if (negativeContrastButton) {
                this.toggleBody(negativeContrastButton, 'a11y-negative-contrast');
            }
            const lightBackgroundButton = tool.querySelector('[data-target="light_background"]');
            if (lightBackgroundButton) {
                this.toggleBody(lightBackgroundButton, 'a11y-light-background');
            }
            const linksUnderlineButton = tool.querySelector('[data-target="links_underline"]');
            if (linksUnderlineButton) {
                this.toggleBody(linksUnderlineButton, 'a11y-links-underline');
            }
            const resetButton = tool.querySelector('[data-target="reset"]');
            if (resetButton) {
                this.resetEvent(resetButton);
            }
        }

        toggleEvent(button) {
            button.addEventListener('click', (e) => {
                this.tool.classList.toggle('active');
            })
        }

        increaseEvent(button) {
            button.addEventListener('click', (e) => {
                const oldSize = this.sizeCounter;
                this.sizeCounter = (this.sizeCounter < this.maxSize) ? this.sizeCounter + 1 : this.sizeCounter;
                this.changeSize(this.sizeCounter, oldSize);
            })
        }

        decreaseEvent(button) {
            button.addEventListener('click', (e) => {
                const oldSize = this.sizeCounter;
                this.sizeCounter = (this.sizeCounter > this.minSize) ? this.sizeCounter - 1 : this.sizeCounter;
                this.changeSize(this.sizeCounter, oldSize);
            })
        }

        changeSize(newSize, oldSize) {
            document.body.classList.remove('a11y-resize-font-' + oldSize + '0');
            document.body.classList.add('a11y-resize-font-' + newSize + '0');
            if (newSize > this.minSize) {
                document.documentElement.classList.add('resized');

            }
            else {
                document.documentElement.classList.remove('resized');
            }
        }

        toggleBody(button, className) {
            button.addEventListener('click', (e) => {
                document.body.classList.toggle(className);
            })
        }

        grayscaleEvent(button) {
            button.addEventListener('click', (e) => {
                document.body.classList.toggle('a11y-grayscale');
            })
        }

        highContrastEvent(button) {
            button.addEventListener('click', (e) => {
                document.body.classList.toggle('a11y-high-contrast');
            });
        }

        negativeContrastEvent(button) {
            button.addEventListener('click', (e) => {
                document.body.classList.toggle('a11y-negative-contrast');
            })
        }

        resetEvent(button) {
            button.addEventListener('click', (e) => {
                document.documentElement.classList.remove('resized');
                document.body.className = this.initialBodyClass;
            })
        }
    }

    const initializeBlock = () => {
        const a11y = document.querySelector('[data-target="a11y"]');
        if (a11y) {
            new A11y(a11y);
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
