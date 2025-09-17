import Swiper from "swiper"
import { Navigation } from "swiper/modules";
import { slidersInit } from '../../assets/js/helpers';

(() => {
	const initializeBlock = () => {
		const sections = document.querySelectorAll('.news-section');
		if (sections) {
			sections.forEach(section => {
				slidersInit(section, Swiper, Navigation);
			})
		}
	}

	if (document.readyState === "interactive" || document.readyState === "complete") {
		if (!window.acf) {
			initializeBlock()
		}
		else {
			window.acf.addAction('render_block_preview/type=news-section', initializeBlock)
		}
	}
	else {
		document.addEventListener('DOMContentLoaded', () => {
			if (!window.acf) {
				initializeBlock()
			}
			else {
				window.acf.addAction('render_block_preview/type=news-section', initializeBlock)
			}
		})
	}
})()
