(() => {
	const initializeBlock = () => {
		const resendForm = document.querySelector('[data-target="resend_form"]'),
			resendButton = document.querySelector('[data-target="resend_submit"]');

		if (resendForm && resendButton) {
			const customEvent = new CustomEvent('preventedSubmit');

			resendForm.addEventListener('preventedSubmit', (e) => {
				const data = new FormData(e.target);
				fetch(mm_ajax_object.ajaxURL, {
					method: 'POST',
					credentials: 'same-origin',
					body: data
				})
					.then(response => response.json())
					.then(json => {
						console.log(json)
					})
					.catch(err => console.error(err))
			})

			resendButton.addEventListener('click', (e) => {
				resendButton.setAttribute('disabled', true);
				resendForm.dispatchEvent(customEvent)
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
