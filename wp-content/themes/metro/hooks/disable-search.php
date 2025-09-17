<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Disables the WordPress search functionality.
 *
 * This script prevents the search feature from working on the site by modifying the query object
 * and setting the search query to false. It also disables the search form and removes the search widget.
 */
add_action(
    'parse_query',
    function ($query) {
        if (!is_search() || is_admin()) {
            return;
        }
	      // Disable search results and return a 404 page.
        $query->is_search       = false;
        $query->query_vars['s'] = false;
        $query->query['s']      = false;
        $query->is_404          = true;
    }
);

// Remove the search form.
add_filter('get_search_form', '__return_false');

// Unregister the search widget.
add_action(
    'widgets_init',
    function () {
        unregister_widget('WP_Widget_Search');
    }
);

