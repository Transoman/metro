<?php

/**
 * File: metro/includes/helpers.php
 *
 * Class MetroManhattanHelpers
 *
 * Provides a set of utility functions for the Metro Manhattan theme, including taxonomy management, YouTube data fetching, pagination, and more.
 */
class MetroManhattanHelpers
{
	/**
	 * Retrieves terms hierarchically for a given taxonomy.
	 *
	 * @param string $taxonomy_slug The taxonomy slug.
	 * @param int|null $parentID Optional. The parent term ID to retrieve child terms. Default is null.
	 *
	 * @return array The hierarchical list of terms.
	 */
    public static function get_hierarchically_taxonomy($taxonomy_slug, $parentID = null)
    {
        $result = [];
        $args = [
            'taxonomy' => $taxonomy_slug,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ];

        $parent_taxonomy = get_terms($args);
        if (!is_null($parentID)) {
            self::sort_terms_hierarchically($parent_taxonomy, $result, $parentID);
        } else {
            self::sort_terms_hierarchically($parent_taxonomy, $result);
        }

        return $result;
    }

	/**
	 * Sorts terms hierarchically.
	 *
	 * @param array &$taxonomy The taxonomy array to sort.
	 * @param array &$array The array to hold sorted terms.
	 * @param int $parentID The parent term ID to retrieve child terms.
	 */
    public static function sort_terms_hierarchically(array &$taxonomy, array &$array, $parentID = 0)
    {
        foreach ($taxonomy as $i => $tax) {
            if ($tax->parent == $parentID) {
                $array[$tax->term_id] = $tax;
                unset($taxonomy[$i]);
            }
        }

        foreach ($array as $parent_term) {
            $parent_term->children = array();
            self::sort_terms_hierarchically($taxonomy, $parent_term->children, $parent_term->term_id);
        }
    }

	/**
	 * Finds a parent term within an array of terms.
	 *
	 * @param array $terms_array The array of terms.
	 * @param int $parent The parent term ID to search for.
	 *
	 * @return mixed The found parent term or null.
	 */
    public static function find_parent_term($terms_array, $parent)
    {
        foreach ($terms_array as $item) {
            if ($item->parent == $parent) return $item;
        }
    }

	/**
	 * Retrieves YouTube video data using the YouTube API.
	 *
	 * @param string $api_key The YouTube API key.
	 * @param string $video_id The ID of the video to retrieve.
	 * @param bool $full_data Optional. Whether to retrieve full video data. Default is false.
	 *
	 * @return array|object The video data.
	 */
    public static function get_youtube_video($api_key, $video_id, $full_data = false)
    {
        $base_url = 'https://youtube.googleapis.com/youtube/v3/videos?part=snippet%2C%20statistics%2C%20contentDetails%2C%20status';
        $url = $base_url . '&' . http_build_query([
            'id' => $video_id,
            'key' => $api_key
        ]);
        $response = self::get_cached_request($url);
        if ($response instanceof WP_Error) {
            return [];
        } else {
            return self::build_youtube_data(json_decode($response['body']), $full_data);
        }
    }

	/**
	 * Performs a cached HTTP GET request.
	 *
	 * @param string $url The URL to request.
	 * @param int $expiration_time Optional. The cache expiration time in seconds. Default is 86400 (1 day).
	 *
	 * @return mixed The cached response.
	 */
    public static function get_cached_request($url, $expiration_time = 86400)
    {
        $cache_name = sha1($url);
        $cache = get_transient($cache_name);
        if ($cache === false) {
            $response = wp_remote_get($url);

            if ($response) {
                set_transient($cache_name, $response, $expiration_time);
                $cache = get_transient($cache_name);
            }
        }

        return $cache;
    }

	/**
	 * Constructs YouTube video data for display.
	 *
	 * @param object $data The video data.
	 * @param bool $full_data Optional. Whether to return full video data. Default is false.
	 *
	 * @return object The constructed video data.
	 */
    public static function build_youtube_data($data, $full_data = false)
    {
        if (!is_array($data->items)) {
            return;
        }
        $data = array_shift($data->items);
        $url = 'https://www.youtube.com/watch?' . http_build_query([
            'v' => $data->id,
        ]);
        $video_title = $data->snippet->title;
        $video_statistics = $data->statistics;
        $video_thumbnails = $data->snippet->thumbnails;
        $video_thumbnail = (property_exists($video_thumbnails, 'high')) ? $video_thumbnails->high : $video_thumbnails->high;
        if ($full_data) {
            return $data;
        }
        return (object)[
            'thumbnail' => $video_thumbnail,
            'title' => $video_title,
            'views' => $video_statistics->viewCount,
            'date' => self::get_youtube_diff($data->snippet->publishedAt, 'now'),
            'url' => $url
        ];
    }

	/**
	 * Calculates the time difference between two dates and returns a human-readable string.
	 *
	 * @param string $from The start date.
	 * @param string $to The end date.
	 * @param bool $full Optional. Whether to return the full time difference. Default is false.
	 *
	 * @return string The human-readable time difference.
	 */
    public static function get_youtube_diff($from, $to, $full = false)
    {
        $from = new DateTime($from);
        $to = new DateTime($to);
        $diff = $to->diff($from);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }

	/**
	 * Extracts a substring enclosed in square brackets from a string.
	 *
	 * @param string $string The input string.
	 *
	 * @return string|null The substring or null if not found.
	 */
    public static function get_string_with_brackets($string)
    {
        preg_match("/\[[^\]]*\]/", $string, $match);
        return $match;
    }

	/**
	 * Builds a descriptive string summarizing filter criteria for listings.
	 *
	 * @param array $data An associative array containing filter criteria such as 'uses' and 'locations'.
	 *
	 * @return string A string describing the number of listings and the applied filters.
	 */
    public static function build_filters($data)
    {
        $args = self::get_listings_args($data);
        $args['numberposts'] = -1;
        $total = sizeof(get_posts($args));
        $string = '';
        $string .= ($total > 100) ? round($total, -2) . '+' : $total;
        $substring = ($total > 1) ? "Spaces" : "Space";
        $string .= ((!empty($data['uses']) && sizeof($data['uses']) > 1) ? " Commercial " . $substring . " in Your Selected Categories" : ((!empty($data['uses']) && sizeof($data['uses']) < 2) ? ' ' . current($data['uses']) . ' ' . $substring : " Commercial " . $substring . ""));
        $string .= ((!empty($data['locations']) && sizeof($data['locations']) > 1) ? " in Your Selected Areas" : ((!empty($data['locations']) && sizeof($data['locations']) < 2) ? ' in ' . current($data['locations']) : " in New York City"));
        return $string;
    }

	/**
	 * Builds the meta query arguments for filtering listings based on size, price, and location coordinates.
	 *
	 * @param array $filters An associative array containing filter criteria like 'sizes' and 'prices'.
	 * @param object|null $coordinates An object containing geographical coordinates for filtering by location.
	 *
	 * @return array An array of meta query arguments for use in a WordPress query.
	 */
    public static function build_meta_args($filters, $coordinates = null)
    {
        $meta_args = [
            'relation' => 'AND'
        ];
        if (!empty($filters['sizes']) || !empty($filters['prices'])) {
            $merged = [];
            if (!empty($filters['sizes'])) {
                $merged['square_feet'] = $filters['sizes'];
            }
            if (!empty($filters['prices'])) {
                $merged['monthly_rent'] = $filters['prices'];
            }
            foreach ($merged as $key => $array) {
                $sub_args = [
                    'relation' => 'OR'
                ];
                if (!empty($array)) {
                    foreach ($array as $item) {
                        $match = self::get_string_with_brackets($item);
                        $value = str_replace($match, '', $item);
                        switch (current($match)) {
                            case '[max]':
                                $sub_args[] = [
                                    'key' => $key,
                                    'value' => $value,
                                    'type' => 'numeric',
                                    'compare' => '<'
                                ];
                                break;
                            case '[between]':
                                $value = explode('-', $value);
                                $sub_args[] = [
                                    'key' => $key,
                                    'value' => $value,
                                    'type' => 'numeric',
                                    'compare' => 'BETWEEN'
                                ];
                                break;
                            case '[min]':
                                $sub_args[] = [
                                    'key' => $key,
                                    'value' => $value,
                                    'type' => 'numeric',
                                    'compare' => '>'
                                ];
                                break;
                        }
                    }
                    $meta_args[] = $sub_args;
                }
            }
        }
        if ($coordinates !== null) {
            $northEastLatitude = $coordinates->northEast[0];
            $northEastLongitude = $coordinates->northEast[1];
            $southWestLatitude = $coordinates->southWest[0];
            $southWestLongitude = $coordinates->southWest[1];

            $meta_args[] = [
                'relation' => 'AND',
                [
                    'key' => 'post_latitude',
                    'value' => $northEastLatitude,
                    'compare' => '<'
                ],
                [
                    'key' => 'post_latitude',
                    'value' => $southWestLatitude,
                    'compare' => '>',
                ],
                [
                    'key' => 'post_longitude',
                    'value' => $northEastLongitude,
                    'compare' => '>',
                ],
                [
                    'key' => 'post_longitude',
                    'value' => $southWestLongitude,
                    'compare' => '<'
                ]
            ];
        }
        return $meta_args;
    }

	/**
	 * Retrieves the coordinates for a list of listings.
	 *
	 * @param array $listings An array of WP_Post objects representing the listings.
	 *
	 * @return array An array of associative arrays containing 'id', 'lat', and 'lng' for each listing.
	 */
    public static function get_listings_coordinates($listings)
    {
        $result = [];
        foreach ($listings as $listing) {
            $coordinates = ['id' => $listing->ID, 'lat' => get_field('map', $listing->ID)['lat'], 'lng' => get_field('map', $listing->ID)['lng']];
            $result[] = $coordinates;
        }
        return $result;
    }

