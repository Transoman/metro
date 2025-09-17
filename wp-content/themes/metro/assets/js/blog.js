import Swiper from "swiper";
import { Navigation } from "swiper/modules";

(() => {
	const initializeBlock = (e) => {
		const blogPostsWrapper = document.querySelector('[data-target="blog_posts_wrapper"]'),
			blogPostsLoadMoreForm = document.querySelector('[data-target="blog_posts_load_more"]');

		if (blogPostsWrapper && blogPostsLoadMoreForm) {
			const offsetInput = blogPostsLoadMoreForm.querySelector('input[name="offset"]'),
				numberpostsInput = blogPostsLoadMoreForm.querySelector('input[name="numberposts"]'),
				submitButton = blogPostsLoadMoreForm.querySelector('button[type="submit"]');

			blogPostsLoadMoreForm.addEventListener('submit', (e) => {
				e.preventDefault();

				const data = new FormData(e.target)

				fetch(mm_ajax_object.ajaxURL, {
					method: "POST",
					credentials: 'same-origin',
					body: data
				})
					.then(response => response.json())
					.then(json => {
						let offsetValue = parseInt(offsetInput.value),
							numberpostsValue = parseInt(numberpostsInput.value);
						offsetInput.value = offsetValue + numberpostsValue;
						if (json.posts !== '') {
							blogPostsWrapper.innerHTML += json.posts
						}
						if (json.disabled) {
							submitButton.remove()
						}
					})
			})
		}

		const blogPostsSearch = document.querySelector('[data-target="blog_posts_search"]');
		if (blogPostsSearch) {
			const form = blogPostsSearch.querySelector('form'),
				input = form.querySelector('input[name="search_post"]'),
				resultWrapper = blogPostsSearch.querySelector('.result'),
				submitButton = form.querySelector('button[type="submit"]');

			submitButton.addEventListener('click', (e) => {
				e.preventDefault();
				fetchSearch()
			})

			const debounce = (func, delay = 1000) => {
				let timerId;
				return (...args) => {
					clearTimeout(timerId);
					timerId = setTimeout(() => {
						func.apply(this, args);
					}, delay);
				};
			}

			const fetchSearch = () => {

				const data = new FormData(form);
				if (input.value !== '') {
					fetch(mm_ajax_object.ajaxURL, {
						method: 'POST',
						credentials: 'same-origin',
						body: data
					})
						.then(response => response.json())
						.then(json => {
							if (json) {
								resultWrapper.innerHTML = json;
								resultWrapper.classList.add('show');
							}
						})
				}


			}

			input.addEventListener('input', debounce(fetchSearch));

			window.addEventListener('click', (e) => {
				if (resultWrapper.classList.contains('show') && !resultWrapper.contains(e.target)) {
					resultWrapper.classList.remove('show')
				}
			})
		}


		const blogPostsSelector = document.querySelector('[data-target="blog_posts_selector"]');
		if (blogPostsSelector) {
			const placeholder = blogPostsSelector.querySelector('.placeholder span');
			const activeCategory = blogPostsSelector.querySelector('ul li a.active');
			placeholder.innerText = (activeCategory) ? activeCategory.innerText : placeholder.innerText;

			blogPostsSelector.addEventListener('click', () => {
				blogPostsSelector.classList.toggle('active');
			})

			window.addEventListener('click', (e) => {
				if (blogPostsSelector.classList.contains('active') && !blogPostsSelector.contains(e.target)) {
					blogPostsSelector.classList.remove('active')
				}
			})
		}

		const latestVideos = document.querySelector('[data-target="latest_videos"]');

		if (latestVideos) {
			const swiperElement = latestVideos.querySelector('.swiper'),
				numberOfSlides = parseInt(latestVideos.getAttribute('data-swiper-slides')),
				prevButton = latestVideos.querySelector('[data-target="swiper_left"]'),
				nextButton = latestVideos.querySelector('[data-target="swiper_right"]'),
				options = {
					spaceBetween: 30,
					freeMode: true,
					lazy: true,
					preloadImages: false,
					navigation: {
						nextEl: nextButton,
						prevEl: prevButton
					},
					breakpoints: {
						300: {
							slidesPerView: 1.11,
						},
						577: {
							slidesPerView: 2,
						},
						769: {
							slidesPerView: numberOfSlides,
							freeMode: false,
						}
					},
				};
			Swiper.use(Navigation);
			let instance = new Swiper(swiperElement, options);
			let minResize = false;
			let maxResize = true;

			const enablingSlider = () => {
				instance = new Swiper(swiperElement, options)
			}

			const destroyingSlider = () => {
				if (instance !== undefined) {
					instance.destroy(true, true)
				}
			}


			const resizeWindow = (width, breakpoint) => {
				if (width <= breakpoint && !minResize) {
					minResize = true;
					maxResize = false;
					destroyingSlider()
				}
				if (width >= breakpoint && !maxResize) {
					maxResize = true;
					minResize = false;
					enablingSlider()

				}
			}

			resizeWindow(window.innerWidth, 577)
			window.addEventListener('resize', () => {
				resizeWindow(window.innerWidth, 577)
			})
			let initializedScript = false;

			const insertIFrameApi = () => {
				return new Promise((resolve, reject) => {
					const tag = document.createElement('script');
					tag.src = 'https://www.youtube.com/iframe_api';

					const firstScriptTag = document.getElementsByTagName('script')[0];
					firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

					window.onYouTubeIframeAPIReady = () => {
						resolve()
						initializedScript = true;
					};

					tag.onerror = (error) => reject(error);
				});
			}

			const videos = document.querySelectorAll('[data-target="youtube_video"]');

			const playerHandler = ({ element, videoID }) => {
				return new Promise((resolve, reject) => {
					const youtubePlayer = new YT.Player(element, {
						height: '100%',
						width: '100%',
						videoId: videoID,
						playerVars: {
							'playsinline': 1
						},
						events: {
							'onReady': function () {
								resolve(youtubePlayer)
							},
						},
					});
				});
			}

			if (videos) {
				videos.forEach(video => {
					const button = video.querySelector('button');
					const videoWrapper = video.querySelector('.video');
					const imageWrapper = video.querySelector('.image');
					const loader = video.querySelector('.loader');
					const videoID = video.getAttribute('data-video-id');
					let isIframeInserted = (video.getAttribute('data-video-insert') == 'false');
					let data = {
						element: videoWrapper.querySelector('.element-to-replace'),
						videoID: videoID,
					}
					button.addEventListener('click', async (e) => {
						try {
							if (isIframeInserted) {
								loader.classList.remove('hide');
								button.classList.add('hide');
								if (!initializedScript) {
									await insertIFrameApi();
								}
								const player = await playerHandler(data);
								imageWrapper.classList.add('hide');
								videoWrapper.classList.remove('hide');
								player.playVideo();
							}

						}
						catch (error) {
							console.log(error);
						}


					})
				})
			}

		}
	}

	if (document.readyState === "interactive" || document.readyState === "complete") {
		initializeBlock()
	}
	else {
		document.addEventListener('DOMContentLoaded', (e) => {
			initializeBlock(e)
		})
	}
})()
