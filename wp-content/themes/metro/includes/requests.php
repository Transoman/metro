<?php

/**
 * Handles AJAX requests for MetroManhattan WordPress site.
 *
 * This file includes several AJAX handlers for various functionalities like fetching featured spaces, paginated listings,
 * listing cards for the map, listings based on coordinates, blog posts, and user email verification.
 */
if ( ! function_exists( 'mm_get_featured_spaces_posts' ) ) {
	/**
	 * Retrieves featured spaces posts based on a taxonomy slug.
	 *
	 * @return void
	 */
	function mm_get_featured_spaces_posts() {
		$term_slug   = $_POST['slug'];
		$numberposts = $_POST['numberposts'];
		if ( ! empty( $term_slug ) ) {
			$args = [
				'post_type'   => 'listings',
				'numberposts' => $numberposts,
				'tax_query'   => [
					[
						'taxonomy' => 'location',
						'field'    => 'slug',
						'terms'    => $term_slug
					]
				],
			];

			$posts = get_posts( $args );

			echo json_encode( mm_build_featured_spaces_posts( $posts, $term_slug ) );
			wp_die();
		}
	}

	/**
	 * Builds the featured spaces posts output.
	 *
	 * @param array $posts_array The array of posts.
	 * @param string $term_slug The slug of the term.
	 *
	 * @return array The array of formatted posts.
	 */
	function mm_build_featured_spaces_posts( $posts_array, $term_slug ) {
		$output = [];
		foreach ( $posts_array as $post_item ) {
			$output[] = MetroManhattanHelpers::get_listings_cards( [ $post_item ], false, 'h3' );
		}

		return $output;
	}

	add_action( 'wp_ajax_get_featured_spaces_posts', 'mm_get_featured_spaces_posts' );
	add_action( 'wp_ajax_nopriv_get_featured_spaces_posts', 'mm_get_featured_spaces_posts' );
}


if ( ! function_exists( 'mm_get_paginated_listings' ) ) {
	/**
	 * Retrieves paginated listings for the search results.
	 *
	 * @return void
	 */
	function mm_get_paginated_listings() {
		$output          = [];
		$numberposts     = $_POST['numberposts'];
		$order           = $_POST['order'];
		$coordinates     = ! empty( $_POST['coordinates'] ) ? $_POST['coordinates'] : null;
		$current_page    = $_POST['current_page'];
		$coordinates     = json_decode( stripslashes( $coordinates ) );
		$filters         = ( ! empty( $_POST['filter'] ) ) ? $_POST['filter'] : null;
		$offset          = ( $_POST['page'] * $numberposts ) - $numberposts;
		$result          = MetroManhattanHelpers::get_listings_search_result( $filters, $offset, $numberposts, $order, $coordinates );
		$pagination      = MetroManhattanHelpers::get_pagination_of_search_result( $result['total'], $_POST['page'], $numberposts, $current_page );
		$output['cards'] = MetroManhattanHelpers::get_listings_cards( $result['listings'], false );
		if ( $result['total'] > $numberposts ) {
			$output['pagination'] = ( ! isset( $pagination ) ) ? [] : $pagination;
		}
		$output['total']       = $result['total'];
		$output['filters']     = MetroManhattanHelpers::build_filters( $filters );
		$output['coordinates'] = $result['coordinates'];

		$paged = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
		if ( $paged < 1 ) {
			$paged = 1;
		}

		// Total number of pages
		$max_pages = intval( ceil( $result['total'] / $numberposts ) );
		if ( $max_pages <= 1 ) {
			return; // No pagination — nothing added
		}

		$pagination_link_tag = [];
		// Prev
		if ( $paged > 1 ) {
			$pagination_link_tag['prev'] = '<link rel="prev" href="' . esc_url( $current_page . ( $paged - 1 != 1 ? 'page/' . $paged - 1 : '' ) ) . '" />' . "\n";
		}
		// Next
		if ( $paged < $max_pages ) {
			$pagination_link_tag['next'] = '<link rel="next" href="' . esc_url( $current_page . 'page/' . $paged + 1 ) . '" />' . "\n";
		}

		$output['paginationLinkTag'] = $pagination_link_tag;

		echo json_encode( $output );

		wp_die();
	}


	add_action( 'wp_ajax_pagination_search_result', 'mm_get_paginated_listings' );
	add_action( 'wp_ajax_nopriv_pagination_search_result', 'mm_get_paginated_listings' );
}

