<?php

if (!defined('ABSPATH')) {
	exit;
}

if (is_admin()) {
	return;
}

/**
 * Deregisters jQuery and jQuery Migrate scripts from the front-end.
 *
 * This script removes the default jQuery and jQuery Migrate scripts enqueued by WordPress on the front-end.
 * This can be useful for improving performance if your theme or plugins do not rely on jQuery.
 * It does not affect the admin area to ensure WordPress functionality remains intact.
 */

// Deregister jQuery core and jQuery Migrate on the front-end.
add_action('wp_enqueue_scripts', function () {
	wp_deregister_script('jquery-core');
	wp_deregister_script('jquery-migrate');
});

