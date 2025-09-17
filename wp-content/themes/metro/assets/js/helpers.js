class SearchForm {
	constructor(element) {
		this.mobileBreakpoint = 576;
		this.mobileStorage = [];
		this.section = element;
		this.form = this.section.querySelector("form");
		this.formFields = this.form.querySelectorAll('[data-target="form_field"]');
		this.searchListingsButton = this.section.querySelector(
			'[data-target="search_listings"]'
		);
		this.initForm();
	}

	initForm() {
		this.setEventsForFields(this.formFields);
		this.setEventOnMobileButton(this.searchListingsButton);
		this.checkClickOfElement(this.section);
		this.behaviorOfClearAllButtonOfWholeForm(this.section, this.formFields);
		this.behaviorOfClearAllButtonsOfFields(this.formFields);
		this.behaviorOfCancelButtons(this.formFields);
		this.behaviorOfMainCancelButton(this.section);
		this.behaviorOfApplyButtons(this.formFields);
		this.behaviorOfBackToMenu(this.formFields);
		this.resizeForm(this.section, this.mobileBreakpoint);
		this.setGA4Event(this.form);
	}

	checkClickOfWrapper(formField) {
		window.addEventListener("click", function (e) {
			if (!formField.contains(e.target)) {
				formField.classList.remove("active");
			}
		});
	}

	setEventsForFields(formFields) {
		formFields.forEach((formField) => {
			if (formField.getAttribute("data-init") !== "true") {
				const placeholder = formField.querySelector(".placeholder");
				const checkboxWrappers = formField.querySelectorAll(
					'[data-target="wrapper"]'
				);
				this.setEventForCheckboxes(checkboxWrappers, formField);
				this.checkClickOfWrapper(formField);
				placeholder.addEventListener("click", () => {
					if (window.innerWidth < 992) {
						formField.classList.toggle("active");
						if (window.innerWidth < this.mobileBreakpoint) {
							this.addRecordToMobileStorage(formField);
						}
					}
				});
				formField.setAttribute("data-init", true);
			}
		});
	}

	setEventForCheckboxes(wrappers, formField) {
		wrappers.forEach((wrapper) => {
			const rangeMode = formField.getAttribute("data-range") === "true";
			const singleMode = formField.getAttribute("data-single") === "true";
			const select_all_checkbox = wrapper.querySelector(
				'[data-target="select_all"]'
			);
			if (select_all_checkbox) {
				this.selectAllCheckboxes(
					wrapper,
					select_all_checkbox,
					formField,
					singleMode
				);
			}
			const default_checkboxes = wrapper.querySelectorAll(
				'[data-target="checkbox"]'
			);
			const groups_checkboxes = wrapper.querySelectorAll(
				'[data-target="group_checkbox"]'
			);
			if (default_checkboxes) {
				this.behaviorOfDefaultCheckboxes(
					default_checkboxes,
					select_all_checkbox,
					wrapper,
					formField,
					rangeMode,
					singleMode
				);
			}
			if (groups_checkboxes) {
				this.behaviorOfGroupCheckboxes(
					groups_checkboxes,
					select_all_checkbox,
					wrapper,
					formField,
					rangeMode,
					singleMode
				);
			}
			wrapper.setAttribute("data-init", true);
		});
	}

	selectAllCheckboxes(wrapper, select_all, formField) {
		const checkboxElements = wrapper.querySelectorAll(
			'.checkbox:not([data-target="select_all"])'
		);
		if (select_all) {
			const input = select_all.querySelector("input");
			input.addEventListener("input", () => {
				checkboxElements.forEach((checkboxElement) => {
					const checkbox = checkboxElement.querySelector(
						'input[type="checkbox"]'
					);
					checkbox.checked = input.checked;
				});
				if (window.innerWidth > this.mobileBreakpoint) {
					this.behaviorOfPlaceholder(formField);
				}
			});
			input.checked = this.AreAllCheckboxesChecked(checkboxElements);
		}
	}

	AreAllCheckboxesChecked(checkboxElements) {
		let result = true;
		checkboxElements.forEach((checkboxElement) => {
			const checkbox = checkboxElement.querySelector('input[type="checkbox"]');
			if (!checkbox.checked) {
				result = false;
			}
		});
		return result;
	}

	behaviorOfDefaultCheckboxes(
		checkboxes,
		select_all = null,
		wrapper,
		formField,
		rangeMode,
		singleMode
	) {
		if (checkboxes) {
			let selectAllElement;
			if (select_all) {
				selectAllElement = select_all.querySelector('input[type="checkbox"]');
			}
			checkboxes.forEach((checkbox) => {
				const input = checkbox.querySelector('input[type="checkbox"]');
				checkbox.addEventListener("click", (e) => {
					e.preventDefault();
					input.checked = !input.checked;
					if (rangeMode) {
						this.behaviorOfRangeMode(checkboxes);
					}
					if (singleMode) {
						this.behaviorOfSingleMode(checkboxes, checkbox);
					}
					if (select_all) {
						selectAllElement.checked = this.isSelectAllCheckboxChecked(wrapper);
					}
					if (window.innerWidth > this.mobileBreakpoint) {
						this.behaviorOfPlaceholder(formField);
					}
				});
			});
		}
	}

	behaviorOfSingleMode(checkboxes, checkbox) {
		const anotherCheckboxes = Array.from(checkboxes).filter(
			(item) => item !== checkbox
		);
		anotherCheckboxes.forEach((checkbox) => {
			const input = checkbox.querySelector('input[type="checkbox"]');
			if (input.checked) {
				input.checked = false;
			}
		});
	}

	behaviorOfRangeMode(checkboxes) {
		const lengthOfChecked = this.getLengthOfCheckedCheckboxes(checkboxes);
		if (lengthOfChecked > 1) {
			const firstCheckedCheckbox = this.getFirstOrLastCheckbox(
				checkboxes,
				true
			);
			const lastCheckedCheckbox = this.getFirstOrLastCheckbox(
				checkboxes,
				false
			);
			const range = Array.from(checkboxes).slice(
				Array.from(checkboxes).indexOf(lastCheckedCheckbox),
				Array.from(checkboxes).indexOf(firstCheckedCheckbox)
			);
			range.forEach((checkbox) => {
				const input = checkbox.querySelector('input[type="checkbox"]');
				input.checked = true;
			});
		}
	}

	getFirstOrLastCheckbox(checkboxes, isFirst) {
		checkboxes = isFirst ? checkboxes : Array.from(checkboxes).reverse();
		let result;
		checkboxes.forEach((checkbox) => {
			const input = checkbox.querySelector('input[type="checkbox"]');
			if (input.checked) {
				result = checkbox;
			}
		});
		return result;
	}

	behaviorOfGroupCheckboxes(groups, select_all, wrapper, formField, rangeMode) {
		if (select_all) {
			const select_all_input = select_all.querySelector(
				'input[type="checkbox"]'
			);
			groups.forEach((group) => {
				const parent_checkbox = group.querySelector(
					'[data-target="group_parent_checkbox"]'
				);
				const accordion_button = group.querySelector(
					'[data-target="accordion_button"]'
				);
				const checkboxes = group.querySelectorAll('[data-target="checkbox"]');
				const parent_input = parent_checkbox.querySelector(
					'input[type="checkbox"]'
				);
				this.behaviorOfGroupAccordion(group, accordion_button);
				this.behaviorOfGroupChildCheckbox(
					checkboxes,
					parent_input,
					select_all_input,
					wrapper
				);
				parent_input.addEventListener("input", () => {
					for (const checkbox of checkboxes) {
						const input = checkbox.querySelector('input[type="checkbox"]');
						input.checked = parent_input.checked;
						select_all_input.checked = this.isSelectAllCheckboxChecked(wrapper);
						this.behaviorOfPlaceholder(formField);
					}
				});
			});
		}
	}

	behaviorOfGroupAccordion(group, button) {
		const accordion_content = group.querySelector(
			'[data-target="accordion_content"]'
		);
		accordion_content.style.setProperty(
			"--element-height",
			`${accordion_content.scrollHeight}px`
		);
		if (group && button) {
			button.addEventListener("click", () => {
				group.classList.toggle("open");
			});
		}
	}

	isSelectAllCheckboxChecked(wrapper) {
		const checkboxes = wrapper.querySelectorAll(
			'.checkbox:not([data-target="select_all"])'
		);
		let isSelectAllChecked = true;
		for (const checkbox of checkboxes) {
			const input = checkbox.querySelector('input[type="checkbox"]');
			if (!input.checked) {
				isSelectAllChecked = false;
			}
		}
		return isSelectAllChecked;
	}

	isParentCheckboxOfGroupChecked(checkboxes) {
		let isParentChecked = true;
		for (const checkbox of checkboxes) {
			const input = checkbox.querySelector('input[type="checkbox"]');
			if (!input.checked) {
				isParentChecked = false;
			}
		}
		return isParentChecked;
	}

	behaviorOfGroupChildCheckbox(checkboxes, parent, select_all, wrapper) {
		checkboxes.forEach((checkbox) => {
			checkbox.addEventListener("click", () => {
				parent.checked = this.isParentCheckboxOfGroupChecked(checkboxes);
				select_all.checked = this.isSelectAllCheckboxChecked(wrapper);
			});
		});
	}

	setEventOnMobileButton(button) {
		if (button) {
			if (button.getAttribute("data-info") !== "true") {
				button.setAttribute("data-info", true);
				button.addEventListener("click", () => {
					this.toggleFormState();
				});
			}
		}
	}

	getLengthOfCheckedCheckboxes(checkboxes) {
		let counter = 0;
		checkboxes.forEach((item) => {
			const input = item.querySelector("input");
			if (input.checked) {
				counter++;
			}
		});
		return counter;
	}

	getStringForPlaceholderForRangeMode(formField) {
		let result;
		const checkboxes = formField.querySelectorAll('[data-target="checkbox"]');
		const firstCheckedCheckbox = this.getFirstOrLastCheckbox(checkboxes, false);
		const lastCheckedCheckbox = this.getFirstOrLastCheckbox(checkboxes, true);
		const allCheckboxes = formField.querySelectorAll(
			'[data-target="checkbox"] input[type="checkbox"]'
		);
		const checkedCheckboxes = formField.querySelectorAll(
			'[data-target="checkbox"] input[type="checkbox"]:checked'
		);
		const tempText = formField.getAttribute("data-name");
		if (
			firstCheckedCheckbox &&
			lastCheckedCheckbox &&
			allCheckboxes.length !== checkedCheckboxes.length
		) {
			const nameOfFirstCheckedCheckbox = firstCheckedCheckbox.querySelector(
				'input[type="checkbox"]'
			).value;
			const nameOfLastCheckedCheckbox = lastCheckedCheckbox.querySelector(
				'input[type="checkbox"]'
			).value;
			const firstPart = this.getNumberFromStringForPlaceholderForRangeMode(
				nameOfFirstCheckedCheckbox,
				true
			);
			const lastPart = this.getNumberFromStringForPlaceholderForRangeMode(
				nameOfLastCheckedCheckbox,
				false
			);
			result =
				firstPart !== lastPart ? firstPart + " - " + lastPart : firstPart;
			result += " SF";
		} else {
			result = this.formatStringOfCheckboxes(tempText, true, 0);
		}
		return result;
	}

	getNumberFromStringForPlaceholderForRangeMode(string, isFirst) {
		let result = isFirst
			? string.includes("between")
				? string.match(/[0-9]+/)[0]
				: string.includes("max")
					? "<" + string.match(/[0-9]+/)[0]
					: ">" + string.match(/[0-9]+/)[0]
			: string.includes("between")
				? string.match(/(\d+)\D*$/)[0]
				: string.includes("max")
					? "<" + string.match(/[0-9]+/)[0]
					: ">" + string.match(/[0-9]+/)[0];
		result = this.numberWithCommas(result);
		return result;
	}

	numberWithCommas(x) {
		return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
	}

	behaviorOfPlaceholder(formField) {
		const singleMode = formField.getAttribute("data-single") === "true";
		const rangeMode = formField.getAttribute("data-range") === "true";
		const placeholder = formField.querySelector(".placeholder span");
		const tempText = formField.getAttribute("data-name");
		const allCheckboxes = formField.querySelectorAll(
			'[data-target="checkbox"] input[type="checkbox"]'
		);
		const checkedCheckboxes = formField.querySelectorAll(
			'[data-target="checkbox"] input[type="checkbox"]:checked'
		);
		if (!singleMode && !rangeMode) {
			placeholder.innerText = this.formatStringOfCheckboxes(
				tempText,
				checkedCheckboxes.length === allCheckboxes.length ||
				checkedCheckboxes.length === 0,
				checkedCheckboxes.length
			);
		} else if (rangeMode && !singleMode) {
			placeholder.innerText =
				this.getStringForPlaceholderForRangeMode(formField);
		} else {
			const checkedCheckbox = formField.querySelector(
				'[data-target="checkbox"] input:checked'
			);
			if (
				checkedCheckbox &&
				allCheckboxes.length !== checkedCheckboxes.length
			) {
				const checkedCheckboxID = checkedCheckbox.id;
				const label = formField.querySelector(
					`label[for="${checkedCheckboxID}"]`
				);
				placeholder.innerText = label.innerHTML;
			} else {
				placeholder.innerText = tempText;
			}
		}
	}

	formatStringOfCheckboxes(string, boolean, number) {
		string = string.charAt(0).toUpperCase() + string.slice(1);
		if (boolean) {
			string = `All ${string}`;
		} else {
			string = string + ` (${number})`;
		}
		return string;
	}

	toggleFormState() {
		this.section.classList.toggle("active");
		document.body.classList.toggle("overlay");
		document.body.classList.toggle("freeze");
	}

	checkClickOfElement(element) {
		const targetElement = element.querySelector(".form");
		const form = element.querySelector("form");
		const openButton = element.querySelector('[data-target="search_listings"]');
		targetElement.addEventListener("click", (e) => {
			if (
				(element.classList.contains("active") &&
					!form.contains(e.target) &&
					openButton &&
					!openButton.contains(e.target)) ||
				(element.classList.contains("active") && !form.contains(e.target))
			) {
				this.toggleFormState();
			}
		});
	}

	behaviorOfCancelButtons(formFields) {
		formFields.forEach((formField) => {
			const cancelButton = formField.querySelector(
				'[data-target="cancel_button"]'
			);
			if (cancelButton) {
				cancelButton.addEventListener("click", () => {
					if (formField.classList.contains("active")) {
						formField.classList.remove("active");
						this.section.classList.toggle("high-z-index");
						this.cancelChangeOfFormField(formField);
					}
				});
			}
		});
	}

	behaviorOfBackToMenu(formFields) {
		formFields.forEach((formField) => {
			const backToMenuButton = formField.querySelector(
				'[data-target="back_to_menu"]'
			);
			if (backToMenuButton) {
				backToMenuButton.addEventListener("click", () => {
					if (formField.classList.contains("active")) {
						formField.classList.remove("active");
						this.section.classList.toggle("high-z-index");
						this.cancelChangeOfFormField(formField);
					}
				});
			}
		});
	}

	behaviorOfApplyButtons(formFields) {
		formFields.forEach((formField) => {
			const singleMode = formField.getAttribute("data-single") === "true";
			const applyButton = formField.querySelector(
				'[data-target="apply_button"]'
			);
			if (applyButton) {
				applyButton.addEventListener("click", () => {
					if (formField.classList.contains("active")) {
						formField.classList.remove("active");
						this.section.classList.toggle("high-z-index");
						this.applyChangesOfFormField(formField);
					}
				});
			}
		});
	}

	applyChangesOfFormField(formField) {
		this.behaviorOfPlaceholder(formField);
		this.mobileStorage = [];
	}

	cancelChangeOfFormField() {
		this.mobileStorage.forEach((element) => {
			if (element.hasOwnProperty("checked")) {
				element.element.checked = element.checked;
			}
		});
		this.mobileStorage = [];
	}

	addRecordToMobileStorage(element) {
		this.mobileStorage = this.createRecordFromElement(element);
	}

	createRecordFromElement(element) {
		const inputs = element.querySelectorAll(".wrapper-list .checkbox input");
		const select = element.querySelectorAll("select");
		let fieldsArray = [inputs, select];
		fieldsArray = fieldsArray.filter((item) => item.length > 0).shift();
		let record = [];
		fieldsArray.forEach((field) => {
			record.push({ element: field, checked: field.checked });
		});
		return record;
	}

	behaviorOfClearAllButtonOfWholeForm(formElement, formFields) {
		const clearAllButton = formElement.querySelector(
			'[data-target="clear_all"]'
		);
		if (clearAllButton) {
			clearAllButton.addEventListener("click", () => {
				formFields.forEach((formField) => {
					this.dropValuesOfFormField(formField);
					this.behaviorOfPlaceholder(formField);
				});
			});
		}
	}

	checkAllCheckboxesOfForm(formFields) {
		formFields.forEach((formField) => {
			const singleMode = formField.getAttribute("data-single") === "true";
			this.checkAllCheckboxesOfFormField(formField, singleMode, singleMode);
		});
	}

	behaviorOfClearAllButtonsOfFields(formFields) {
		formFields.forEach((formField) => {
			const singleMode = formField.getAttribute("data-single") === "true";
			const clearAllButton = formField.querySelector(
				'[data-target="clear_field"]'
			);
			if (clearAllButton) {
				clearAllButton.addEventListener("click", () => {
					this.dropValuesOfFormField(formField);
				});
			}
		});
	}

	checkAllCheckboxesOfFormField(formField) {
		const checkboxes = formField.querySelectorAll('input[type="checkbox"]');
		checkboxes.forEach((checkbox) => {
			checkbox.checked = true;
		});
		this.behaviorOfPlaceholder(formField);
	}

	dropValuesOfFormField(formField) {
		const inputs = formField.querySelectorAll(".wrapper-list .checkbox input");
		inputs.forEach((input) => {
			input.checked = false;
		});
	}

	behaviorOfMainCancelButton(formElement) {
		const mainCancelButton = formElement.querySelector(
			'[data-target="cancel_form"]'
		);
		if (mainCancelButton) {
			mainCancelButton.addEventListener("click", () => {
				formElement.classList.remove("active");
				document.body.classList.remove("freeze");
				document.body.classList.remove("overlay");
			});
		}
	}

	resizeForm(form, breakpoint) {
		window.addEventListener("resize", () => {
			if (
				(window.innerWidth > breakpoint &&
					!MMMenu.classList.contains("active")) ||
				(window.innerWidth > breakpoint && !MMMenu.classList.contains("active"))
			) {
				form.classList.remove("active");
				document.body.classList.remove("overlay");
				document.body.classList.remove("freeze");
			}
		});
	}

	setGA4Event(form) {
		if (!form) {
			return false;
		}

		form.addEventListener("submit", (e) => {
			const object = {
				event: "search_listings",
				searchUses: [],
				searchLocations: [],
				searchPrices: [],
				searchSizes: [],
			};
			const formData = new FormData(e.target);
			for (let item of formData.entries()) {
				if (item[0].includes("uses")) {
					object.searchUses.push(item[1]);
				} else if (item[0].includes("locations")) {
					object.searchLocations.push(item[1]);
				} else if (item[0].includes("sizes")) {
					object.searchSizes.push(item[1]);
				} else if (item[0].includes("prices")) {
					object.searchPrices.push(item[1]);
				}
			}
			if (typeof window.dataLayer !== "undefined") {
				window.dataLayer.push(object);
			}
		});
	}
}