	/**
	 * Retrieves search results for listings based on provided filters.
	 *
	 * @param array $filters The filter criteria for searching listings.
	 * @param int $offset The offset for the query results.
	 * @param int $numberposts The number of posts to retrieve.
	 * @param string $order The order in which to retrieve the listings (ASC or DESC).
	 * @param object|null $coordinates Optional coordinates to filter listings by location.
	 *
	 * @return array An associative array containing 'total', 'listings', and 'coordinates'.
	 */
    public static function get_listings_search_result($filters, $offset = 0, $numberposts = 15, $order = 'DESC', $coordinates = null)
    {
        static $results = null;
        if (is_null($results) || $results['total'] === 0) {
            do_action('get_listings_search_result', $filters);
            $_SESSION['filter'] = $filters;
            $sort_by = $_POST['order'] ?? 'DESC';
            $args = self::get_listings_args($filters, $sort_by, $coordinates);
            $args['posts_per_page'] = $numberposts;
            $args['offset'] = $offset;
            $args['custom_listings_query'] = 1;
            $query = new WP_Query($args);
            $all_listings_coordinates = self::get_listings_coordinates($query->get_posts());
            $results = ['total' => $query->found_posts, 'listings' => $query->get_posts(), 'coordinates' => $all_listings_coordinates];
            wp_reset_query();
        }
        return $results;
    }

	/**
	 * Builds the arguments array for querying listings based on the provided filters.
	 *
	 * @param array $request The request data containing filters like 'uses' and 'locations'.
	 * @param string $order The order in which to retrieve the listings (ASC or DESC).
	 * @param object|null $coordinates Optional coordinates to filter listings by location.
	 *
	 * @return array The arguments array for use in a WP_Query.
	 */
    public static function get_listings_args($request, $order = 'DESC', $coordinates = null)
    {
        $meta_args = self::build_meta_args($request, $coordinates);

        $uses = (!empty($request['uses'])) ? array_keys($request['uses']) : null;
        $locations = (!empty($request['locations'])) ? array_keys($request['locations']) : null;

        $locations = (!empty($locations) && current($locations) === -1) ? self::get_all_terms_ids('location') : $locations;
        $uses = (!empty($uses) && current($uses) === -1) ? self::get_all_terms_ids('listing-type') : $uses;
        
        $args = [
            'post_type' => 'listings',
            'orderby' => 'date',
            'order' => $order,
            'post_status' => 'publish',
            'tax_query' => [
                'relation' => 'AND',
                (!empty($request['uses'])) ? [
                    'taxonomy' => 'listing-type',
                    'field' => 'ID',
                    'terms' => $uses,
                ] : 1,
                (!empty($request['locations'])) ? [
                    'taxonomy' => 'location',
                    'field' => 'ID',
                    'terms' => $locations
                ] : 1,
            ],
            'meta_query' => $meta_args
        ];
      
          $sort_by = $order;
          
          switch ($sort_by) {
            case 'ASC':
              $args['orderby'] = 'date';
              $args['order'] = 'ASC';
              break;
            case 'DESC':
              $args['orderby'] = 'date';
              $args['order'] = 'DESC';
              break;
            case 'price_asc':
              $args['orderby'] = 'meta_value_num';
              $args['meta_key'] = 'rent_sf';
              $args['order'] = 'ASC';
              break;
            case 'price_desc':
              $args['orderby'] = 'meta_value_num';
              $args['meta_key'] = 'rent_sf';
              $args['order'] = 'DESC';
              break;
            case 'monthly_rent_asc':
              $args['orderby'] = 'meta_value_num';
              $args['meta_key'] = 'monthly_rent';
              $args['order'] = 'ASC';
              break;
            case 'monthly_rent_desc':
              $args['orderby'] = 'meta_value_num';
              $args['meta_key'] = 'monthly_rent';
              $args['order'] = 'DESC';
              break;
            case 'sqft_asc':
              $args['orderby'] = 'meta_value_num';
              $args['meta_key'] = 'square_feet';
              $args['order'] = 'ASC';
              break;
            case 'sqft_desc':
              $args['orderby'] = 'meta_value_num';
              $args['meta_key'] = 'square_feet';
              $args['order'] = 'DESC';
              break;
            default:
              $args['orderby'] = 'date';
              $args['order'] = 'DESC';
          }

        return $args;
    }

	/**
	 * Retrieves all term IDs for a given taxonomy.
	 *
	 * @param string $slug The taxonomy slug (e.g., 'location').
	 *
	 * @return array An array of term IDs for the specified taxonomy.
	 */
    private static function get_all_terms_ids($slug)
    {
        $terms = get_terms(['taxonomy' => $slug, 'hide_empty' => false]);
        return array_map(function ($item) {
            return $item->term_id;
        }, $terms);
    }

	/**
	 * Generates the HTML for pagination based on the total number of listings and the current page.
	 *
	 * @param int $total The total number of listings.
	 * @param int $current_page The current page number.
	 * @param int $limit The number of listings per page.
	 * @param string $current_url The base URL for pagination links.
	 *
	 * @return string The HTML for pagination.
	 */
    public static function get_pagination_of_search_result($total, $current_page, $limit, $current_url)
    {
        $total_pages = ceil($total / $limit);
        if ($current_page === 0) {
            $page_number = 1;
        } else {
            $page_number = $current_page;
        }

        $HTML = '';
        $prev_condition = ($page_number > 1) ? '' : 'disabled';
        $prev_link = ($page_number > 2) ? $current_url . 'page/' . $page_number - 1 . '/' : $current_url;
        $next_link = ($page_number < $total_pages) ? $current_url . 'page/' . $page_number + 1 . '/' : $current_url . 'page/' . $page_number . '/';
        $HTML .= '<a data-target="pagination_prev" href="' . $prev_link . '" class="button prev ' . $prev_condition . '" rel="prev">
                        <svg width="15" height="25" viewBox="0 0 15 25" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <path d="M13.5 1L2 12.5L13.5 24" stroke="var(--mm-blue-color)" stroke-width="2"
                                  stroke-linecap="round"/>
                        </svg>
                        <span>Previous</span>
                    </a>';
        $HTML .= '<ul>';
        $args = [
            'base' => $current_url . '%_%',
            'format' => 'page/%#%/',
            'total' => $total_pages,
            'current' => $page_number,
            'show_all' => False,
            'end_size' => 0,
            'mid_size' => 2,
            'prev_next' => False,
            'type' => 'array',
            'add_args' => False,
            'before_page_number' => '',
            'after_page_number' => ''
        ];
        $pagination = paginate_links($args);
        if (is_array($pagination)) {
            foreach ($pagination as $item) {
                $class_name = str_contains($item, 'current') ? 'class="current-page"' : (str_contains($item, 'dots') ? ('class="dots"') : '');
                $HTML .= '<li ' . $class_name . '>' . str_replace('<a', '<a data-target="pagination_link"', $item) . '</li>';
            }
        }
        $HTML .= '</ul>';
        $next_condition = ($current_page >= $total_pages) ? 'disabled' : '';
        $HTML .= '<a data-target="pagination_next" href="' . $next_link . '" class="button next ' . $next_condition . '" rel="next">
                        <svg width="14" height="25" viewBox="0 0 14 25" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 1L12.5 12.5L1 24" stroke="var(--mm-blue-color)" stroke-width="2"
                                  stroke-linecap="round"/>
                        </svg>
                        <span>Next</span>
                    </a>';
        return $HTML;
    }

	/**
	 * Generates HTML for listing cards.
	 *
	 * @param array $listings The list of listings to display.
	 * @param bool $map_card Whether to display as a map card.
	 * @param string|null $heading Optional heading text.
	 * @param bool $favourites_template Whether to use the favorites template.
	 *
	 * @return string The generated HTML for the listing cards.
	 */
    public static function get_listings_cards($listings, $map_card = false, $heading = null, $favourites_template = false)
    {
        ob_start();
        foreach ($listings as $listing) {
            get_template_part('templates/parts/listing', 'card', ['id' => $listing->ID, 'map_card' => $map_card, 'heading' => $heading, 'favourites_template' => $favourites_template]);
        }

        return ob_get_clean();
    }

	/**
	 * Retrieves filter fields based on the provided key.
	 *
	 * @param string $key The key to search for in the filter fields.
	 *
	 * @return mixed The value of the filter field corresponding to the key.
	 */
    public static function get_filters_fields($key)
    {
        $filters = get_field('choose_filters', 'option');
        foreach ($filters as $filter) {
            if (!empty($filter[$key])) {
                return $filter[$key];
            }
        }
    }

	/**
	 * Compares size values and returns matching sub-array.
	 *
	 * @param mixed $value The value to compare against.
	 * @param array $array The array of size ranges to compare.
	 *
	 * @return array The matching sub-array.
	 */
    public static function compare_size_values( $value, $array ) {
	    $result = array();
	    foreach ($array as $sub_array) {
		    if ($sub_array['type'] === '[max]' && $sub_array['value'] > $value) {
			    $result[] = $sub_array;
			    break;
		    } elseif ($sub_array['type'] === '[min]' && $sub_array['value'] < $value) {
			    $result[] = $sub_array;
			    break;
		    } elseif ($sub_array['type'] === '[between]') {
			    $sub_array_values = explode('-', $sub_array['value']);
			    if ($sub_array_values[0] <= $value && $sub_array_values[1] >= $value) {
				    $result[] = $sub_array;
				    break;
			    }
		    }
		    $result[] = $sub_array;
	    }
	    return $result;
    }

