<?php
  
  // includes/admin-featured-listings.php
  
  add_action( 'admin_menu', function () {
    add_menu_page(
      'Generate Featured Listings',
      'Generate Featured Listings',
      'manage_options',
      'generate-featured-listings',
      'render_generate_featured_listings_page',
      'dashicons-admin-generic',
      80
    );
  } );
  
  function render_generate_featured_listings_page() {
    if ( isset( $_GET['updated'] ) ) {
      echo '<div class="notice notice-success"><p>Featured Listings generated.</p></div>';
    }
    ?>
      <div class="wrap">
          <h1>Generate Featured Listings</h1>
          <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'generate_featured_listings_nonce', 'gfl_nonce' ); ?>
              <input type="hidden" name="action" value="generate_featured_listings">
              <p>
                  <button type="submit" class="button button-primary">Run & Assign Listings</button>
              </p>
          </form>
      </div>
    <?php
  }
  
  /**
   * (Re‑)generate and persist featured‐listings arrays for every blog post.
   */
  function metro_regenerate_featured_listings() {
    // 1) fetch all published blog posts…
    $posts = get_posts([
      'post_type'      => ['post', 'page'],
      'posts_per_page' => -1,
      'post_status'    => 'publish',
      'fields'         => 'ids',
    ]);
    if ( empty($posts) ) {
      return;
    }
    
    // 2) fetch all "Featured" listings…
    $featured = get_posts([
      'post_type'      => 'listings',
      'posts_per_page' => -1,
      'fields'         => 'ids',
      'tax_query'      => [[
        'taxonomy' => 'listing-category',
        'field'    => 'term_id',
        'terms'    => 157,
      ]],
    ]);
    if ( empty($featured) ) {
      return;
    }
    
    shuffle( $posts );
    shuffle( $featured );
    
    // 3) round‑robin seed + fill to 12 + patch any missing …
    $assign     = array_fill_keys( $posts, [] );
    $count      = count( $posts );
    foreach ( $featured as $i => $lid ) {
      $pid = $posts[ $i % $count ];
      $assign[ $pid ][] = $lid;
    }
    foreach ( $posts as $pid ) {
      while ( count( $assign[ $pid ] ) < 12 ) {
        $pick = $featured[ array_rand( $featured ) ];
        if ( ! in_array( $pick, $assign[ $pid ], true ) ) {
          $assign[ $pid ][] = $pick;
        }
      }
    }
    // patch any truly missing
    $all_assigned = array_unique( call_user_func_array( 'array_merge', $assign ) );
    $missing      = array_diff( $featured, $all_assigned );
    foreach ( $missing as $miss ) {
      $rp   = $posts[ array_rand($posts) ];
      $slot = array_rand( $assign[$rp] );
      $assign[$rp][$slot] = $miss;
    }
    
    // 4) persist
    foreach ( $assign as $pid => $ids ) {
      update_post_meta( $pid, '_metro_featured_listings', $ids );
    }
  }
  
  add_action('admin_post_generate_featured_listings', function(){
    metro_regenerate_featured_listings();
    wp_safe_redirect( add_query_arg('updated','true', menu_page_url('generate-featured-listings',false)) );
    exit;
  });

// Add hooks for post/listing changes to regenerate featured listings
add_action('save_post_post', 'metro_regenerate_featured_listings');
add_action('save_post_listings', 'metro_regenerate_featured_listings');
add_action('delete_post', function($post_id) {
    $post_type = get_post_type($post_id);
    if ($post_type === 'post' || $post_type === 'listings') {
        metro_regenerate_featured_listings();
    }
});