const slidersInit = (section, swiper, navigation) => {
	const element = section.querySelector(".swiper");
	const prevBtn = section.querySelector('[data-target="swiper_left"]');
	const nextBtn = section.querySelector('[data-target="swiper_right"]');
	const numberOfSlides = parseInt(section.getAttribute("data-swiper-slides"));
	const numberOfSpaceBetween = parseInt(
		section.getAttribute("data-swiper-space-between")
	);
	const numberOfTabletSlides = parseInt(
		section.getAttribute("data-swiper-tablet-slides")
	);

	let options = {
		freeMode: true,
		lazy: true,
		preloadImages: false,
		breakpoints: {
			300: {
				slidesPerView: 1.037,
			},
			577: {
				slidesPerView: numberOfTabletSlides,
			},
			992: {
				slidesPerView: numberOfSlides,
				freeMode: false,
			},
		},
	};

	if (prevBtn && nextBtn) {
		swiper.use(navigation);
		options.navigation = {
			nextEl: nextBtn,
			prevEl: prevBtn,
		};
	}

	if (numberOfSpaceBetween) {
		options.spaceBetween = numberOfSpaceBetween;
		options.breakpoints[300].spaceBetween = 20;
		options.breakpoints[300].slidesPerView = 1.11;
	}

	const initSlider = new swiper(element, options);
};

