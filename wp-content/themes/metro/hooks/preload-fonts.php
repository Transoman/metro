<?php

/**
 * Preloads font files to improve performance.
 *
 * This script adds font files (TTF, WOFF, WOFF2) to the list of resources that are preloaded
 * by the browser, reducing render-blocking and improving load times. The fonts are identified
 * from a Vite manifest and preloaded using the `wp_preload_resources` filter.
 *
 * @param array $preload_resources The existing array of resources to preload.
 *
 * @return array The modified array including font files for preloading.
 */
add_filter('wp_preload_resources', function ($preload_resources = []) {
	$preload_resources = [];
	foreach (array_keys(\Profidev\Vite\Data::$manifest) as $asset) {
		if (preg_match('/\.(ttf|woff2?)$/', $asset)) {
			$preload_resources[] = [
				'href' => get_theme_file_uri('/dist/' . \Profidev\Vite\Data::$manifest[$asset]['file']),
				'as' => 'font'
			];
		}
	}
	return $preload_resources;
}, 10, 1);

