<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Enables Gutenberg editor for WooCommerce products and integrates product taxonomies with the REST API.
 *
 * This script allows the use of the Gutenberg block editor for WooCommerce products and enables
 * product categories and tags to be accessible via the REST API, improving compatibility with
 * block-based themes and custom development.
 */

// Enable Gutenberg editor for WooCommerce products.
add_filter( 'use_block_editor_for_post_type', function ($can_edit, $post_type) {
	if ( $post_type == 'product' ) {
		$can_edit = true;
	}
	return $can_edit;
}, 10, 2 );

// Enable product categories in the REST API.
add_filter( 'woocommerce_taxonomy_args_product_cat', function ($args) {
	$args['show_in_rest'] = true;
	return $args;
} );

// Enable product tags in the REST API.
add_filter( 'woocommerce_taxonomy_args_product_tag', function ($args) {
	$args['show_in_rest'] = true;
	return $args;
} );

