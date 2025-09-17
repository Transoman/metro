<?php

namespace Profidev\Cleanup;

use function YoastSEO_Vendor\GuzzleHttp\Psr7\str;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Removes WordPress version from styles and scripts URLs.
 *
 * This function removes the `ver` query parameter from the URLs of enqueued styles and scripts,
 * which contains the WordPress version number. This is often done to improve security by obscuring
 * the WordPress version in use.
 *
 * @param string $src The source URL of the enqueued style or script.
 *
 * @return string The modified URL without the version query parameter.
 */
function remove_wp_version_from_url($src)
{
	if (strpos($src, 'ver=' . get_bloginfo('version'))) {
		$src = remove_query_arg('ver', $src);
	}
	return $src;
}
add_filter('style_loader_src', __NAMESPACE__ . '\remove_wp_version_from_url', 9999);
add_filter('script_loader_src', __NAMESPACE__ . '\remove_wp_version_from_url', 9999);

/**
 * Dequeues unnecessary block library styles.
 *
 * This anonymous function removes the default block library CSS and related theme styles from
 * the front-end, as these may not be needed for certain themes or projects.
 */
add_action('wp_enqueue_scripts', function () {
	wp_dequeue_style('wp-block-library');
	wp_dequeue_style('wp-block-library-theme');
	wp_dequeue_style('classic-theme-styles');
});

/**
 * Removes various default WordPress actions and features.
 *
 * This anonymous function disables several WordPress features, such as global styles,
 * resource hints, and the XML-RPC API, which may not be needed or wanted in certain projects.
 */
add_action('init', function () {
	// Remove wp default css variables.
	remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
	remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
	remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');

	// Remove dns-prefetch Link from WordPress Head (Frontend).
	remove_action('wp_head', 'wp_resource_hints', 2);

	// Removes Generator meta tag.
	remove_action('wp_head', 'wp_generator');

	// Removes wlwmanifest_link.
	remove_action('wp_head', 'wlwmanifest_link');

	// Remove EditURI
	remove_action('wp_head', 'rsd_link');

	// Disable XMLRPC.
	add_filter('xmlrpc_enabled', '__return_false');

	// Disable unnecessary tags from WPSEO plugin.
	add_filter('wpseo_debug_markers', '__return_false');
});

/**
 * Removes the block directory assets from the block editor.
 *
 * This function disables the ability to install custom blocks from the WordPress blocks directory,
 * which may be unnecessary for some projects.
 */
remove_action('enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets');

// Disable XMLRPC
add_filter('xmlrpc_enabled', '__return_false');


/**
 * Removes the `type="text/javascript"` attribute from script tags.
 *
 * This anonymous function uses output buffering to remove the `type` attribute from script tags,
 * which is no longer necessary in HTML5 and can be omitted for cleaner markup.
 */
add_action('template_redirect', function () {
	ob_start(function ($buffer) {
		return preg_replace("%[ ]type=[\'\"]text\/(javascript)[\'\"]%", '', $buffer);
	});
});

/**
 * Deregisters block styles in the front-end.
 *
 * This anonymous function loops through the registered styles and deregisters any styles related
 * to WordPress blocks, which may not be needed for certain themes or projects.
 */
add_action('wp_enqueue_scripts', function () {
	global $wp_styles;

	foreach ($wp_styles->registered as $key => $handle) {
		if(str_contains($key, 'wp-block-')){
			wp_deregister_style($key);
		}
	}
});

// Disable the links to the extra feeds such as category feeds
remove_action( 'wp_head', 'feed_links_extra', 3 );

// Disable the links to the general feeds: Post and Comment Feed
remove_action( 'wp_head', 'feed_links', 2 );

// Remove Rank Math SEO plugin comment
// add_filter( 'rank_math/frontend/remove_credit_notice', '__return_true' );