const GoogleRatingRender = (element) => {
	if (element) {
		const rating = parseFloat(element.getAttribute("data-google-rating"));
		const stars = element.querySelectorAll(".star");
		for (let i = 1; i <= stars.length; i++) {
			const inner = stars[i - 1].querySelector(".inner");
			if (rating / i >= 1) {
				inner.style.width = "100%";
			} else {
				inner.style.width = `${100 - (i - rating) * 100}%`;
			}
		}
	}
};

const addToFavourites = (listing) => {
	let listingID;
	if (typeof listing === "object") {
		listingID = listing.getAttribute("data-id");
	} else {
		listingID = listing;
	}

	let data = new FormData();
	data.append("listing_id", listingID);
	data.append("action", "mm_add_to_favourites");

	return fetch(mm_ajax_object.ajaxURL, {
		method: "POST",
		credentials: "same-origin",
		body: data,
	})
		.then((response) => response.json())
		.then((json) => {
			if (json.status === true && window.dataLayer) {
				window.dataLayer.push({
					event: "saved_listing",
				});
			}
		});
};

const changeWindowHistory = (link) => {
	window.history.pushState({}, "", link.href);
};

const isElementInViewport = (el) => {
	const rect = el.getBoundingClientRect();

	return (
		rect.top >= 0 &&
		rect.left >= 0 &&
		rect.bottom <=
		(window.innerHeight || document.documentElement.clientHeight) &&
		rect.right <= (window.innerWidth || document.documentElement.clientWidth)
	);
};

