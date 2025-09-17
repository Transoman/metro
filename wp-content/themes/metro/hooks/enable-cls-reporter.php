<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Enables a CLS (Cumulative Layout Shift) reporter in the browser console.
 *
 * This script injects JavaScript into the page that tracks Cumulative Layout Shift (CLS) values
 * and logs the current CLS value to the browser console. This can be useful for monitoring
 * layout shifts during development and optimizing page stability.
 */
add_action('wp_body_open', function () {
	echo <<<EOL

<script>
(() => {
	"use strict"

	let cls=0
	new PerformanceObserver((entryList) => {
		for (const entry of entryList.getEntries()) {
			if (!entry.hadRecentInput) {
				cls += entry.value
				console.log("Current CLS value:", cls, entry)
			}
		}
	}).observe({ type: "layout-shift", buffered: true })
})()
</script>
EOL;
});

