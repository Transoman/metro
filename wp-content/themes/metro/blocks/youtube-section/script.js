import Swiper from "swiper"
import { Navigation } from "swiper/modules"
import { slidersInit, YoutubeVideos } from '../../assets/js/helpers';
(() => {
	const initializeBlock = () => {
		const sections = document.querySelectorAll('.youtube-section');
		if (sections) {
			sections.forEach(section => {
				slidersInit(section, Swiper, Navigation);
			})
		}

		const videos = document.querySelectorAll('[data-target="youtube_video"]');

		if (videos) {
			new YoutubeVideos(videos);
		}
	}

	if (document.readyState === "interactive" || document.readyState === "complete") {
		if (!window.acf) {
			initializeBlock()
		}
		else {
			window.acf.addAction('render_block_preview/type=youtube-section', initializeBlock);
		}
	}
	else {
		document.addEventListener('DOMContentLoaded', () => {
			if (!window.acf) {
				initializeBlock()
			}
			else {
				window.acf.addAction('render_block_preview/type=youtube-section', initializeBlock);
			}
		})
	}
})()