class YoutubeVideos {
	constructor(videos) {
		this.initializedScript = false;
		this.videos = videos;
		this.initializeVideos(this.videos);
	}

	initializeVideos(videos) {
		if (videos) {
			videos.forEach((video) => {
				const button = video.querySelector("button");
				const videoWrapper = video.querySelector(".video");
				const imageWrapper = video.querySelector(".image");
				const loader = video.querySelector(".loader");
				const videoID = video.getAttribute("data-video-id");
				let isIframeInserted =
					video.getAttribute("data-video-insert") == "false";
				let data = {
					element: videoWrapper.querySelector(".element-to-replace"),
					videoID: videoID,
				};
				button.addEventListener("click", async (e) => {
					try {
						if (isIframeInserted) {
							loader.classList.remove("hide");
							button.classList.add("hide");
							if (!this.initializedScript) {
								await this.insertIFrameApi();
							}
							const player = await this.playerHandler(data);
							imageWrapper.classList.add("hide");
							videoWrapper.classList.remove("hide");
							player.playVideo();
						}
					} catch (error) {
						console.log(error);
					}
				});
			});
		}
	}

	insertIFrameApi = () => {
		return new Promise((resolve, reject) => {
			if (!this.initializedScript) {
				const tag = document.createElement("script");
				tag.src = "https://www.youtube.com/iframe_api";

				const firstScriptTag = document.getElementsByTagName("script")[0];
				firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

				this.initializedScript = true;

				window.onYouTubeIframeAPIReady = () => {
					resolve();
				};

				tag.onerror = (error) => reject(error);
			}
		});
	};