if ( ! function_exists( 'mm_get_listing_card_for_map' ) ) {
	/**
	 * Retrieves listing card(s) for the map based on listing IDs.
	 *
	 * @return void
	 */
	function mm_get_listing_card_for_map() {
		$listing_ids = $_POST['listing_id'];
		$output      = [];
		if ( sizeof( explode( ',', $listing_ids ) ) < 2 ) {
			$array[]           = (object) [
				'ID' => $listing_ids
			];
			$output['card']    = MetroManhattanHelpers::get_listings_cards( $array, true, 'h3' );
			$output['several'] = false;
		} else {
		    $output['card'] = '';
			foreach ( explode( ',', $listing_ids ) as $listing_id ) {
				$array             = (object) [
					'ID' => $listing_id
				];
				$output['card']    .= MetroManhattanHelpers::get_listings_cards( [ $array ], true, 'h3' );
				$output['several'] = true;
			}
		}


		echo json_encode( $output );
		wp_die();
	}

	add_action( 'wp_ajax_get_map_listing_card', 'mm_get_listing_card_for_map' );
	add_action( 'wp_ajax_nopriv_get_map_listing_card', 'mm_get_listing_card_for_map' );
}

if ( ! function_exists( 'mm_get_listings_by_coordinates' ) ) {
	/**
	 * Retrieves listings based on map coordinates.
	 *
	 * @return void
	 */
	function mm_get_listings_by_coordinates() {
		global $wpdb;

		$coordinates        = $_POST['coordinates'];
		$coordinates        = json_decode( stripslashes( $coordinates ) );
		$order              = $_POST['order'];
		$northEastLatitude  = $coordinates->northEast[0];
		$northEastLongitude = $coordinates->northEast[1];
		$southWestLatitude  = $coordinates->southWest[0];
		$southWestLongitude = $coordinates->southWest[1];
		$sql                = "SELECT * FROM $wpdb->postmeta, $wpdb->posts WHERE $wpdb->posts.id = $wpdb->postmeta.post_id AND $wpdb->posts.post_type = 'listings' AND ($wpdb->postmeta.meta_key = 'post_latitude' AND $wpdb->postmeta.meta_value < $northEastLatitude AND $wpdb->postmeta.meta_value > $southWestLatitude) OR ($wpdb->postmeta.meta_key = 'post_longitude' AND $wpdb->postmeta.meta_value > $northEastLongitude and $wpdb->postmeta.meta_value < $southWestLongitude) ORDER BY $wpdb->posts.post_date $order";
		$result             = $wpdb->get_results( $wpdb->prepare( $sql ) );
		$output['total']    = sizeof( $result );
		$output['cards']    = MetroManhattanHelpers::get_listings_cards( $result );
		echo json_encode( $output );

		wp_die();
	}

	add_action( 'wp_ajax_get_listings_by_coordinates', 'mm_get_listings_by_coordinates' );
	add_action( 'wp_ajax_nopriv_get_listings_by_coordinates', 'mm_get_listings_by_coordinates' );
}

