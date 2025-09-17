<?php

/**
 * Disables comments across the entire WordPress site, both in the admin area and on the front-end.
 *
 * This script handles the removal of comment functionality by:
 * - Redirecting users who try to access the comments page in the admin area.
 * - Removing the comments metabox from the WordPress dashboard.
 * - Disabling comment and trackback support for all post types.
 * - Closing comments on the front-end and hiding existing comments.
 * - Removing the comments page from the admin menu.
 * - Removing the comments links from the admin bar.
 */
add_action('admin_init', function () {
	// Redirect any user trying to access the comments page in the admin area.
	global $pagenow;

	if ($pagenow === 'edit-comments.php') {
		wp_redirect(admin_url());
		exit;
	}

	// Remove comments metabox from dashboard.
	remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');

	// Disable support for comments and trackbacks in all post types.
	foreach (get_post_types() as $post_type) {
		if (post_type_supports($post_type, 'comments')) {
			remove_post_type_support($post_type, 'comments');
			remove_post_type_support($post_type, 'trackbacks');
		}
	}
});

// Close comments on the front-end.
add_filter('comments_open', '__return_false', 20, 2);
add_filter('pings_open', '__return_false', 20, 2);

// Hide existing comments.
add_filter('comments_array', '__return_empty_array', 10, 2);

// Remove comments page from the admin menu.
add_action('admin_menu', function () {
	remove_menu_page('edit-comments.php');
});

// Remove comments links from the admin bar.
add_action('init', function () {
	if (is_admin_bar_showing()) {
		remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
	}
});

