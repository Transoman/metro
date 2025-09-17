<?php

/**
 * Redirects author archive pages to the home page.
 *
 * This anonymous function checks if the current page is an author archive, and if so,
 * it redirects the user to the site’s homepage with a 301 status code. This is useful
 * for sites that do not utilize author archives and want to prevent access to them.
 */
add_action('template_redirect', function()
{
    if (is_author()){
        wp_redirect(get_option('siteurl'), 301);
        die();
    }
});

