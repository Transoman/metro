import { SearchForm, GoogleRatingRender } from "../../assets/js/helpers";
(() => {
	const initializeBlock = () => {
		let initForm = false;
		const sectionWithSearchFrom = document.querySelector('[data-target="search_form_section"]');

		const rating = sectionWithSearchFrom.querySelector('[data-google-rating]');

		if (rating) {
			GoogleRatingRender(rating);
		}

		if (sectionWithSearchFrom) {
			const formFields = sectionWithSearchFrom.querySelectorAll('[data-target="form_field"]');
			const events = ['mouseover', 'mouseenter', 'click'];
			formFields.forEach(formField => {
				events.forEach(event =>{
					formField.addEventListener(event, (e)=>{
						if(initForm){
							return
						}
						new SearchForm(sectionWithSearchFrom);
						initForm = true;
					})
				})
			})

			const searchButton = sectionWithSearchFrom.querySelector('[data-target="search_listings"]');
			if(searchButton){
				searchButton.addEventListener('click', (e)=>{
					if(initForm){
						return
					}
					new SearchForm(sectionWithSearchFrom).toggleFormState();
					initForm = true
				})
			}
		}
	}

	if (document.readyState === "interactive" || document.readyState === "complete") {
		initializeBlock()
	}
	else {
		document.addEventListener('DOMContentLoaded', (e) => {
			if (!window.acf) {
				initializeBlock()
			}
			else {
				window.acf.addAction('render_block_preview/type=home-hero-section', initializeBlock)
			}
		})
	}
})()

