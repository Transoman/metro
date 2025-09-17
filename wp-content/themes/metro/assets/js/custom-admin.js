class YoastModificator {
    #page_content;
    #page_id;
    #modificator_name = "ContentModificator";

    constructor() {
        if (typeof YoastSEO === "undefined" || typeof wp === "undefined") {
            return;
        }

        this.getPostID();

        this.registerYoastModification();

        this.fetchContent().then(() => {
            YoastSEO.app.registerModification("content", ()=>{
                return this.#page_content
            }, this.#modificator_name, 10);
        });
    }

    getPostID() {
        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        this.#page_id = urlParams.get('post');
    }

    registerYoastModification() {
        YoastSEO.app.registerPlugin(this.#modificator_name, { status: "ready" });
    }

    async fetchContent() {
        const apiUrl = `/wp-json/wp/v2/pages/${this.#page_id}`;

        try {
            const response = await fetch(apiUrl);
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            const data = await response.json();
            this.#page_content = data.content.rendered;
        } catch (error) {
            console.error('Error:', error);
        }
    }
}
document.addEventListener('DOMContentLoaded', (e) => {
    if (typeof YoastSEO !== "undefined" && typeof wp !== "undefined") {
        new YoastModificator();
    }
})

