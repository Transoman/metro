<?php
  /**
   * File location: /includes/openai-integration.php
   *
   * OpenAI Integration for Related Content.
   *
   * This file provides functions for:
   *   - Requesting embeddings from OpenAI (with plain language instructions),
   *   - Computing cosine similarity,
   *   - Saving embeddings on post save (and for manual regeneration),
   *   - Retrieving related posts via semantic similarity (with bonuses for matching categories and a recency penalty),
   *   - Fallback to category-based related posts,
   *   - Manual override via a meta box, and
   *   - Displaying related articles on the front-end.
   *
   * Additionally, to avoid over-representation of a few posts, the system now
   * tracks how often each candidate appears and applies a diversity bonus based on underuse.
   * If a candidate’s recommendation frequency reaches a dynamic threshold (10% of published posts),
   * it will be skipped, ensuring broader internal linking coverage.
   *
   * Place this file in your theme's "includes" folder and include it in your theme's functions.php.
   */
  
  if ( ! defined( 'OPENAI_API_KEY' ) ) {
    define( 'OPENAI_API_KEY', 'YOUR_OPENAI_API_KEY_HERE' );
  }
  
  /**
   * Get persistent frequency data with decay.
   *
   * The stored option is an associative array with keys:
   *   - 'counts': an array mapping post IDs to recommendation counts.
   *   - 'last_decay': a timestamp when decay was last applied.
   *
   * Every 24 hours, all counts are decayed (multiplied by 0.5, floored to at least 1).
   *
   * @return array The associative array of recommendation counts.
   */
  function get_persistent_frequency() {
    $data = get_option( 'related_article_frequency', array() );
    if ( empty( $data ) || ! isset( $data['counts'] ) ) {
      $data = array(
        'counts'     => array(),
        'last_decay' => time(),
      );
    }
    // Decay period: 24 hours (86400 seconds)
    if ( time() - $data['last_decay'] > 86400 ) {
      foreach ( $data['counts'] as $id => $count ) {
        // Decay factor: 0.5 with a minimum of 1
        $data['counts'][ $id ] = max( 1, floor( $count * 0.5 ) );
      }
      $data['last_decay'] = time();
      update_option( 'related_article_frequency', $data, 'no' );
    }
    
    return $data['counts'];
  }
  
  /**
   * Update the persistent recommendation frequency data.
   *
   * @param array $freq The updated associative array of recommendation counts.
   */
  function update_persistent_frequency( $freq ) {
    $data = get_option( 'related_article_frequency', array() );
    if ( empty( $data ) || ! isset( $data['counts'] ) ) {
      $data = array(
        'counts'     => array(),
        'last_decay' => time(),
      );
    }
    $data['counts'] = $freq;
    update_option( 'related_article_frequency', $data, 'no' );
  }
  
  /**
   * Calculate the dynamic candidate frequency threshold based on total published posts.
   * The threshold is set as 10% of all published posts (with a minimum of 1).
   *
   * @return int Dynamic threshold.
   */
  function get_dynamic_candidate_threshold() {
    $count_obj            = wp_count_posts( 'post' );
    $published            = isset( $count_obj->publish ) ? $count_obj->publish : 0;
    $threshold_percentage = 0.1; // 10%
    
    return max( 1, ceil( $published * $threshold_percentage ) );
  }
  
  /**
   * Request an embedding for a given input text from OpenAI.
   *
   * The plain language prompt instructs the model to:
   *   - Prioritize content from the same category.
   *   - For evergreen, tenant-focused categories (finding-your-space, real-estate-guides, client-case-studies, office-workspace-design),
   *     maintain relevance even if older.
   *   - For news-driven categories (expert-insights, real-estate-news, real-estate-market-reports), favor more recent content.
   *
   * @param string $input The input text.
   *
   * @return array|WP_Error The embedding vector or WP_Error on failure.
   */
  function get_openai_embedding( $input ) {
    $endpoint               = 'https://api.openai.com/v1/embeddings';
    $preferred_instructions = "\n\n[When generating an embedding, not only prioritize content from the same category but also capture the unique tone, key topics, and specific phrases that differentiate this article from others. For evergreen, tenant-focused content (e.g., 'finding-your-space', 'real-estate-guides', 'client-case-studies', 'office-workspace-design'), maintain important context even if older. For news-driven categories (e.g., 'expert-insights', 'real-estate-news', 'real-estate-market-reports'), ensure recent and time-specific details stand out.]";
    $enhanced_input         = $input . $preferred_instructions;
    $args                   = array(
      'headers' => array(
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer ' . OPENAI_API_KEY,
      ),
      'body'    => json_encode( array(
        'model' => 'text-embedding-ada-002',
        'input' => $enhanced_input,
      ) ),
      'timeout' => 15,
    );
    $response               = wp_remote_post( $endpoint, $args );
    if ( is_wp_error( $response ) ) {
      return $response;
    }
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    if ( isset( $data['data'][0]['embedding'] ) ) {
      return $data['data'][0]['embedding'];
    }
    
    return new WP_Error( 'no_embedding', 'No embedding found in the response.' );
  }
  
  /**
   * Compute the cosine similarity between two numeric vectors.
   *
   * @param array $vec1 First vector.
   * @param array $vec2 Second vector.
   *
   * @return float Cosine similarity score.
   */
  function compute_cosine_similarity( $vec1, $vec2 ) {
    $dot   = 0;
    $norm1 = 0;
    $norm2 = 0;
    foreach ( $vec1 as $i => $value ) {
      if ( ! isset( $vec2[ $i ] ) ) {
        continue;
      }
      $dot   += $value * $vec2[ $i ];
      $norm1 += $value * $value;
      $norm2 += $vec2[ $i ] * $vec2[ $i ];
    }
    $norm1 = sqrt( $norm1 );
    $norm2 = sqrt( $norm2 );
    if ( $norm1 == 0 || $norm2 == 0 ) {
      return 0;
    }
    
    return $dot / ( $norm1 * $norm2 );
  }
  
  /**
   * Compute and store an OpenAI embedding for a post.
   *
   * @param int $post_id The post ID.
   * @param WP_Post $post The post object.
   * @param bool $force If true, force regeneration.
   */
  function compute_and_store_openai_embedding( $post_id, $post, $force = false ) {
    $allowed_post_types = array( 'post', 'listings', 'buildings' );
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || ! in_array( $post->post_type, $allowed_post_types ) ) {
      return;
    }
    if ( ! $force ) {
      $existing = get_post_meta( $post_id, 'openai_embedding', true );
      if ( ! empty( $existing ) && is_array( $existing ) ) {
        return;
      }
    }
    $input_text = $post->post_title . "\n" . wp_strip_all_tags( $post->post_content );
    $embedding  = get_openai_embedding( $input_text );
    if ( ! is_wp_error( $embedding ) ) {
      update_post_meta( $post_id, 'openai_embedding', $embedding );
    }
  }
  
  add_action( 'save_post', 'compute_and_store_openai_embedding', 10, 3 );
  
  /**
   * Retrieve fallback related posts based on matching categories.
   *
   * @param int $post_id The current post ID.
   * @param int $limit Number of posts to return.
   *
   * @return array Array of fallback related post IDs.
   */
  function get_fallback_related_posts( $post_id, $limit = 3 ) {
    $categories = wp_get_post_categories( $post_id );
    if ( empty( $categories ) ) {
      return array();
    }
    $args           = array(
      'post_type'      => 'post',
      'post_status'    => 'publish',
      'post__not_in'   => array( $post_id ),
      'posts_per_page' => $limit,
      'category__in'   => $categories,
      'fields'         => 'ids',
    );
    $fallback_query = new WP_Query( $args );
    $fallback_ids   = ( $fallback_query->have_posts() ) ? $fallback_query->posts : array();
    wp_reset_postdata();
    
    return $fallback_ids;
  }
  
  /**
   * Add a bonus score if the candidate post shares categories with the source.
   * For evergreen, tenant-focused categories (finding-your-space, real-estate-guides, client-case-studies, office-workspace-design),
   * a bonus of 0.2 is applied; otherwise, a bonus of 0.1 is applied.
   *
   * @param int $post_id Source post ID.
   * @param int $candidate_id Candidate post ID.
   *
   * @return float Bonus score.
   */
  function get_category_bonus( $post_id, $candidate_id ) {
    $preferred_categories = array(
      'finding-your-space',
      'real-estate-guides',
      'client-case-studies',
      'office-workspace-design'
    );
    $current_categories   = wp_get_post_terms( $post_id, 'category', array( 'fields' => 'slugs' ) );
    $candidate_categories = wp_get_post_terms( $candidate_id, 'category', array( 'fields' => 'slugs' ) );
    $shared               = array_intersect( $current_categories, $candidate_categories );
    if ( ! empty( $shared ) ) {
      $preferred_shared = array_intersect( $preferred_categories, $shared );
      
      return ! empty( $preferred_shared ) ? 0.2 : 0.1;
    }
    
    return 0;
  }
  
  /**
   * Additional bonus for high quality articles.
   * Award an extra bonus (0.3) if the candidate and source share an exact category match.
   *
   * @param int $post_id Source post ID.
   * @param int $candidate_id Candidate post ID.
   *
   * @return float Extra bonus.
   */
  function get_high_quality_category_bonus( $post_id, $candidate_id ) {
    $source_categories    = wp_get_post_terms( $post_id, 'category', array( 'fields' => 'slugs' ) );
    $candidate_categories = wp_get_post_terms( $candidate_id, 'category', array( 'fields' => 'slugs' ) );
    if ( ! empty( $source_categories ) && ! empty( $candidate_categories ) ) {
      $common = array_intersect( $source_categories, $candidate_categories );
      if ( ! empty( $common ) ) {
        return 0.3;
      }
    }
    
    return 0;
  }
  
  /**
   * Calculate a recency penalty for the candidate post.
   * - For candidates older than 180 days, a base penalty is computed.
   * - For news-driven categories (expert-insights, real-estate-news, real-estate-market-reports), the penalty is doubled,
   *   and an extra 0.1 is added if the candidate was published before the source.
   * - For evergreen categories (finding-your-space, real-estate-guides, client-case-studies, office-workspace-design),
   *   the penalty is reduced (multiplied by 0.5).
   *
   * @param int $source_id Source post ID.
   * @param int $candidate_id Candidate post ID.
   *
   * @return float Adjusted recency penalty.
   */
  function get_recency_penalty( $source_id, $candidate_id ) {
    $source_date    = get_the_date( 'U', $source_id );
    $candidate_date = get_the_date( 'U', $candidate_id );
    $now            = current_time( 'timestamp' );
    $days_old       = ( $now - $candidate_date ) / DAY_IN_SECONDS;
    $penalty        = 0;
    if ( $days_old > 180 ) {
      $penalty = 0.05 * ( ( $days_old - 180 ) / 30 );
    }
    $news_categories      = array( 'expert-insights', 'real-estate-news', 'real-estate-market-reports' );
    $evergreen_categories = array(
      'finding-your-space',
      'real-estate-guides',
      'client-case-studies',
      'office-workspace-design'
    );
    $candidate_categories = wp_get_post_terms( $candidate_id, 'category', array( 'fields' => 'slugs' ) );
    if ( ! empty( $candidate_categories ) ) {
      if ( array_intersect( $news_categories, $candidate_categories ) ) {
        $penalty *= 2;
        if ( $candidate_date < $source_date ) {
          $penalty += 0.1;
        }
      } elseif ( array_intersect( $evergreen_categories, $candidate_categories ) ) {
        $penalty *= 0.5;
      }
    }
    
    return $penalty;
  }
  
  /**
   * Utility function to sort an array of post IDs by publication date (newest first).
   *
   * @param array $post_ids Array of post IDs.
   *
   * @return array Sorted post IDs.
   */
  function sort_posts_by_date( $post_ids ) {
    usort( $post_ids, function ( $a, $b ) {
      return get_post_time( "U", false, $b ) - get_post_time( "U", false, $a );
    } );
    
    return $post_ids;
  }
  
  /**
   * Retrieve semantically similar posts using stored embeddings.
   * After filtering for relevance, the list is sorted by publication date (newest first).
   * A diversity bonus is applied—adding a bonus of 0.2 divided by (frequency + 1).
   * Additionally, if a candidate's frequency equals or exceeds the dynamic threshold,
   * it is skipped entirely.
   *
   * @param int $post_id Source post ID.
   * @param int $limit Number of posts to return.
   *
   * @return array Array of related post IDs.
   */
  function get_semantically_similar_posts( $post_id, $limit = 5 ) {
    // Load persistent frequency data.
    global $g_recommendation_frequency;
    $g_recommendation_frequency = get_persistent_frequency();
    
    $current_embedding = get_post_meta( $post_id, 'openai_embedding', true );
    if ( empty( $current_embedding ) || ! is_array( $current_embedding ) ) {
      $results = sort_posts_by_date( get_fallback_related_posts( $post_id, $limit ) );
      
      return $results;
    }
    
    $query = new WP_Query( array(
      'post_type'      => 'post',
      'post_status'    => 'publish',
      'post__not_in'   => array( $post_id ),
      'posts_per_page' => - 1,
      'fields'         => 'ids',
    ) );
    
    $scored_posts = array();
    if ( $query->have_posts() ) {
      foreach ( $query->posts as $other_post_id ) {
        // Skip candidates over the dynamic threshold.
        if ( isset( $g_recommendation_frequency[ $other_post_id ] ) &&
             $g_recommendation_frequency[ $other_post_id ] >= get_dynamic_candidate_threshold() ) {
          continue;
        }
        $other_embedding = get_post_meta( $other_post_id, 'openai_embedding', true );
        if ( empty( $other_embedding ) || ! is_array( $other_embedding ) ) {
          continue;
        }
        $similarity                     = compute_cosine_similarity( $current_embedding, $other_embedding );
        $similarity                     += get_category_bonus( $post_id, $other_post_id );
        $similarity                     -= get_recency_penalty( $post_id, $other_post_id );
        $diversity_bonus                = 0.2 / ( isset( $g_recommendation_frequency[ $other_post_id ] ) ? $g_recommendation_frequency[ $other_post_id ] + 1 : 1 );
        $similarity                     += $diversity_bonus;
        $scored_posts[ $other_post_id ] = $similarity;
      }
    }
    wp_reset_postdata();
    
    arsort( $scored_posts );
    $top_post_ids = array_slice( array_keys( $scored_posts ), 0, $limit );
    if ( count( $top_post_ids ) < $limit ) {
      $fallback_posts = get_fallback_related_posts( $post_id, $limit - count( $top_post_ids ) );
      $top_post_ids   = array_merge( $top_post_ids, $fallback_posts );
    }
    
    // Increment persistent recommendation counters.
    foreach ( $top_post_ids as $id ) {
      if ( ! isset( $g_recommendation_frequency[ $id ] ) ) {
        $g_recommendation_frequency[ $id ] = 0;
      }
      $g_recommendation_frequency[ $id ] ++;
    }
    // Save the updated frequency data.
    update_persistent_frequency( $g_recommendation_frequency );
    
    return sort_posts_by_date( $top_post_ids );
  }
  
  /**
   * Retrieve manually specified related posts from post meta.
   *
   * @param int $post_id Source post ID.
   *
   * @return array Array of manually set related post IDs.
   */
  function get_manual_related_posts( $post_id ) {
    $manual = get_post_meta( $post_id, '_manual_related_posts', true );
    if ( ! empty( $manual ) && is_array( $manual ) ) {
      return $manual;
    }
    
    return array();
  }
  
  /**
   * Main function to retrieve related posts.
   * If a manual override exists, returns that; otherwise, returns semantically similar posts.
   *
   * @param int $post_id Source post ID.
   * @param int $limit Number of posts to return.
   *
   * @return array Array of related post IDs.
   */
  function get_related_posts( $post_id, $limit = 5 ) {
    $manual = get_manual_related_posts( $post_id );
    if ( ! empty( $manual ) ) {
      return sort_posts_by_date( $manual );
    }
    
    return get_semantically_similar_posts( $post_id, $limit );
  }
  
  /**
   * Retrieve semantically similar high quality posts.
   * Only considers posts with meta key "high_quality" (set to "1")
   * and excludes posts that appear in the regular related list.
   * A diversity bonus is applied and the final list is sorted by publication date (newest first).
   *
   * @param int $post_id Source post ID.
   * @param int $limit Number of posts to return.
   *
   * @return array Array of high quality related post IDs.
   */
  function get_semantically_similar_high_quality_posts( $post_id, $limit = 3 ) {
    // Load persistent frequency data.
    global $g_recommendation_frequency;
    $g_recommendation_frequency = get_persistent_frequency();
    
    $current_embedding = get_post_meta( $post_id, 'openai_embedding', true );
    if ( empty( $current_embedding ) || ! is_array( $current_embedding ) ) {
      // Fall back to posts in the same categories with high_quality meta.
      $categories = wp_get_post_categories( $post_id );
      if ( empty( $categories ) ) {
        return array();
      }
      $args           = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'post__not_in'   => array( $post_id ),
        'posts_per_page' => $limit,
        'category__in'   => $categories,
        'meta_key'       => 'high_quality',
        'meta_value'     => '1',
        'fields'         => 'ids',
      );
      $fallback_query = new WP_Query( $args );
      $fallback_ids   = $fallback_query->posts;
      wp_reset_postdata();
      
      return sort_posts_by_date( $fallback_ids );
    }
    
    $args  = array(
      'post_type'      => 'post',
      'post_status'    => 'publish',
      'post__not_in'   => array( $post_id ),
      'posts_per_page' => - 1,
      'meta_key'       => 'high_quality',
      'meta_value'     => '1',
      'fields'         => 'ids',
    );
    $query = new WP_Query( $args );
    
    $scored_posts = array();
    if ( $query->have_posts() ) {
      foreach ( $query->posts as $other_post_id ) {
        if ( isset( $g_recommendation_frequency[ $other_post_id ] ) &&
             $g_recommendation_frequency[ $other_post_id ] >= get_dynamic_candidate_threshold() ) {
          continue;
        }
        $other_embedding = get_post_meta( $other_post_id, 'openai_embedding', true );
        if ( empty( $other_embedding ) || ! is_array( $other_embedding ) ) {
          continue;
        }
        $similarity = compute_cosine_similarity( $current_embedding, $other_embedding );
        //        $similarity                     += get_category_bonus( $post_id, $other_post_id );
        //        $similarity                     += get_high_quality_category_bonus( $post_id, $other_post_id );
        //        $similarity                     -= get_recency_penalty( $post_id, $other_post_id );
        //        $diversity_bonus                = 0.2 / ( isset( $g_recommendation_frequency[ $other_post_id ] ) ? $g_recommendation_frequency[ $other_post_id ] + 1 : 1 );
        //        $similarity                     += $diversity_bonus;
        $scored_posts[ $other_post_id ] = $similarity;
      }
    }
    wp_reset_postdata();
    
    //    if ( $post_id == 57693 ) {
    //      // TODO testing phase
    //      $demo = 123;
    //    }
    
    arsort( $scored_posts );
    $top_post_ids = array_slice( array_keys( $scored_posts ), 0, $limit );
    
    //    if ( $post_id == 57693 ) {
    //      // TODO testing phase
    //      $top_post_ids = array(21041, 54941, 61043);
    //    }
    
    $related      = get_related_posts( $post_id, 5 );
    $top_post_ids = array_diff( $top_post_ids, $related );
    
    $source_terms = wp_get_post_terms( $post_id, 'category', [ 'fields' => 'ids' ] );
    $bumped_id    = null;
    foreach ( $top_post_ids as $idx => $candidate_id ) {
      $candidate_terms = wp_get_post_terms( $candidate_id, 'category', [ 'fields' => 'ids' ] );
      if ( array_intersect( $source_terms, $candidate_terms ) ) {
        $bumped_id = $candidate_id;
        unset( $top_post_ids[ $idx ] );
        break;
      }
    }
    if ( $bumped_id ) {
      array_unshift( $top_post_ids, $bumped_id );
      $top_post_ids = array_slice( $top_post_ids, 0, $limit );
    }
    
    if ( count( $top_post_ids ) < $limit ) {
      $additional_args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'post__not_in'   => array_merge( array( $post_id ), $top_post_ids ),
        'posts_per_page' => $limit - count( $top_post_ids ),
        'meta_key'       => 'high_quality',
        'meta_value'     => '1',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'fields'         => 'ids',
      );
      $fallback_query  = new WP_Query( $additional_args );
      if ( $fallback_query->have_posts() ) {
        $fallback_ids = $fallback_query->posts;
        $top_post_ids = array_merge( $top_post_ids, $fallback_ids );
      }
      wp_reset_postdata();
    }
    
    // Increment persistent recommendation counters.
    foreach ( $top_post_ids as $id ) {
      if ( ! isset( $g_recommendation_frequency[ $id ] ) ) {
        $g_recommendation_frequency[ $id ] = 0;
      }
      $g_recommendation_frequency[ $id ] ++;
    }
    // Save the updated frequency data.
    update_persistent_frequency( $g_recommendation_frequency );
    
    return sort_posts_by_date( array_slice( $top_post_ids, 0, $limit ) );
  }
  
  /**
   * Display related articles on the front-end for single posts.
   */
  function display_related_articles() {
    if ( ! is_singular( 'post' ) ) {
      return;
    }
    $current_post_id = get_the_ID();
    $related_posts   = get_related_posts( $current_post_id, 5 );
    if ( ! empty( $related_posts ) ) {
      echo '<div class="related-articles">';
      echo '<h3>Related Articles</h3>';
      echo '<ul>';
      foreach ( $related_posts as $related_id ) {
        $title     = get_the_title( $related_id );
        $permalink = get_permalink( $related_id );
        echo '<li><a href="' . esc_url( $permalink ) . '">' . esc_html( $title ) . '</a></li>';
      }
      echo '</ul>';
      echo '</div>';
    }
  }
  
  /**
   * Add a meta box for manually overriding related posts.
   */
  function add_manual_related_posts_meta_box() {
    add_meta_box(
      'manual_related_posts_meta_box',
      'Manual Related Posts',
      'render_manual_related_posts_meta_box',
      'post',
      'side'
    );
  }
  
  add_action( 'add_meta_boxes', 'add_manual_related_posts_meta_box' );
  
  /**
   * Render the meta box for manually specifying related posts (comma-separated).
   *
   * @param WP_Post $post The current post.
   */
  function render_manual_related_posts_meta_box( $post ) {
    wp_nonce_field( 'manual_related_posts_nonce', 'manual_related_posts_nonce_field' );
    $manual = get_post_meta( $post->ID, '_manual_related_posts', true );
    $manual = is_array( $manual ) ? implode( ',', $manual ) : '';
    echo '<p><label for="manual_related_posts">Enter related post IDs (comma-separated):</label></p>';
    echo '<input type="text" id="manual_related_posts" name="manual_related_posts" value="' . esc_attr( $manual ) . '" style="width:100%;" />';
  }
  
  /**
   * Save the manual related posts meta box data.
   *
   * @param int $post_id The post ID.
   */
  function save_manual_related_posts_meta_box( $post_id ) {
    if ( ! isset( $_POST['manual_related_posts_nonce_field'] ) || ! wp_verify_nonce( $_POST['manual_related_posts_nonce_field'], 'manual_related_posts_nonce' ) ) {
      return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
      return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
      return;
    }
    if ( isset( $_POST['manual_related_posts'] ) ) {
      $manual_raw = sanitize_text_field( $_POST['manual_related_posts'] );
      $ids        = array_filter( array_map( 'intval', explode( ',', $manual_raw ) ) );
      update_post_meta( $post_id, '_manual_related_posts', $ids );
    }
  }
  
  add_action( 'save_post', 'save_manual_related_posts_meta_box' );
