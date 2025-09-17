<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Disables the WordPress oEmbed functionality.
 *
 * This script removes various actions and filters related to WordPress oEmbed, which allows embedding content
 * from external sources (like YouTube, Twitter, etc.). Disabling oEmbed can improve performance and security
 * by reducing external requests and potential vulnerabilities.
 */
add_action(
    'init',
    function () {
	      // Remove the REST API route for oEmbed.
        remove_action('rest_api_init', 'wp_oembed_register_route');

	      // Disable oEmbed discovery.
        add_filter('embed_oembed_discover', '__return_false');

	      // Remove oEmbed-specific filters.
        remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');
        remove_filter('pre_oembed_result', 'wp_filter_pre_oembed_result', 10);

	      // Modify rewrite rules to remove oEmbed support.
        add_filter(
            'rewrite_rules_array',
            function ($plugins) {
                return array_diff($plugins, [ 'wpembed' ]);
            }
        );

	      // Remove the TinyMCE oEmbed plugin.
        add_filter(
            'tiny_mce_plugins',
            function ($rules) {
                foreach ($rules as $rule => $rewrite) {
                    if (strpos($rewrite, 'embed=true') !== false) {
                        unset($rules[ $rule ]);
                    }
                }
                return $rules;
            }
        );
    },
    9999
);

/**
 * Dequeues the oEmbed JavaScript from the footer.
 *
 * This ensures that the `wp-embed` script, which handles oEmbed functionality, is not loaded in the footer.
 */
add_action(
    'wp_footer',
    function () {
        wp_dequeue_script('wp-embed');
    }
);