	/**
	 * Compares price values and returns matching sub-array.
	 *
	 * @param mixed $value The value to compare against.
	 * @param array $array The array of price ranges to compare.
	 *
	 * @return array The matching sub-array.
	 */
    public static function compare_price_values( $value, $array ) {
		$result        = array();
		$break_on_next = false;
		foreach ( $array as $sub_array ) {
			if ( $break_on_next ) {
				break;
			}
			if ( $sub_array['type'] === '[max]' && $sub_array['value'] >= $value ) {
				$result[]      = $sub_array;
				$break_on_next = true;
			} elseif ( $sub_array['type'] === '[max]' && $sub_array['value'] <= $value ) {
				$result[] = $sub_array;
			}

			if ( $sub_array['type'] === '[min]' && $sub_array['value'] < $value ) {
				$result[] = $sub_array;
			} elseif ( $sub_array['type'] === '[between]' ) {
				$sub_array_values = explode( '-', $sub_array['value'] );
				if ( $sub_array_values[0] >= $value && $sub_array_values[1] <= $value ) {
					$result[] = $sub_array;
				}
			}
		}

		return $result;
	}

	/**
	 * Compares filters values and returns the first matching sub-array.
	 *
	 * @param mixed $value The value to compare against.
	 * @param array $array The array of filter ranges to compare.
	 *
	 * @return array|null The matching sub-array or null if no match is found.
	 */
    public static function compare_filters_values( $value, $array ) {
		foreach ( $array as $sub_array ) {
			if ( $sub_array['type'] === '[max]' && $sub_array['value'] > $value ) {
				return $sub_array;
			} elseif ( $sub_array['type'] === '[min]' && $sub_array['value'] < $value ) {
				return $sub_array;
			} elseif ( $sub_array['type'] === '[between]' ) {
				$sub_array_values = explode( '-', $sub_array['value'] );
				if ( $sub_array_values[0] <= $value && $sub_array_values[1] >= $value ) {
					return $sub_array;
				}
			}
		}
	}

	/**
	 * Retrieves grandchild terms for a given set of terms.
	 *
	 * @param array $terms The array of terms.
	 *
	 * @return array|bool The array of grandchild terms or false if none are found.
	 */
    public static function get_grandchild_terms($terms)
    {
        if (empty($terms)) {
            return false;
        }

        return array_filter($terms, function ($item) {
            $children_exists = get_term_children($item->term_id, 'location');
            return (empty($children_exists));
        });
    }

	/**
	 * Generates breadcrumbs for a listing.
	 *
	 * @param int $post_id The ID of the post.
	 *
	 * @return string The generated HTML for the breadcrumbs.
	 */
    public static function listing_breadcrumbs( $post_id ) {
		$terms = wp_get_object_terms( $post_id, 'location', [ 'orderby' => 'parent' ] );
		if ( ! empty( $terms ) && isset( $terms[0] ) && $terms[0]->parent == 0 ) {
			array_shift( $terms );
		}
		$output = '';
		if ( ! empty( $terms ) ) {
			$output .= '<ul class="breadcrumbs">';
			$output .= '<li>New York City</li>';
			$output .= '<li>Manhattan</li>';
			foreach ( $terms as $term ) {
				$page_location = get_field( 'page_id', $term );
				if ( $page_location ) {
					$output .= '<li><a href="' . get_permalink( $page_location ) . '">' . $term->name . '</a></li>';
				}
			}
			$output .= '</ul>';
		}

		return $output;
	}

	/**
	 * Retrieves the primary location term for a given post.
	 *
	 * @param int $post_id The ID of the post.
	 *
	 * @return int The primary term ID or null if not found.
	 */
    public static function get_primary_location_term($post_id)
    {
        if (!function_exists('yoast_get_primary_term_id') && !empty($post_id)) {
            return;
        }

        return yoast_get_primary_term_id('location', $post_id);
    }

	/**
	 * Retrieves the parent term of a listing for a given taxonomy.
	 *
	 * @param int $post_id The ID of the post.
	 * @param string $taxonomy The taxonomy slug.
	 *
	 * @return WP_Term|null The parent term or null if not found.
	 */
    public static function listing_parent_term($post_id, $taxonomy)
    {
        $terms = wp_get_object_terms($post_id, $taxonomy, ['orderby' => 'parent']);
        if (is_array($terms)) {
            $parent_term = current($terms);
            foreach ($terms as $term) {
                if ($term->parent === $parent_term->term_id) {
                    return $term;
                }
            }
        }
    }

	/**
	 * Retrieves the child neighborhood term of a listing for a given parent term.
	 *
	 * @param int $post_id The ID of the post.
	 * @param WP_Term $parent_term The parent term.
	 *
	 * @return WP_Term|null The child neighborhood term or null if not found.
	 */
    public static function listing_child_neighborhood($post_id, $parent_term)
    {
        $primary_term = self::get_primary_location_term($post_id);
        $terms = wp_get_post_terms($post_id, 'location');
        $grandchild_terms = (is_array(self::get_grandchild_terms($terms))) ? self::get_grandchild_terms($terms) : [];
        $primary_grand_child_term = array_filter($grandchild_terms, function ($item) use ($primary_term) {
            return (!empty($primary_term) && $item->term_id == $primary_term);
        });
        return (!empty($primary_grand_child_term)) ? current($primary_grand_child_term) : current($grandchild_terms);
    }

	/**
	 * Retrieves HTML for blog posts based on provided arguments.
	 *
	 * @param array $args The query arguments.
	 *
	 * @return string The generated HTML for the blog posts.
	 */
    public static function get_blog_posts($args)
    {
        $posts = get_posts($args);
        $HTML = '';
        $template_args = [];
        if (!empty($args['category'])) {
            $template_args['category'] = get_cat_name($args['category']);
        }
        ob_start();
        foreach ($posts as $post) {
            $template_args['id'] = $post->ID;
            $HTML .= get_template_part('templates/parts/blog', 'post', $template_args);
        }
        $HTML = ob_get_clean();
        return $HTML;
    }

	/**
	 * Generates HTML breadcrumbs for a page or post.
	 *
	 * @param bool $is_page Whether the current item is a page.
	 *
	 * @return string The generated HTML for the breadcrumbs.
	 */
    public static function breadcrumbs($is_page = false)
    {
        $output = '<ul class="breadcrumbs">';
        $output .= '<li class="main">';
        $output .= '<a aria-label="home" href="' . get_home_url() . '">';
        $output .= '<svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8.08084 2.20959L12.7165 6.47441L12.9577 6.69636V13H8.84935V9.07834H6.6084V13H2.5V6.71473L2.7188 6.49596L6.98226 2.23246C7.12706 2.08767 7.32222 2.00444 7.52695 2.00017C7.73169 1.99591 7.93014 2.07094 8.08084 2.20959Z" stroke="#023A6C" stroke-width="1.5" stroke-linejoin="round"/></svg>';
        $output .= '</a></li>';
        if (!is_admin()) {
            $itemListElement = [];
            $position = 1;
            $itemListElement[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => "Home",
                'item' => get_home_url()
            ];
        }
        if (!$is_page) {
            $page_for_posts = get_option('page_for_posts');
            $page_for_posts = get_post($page_for_posts);
            if (is_home() || is_archive()) {
                $output .= '<li><a aria-label="' . $page_for_posts->post_title . '" href="' . get_permalink($page_for_posts->ID) . '"><span>' . $page_for_posts->post_title . '</span></a></li>';
                if (!is_admin()) {
                    $itemListElement[] = [
                        '@type' => 'ListItem',
                        'position' => $position++,
                        'name' => $page_for_posts->post_title,
                        'item' => get_permalink($page_for_posts->ID)
                    ];
                }
            }
            if (is_single()) {
                $category = current(get_the_category());
                $output .= '<li><a aria-label="' . $page_for_posts->post_title . '" href="' . get_permalink($page_for_posts->ID) . '"><span>' . $page_for_posts->post_title . '</span></a></li>';
                if (!is_admin()) {
                    $itemListElement[] = [
                        '@type' => 'ListItem',
                        'position' => $position++,
                        'name' => $page_for_posts->post_title,
                        'item' => get_permalink($page_for_posts->ID)
                    ];
                }
                $output .= '<li><span>' . $category->name . '</span></li>';
                if (!is_admin()) {
                    $itemListElement[] = [
                        '@type' => 'ListItem',
                        'position' => $position++,
                        'name' => $category->name,
                    ];
                }
            }
        } else {
            $current_page = get_post();
            $parent_pages = array_reverse(get_post_ancestors($current_page->ID));
            foreach ($parent_pages as $parent_page) {
                $output .= '<li class="parent"><a aria-label="' . get_the_title($parent_page) . '" href="' . get_permalink($parent_page) . '"><span>' . get_the_title($parent_page) . '</span></a></li>';
                if (!is_admin()) {
                    $itemListElement[] = [
                        '@type' => 'ListItem',
                        'position' => $position++,
                        'name' => get_the_title($parent_page),
                        'item' => get_permalink($parent_page)
                    ];
                }
            }
            if (!is_admin()) {
                $itemListElement[] = [
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => $current_page->post_title,
                ];
            }
            $output .= '<li class="current"><span>' . $current_page->post_title . '</span></li>';
        }


        $output .= '</ul>';
        ob_start();
        if (!is_admin()) {
            // Check if current URL is not a blog-related page
            $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $blog_url = home_url('/blog/');
            
            // Check if URL starts with blog path
            if (strpos($current_url, $blog_url) !== 0) {
                // $schema = [
                //     '@context' => 'https://schema.org',
                //     '@type' => 'BreadcrumbList',
                //     'itemListElement' => $itemListElement
                // ];
            }
        }

        if (!is_admin() && isset($schema)) : ?>
            <script type="application/ld+json">
                <?php
                  // echo json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                ?>
            </script>
        <?php
        endif;
        $output .= ob_get_clean();
        return $output;
    }

