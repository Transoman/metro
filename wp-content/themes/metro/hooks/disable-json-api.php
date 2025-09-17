<?php

/**
 * Disables the WordPress JSON REST API for non-authenticated users.
 *
 * This script removes REST API links from the WordPress head and disables the JSON REST API
 * for non-logged-in users. This can be used to prevent unauthorized access to the REST API,
 * improving security. The admin area is unaffected to ensure WordPress functionality remains intact.
 */
add_action('after_setup_theme', function () {
	if (is_admin()) {
		return;
	}

	// Remove REST API links from the WordPress head.
	remove_action('wp_head', 'rest_output_link_wp_head', 10);
	remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);

	// Disable the JSON REST API.
	add_filter('json_enabled', '__return_false');
	add_filter('json_jsonp_enabled', '__return_false');
	add_filter('rest_jsonp_enabled', '__return_false');
});

/**
 * Restrict REST API access to authenticated users only.
 *
 * This filter returns an error for non-logged-in users attempting to access the REST API,
 * effectively disabling it for public use.
 *
 * @param mixed $access The current REST API access status.
 *
 * @return WP_Error|mixed Returns an error if the user is not logged in, otherwise returns the original access status.
 */
add_filter('rest_authentication_errors', function ($access) {
	if (is_user_logged_in()) {
		return;
	}
	return new WP_Error('rest_disabled', __('The WordPress REST API has been disabled.'), array('status' => rest_authorization_required_code()));
});

