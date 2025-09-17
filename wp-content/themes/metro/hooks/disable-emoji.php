<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Disables the WordPress emoji script and related functionality.
 *
 * This script removes actions and filters related to WordPress emojis, which are loaded by default to support
 * emoji rendering across different browsers. Disabling them can improve site performance by reducing
 * unnecessary HTTP requests and scripts.
 */

// Remove the emoji detection script from the front-end and admin pages.
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('admin_print_scripts', 'print_emoji_detection_script');

// Remove the emoji styles from the front-end and admin pages.
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_styles', 'print_emoji_styles');

// Remove emoji-related filters from content feeds and emails.
remove_filter('the_content_feed', 'wp_staticize_emoji');
remove_filter('comment_text_rss', 'wp_staticize_emoji');
remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

// Remove the TinyMCE emoji plugin.
add_filter(
    'tiny_mce_plugins',
    function ($plugins) {
        if (is_array($plugins)) {
            return array_diff($plugins, [ 'wpemoji' ]);
        }
        return [];
    }
);

// Remove the emoji CDN hostname from DNS prefetching hints.
add_filter(
    'wp_resource_hints',
    function ($urls, $relation_type) {
        if ($relation_type === 'dns-prefetch') {
            /** This filter is documented in wp-includes/formatting.php */
            $emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/');
            $urls          = array_diff($urls, [ $emoji_svg_url ]);
        }

        return $urls;
    },
    10,
    2
);