	/**
	 * Retrieves the latest YouTube videos for a channel.
	 *
	 * @param string $api_key The YouTube API key.
	 * @param int $maxResults The maximum number of videos to retrieve.
	 * @param string $channelID The ID of the YouTube channel.
	 *
	 * @return array The list of video data.
	 */
    public static function get_latest_videos($api_key, $maxResults, $channelID)
    {
        $base_url = "https://www.googleapis.com/youtube/v3/search?part=snippet&order=date&type=video";
        $url = $base_url . '&' . http_build_query([
            'key' => $api_key,
            'channelId' => $channelID,
            'maxResults' => $maxResults
        ]);
        $response = self::get_cached_request($url);
        if ($response instanceof WP_Error) {
            return [];
        } else {
            $videos = json_decode($response['body'])->items;
            $result = [];
            foreach ($videos as $video) {
                $data = self::get_youtube_video($api_key, $video->id->videoId);
                if (is_object($data)) {
                    $data->id = $video->id->videoId;
                }
                array_push($result, $data);
            }
            return $result;
        }
    }

	/**
	 * Retrieves the field value from a specific block within a page.
	 *
	 * @param string $field_name The name of the field.
	 * @param int $page_id The ID of the page.
	 * @param string $block_name The name of the block.
	 *
	 * @return mixed The value of the field or an empty string if not found.
	 */
    public static function get_field_of_pages_block($field_name, $page_id, $block_name)
    {
        if (isset($field_name) && is_page($page_id) && isset($block_name)) {
            $output = '';
            $parse_blocks = parse_blocks(get_the_content($page_id));
            foreach ($parse_blocks as $block) {
                if ($block['blockName'] === 'acf/' . $block_name) {
                    foreach ($block['attrs']['data'] as $key => $value) {
                        if ($key == $field_name) {
                            $output = $block['attrs']['data'][$key];
                        }
                    }
                }
            }
            return $output;
        }
    }

	/**
	 * Recursively searches for a value in a multidimensional array.
	 *
	 * @param mixed $needle The value to search for.
	 * @param array $haystack The array to search within.
	 * @param bool $strict Whether to use strict comparison (default: false).
	 *
	 * @return bool True if the value is found, otherwise false.
	 */
    public static function in_array_r($needle, $haystack, $strict = false)
    {
        if (!empty($haystack)) {
            foreach ($haystack as $item) {
                if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && self::in_array_r($needle, $item, $strict))) {
                    return true;
                }
            }

