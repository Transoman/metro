<?php
/**
 *  Non-production environment functionality.
 */

if ( 'production' !== wp_get_environment_type() ) {

	// Block crawling.
	add_filter( 'robots_txt', 'wpdocs_name_block_crawling', PHP_INT_MAX );

	// Enable "Discourage search engines from indexing this site" option.
	add_filter( 'pre_option_blog_public', '__return_zero', PHP_INT_MAX );

}

/**
 * Filters the robots.txt output to block crawling on non-production environment.
 *
 * @param string $output The robots.txt output.
 */
function wpdocs_name_block_crawling( $output ) {

	$output = '# Crawling is blocked for non-production environment' . PHP_EOL;
	$output .= 'User-agent: *' . PHP_EOL;
	$output .= 'Disallow: /';

	return $output;
}