	playerHandler = ({ element, videoID }) => {
		return new Promise((resolve, reject) => {
			const youtubePlayer = new YT.Player(element, {
				height: "100%",
				width: "100%",
				enablejsapi: 1,
				videoId: videoID,
				playerVars: {
					playsinline: 1,
					rel: 0,
				},
				events: {
					onReady: function () {
						resolve(youtubePlayer);
					},
					onStateChange: function (event) {
						if (typeof window.dataLayer !== "undefined") {
							const object = {
								event: "gtm.video",
							};
							switch (event.data) {
								case 1:
									object.custom_video_status = "start";
									window.dataLayer.push(object);
									break;
								case 0:
									object.custom_video_status = "complete";
									window.dataLayer.push(object);
									break;
							}
						}
					},
				},
			});
		});
	};
}

const togglePasswordField = (input, button) => {
	if (!input && !button) {
		return;
	}
	let state = false;
	["click", "focus"].forEach((event) => {
		input.addEventListener(event, (e) => {
			e.preventDefault();
			e.stopPropagation();
		});
	});
	button.addEventListener("click", (e) => {
		state = !state;
		input.type = state ? "text" : "password";
		button.classList.toggle("active");
	});
};

class ajaxForm {
	constructor(element = null, autoInit = true) {
		this.form = element;
		if (!this.form) {
			return;
		}
		this.emailField = this.form.querySelector('[data-field="email"]');
		this.passwordField = this.form.querySelector('[data-field="password"]');
		if (autoInit) {
			this.setEventOnForm(this.form);
		}
	}