            return false;
        }
    }

	/**
	 * Retrieves a list of listing IDs based on the provided taxonomy and term.
	 *
	 * @param string $taxonomy The taxonomy slug.
	 * @param int $term The term ID to filter listings.
	 * @param int $offset The offset for pagination.
	 * @param int $numberposts Optional. The number of posts to retrieve. Default is 12.
	 * @param string $order Optional. The order in which to retrieve the listings. Default is 'DESC'.
	 *
	 * @return array An array containing the total count and the list of listing objects.
	 */
    public static function get_listings_by_taxonomy($taxonomy, $term, $offset, $numberposts = 12, $order = 'DESC')
    {

        $args = [
            'post_type' => 'listings',
            'orderby' => 'date',
            'posts_per_page' => $numberposts,
            'offset' => $offset,
            'post_status' => 'publish',
            'order' => $order,
            'custom_listings_query' => 1,
            'tax_query' => [
                'relation' => 'AND',
                [
                    'taxonomy' => $taxonomy,
                    'field' => 'ID',
                    'terms' => $term
                ],
            ],
        ];
        $query = new WP_Query($args);
        return ['total' => $query->found_posts, 'listings' => $query->get_posts()];

        wp_reset_query();
    }

	/**
	 * Generates an HTML sidebar menu for a page based on its parent page.
	 *
	 * @param int $page_id The ID of the current page.
	 * @param int $parent_id The ID of the parent page.
	 * @param bool $parent_title Optional. Whether to include the parent title in the menu. Default is false.
	 *
	 * @return string The HTML string representing the sidebar menu.
	 */
    public static function sidebar($page_id, $parent_id, $parent_title = false)
    {
        if (isset($page_id) && isset($parent_id)) {
            $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $output = '<ul class="menu">';
            $menu_pages = get_posts([
                'post_type' => 'page',
                'numberposts' => -1,
                'post_parent' => $parent_id,
                'order' => 'ASC',
                'orderby' => 'title'
            ]);
            $class = ($actual_link === get_permalink($parent_id)) ? "active" : "no-active";
            if ($parent_title) {
                $output .= '<li><a class="' . $class . '" href="' . get_permalink($parent_id) . '"><span>' . $parent_title . '</span></a></li>';
            }
            foreach ($menu_pages as $menu_page) {
                $class = ($actual_link === get_permalink($menu_page->ID)) ? "active" : "no-active";
                $output .= '<li><a class="' . $class . '" href="' . get_permalink($menu_page->ID) . '"><span>' . get_the_title($menu_page->ID) . '</span></a></li>';
            }
            $output .= '</ul>';
            return $output;
        }
    }

	/**
	 * Generates an HTML list with articles inserted at regular intervals.
	 *
	 * @param array $list The list of main items to display.
	 * @param array $articles The list of articles to insert.
	 * @param int $step The number of main items to display before inserting an article.
	 * @param bool $random Whether to randomly insert articles into the list.
	 *
	 * @return string The HTML string representing the list with articles.
	 */
    public static function list_with_articles( $list, $articles, $step, $random ) {
		if ( is_array( $list ) && is_array( $articles ) ) {
			$output        = '<ul>';
			$article_index = 0;
			if ( ! $random ) {
				for ( $i = 0; $i < count( $list ); $i ++ ) {
					$output .= '<li><a href="' . get_permalink( $list[ $i ]->ID ) . '">' . get_the_title( $list[ $i ]->ID ) . '</a></li>';
					if ( $i % $step == 0 && isset( $articles[ $article_index ] ) ) {
						$output .= self::get_recommended_article( $articles[ $article_index ] );
						$article_index ++;
					}
				}
			} else {
				$newArray = array_merge( $list );
				for ( $i = 0; $i < count( $articles ); $i ++ ) {
					$position = rand( 0, count( $newArray ) );
					array_splice( $newArray, $position, 0, $articles[ $i ] );
				}
				for ( $i = 0; $i < count( $newArray ); $i ++ ) {
					if ( is_object( $newArray[ $i ] ) ) {
						$output .= '<li><a href="' . get_permalink( $newArray[ $i ]->ID ) . '">' . get_the_title( $newArray[ $i ]->ID ) . '</a></li>';
					} else {
						$output .= self::get_recommended_article( $newArray[ $i ] );
					}
				}
			}

			$output .= '</ul>';

			return $output;
		}
	}

	/**
	 * Retrieves the recommended article HTML for a given post ID.
	 *
	 * @param int $id The post ID of the recommended article.
	 *
	 * @return string The HTML string representing the recommended article.
	 */
  private static function get_recommended_article($id)
  {
    $output = '';
    $category = current(get_the_category($id));
    $publish_date = date('d M \'y', strtotime(get_the_date('Y-m-d', $id)));
    $author_name = get_the_author_meta('display_name', get_post_field('post_author', $id));
    
    $output .= '<li class="recommended-article">';
    $output .= '<div class="article">';
    $output .= '<h2>Recommended Articles</h2>';
    $output .= '<a href="' . get_permalink($id) . '">';
    $output .= '<div class="image">';
    $output .= wp_get_attachment_image(get_post_thumbnail_id($id), 'full', '', ['loading' => 'lazy']);
    $output .= '</div>';
    $output .= '<div class="text">';
    $output .= '<span class="category">' . $category->name . '</span>';
    $output .= '<h3>' . get_the_title($id) . '</h3>';
    
    // Add meta section
    $output .= '<div class="meta" style="margin-top: auto; padding-top: 10px; display: flex; gap: 5px;">';
    $output .= '<span class="date" style="font-weight: 400;font-size: 11.5px;color: #6E7484;">' . $publish_date . '</span>';
    $output .= '<span style="font-weight: 400; font-size: 10px; color: #6E7484;">•</span>';
    $output .= '<span class="author" style="font-weight: 400;font-size: 11.5px;color: #6E7484;">By ' . $author_name . '</span>';
    $output .= '</div>';
    
    $output .= '</div></div></a></li>';
    
    return $output;
  }

	/**
	 * Retrieves posts based on the specified mode and arguments.
	 *
	 * @param string $mode The mode of retrieval ('featured', 'nearby', 'category').
	 * @param array $args Optional. Additional arguments for retrieving posts.
	 *
	 * @return array The list of post IDs matching the criteria.
	 */
    public static function get_posts_by_mode($mode, $args = [])
    {
        $query_args = [
            'orderby' => 'rand',
            'post_type' => $args['post_type']
        ];

        if (array_key_exists('numberposts', $args)) {
            $query_args['numberposts'] = $args['numberposts'];
        }
        
        if (array_key_exists('exclude', $args)) {
            if (is_array($args['exclude'])) {
                $query_args['post__not_in'] = $args['exclude'];
            } else {
                $query_args['post__not_in'] = [$args['exclude']];
            }
        }

        switch ($mode) {
            case 'featured':
                if ($query_args['post_type'] == 'listings') {
                    $query_args['orderby'] = 'date';
                    $query_args['order'] = 'DESC';
                    $query_args['meta_query'] = [
                        'relation' => 'OR',
                        [
                            'key' => 'high_quality',
                            'value' => '0',
                            'compare' => '='
                        ],
                        [
                            'key' => 'high_quality',
                            'value' => '',
                            'compare' => 'NOT EXISTS'
                        ]
                    ];
                } else if ($query_args['post_type'] == 'post') {
                    $query_args['meta_key'] = 'high_quality';
                    $query_args['meta_value'] = true;
                }
                break;
            case 'nearby':
                $query_args['tax_query'] = [
                    [
                        'taxonomy' => 'location',
                        'field' => 'ID',
                        'terms' => $args['location']
                    ]
                ];
                break;
            case 'category':
                $query_args['category'] = $args['category'];
                break;
        }
        $posts = get_posts($query_args);
        return array_map(function ($post) {
            return $post->ID;
        }, $posts);
    }

	/**
	 * Retrieves nearby transportation options based on given coordinates.
	 *
	 * @param float $latitude The latitude of the location.
	 * @param float $longitude The longitude of the location.
	 * @param int $radius Optional. The search radius in meters. Default is 500.
	 * @param int $speed Optional. The speed used to calculate distance. Default is 3500.
	 *
	 * @return array|string The list of nearby transportation options or an error message.
	 */
    public static function get_nearby_transport( $latitude, $longitude, $radius = 500, $speed = 3500 ) {
		$overpass_url = 'http://overpass-api.de/api/interpreter';

		$query = "[out:json];
        (
          node['public_transport'='stop_position'](around:$radius,$latitude,$longitude)->.a;
          way['public_transport'='platform']['bus'='yes'](around:$radius,$latitude,$longitude);
          way['public_transport'='platform']['tram'='yes'](around:$radius,$latitude,$longitude);
          way['public_transport'='platform']['subway'='yes'](around:$radius,$latitude,$longitude);
          way['amenity'='parking']['park_ride'='yes'](around:$radius,$latitude,$longitude);
          relation['public_transport'='platform']['bus'='yes'](around:$radius,$latitude,$longitude);
          relation['public_transport'='platform']['tram'='yes'](around:$radius,$latitude,$longitude);
          relation['public_transport'='platform']['subway'='yes'](around:$radius,$latitude,$longitude);
          relation['amenity'='parking']['park_ride'='yes'](around:$radius,$latitude,$longitude);
        );
        out center;";

		$data = array(
			'data' => $query
		);

		$request = wp_remote_post( $overpass_url, [
			'method'  => 'POST',
			'timeout' => 15,
			'body'    => $data
		] );

		$storage = [
			'bus'     => [],
			'subway'  => [],
			'parking' => []
		];
		$output  = [];

		if ( ! is_wp_error( $request ) && $request['response']['code'] == 200 ) {
			$result   = json_decode( $request['body'], true );
			$elements = $result['elements'];
			foreach ( $elements as $element ) {
				if ( $element['type'] == 'node' ) {
					if ( isset( $element['tags']['bus'] ) && $element['tags']['bus'] == 'yes' && ! empty( $element['tags']['name'] ) && ! self::in_array_r( $element['tags']['name'], $storage['bus'] ) ) {
						$storage['bus'][] = $element;
					}
					if ( isset( $element['tags']['subway'] ) && $element['tags']['subway'] == 'yes' && ! self::in_array_r( $element['tags']['name'], $storage['subway'] ) ) {
						$storage['subway'][] = $element;
					}
				}
				if ( $element['type'] == 'way' && isset( $element['tags']['amenity'] ) && $element['tags']['amenity'] == 'parking' ) {
					$storage['parking'][] = $element;
				}
			}
			if ( ! empty( $storage['subway'] ) ) {
				foreach ( $storage['subway'] as $item ) {
					if ( isset( $item['tags']['name'] ) && isset( $item['lat'] ) && isset( $item['lon'] ) ) {
						$output['subway'][] = [
							'name'     => self::get_subway_icon( $item['tags']['name'] ),
							'distance' => round( ( self::get_distance_by_coordinates( [ $latitude, $longitude ], [
										$item['lat'],
										$item['lon']
									] ) / $speed ) * 60 )
						];
					}
				}
			}

			if ( ! empty( $storage['parking'] ) ) {
				foreach ( $storage['parking'] as $item ) {
					if ( isset( $item['tags']['name'] ) && isset( $item['center'] ) ) {
						$output['parking'][] = [
							'name'     => $item['tags']['name'],
							'distance' => round( ( self::get_distance_by_coordinates( [
										$latitude,
										$longitude
									], [ $item['center']['lat'], $item['center']['lon'] ] ) / $speed ) * 60 )
						];
					}
				}
			}

			if ( ! empty( $storage['bus'] ) ) {
				foreach ( $storage['bus'] as $item ) {
					if ( isset( $item['tags']['name'] ) && isset( $item['lat'] ) && isset( $item['lon'] ) ) {
						$output['bus'][] = [
							'name'     => $item['tags']['name'],
							'distance' => round( ( self::get_distance_by_coordinates( [ $latitude, $longitude ], [
										$item['lat'],
										$item['lon']
									] ) / $speed ) * 60 )
						];
					}
				}
			}

			return $output;
		} else {
			return 'Something went wrong';
		}
	}

	/**
	 * Retrieves a formatted string with subway icons based on the station name.
	 *
	 * @param string $name The name of the subway station.
	 *
	 * @return string The formatted string with subway icons.
	 */
    public static function get_subway_icon($name)
    {
        if ($name) {
            $subway_stations = array(
                '1' => array(
                    'Van Cortlandt Park - 242nd Street',
                    '238th Street',
                    '231st Street',
                    'Marble Hill - 225th Street',
                    '215th Street',
                    '207th Street',
                    'Dyckman Street',
                    '191st Street',
                    '181st Street',
                    '168th Street - Washington Heights',
                    '157th Street',
                    '145th Street',
                    '137th Street - City College',
                    '125th Street',
                    '116th Street - Columbia University',
                    'Cathedral Parkway - 110th Street',
                    '103rd Street',
                    '96th Street',
                    '86th Street',
                    '79th Street',
                    '72nd Street',
                    '66th Street - Lincoln Center',
                    '59th Street - Columbus Circle',
                    '50th Street',
                    'Times Square - 42nd Street',
                    '34th Street - Penn Station',
                    '28th Street',
                    '23rd Street',
                    '18th Street',
                    '14th Street',
                    'Christopher Street - Sheridan Square',
                    'Houston Street',
                    'Canal Street',
                    'Franklin Street',
                    'Chambers Street',
                    'Cortlandt Street',
                    'Rector Street',
                    'South Ferry'
                ),
                '2' => array(
                    'Wakefield - 241st Street',
                    'Nereid Avenue',
                    '233rd Street',
                    '225th Street',
                    '219th Street',
                    'Gun Hill Road',
                    'Burke Avenue',
                    'Allerton Avenue',
                    'Pelham Parkway',
                    'Bronx Park East',
                    'East 180th Street',
                    'West Farms Square - East Tremont Avenue',
                    '174th Street',
                    'Freeman Street',
                    'Simpson Street',
                    'Intervale Avenue',
                    'Prospect Avenue',
                    'Jackson Avenue',
                    '3rd Avenue - 149th Street',
                    '149th Street - Grand Concourse',
                    '135th Street',
                    '125th Street',
                    '116th Street',
                    'Central Park North - 110th Street',
                    '96th Street',
                    '72nd Street',
                    'Times Square - 42nd Street',
                    '34th Street - Penn Station',
                    '14th Street',
                    'Chambers Street',
                    'Park Place',
                    'Fulton Street',
                    'Wall Street',
                    'Clark Street',
                    'Borough Hall',
                    'Hoyt Street',
                    'Nevins Street',
                    'Atlantic Avenue - Barclays Center',
                    'Bergen Street',
                    'Grand Army Plaza',
                    'Eastern Parkway - Brooklyn Museum',
                    'Franklin Avenue',
                    'Nostrand Avenue',
                    'Kingston Avenue',
                    'Crown Heights - Utica Avenue',
                    'Sutter Avenue - Rutland Road',
                    'Saratoga Avenue',
                    'Rockaway Avenue',
                    'Junius Street',
                    'Pennsylvania Avenue',
                    'Van Siclen Avenue',
                    'New Lots Avenue'
                ),
                '3' => array(
                    'Harlem - 148th Street',
                    '145th Street',
                    '135th Street',
                    '125th Street',
                    '116th Street',
                    'Central Park North - 110th Street',
                    '96th Street',
                    '72nd Street',
                    'Times Square - 42nd Street',
                    '34th Street - Penn Station',
                    '14th Street',
                    'Chambers Street',
                    'Park Place',
                    'Fulton Street',
                    'Wall Street',
                    'Clark Street',
                    'Borough Hall',
                    'Hoyt Street',
                    'Nevins Street',
                    'Atlantic Avenue - Barclays Center',
                    'Bergen Street',
                    'Grand Army Plaza',
                    'Eastern Parkway - Brooklyn Museum',
                    'Franklin Avenue',
                    'Nostrand Avenue',
                    'Kingston Avenue',
                    'Crown Heights - Utica Avenue',
                    'Sutter Avenue - Rutland Road',
                    'Saratoga Avenue',
                    'Rockaway Avenue',
                    'Junius Street',
                    'Pennsylvania Avenue',
                    'Van Siclen Avenue',
                    'New Lots Avenue'
                ),
                '4' => array(
                    'Woodlawn',
                    'Mosholu Parkway',
                    'Bedford Park Boulevard - Lehman College',
                    'Kingsbridge Road',
                    'Fordham Road',
                    '183rd Street',
                    'Burnside Avenue',
                    '176th Street',
                    'Mount Eden Avenue',
                    '170th Street',
                    '167th Street',
                    '161st Street - Yankee Stadium',
                    '149th Street - Grand Concourse',
                    '138th Street - Grand Concourse',
                    '125th Street',
                    '86th Street',
                    '59th Street',
                    'Grand Central - 42nd Street',
                    '14th Street - Union Square',
                    'Brooklyn Bridge - City Hall',
                    'Wall Street',
                    'Bowling Green'
                ),
                '5' => array(
                    'Eastchester - Dyre Avenue',
                    'Baychester Avenue',
                    'Gun Hill Road',
                    'Pelham Parkway',
                    'Morris Park',
                    'E 180th Street',
                    'West Farms Square - E Tremont Avenue',
                    '174th Street',
                    'Freeman Street',
                    'Simpson Street',
                    'Intervale Avenue',
                    'Prospect Avenue',
                    'Jackson Avenue',
                    '3rd Avenue - 149th Street',
                    '149th Street - Grand Concourse',
                    '138th Street - Grand Concourse',
                    '125th Street',
                    '86th Street',
                    '59th Street',
                    'Grand Central - 42nd Street',
                    '14th Street - Union Square',
                    'Brooklyn Bridge - City Hall',
                    'Wall Street',
                    'Bowling Green'
                ),
                '6' => array(
                    'Pelham Bay Park',
                    'Buhre Avenue',
                    'Middletown Road',
                    'Westchester Square - E Tremont Avenue',
                    'Zerega Avenue',
                    'Castle Hill Avenue',
                    'Parkchester',
                    'St Lawrence Avenue',
                    'Morrison Avenue- Sound View',
                    'Elder Avenue',
                    'Whitlock Avenue',
                    'Hunts Point Avenue',
                    'Longwood Avenue',
                    'E 149th Street',
                    'E 143rd Street - St Mary\'s Street',
                    'Cypress Avenue',
                    'Brook Avenue',
                    '3rd Avenue - 138th Street',
                    '125th Street',
                    '116th Street',
                    '110th Street',
                    '103rd Street',
                    '96th Street',
                    '77th Street',
                    '68th Street - Hunter College',
                    '59th Street',
                    '51st Street',
                    'Grand Central - 42nd Street',
                    '33rd Street',
                    '28th Street',
                    '23rd Street',
                    '14th Street - Union Square',
                    'Astor Place',
                    'Bleecker Street',
                    'Spring Street',
                    'Canal Street',
                    'Brooklyn Bridge - City Hall',
                    'Fulton Street',
                    'Wall Street',
                    'Bowling Green'
                ),
                '7' => array(
                    'Flushing - Main Street',
                    'Mets - Willets Point',
                    '111th Street',
                    '103rd Street - Corona Plaza',
                    'Junction Boulevard',
                    '90th Street - Elmhurst Avenue',
                    '82nd Street - Jackson Heights',
                    '74th Street - Broadway',
                    '69th Street',
                    'Woodside - 61st Street',
                    '52nd Street',
                    '46th Street - Bliss Street',
                    '40th Street - Lowery Street',
                    '33rd Street - Rawson Street',
                    'Queensboro Plaza',
                    'Court Square - 23rd Street',
                    'Hunters Point Avenue',
                    'Vernon Boulevard - Jackson Avenue',
                    'Grand Central - 42nd Street',
                    '5th Avenue',
                    'Times Square - 42nd Street',
                    '34th Street - Hudson Yards'
                ),
                'A' => array(
                    'Inwood - 207th Street',
                    'Dyckman Street',
                    '190th Street',
                    '181st Street',
                    '175th Street',
                    '168th Street',
                    '163rd Street - Amsterdam Avenue',
                    '155th Street',
                    '145th Street',
                    '135th Street',
                    '125th Street',
                    '116th Street',
                    'Cathedral Parkway (110th Street)',
                    '103rd Street',
                    '96th Street',
                    '86th Street',
                    '81st Street - Museum of Natural History',
                    '72nd Street',
                    '59th Street - Columbus Circle',
                    '50th Street',
                    '42nd Street - Port Authority Bus Terminal',
                    '34th Street - Penn Station',
                    '23rd Street',
                    '14th Street',
                    'Christopher Street - Sheridan Square',
                    'Houston Street',
                    'Canal Street',
                    'Chambers Street',
                    'Fulton Street',
                    'High Street',
                    'Jay Street - MetroTech',
                    'Hoyt - Schermerhorn Streets',
                    'Lafayette Avenue',
                    'Clinton - Washington Avenues',
                    'Franklin Avenue',
                    'Nostrand Avenue',
                    'Kingston - Throop Avenues',
                    'Utica Avenue',
                    'Ralph Avenue',
                    'Rockaway Avenue',
                    'Broadway Junction',
                    'Liberty Avenue',
                    'Van Siclen Avenue',
                    'Shepherd Avenue',
                    'Euclid Avenue',
                    'Grant Avenue',
                    '80th Street',
                    '88th Street',
                    'Rockaway Boulevard',
                    '104th Street',
                    '111th Street',
                    'Ozone Park - Lefferts Boulevard',
                    'Aqueduct - North Conduit Avenue',
                    'Aqueduct Racetrack',
                    'Howard Beach - JFK Airport',
                    'Broad Channel',
                    'Beach 67th Street',
                    'Beach 60th Street',
                    'Beach 44th Street',
                    'Beach 36th Street',
                    'Beach 25th Street',
                    'Far Rockaway - Mott Avenue'
                ),
                'B' => array(
                    'Bedford Park Boulevard',
                    'Kingsbridge Road',
                    'Fordham Road',
                    '182-183 Streets',
                    'Tremont Avenue',
                    '174-175 Streets',
                    '170th Street',
                    '167th Street',
                    '161st Street - Yankee Stadium',
                    '155th Street',
                    '145th Street',
                    '135th Street',
                    '125th Street',
                    '116th Street',
                    'Cathedral Parkway (110th Street)',
                    '103rd Street',
                    '96th Street',
                    '86th Street',
                    '81st Street - Museum of Natural History',
                    '72nd Street',
                    '59th Street - Columbus Circle',
                    '50th Street',
                    '42nd Street - Bryant Park',
                    '34th Street - Herald Square',
                    '23rd Street',
                    '14th Street',
                    'West 4th Street',
                    'Broadway-Lafayette Street',
                    'Grand Street',
                    'DeKalb Avenue',
                    'Atlantic Avenue - Barclays Center',
                    '7th Avenue',
                    'Prospect Park',
                    'Church Avenue',
                    'Beverly Road',
                    'Newkirk Avenue',
                    'Flatbush Avenue - Brooklyn College'
                ),
                'C' => array(
                    '168th Street',
                    '163rd Street - Amsterdam Avenue',
                    '155th Street',
                    '145th Street',
                    '135th Street',
                    '125th Street',
                    '116th Street',
                    'Cathedral Parkway (110th Street)',
                    '103rd Street',
                    '96th Street',
                    '86th Street',
                    '81st Street - Museum of Natural History',
                    '72nd Street',
                    '59th Street - Columbus Circle',
                    '50th Street',
                    '42nd Street - Port Authority Bus Terminal',
                    '34th Street - Penn Station',
                    '23rd Street',
                    '14th Street',
                    'West 4th Street',
                    'Spring Street',
                    'Canal Street',
                    'Chambers Street',
                    'Fulton Street',
                    'High Street',
                    'Jay Street - MetroTech',
                    'Hoyt - Schermerhorn Streets',
                    'Lafayette Avenue',
                    'Clinton - Washington Avenues',
                    'Franklin Avenue',
                    'Nostrand Avenue',
                    'Kingston - Throop Avenues',
                    'Utica Avenue',
                    'Ralph Avenue',
                    'Rockaway Avenue',
                    'Broadway Junction',
                    'Liberty Avenue',
                    'Van Siclen Avenue',
                    'Shepherd Avenue',
                    'Euclid Avenue',
                    'Grant Avenue',
                    '80th Street',
                    '88th Street',
                    'Rockaway Boulevard',
                    '104th Street',
                    '111th Street',
                    'Ozone Park - Lefferts Boulevard',
                    'Aqueduct - North Conduit Avenue',
                    'Aqueduct Racetrack',
                    'Howard Beach - JFK Airport'
                ),
                'D' => array(
                    'Norwood - 205th Street',
                    'Bedford Park Boulevard',
                    'Kingsbridge Road',
                    'Fordham Road',
                    '182nd-183rd Streets',
                    'Tremont Avenue',
                    '174th-175th Streets',
                    '170th Street',
                    '167th Street',
                    '161st Street - Yankee Stadium',
                    '155th Street',
                    '145th Street',
                    '135th Street',
                    '125th Street',
                    '116th Street',
                    'Cathedral Parkway (110th Street)',
                    '103rd Street',
                    '96th Street',
                    '86th Street',
                    '81st Street - Museum of Natural History',
                    '72nd Street',
                    '59th Street - Columbus Circle',
                    '50th Street',
                    '42nd Street - Bryant Park',
                    '34th Street - Herald Square',
                    '23rd Street',
                    '14th Street',
                    'West 4th Street',
                    'Broadway-Lafayette Street',
                    'Grand Street',
                    'DeKalb Avenue',
                    'Atlantic Avenue - Barclays Center',
                    '7th Avenue',
                    'Prospect Park',
                    'Parkside Avenue',
                    'Church Avenue',
                    'Beverly Road',
                    'Newkirk Avenue',
                    'Flatbush Avenue - Brooklyn College'
                ),
                'E' => array(
                    'Jamaica Center - Parsons/Archer',
                    'Sutphin Blvd - Archer Avenue - JFK Airport',
                    'Jamaica - Van Wyck',
                    'Forest Hills - 71st Avenue',
                    '67th Avenue',
                    '63rd Drive - Rego Park',
                    'Woodhaven Boulevard',
                    'Grand Avenue - Newtown',
                    'Elmhurst Avenue',
                    'Jackson Heights - Roosevelt Avenue',
                    '65th Street',
                    'Northern Blvd',
                    '46th Street',
                    'Steinway Street',
                    '36th Street',
                    'Queens Plaza',
                    'Court Square - 23rd Street',
                    'Lexington Avenue - 53rd Street',
                    '5th Avenue - 53rd Street',
                    '7th Avenue',
                    '50th Street',
                    '42nd Street - Port Authority Bus Terminal',
                    '34th Street - Penn Station',
                    '23rd Street',
                    '14th Street',
                    'West 4th Street',
                    'Spring Street',
                    'Canal Street',
                    'Chambers Street',
                    'World Trade Center'
                ),
                'F' => array(
                    'Jamaica - 179 Street',
                    '169 Street',
                    'Parsons Boulevard',
                    'Sutphin Boulevard',
                    'Briarwood - Van Wyck Boulevard',
                    'Kew Gardens - Union Tpke',
                    '75 Avenue',
                    'Forest Hills - 71st Avenue',
                    'Jackson Heights - Roosevelt Avenue',
                    '65th Street',
                    'Northern Blvd',
                    '46th Street',
                    'Steinway Street',
                    '36th Street',
                    'Queens Plaza',
                    'Court Square - 23rd Street',
                    'Lexington Avenue - 53rd Street',
                    '5th Avenue - 53rd Street',
                    '7th Avenue',
                    '42nd Street - Bryant Park',
                    '34th Street - Herald Square',
                    '23rd Street',
                    '14th Street',
                    'West 4th Street',
                    'Broadway-Lafayette Street',
                    '2nd Avenue',
                    'Delancey Street - Essex Street',
                    'East Broadway',
                    'York Street',
                    'Bergen Street',
                    'Carroll Street',
                    'Smith-9 Streets',
                    '4th Avenue',
                    '7th Avenue',
                    '15th Street - Prospect Park',
                    'Fort Hamilton Parkway',
                    'Church Avenue',
                    'Ditmas Avenue',
                    '18th Avenue',
                    'Avenue I',
                    'Bay Parkway',
                    'Avenue N',
                    'Avenue P',
                    'Kings Highway',
                    'Avenue U',
                    'Avenue X',
                    'Neptune Avenue',
                    'West 8th Street - NY Aquarium',
                    'Coney Island - Stillwell Avenue'
                ),
                'G' => array(
                    'Court Square',
                    '21 Street',
                    'Greenpoint Avenue',
                    'Nassau Avenue',
                    'Metropolitan Avenue',
                    'Broadway',
                    'Flushing Avenue',
                    'Myrtle - Willoughby Avenues',
                    'Bedford - Nostrand Avenues',
                    'Classon Avenue',
                    'Clinton - Washington Avenues',
                    'Fulton Street',
                    'Hoyt - Schermerhorn Streets',
                    'Bergen Street',
                    'Carroll Street',
                    'Smith-9 Streets',
                    '4 Avenue - 9 Street',
                    '7 Avenue',
                    '15 Street - Prospect Park',
                    'Fort Hamilton Parkway',
                    'Church Avenue',
                    'Beverly Road',
                    'Newkirk - Plaza',
                    'Avenue H',
                    'Avenue J',
                    'Avenue M',
                    'Kings Highway',
                    'Avenue U',
                    'Avenue X',
                    'Neptune Avenue'
                ),
                'J' => array(
                    'Jamaica Center - Parsons/Archer',
                    'Sutphin Blvd - Archer Avenue - JFK Airport',
                    'Jamaica - Van Wyck',
                    'Woodhaven Boulevard',
                    '85th Street - Forest Parkway',
                    'Woodhaven Boulevard',
                    '75th Street',
                    'Cypress Hills',
                    'Crescent Street',
                    'Norwood Avenue',
                    'Cleveland Street',
                    'Van Siclen Avenue',
                    'Alabama Avenue',
                    'Broadway Junction',
                    'Chauncey Street',
                    'Halsey Street',
                    'Gates Avenue',
                    'Kosciuszko St',
                    'Myrtle Avenue',
                    'Flushing Avenue',
                    'Lorimer Street',
                    'Hewes Street',
                    'Marcy Avenue',
                    'Essex Street',
                    'Bowery',
                    'Canal Street',
                    'Chambers Street',
                    'Fulton Street',
                    'Broad Street',
                    'Wall Street',
                    'Clark Street',
                    'High Street',
                    'Jay Street - MetroTech',
                    'DeKalb Avenue',
                    'Hewes Street',
                    'Lorimer Street',
                    'Flushing Avenue',
                    'Myrtle Avenue',
                    'Kosciuszko Street',
                    'Gates Avenue',
                    'Halsey Street',
                    'Chauncey Street',
                    'Broadway Junction',
                    'Alabama Avenue',
                    'Van Siclen Avenue',
                    'Cleveland Street',
                    'Norwood Avenue',
                    'Crescent Street',
                    'Cypress Hills',
                    '75th Street',
                    'Woodhaven Boulevard',
                    '85th Street - Forest Parkway'
                ),
                'L' => array(
                    '8 Avenue',
                    '6 Avenue',
                    'Union Square - 14th Street',
                    '3 Avenue',
                    '1 Avenue',
                    'Bedford Avenue',
                    'Lorimer Street',
                    'Graham Avenue',
                    'Grand Street',
                    'Montrose Avenue',
                    'Morgan Avenue',
                    'Jefferson Street',
                    'DeKalb Avenue',
                    'Myrtle - Wyckoff Avenues',
                    'Halsey Street',
                    'Wilson Avenue',
                    'Bushwick Avenue - Aberdeen Street',
                    'Broadway Junction',
                    'Atlantic Avenue',
                    'Sutter Avenue',
                    'Livonia Avenue',
                    'New Lots Avenue'
                ),
                'M' => array(
                    '96th Street',
                    '86th Street',
                    '81st Street - Museum of Natural History',
                    '72nd Street',
                    '59th Street - Columbus Circle',
                    '7th Avenue',
                    '5th Avenue - 53rd Street',
                    '47-50 Streets - Rockefeller Center',
                    '42nd Street - Bryant Park',
                    '34th Street - Herald Square',
                    '23rd Street',
                    '14th Street',
                    'West 4th Street',
                    'Broadway-Lafayette Street',
                    '2nd Avenue',
                    'Essex Street',
                    'Delancey Street - Essex Street',
                    'East Broadway',
                    'York Street',
                    'High Street',
                    'Clark Street',
                    'Court Street',
                    'Hoyt Street',
                    'Jay Street - MetroTech',
                    'DeKalb Avenue',
                    'Myrtle Avenue',
                    'Flushing Avenue',
                    'Lorimer Street',
                    'Hewes Street',
                    'Marcy Avenue',
                    'Essex Street',
                    'Bowery',
                    'Canal Street',
                    'Chambers Street',
                    'Fulton Street',
                    'Broad Street'
                ),
                'N' => array(
                    'Astoria - Ditmars Boulevard',
                    'Astoria Boulevard',
                    '30th Avenue',
                    'Broadway',
                    '36th Avenue',
                    '39th Avenue',
                    'Queensboro Plaza',
                    'Lexington Avenue - 59th Street',
                    '5th Avenue',
                    '57th Street - 7th Avenue',
                    '49th Street',
                    'Times Square - 42nd Street',
                    '34th Street - Herald Square',
                    '28th Street',
                    '23rd Street',
                    'Union Square - 14th Street',
                    '8th Street - NYU',
                    'Prince Street',
                    'Canal Street',
                    'City Hall',
                    'Cortlandt Street',
                    'Rector Street',
                    'Whitehall Street'
                ),
                'Q' => array(
                    '96th Street',
                    '86th Street',
                    '81st Street - Museum of Natural History',
                    '72nd Street',
                    '59th Street - Columbus Circle',
                    '7th Avenue',
                    '5th Avenue - 53rd Street',
                    '47th-50th Streets - Rockefeller Center',
                    '42nd Street - Bryant Park',
                    '34th Street - Herald Square',
                    '23rd Street',
                    '14th Street',
                    'Union Square - 14th Street',
                    '8th Street - NYU',
                    'Prince Street',
                    'Canal Street',
                    'DeKalb Avenue',
                    'Atlantic Avenue - Barclays Center',
                    '7th Avenue',
                    'Prospect Park',
                    'Parkside Avenue',
                    'Church Avenue',
                    'Beverly Road',
                    'Newkirk Avenue',
                    'Flatbush Avenue - Brooklyn College'
                ),
                'R' => array(
                    'Forest Hills - 71st Avenue',
                    '67th Avenue',
                    '63 Drive - Rego Park',
                    'Woodhaven Boulevard',
                    'Grand Avenue - Newtown',
                    'Elmhurst Avenue',
                    'Jackson Heights - Roosevelt Avenue',
                    '65th Street',
                    'Northern Boulevard',
                    '46th Street',
                    'Steinway Street',
                    '36th Street',
                    'Queens Plaza',
                    'Court Square - 23rd Street',
                    'Lexington Avenue - 59th Street',
                    '5th Avenue - 59th Street',
                    '57th Street - 7th Avenue',
                    '49th Street',
                    'Times Square - 42nd Street',
                    '34th Street - Herald Square',
                    '28th Street',
                    '23rd Street',
                    'Union Square - 14th Street',
                    '8th Street - NYU',
                    'Prince Street',
                    'Canal Street',
                    'City Hall',
                    'Cortlandt Street',
                    'Rector Street',
                    'Whitehall Street'
                ),
                'S' => array(
                    'Times Square - 42nd Street',
                    'Grand Central - 42nd Street',
                    '5th Avenue - 42nd Street',
                    '34th Street - Herald Square',
                    '28th Street',
                    '23rd Street',
                    '14th Street',
                    'Christopher Street - Sheridan Square',
                    'Houston Street',
                    'Canal Street',
                    'Franklin Street',
                    'City Hall',
                    'Court Street',
                    'Jay Street - MetroTech',
                    'Hoyt - Schermerhorn Streets',
                    'Nevins Street',
                    'Atlantic Avenue - Barclays Center',
                    'Bergen Street',
                    'Grand Army Plaza',
                    'Eastern Parkway - Brooklyn Museum',
                    'Franklin Avenue',
                    'Botanic Garden',
                    'Prospect Park',
                    'Parkside Avenue',
                    'Church Avenue',
                    'Beverly Road',
                    'Newkirk Avenue',
                    'Flatbush Avenue - Brooklyn College'
                ),
                'W' => array(
                    'Astoria - Ditmars Boulevard',
                    'Astoria Boulevard',
                    '30th Avenue',
                    'Broadway',
                    '36th Avenue',
                    '39th Avenue',
                    'Queensboro Plaza',
                    'Lexington Avenue - 59th Street',
                    '5th Avenue',
                    '57th Street - 7th Avenue',
                    '49th Street',
                    'Times Square - 42nd Street',
                    '34th Street - Herald Square',
                    '28th Street',
                    '23rd Street',
                    'Union Square - 14th Street',
                    '8th Street - NYU',
                    'Prince Street',
                    'Canal Street',
                    'Rector Street',
                    'Whitehall Street'
                ),
                'Z' => array(
                    'Jamaica Center - Parsons/Archer',
                    'Sutphin Boulevard - Archer Avenue - JFK Airport',
                    'Jamaica - Van Wyck',
                    'Woodhaven Boulevard',
                    '85th Street - Forest Parkway',
                    '75th Street',
                    'Cypress Hills',
                    'Crescent Street',
                    'Norwood Avenue',
                    'Cleveland Street',
                    'Van Siclen Avenue',
                    'Alabama Avenue',
                    'Broadway Junction',
                    'Chauncey Street',
                    'Halsey Street',
                    'Gates Avenue',
                    'Kosciuszko Street',
                    'Myrtle Avenue',
                    'Flushing Avenue',
                    'Lorimer Street',
                    'Hewes Street',
                    'Marcy Avenue',
                    'Essex Street',
                    'Bowery',
                    'Canal Street',
                    'Chambers Street',
                    'Fulton Street',
                    'Broad Street'
                )
            );
            $colors = [
                'red' => ['1', '2', '3'],
                'green' => ['4', '5', '6'],
                'blue' => ['A', 'C', 'E'],
                'orange' => ['B', 'D', 'F', 'M'],
                'purple' => ['7'],
                'green-2' => ['G'],
                'yellow' => ['N', 'Q', 'R'],
                'gray' => ['L'],
                'brown' => ['J', 'Z']
            ];
            $icons = [];
            foreach ($subway_stations as $key => $values) {
                if (in_array($name, $values)) {
                    foreach ($colors as $color_key => $color_values) {
                        if (in_array($key, $color_values)) {
                            $icons[] = ['color' => $color_key, 'line' => $key];
                        }
                    }
                }
            }

            if ($icons) {
                $string = $name . ' (';
                foreach ($icons as $icon) {
                    $string .= '<span class="subway-icon ' . $icon['color'] . '">' . $icon['line'] . '</span>';
                }
                $string .= ')';
                return $string;
            }

            return $name;
        }
    }

	/**
	 * Calculates the distance between two geographical coordinates.
	 *
	 * @param array $start_point The starting point coordinates [latitude, longitude].
	 * @param array $finish_point The destination point coordinates [latitude, longitude].
	 *
	 * @return float The calculated distance in meters.
	 */
    public static function get_distance_by_coordinates($start_point, $finish_point)
    {
        $latFrom = deg2rad($start_point[0]);
        $lonFrom = deg2rad($start_point[1]);
        $latTo = deg2rad($finish_point[0]);
        $lonTo = deg2rad($finish_point[1]);

        $earthRadius = 6371000;

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }

	/**
	 * Retrieves a list of nearby listings within 1000 meters of the specified post.
	 *
	 * @param int $post_id The ID of the post to find nearby listings.
	 *
	 * @return array The list of nearby listing IDs.
	 */
    public static function get_nearby_listings($post_id)
    {
        $get_coordinates_of_post = get_field('map', $post_id);
        $post_latitude = floatval($get_coordinates_of_post['lat']);
        $post_longitude = floatval($get_coordinates_of_post['lng']);

        $listings = get_posts([
            'post_type' => 'listings',
            'numberposts' => -1,
            'post_status' => 'publish'
        ]);
        $result = [];

        if ($listings) {
            foreach ($listings as $listing) {
                $get_coordinates_of_listing = get_field('map', $listing->ID);
                if ($get_coordinates_of_listing) {
                    $listing_lat = floatval($get_coordinates_of_listing['lat']);
                    $listing_lng = floatval($get_coordinates_of_listing['lng']);
                    if (self::get_distance_by_coordinates([$post_latitude, $post_longitude], [$listing_lat, $listing_lng]) <= 1000) {
                        $result[] = $listing->ID;
                    }
                }
            }
        }

        if (!empty($result) && is_array($result)) {
            $random_elements = array_rand(array_flip($result), count($result) > 12 ? 12 : count($result));

            if (!is_array($random_elements)) {
                $random_elements = [$random_elements];
            }

            return $random_elements;
        } else {
            return [];
        }
    }

	/**
	 * Retrieves the real IP address of the user.
	 *
	 * @return string The user's IP address.
	 */
    public static function get_real_ip_address()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

	/**
	 * Retrieves geolocation data for a given IP address.
	 *
	 * @param string $ip The IP address to lookup.
	 *
	 * @return string The geolocation information.
	 */
    public static function get_geolocation_by_ip($ip)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.ipstack.com/" . $ip . "?access_key=3e336032eea0e36b003cb578a7d984a5",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "accept: application/json",
                "content-type: application/json"
            ),
        ));

        $json_geo = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $user_geo = "cURL Error #:" . $err;
        } else {
            $geo_data = json_decode($json_geo, true);
            $user_geo = $geo_data['city'] . '<br>' . $geo_data['region_name'] . '<br>' . $geo_data['country_name'];
        }

        return $user_geo;
    }
    
    public static function get_generated_featured_listings( $post_id ) {
        $ids = get_post_meta( $post_id, '_metro_featured_listings', true );
        if ( ! empty( $ids ) && is_array( $ids ) ) {
          return $ids;
        }
        
        // Fallback: whatever your current get_posts_by_mode('featured', …) does
        return self::get_posts_by_mode( 'featured', [
          'post_type'   => 'listings',
          'numberposts' => 12,
        ] );
      }
}

