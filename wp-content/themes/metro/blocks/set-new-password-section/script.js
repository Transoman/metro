import { ajaxForm } from "../../assets/js/helpers";

(() => {
	const initializeBlock = () => {
		const setPasswordForm = document.querySelector('[data-target="set_password"]');
		new ajaxForm(setPasswordForm, true);
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