	clearErrors() {
		if (this.emailField && this.emailField.classList.contains("invalid")) {
			this.emailField.classList.remove("invalid");
		}

		if (
			this.passwordField &&
			this.passwordField.classList.contains("invalid")
		) {
			this.passwordField.classList.remove("invalid");
		}

		const errors = this.form.querySelectorAll("span.error");
		if (errors) {
			errors.forEach((error) => error.remove());
		}
	}

	setEventOnForm(form, customHandler = "") {
		form.addEventListener("submit", (e) => {
			e.preventDefault();
			this.clearErrors();
			const data = new FormData(e.target);
			fetch(mm_ajax_object.ajaxURL, {
				method: "POST",
				credentials: "same-origin",
				body: data,
			})
				.then((response) => response.json())
				.then((json) =>
					customHandler == "" ? this.responseHandler(json) : customHandler(json)
				);
		});
	}

	responseHandler(json) {
		if (json.invalid) {
			if (this.emailField) {
				if (json.invalid.includes("email")) {
					this.emailField.classList.add("invalid");
				} else {
					this.emailField.classList.remove("invalid");
				}
			}

			if (this.passwordField) {
				if (json.invalid.includes("password")) {
					this.passwordField.classList.add("invalid");
				} else {
					this.passwordField.classList.remove("invalid");
				}
			}
		}
		if (json.messages) {
			for (let message in json.messages) {
				this.createErrorMessage(message, json.messages[message]);
			}
		}
		if (json.redirect) {
			window.location.href = json.redirect;
		}
	}

	resetForm(except = "") {
		if (this.emailField && except !== "email") {
			this.emailField.classList.remove("invalid");
			this.emailField.value = "";
		}
		if (this.passwordField && except !== "password") {
			this.passwordField.classList.remove("invalid");
			this.passwordField.value = "";
		}
		this.clearErrors();
	}

	createErrorMessage(fieldKey, message) {
		if (!fieldKey || !message) {
			return;
		}

		const field = this.form.querySelector(`[data-input="${fieldKey}"]`);
		if (field) {
			field.insertAdjacentHTML(
				"beforeend",
				`<span class="error">${message}</span>`
			);
		}
	}
}

const isAppleDevice = () => {
	const platform = navigator.platform.toLowerCase();
	return (
		platform.includes("mac") ||
		platform.includes("iphone") ||
		platform.includes("ipad")
	);
};