if ( ! function_exists( 'mm_get_blog_posts' ) ) {
	/**
	 * Retrieves blog posts for the "Load More" functionality.
	 *
	 * @return void
	 */
	function mm_get_blog_posts() {
		header( 'Content-Type: application/json; charset=utf-8' );

		$output      = [];
		$numberposts = $_REQUEST['numberposts'];
		$offset      = $_REQUEST['offset'];
		$args        = [
			'numberposts' => - 1,
			'offset'      => $offset,
			'order'       => 'DESC',
			'orderby'     => 'date'
		];
		if ( $category = ( $_REQUEST['category'] != '0' ) ? $_REQUEST['category'] : false ) {
			$args['category'] = $category;
		}
		$total = get_posts( $args );
		if ( sizeof( $total ) - $numberposts <= (int) $offset ) {
			$output['disabled'] = true;
		}
		$args['numberposts'] = $numberposts;
		$output['posts']     = MetroManhattanHelpers::get_blog_posts( $args );

		$payload = json_encode( $output, JSON_INVALID_UTF8_IGNORE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
		if ( json_last_error() ) {
			echo json_encode( [ 'status' => 'error', 'message' => json_last_error_msg() ] );
		} else {
			echo $payload;
		}
		wp_die();
	}

	add_action( 'wp_ajax_load_more_blog_posts', 'mm_get_blog_posts' );
	add_action( 'wp_ajax_nopriv_load_more_blog_posts', 'mm_get_blog_posts' );
}


if ( ! function_exists( 'mm_get_blog_posts_search' ) ) {
	/**
	 * Searches blog posts by title.
	 *
	 * @return void
	 */
	function mm_get_blog_posts_search() {
		$search   = $_POST['search_post'];
		$category = $_POST['category'];
		$args     = [
			'post_type'      => 'post',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'numberposts'    => - 1,
			's'              => $search,
			'search_columns' => [ 'post_title' ]
		];
		if ( ! empty( $category ) ) {
			$args['category'] = $category;
		}
		$posts  = get_posts( $args );
		$output = '<ul>';
		if ( $posts ) {
			foreach ( $posts as $post ) {
				$link     = get_permalink( $post->ID );
				$title    = get_the_title( $post->ID );
				$category = current( get_the_category( $post->ID ) )->name;
				$output   .= '<li><a href="' . $link . '"><span class="title">' . $title . '</span><span>' . $category . '</span></a></li>';
			}
		} else {
			$output .= '<li class="no-results">No posts found</li>';
		}

		$output .= '</ul>';
		echo json_encode( $output );
		wp_die();
	}

	add_action( 'wp_ajax_blog_posts_search', 'mm_get_blog_posts_search' );
	add_action( 'wp_ajax_nopriv_blog_posts_search', 'mm_get_blog_posts_search' );
}

if ( ! function_exists( 'mm_get_listings_with_pagination' ) ) {
	/**
	 * Retrieves listings with pagination based on taxonomy.
	 *
	 * @return void
	 */
	function mm_get_listings_with_pagination() {
		$taxonomy     = $_POST['taxonomy'];
		$term         = $_POST['term'];
		$numberposts  = $_POST['numberposts'];
		$page         = $_POST['page'];
		$current_page = $_POST['current_page'];
		$offset       = ( $page * $numberposts ) - $numberposts;
		if ( $offset < 1 ) {
			$offset = 0;
		}
		$result          = MetroManhattanHelpers::get_listings_by_taxonomy( $taxonomy, $term, $offset, $numberposts );
		$pagination      = MetroManhattanHelpers::get_pagination_of_search_result( $result['total'], $_POST['page'], $numberposts, $current_page );
		$output['cards'] = MetroManhattanHelpers::get_listings_cards( $result['listings'], false, 'h3' );
		if ( $result['total'] > $numberposts ) {
			$output['pagination'] = ( ! isset( $pagination ) ) ? [] : $pagination;
		}
		$output['total'] = $result['total'];

		$paged = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
		if ( $paged < 1 ) {
			$paged = 1;
		}

		// Total number of pages
		$max_pages = intval( ceil( $result['total'] / $numberposts ) );
		if ( $max_pages <= 1 ) {
			return; // No pagination — nothing added
		}

		$pagination_link_tag = [];
		// Prev
		if ( $paged > 1 ) {
			$pagination_link_tag['prev'] = '<link rel="prev" href="' . esc_url( $current_page . ( $paged - 1 != 1 ? 'page/' . $paged - 1 : '' ) ) . '" />' . "\n";
		}
		// Next
		if ( $paged < $max_pages ) {
			$pagination_link_tag['next'] = '<link rel="next" href="' . esc_url( $current_page . 'page/' . $paged + 1 ) . '" />' . "\n";
		}

		$output['paginationLinkTag'] = $pagination_link_tag;

		echo json_encode( $output );


		wp_die();
	}

	add_action( 'wp_ajax_pagination_for_listings', 'mm_get_listings_with_pagination' );
	add_action( 'wp_ajax_nopriv_pagination_for_listings', 'mm_get_listings_with_pagination' );
}

if ( ! function_exists( 'mm_get_calculated_listings' ) ) {
	/**
	 * Retrieves calculated listings based on square footage range.
	 *
	 * @return void
	 */
	function mm_get_calculated_listings() {

		$range = $_POST['range'];
		$range = explode( ',', $range );

		$output = [];

		$listings = get_posts( [
			'post_type'  => 'listings',
			'meta_query' => [
				'relation' => 'OR',
				[
					'key'     => 'square_feet',
					'value'   => $range,
					'type'    => 'numeric',
					'compare' => 'BETWEEN'
				]
			]
		] );

		$output['listings'] = [];
		foreach ( $listings as $listing ) {
			$output['listings'][] = MetroManhattanHelpers::get_listings_cards( [ $listing ], false, 'h3' );
		}

		echo json_encode( $output );

		wp_die();
	}

	add_action( 'wp_ajax_get_calculated_offices', 'mm_get_calculated_listings' );
	add_action( 'wp_ajax_nopriv_get_calculated_offices', 'mm_get_calculated_listings' );
}


if ( ! function_exists( 'mm_is_email_registered' ) ) {
	/**
	 * Checks if an email is already registered.
	 *
	 * @return void
	 */
	function mm_is_email_registered() {
		$email    = filter_var( $_POST['email'], FILTER_SANITIZE_EMAIL );
		$response = ( is_email( $email ) ) ? [ 'response' => email_exists( $email ) ] : [ 'error' => 'Invalid email' ];
		echo json_encode( $response );
		wp_die();
	}

	add_action( 'wp_ajax_is_email_registered', 'mm_is_email_registered' );
	add_action( 'wp_ajax_nopriv_is_email_registered', 'mm_is_email_registered' );
}