class MetroManhattanShorcodes
{
	/**
	 * Initializes the shortcode registration.
	 */
    public function __construct()
    {
        $shortcodes = ['youtube_video'];
        foreach ($shortcodes as $shortcode) {
            add_shortcode($shortcode, [$this, $shortcode . '_shortcode_callback']);
        }
    }

	/**
	 * Renders the YouTube video shortcode.
	 *
	 * @param array $attrs The attributes passed to the shortcode.
	 * @param string $content The content enclosed within the shortcode.
	 *
	 * @return string The HTML string for the YouTube video.
	 */
    public function youtube_video_shortcode_callback($attrs, $content)
    {
        if (empty($content)) {
            return;
        }
        $url = parse_url($content);
        $query = (!empty($url['$query'])) ? parse_str($url['query'], $query) : null;
        $videoID = !empty($query['v']) ? $query['v'] : null;
        $youtube_api_key = get_field('youtube_api_key', 'option');
        if (!empty($videoID)) {
            $video_data = MetroManhattanHelpers::get_youtube_video($youtube_api_key, $videoID);
            ob_start() ?>
            <div data-target="youtube_video" data-video-insert="false" data-video-id="<?php echo $videoID ?>" class="youtube-video">
                <div class="image">
                    <?php if ($video_data->thumbnail) : ?>
                        <img loading="lazy" class="youtube-thumbnail" width="<?php echo $video_data->thumbnail->width ?>" height="<?php echo $video_data->thumbnail->height ?>" src="<?php echo $video_data->thumbnail->url ?>" alt="<?php echo $video_data->title ?>">
                    <?php else : ?>
                        <img width="360" height="240" class="youtube-thumbnail" src="<?php echo get_template_directory_uri() . '/assets/images/video-placeholder.png' ?>" alt="<?php echo $video_data->title ?>">
                    <?php endif; ?>
                    <button type="button" aria-label="play <?php echo $video_data->title ?>" class="video-icon">
                        <svg width="114" height="80" viewBox="0 0 114 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M111.065 12.2263C109.717 7.44288 105.987 3.69136 101.183 2.3652C92.3356 0.000113072 56.7706 0.000113644 56.7706 0.000113644C56.7706 0.000113644 21.2729 -0.0566209 12.3338 2.3652C7.554 3.6949 3.80602 7.44288 2.47278 12.2263C0.795582 21.293 -0.0376961 30.4697 0.0013084 39.689C-0.0164209 48.8479 0.813311 58.0105 2.47278 67.0205C3.80602 71.8039 7.554 75.5554 12.3338 76.9028C21.1808 79.2679 56.7706 79.2679 56.7706 79.2679C56.7706 79.2679 92.247 79.2679 101.183 76.9028C105.987 75.5554 109.717 71.8074 111.065 67.0205C112.692 58.0105 113.487 48.8479 113.43 39.689C113.487 30.4697 112.71 21.293 111.065 12.2263ZM45.4096 56.663V22.6405L75.0141 39.689L45.4096 56.663Z" fill="var(--mm-white-color)" />
                        </svg>
                    </button>
                    <div class="loader hide">
                        <img loading="lazy" src="<?php echo get_template_directory_uri() ?>/assets/images/loader-video.png" alt="loader">
                    </div>
                </div>
                <div class="video hide">
                    <div class="element-to-replace"></div>
                </div>
            </div>
<?php return ob_get_clean();
        }
    }
}

// Initialize the shortcodes
new MetroManhattanShorcodes();

