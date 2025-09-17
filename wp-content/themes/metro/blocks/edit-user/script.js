(() => {
	const initializeBlock = () => {
		const editUserForm = document.querySelector(
			'[data-target="edit_user_form"]'
		);
		if (editUserForm) {
			let oldData = {};
			const cancelButton = editUserForm.querySelector('[data-target="cancel"]');

			const passInputs = editUserForm.querySelectorAll(
				'[data-target="pass_input"]'
			);

			const allFields = editUserForm.querySelectorAll(".field input");

			const submitButton = editUserForm.querySelector('button[type="submit"]');
			passInputs.forEach((passInput) => {
				const input = passInput.querySelector("input");
				const button = passInput.querySelector("button");
				let toggleState = false;
				if (input && button) {
					button.addEventListener("click", (e) => {
						toggleState = !toggleState;
						if (toggleState) {
							input.type = "text";
						} else {
							input.type = "password";
						}
					});
				}
			});

			allFields.forEach((field) => {
				field.addEventListener("input", (e) => {
					submitButton.innerText = "Save Changes";
				});
			});

			editUserForm.addEventListener("submit", (e) => {
				e.preventDefault();
				submitButton.classList.add("loading");
				const data = new FormData(e.target);
				removeAllErrorMessage(e.target);

				fetch(mm_ajax_object.ajaxURL, {
					method: "POST",
					credentials: "same-origin",
					body: data,
				})
					.then((response) => response.json())
					.then((json) => {
						submitButton.classList.remove("loading");
						if (json.invalid && json.messages) {
							showErrors(e.target, json);
							scrollToFirstErrorField(e.target);
						}
						if (json.success == true) {
							submitButton.innerText = "Saved";
						}
					});
			});

			const showErrors = (form, json) => {
				json.invalid.forEach((item) => {
					const input = form.querySelector(`input[name=${item.field}]`);
					input.classList.add("invalid");
					if (item.field.includes("password")) {
						const element = form.querySelector(
							`input[name=${item.field}]`
						).parentElement;
						element.insertAdjacentHTML(
							"afterend",
							getErrorMessage(json.messages, item.id)
						);
					} else {
						input.insertAdjacentHTML(
							"afterend",
							getErrorMessage(json.messages, item.id)
						);
					}
				});
			};

			const getErrorMessage = (messages, id) => {
				let result;
				messages.forEach((message) => {
					if (message.id === id) {
						result = message.html;
					}
				});
				return result;
			};

			const scrollToFirstErrorField = (form) => {
				const input = form.querySelector('input[class="invalid"]');
				const parent = input.offsetParent;
				window.scrollTo({
					top: parent.offsetTop - MMHeader.offsetHeight,
					left: 0,
					behavior: "smooth",
				});
			};

			const removeAllErrorMessage = (form) => {
				const errors = form.querySelectorAll('span[class="error"]');
				const inputs = form.querySelectorAll('input[class="invalid"]');
				inputs.forEach((input) => {
					input.classList.remove("invalid");
				});
				if (errors) {
					errors.forEach((error) => {
						error.remove();
					});
				}
			};

			const storeOldData = (form, object) => {
				const inputs = form.querySelectorAll(
					'input:not([type="hidden"], [type="password"])'
				);
				inputs.forEach((input) => {
					object[`${input.name}`] = input.value;
				});
			};
			storeOldData(editUserForm, oldData);

			const cancelNewValues = (form, data) => {
				for (const key in data) {
					const input = form.querySelector(`input[name=${key}]`);
					input.value = data[key];
				}
			};

			cancelButton.addEventListener("click", (e) => {
				cancelNewValues(editUserForm, oldData);
			});

			const deleteButton = editUserForm.querySelector(
				'[data-target="delete_account"]'
			);
			const confirmation = editUserForm.querySelector(
				'[data-target="confirmation"]'
			);
			if (deleteButton && confirmation) {
				deleteButton.addEventListener("click", (e) => {
					deleteButton.classList.add("hide");
					confirmation.classList.remove("hide");
				});

				const cancelConfirmation = confirmation.querySelector(
					'[data-target="cancel"]'
				);
				const confirmConfirmation = confirmation.querySelector(
					'[data-target="confirm"]'
				);
				if (cancelConfirmation && confirmConfirmation) {
					cancelConfirmation.addEventListener("click", (e) => {
						deleteButton.classList.remove("hide");
						confirmation.classList.add("hide");
					});

					confirmConfirmation.addEventListener("click", (e) => {
						const data = new FormData();
						data.append("action", "mm_delete_user");
						data.append("user_id", confirmConfirmation.dataset.userId);
						fetch(mm_ajax_object.ajaxURL, {
							body: data,
							method: "POST",
							credentials: "same-origin",
						})
							.then((response) => response.json())
							.then((json) => {
								if (json.redirect_to) {
									window.location = json.redirect_to;
								}
							});
					});
				}
			}
		}
	};

	if (
		document.readyState === "interactive" ||
		document.readyState === "complete"
	) {
		initializeBlock();
	} else {
		document.addEventListener("DOMContentLoaded", () => {
			initializeBlock();
		});
	}
})();