class Steps {
	constructor(stepsWrapper) {
		this.stepsWrapper = stepsWrapper;
		this.allSteps = this.stepsWrapper.querySelectorAll("[data-step]");
		this.firstStep = this.stepsWrapper.querySelector('[data-step="first"]');
		this.loginStep = this.stepsWrapper.querySelector('[data-step="login"]');
		this.registrationStep = this.stepsWrapper.querySelector(
			'[data-step="register"]'
		);
		this.resetPasswordStep = this.stepsWrapper.querySelector(
			'[data-step="reset"]'
		);
		this.sendResetPasswordStep = this.stepsWrapper.querySelector(
			'[data-step="send_reset"]'
		);
		this.stepsStorage = [];
		this.forms = [];
		this.setEventOnAllSteps(this.stepsWrapper);

		this.setEventOnFirstStep(this.firstStep);
		this.setEventsOnLoginStep(this.loginStep);
		this.setEventsOnRegisterStep(this.registrationStep);
		this.setEventsOnResetPasswordStep(this.resetPasswordStep);
		this.setEventsOnSendResetPasswordStep(this.sendResetPasswordStep);

		this.connectEmailFields(
			this.firstStep,
			this.loginStep,
			this.registrationStep,
			this.resetPasswordStep
		);
	}

	toggleSteps(nextStep, prevStep) {
		if (!this.allSteps && !nextStep) {
			return;
		}
		if (prevStep) {
			this.stepsStorage.push(prevStep);
		}
		Array.from(this.allSteps)
			.filter((item) => item.getAttribute("data-step") !== nextStep)
			.forEach((item) => item.classList.add("hide"));
		Array.from(this.allSteps)
			.filter((item) => item.getAttribute("data-step") === nextStep)
			.forEach((item) => item.classList.remove("hide"));
	}

	eventHandlerForForm(form) {
		if (!form) {
			return;
		}

		const inputs = form.querySelectorAll("input");
		inputs.forEach((input) => {
			input.addEventListener("focus", (e) => {
				if (isAppleDevice()) {
					document.body.classList.add("apple-fixed");
				}
			});
			input.addEventListener("blur", (e) => {
				if (document.body.classList.contains("apple-fixed")) {
					document.body.classList.remove("apple-fixed");
				}
			});
		});
	}

	setEventOnAllSteps(stepsWrapper) {
		if (!stepsWrapper) {
			return;
		}

		const forms = stepsWrapper.querySelectorAll("form");
		if (forms) {
			forms.forEach((form) => {
				this.eventHandlerForForm(form);
			});
		}
	}

	setEventOnFirstStep(firstStep) {
		if (!firstStep) {
			return;
		}

		const form = firstStep.querySelector("form");
		const input = firstStep.querySelector(".input input");
		if (form) {
			const messageBox = form.querySelector(".message");
			const emailField = form.querySelector('[data-field="email"]');
			const forgotPassButton = firstStep.querySelector(
				'[data-target="forgot_pass"]'
			);
			form.addEventListener("submit", (e) => {
				e.preventDefault();

				messageBox.innerHTML = "";
				emailField.classList.remove("invalid");

				fetch(mm_ajax_object.ajaxURL, {
					method: "POST",
					body: new FormData(e.target),
					credentials: "same-origin",
				})
					.then((response) => response.json())
					.then((json) => {
						if (json.response !== "" && !json.error) {
							this.toggleSteps(json.response ? "login" : "register", "first");
						}
						if (json.error) {
							emailField.classList.add("invalid");
							messageBox.innerHTML = `<span class="error">${json.error}</span>`;
						}
					});
			});

			if (forgotPassButton) {
				forgotPassButton.addEventListener("click", (e) => {
					this.toggleSteps("reset", "first");
				});
			}

			if (input) {
				input.addEventListener("input", (e) => {
					if (input.classList.contains("invalid") && input.value == "") {
						input.classList.remove("invalid");
						messageBox.innerHTML = "";
					}
				});
			}
		}
	}

	setEventsOnLoginStep(loginStep) {
		if (!loginStep) {
			return;
		}

		const form = loginStep.querySelector("form");
		const stepBack = loginStep.querySelector(".step-back");
		const forgotPassButton = loginStep.querySelector(
			'[data-target="forgot_pass"]'
		);
		if (form && stepBack && forgotPassButton) {
			const formInstance = new ajaxForm(form);
			this.forms.push(formInstance);
			stepBack.addEventListener("click", (e) => {
				formInstance.resetForm("email");
				this.toggleSteps(this.stepsStorage.pop());
			});
			forgotPassButton.addEventListener("click", (e) => {
				formInstance.resetForm("email");
				this.toggleSteps("reset", "login");
			});
		}
	}

