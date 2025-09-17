<?php
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('clear-wp-rocket-cache', function () {
        if (!function_exists('rocket_clean_post')) {
            WP_CLI::error('WP Rocket is not active or required functions are unavailable.');
            return;
        }

        // Fetch all published posts and pages
        $args = array(
            'post_type' => array('post', 'page', 'listings', 'buildings'),
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        );
        $posts = get_posts($args);

        if (empty($posts)) {
            WP_CLI::success('No posts or pages found to clear cache.');
            return;
        }
        
        foreach ($posts as $post_id) {
            rocket_clean_post($post_id);
            WP_CLI::log("Cache cleared for Post ID: {$post_id}");
        }
        
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
            WP_CLI::log('Entire site cache cleared.');
        }

        WP_CLI::success('Cache clearing process completed.');
    });
}
