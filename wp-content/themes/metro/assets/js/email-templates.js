import grapesjs from 'grapesjs';
import plugin from 'grapesjs-preset-newsletter';

const initEditor = (element, hiddenTextarea) => {
    const existingHtml = hiddenTextarea.value

    const editor = grapesjs.init({
        container: element,
        plugins: [plugin],
        storageManager: false,
        components: existingHtml
    });
    window.testEditor = editor;

    editor.on('update', () => {
        let newHtml = editor.runCommand('gjs-get-inlined-html');
        hiddenTextarea.value = newHtml;
    })
}

(() => {
    const initializeBlock = () => {
        const tabsElement = document.querySelector('[data-target="tabs"]');
        const contentsElement = document.querySelector('[data-target="contents"]');

        const form = document.querySelector('[data-target="email_templates"]');

        if (tabsElement && contentsElement) {
            const contents = contentsElement.querySelectorAll('.template');
            const tabs = tabsElement.querySelectorAll('button');

            tabs.forEach((tab, idx) => {
                tab.addEventListener('click', (e) => {
                    contents.forEach(item => item.classList.remove('active'));
                    contents[idx].classList.add('active');
                })
            })


            contents.forEach(content => {
                const hiddenTextarea = content.querySelector('textarea');
                const editorElement = content.querySelector('div');
                initEditor(editorElement, hiddenTextarea);
            })
        }

        if (form) {
            form.addEventListener('keypress', (e) => {
                if (e.code === 'Enter') {
                    e.preventDefault();
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