	setEventsOnRegisterStep(registrationStep) {
		if (!registrationStep) {
			return;
		}

		const form = registrationStep.querySelector("form");
		const stepBack = registrationStep.querySelector(".step-back");

		if (form && stepBack) {
			const formInstance = new ajaxForm(form);
			this.forms.push(formInstance);

			stepBack.addEventListener("click", (e) => {
				this.toggleSteps(this.stepsStorage.pop());
				formInstance.resetForm("email");
			});
		}
	}

	setEventsOnResetPasswordStep(resetPasswordStep) {
		if (!resetPasswordStep) {
			return;
		}

		const form = resetPasswordStep.querySelector("form");
		const stepBack = resetPasswordStep.querySelector(".step-back");

		if (form && stepBack) {
			const formInstance = new ajaxForm(form, false);
			this.forms.push(formInstance);

			formInstance.setEventOnForm(form, (args) => {
				if (args.invalid) {
					formInstance.responseHandler(args);
				}
				if (args.status) {
					this.toggleSteps("send_reset", "reset");
				}
			});

			stepBack.addEventListener("click", (e) => {
				this.toggleSteps(this.stepsStorage.pop());
				formInstance.resetForm("email", "reset");
			});
		}
	}

	setEventsOnSendResetPasswordStep(sendResetPasswordStep) {
		if (!sendResetPasswordStep) {
			return false;
		}

		const stepBack = sendResetPasswordStep.querySelector(".step-back");
		if (stepBack) {
			stepBack.addEventListener("click", (e) => {
				this.toggleSteps(this.stepsStorage.pop());
			});
		}
	}

	clearAllFormFields() {
		if (!this.allSteps) {
			return;
		}

		this.toggleSteps("first");

		this.forms.forEach((form) => {
			form.clearErrors();
		});

		this.allSteps.forEach((step) => {
			this.clearFormFields(step);
		});
	}

	clearFormFields(step) {
		step
			.querySelectorAll('input:not([type="hidden"])')
			.forEach((input) => (input.value = ""));
	}

	connectEmailFields(
		firstStep,
		loginStep,
		registrationStep,
		resetPasswordStep
	) {
		if (!firstStep || !loginStep || !registrationStep || !resetPasswordStep) {
			return;
		}

		const firstStepEmail = firstStep.querySelector('[data-field="email"]');
		const loginStepEmail = loginStep.querySelector('[data-field="email"]');
		const registrationStepEmail = registrationStep.querySelector(
			'[data-field="email"]'
		);
		const resetPasswordStepEmail = resetPasswordStep.querySelector(
			'[data-field="email"]'
		);
		if (firstStepEmail) {
			firstStepEmail.addEventListener("input", (e) => {
				loginStepEmail.value = e.target.value;
				registrationStepEmail.value = e.target.value;
				resetPasswordStepEmail.value = e.target.value;
			});
		}
	}
}

class wpcf7CustomValidation {
	constructor(form) {
		if (!form) {
			return;
		}
		this.form = form;
		this.errorMessages = mm_ajax_object.error_messages;
		this.requiredFields = this.form.querySelectorAll('.wpcf7-form-control');
		if (this.requiredFields) {
			this.init();
		}
	}

	init() {
		this.requiredFields.forEach(field => this.setMutationObserver(field));
	}

	setMutationObserver(field) {
		const observer = new MutationObserver((mutations) => {
			mutations.forEach(mutation => {

				if (mutation.attributeName === 'class' && field.classList.contains('wpcf7-not-valid')) {
					this.replaceErrorMessage(field)
				}
			})
		})
		observer.observe(field, {
			attributes: true,
		})
	}

	getErrorMessage = (fieldKey) => {
		if (!fieldKey) {
			return null;
		}

		const errorMessages = this.errorMessages;
		for (const object of errorMessages) {
			if (object.keys && object.message) {
				const keyExists = object.keys.some(key => key.key === fieldKey);
				if (keyExists) {
					return object.message;
				}
			}
		}

		return null;
	};

	replaceErrorMessage(field) {
		if (!field) {
			return
		}

		const parent = field.closest('.wpcf7-form-control-wrap');

		if (parent.querySelector('.wpcf7-not-valid-tip') && this.getErrorMessage(parent.dataset.name)) {
			parent.querySelector('.wpcf7-not-valid-tip').innerText = this.getErrorMessage(parent.dataset.name)
		}
	}
}

export {
	SearchForm,
	slidersInit,
	GoogleRatingRender,
	addToFavourites,
	changeWindowHistory,
	isElementInViewport,
	YoutubeVideos,
	togglePasswordField,
	ajaxForm,
	Steps,
	wpcf7CustomValidation
};
