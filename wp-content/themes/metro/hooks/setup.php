<?php
  /**
   * Theme Setup File
   *
   * This file configures essential theme features, enqueues styles/scripts,
   * registers custom post types, taxonomies, menus, and defines various
   * hooks and filters for theme functionality.
   *
   */
  if ( ! defined( 'ABSPATH' ) ) {
    exit;
  }
  // require_once __DIR__ . '/enable-woocommerce-guttenberg.php';
  // require_once __DIR__ . '/disable-json-api.php';
  // require_once __DIR__ . '/preload-fonts.php';
  // require_once __DIR__ . '/enable-smtp.php';
  // require_once __DIR__ . '/metro.php';
  require_once __DIR__ . '/enable-debug.php';
  require_once __DIR__ . '/disable-emoji.php';
  require_once __DIR__ . '/disable-oembed.php';
  require_once __DIR__ . '/disable-search.php';
  require_once __DIR__ . '/disable-auto-update.php';
  require_once __DIR__ . '/disable-comments.php';
  require_once __DIR__ . '/disable-authors-archive.php';
  require_once __DIR__ . '/cleanup.php';
  require_once __DIR__ . '/vite.php';
  require_once __DIR__ . '/register-blocks.php';
  require_once __DIR__ . '/reusable-blocks.php';
  
  if ( profidev_env( "SITE_ENV", "production" ) !== "production" ) {
    require_once __DIR__ . '/enable-cls-reporter.php';
  } else {
    require_once __DIR__ . '/disable-jquery.php';
  }
  
  /**
   * Set up theme defaults and register features.
   *
   * @hook after_setup_theme
   */
  add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'menus' );
    add_theme_support( 'html5', [ 'script', 'style' ] );
    add_theme_support( 'custom-logo' );
    add_theme_support( 'post-thumbnails' );
  }, 100 );
  
  /**
   * Add custom image sizes to the WordPress media library.
   *
   * @hook image_size_names_choose
   *
   * @param array $sizes Existing image sizes.
   *
   * @return array Updated image sizes.
   */
  add_filter( 'image_size_names_choose', function ( $sizes ) {
    return array_merge(
      $sizes,
      array(
        'medium-width'     => __( 'Medium Width' ),
        'medium-height'    => __( 'Medium Height' ),
        'medium-something' => __( 'Medium Something' ),
      )
    );
  } );
  
  /**
   * Add critical CSS for different pages based on conditions.
   *
   * @hook wp_head
   */
  add_action( 'wp_head', function () {
    $current_url   = $_SERVER['REQUEST_URI'];
    $blog_base_url = '/blog/';
    
    if ( is_front_page() ) {
      $critical_css = file_get_contents( get_theme_file_path( 'dist/home-critical-min.css' ) );
      echo '<style>' . $critical_css . '</style>';
      echo '<link rel="preload" href="https://www.metro-manhattan.com/wp-content/plugins/contact-form-7/includes/css/styles.css?ver=5.9.6" as="style">';
    } elseif ( is_singular( 'listings' ) ) {
      $critical_css = file_get_contents( get_theme_file_path( 'dist/listing-critical-min.css' ) );
      echo '<style>' . $critical_css . '</style>';
    } elseif ( is_singular( 'buildings' ) ) {
      $critical_css = file_get_contents( get_theme_file_path( 'dist/building-critical-min.css' ) );
      echo '<style>' . $critical_css . '</style>';
    } elseif ( is_page() || is_single() ) {
      $post_id   = get_the_ID();
      $parent_id = wp_get_post_parent_id( $post_id );
      
      if ( $post_id == 1131 || $parent_id == 1131 ) {
        $critical_css = file_get_contents( get_theme_file_path( 'dist/listings-critical-min.css' ) );
        echo '<style>' . $critical_css . '</style>';
      } elseif ( $post_id == 38305 ) {
        $critical_css = file_get_contents( get_theme_file_path( 'dist/about-us-critical-min.css' ) );
        echo '<style>' . $critical_css . '</style>';
      } elseif ( strpos( $current_url, $blog_base_url ) === 0 ) {
        $critical_css = file_get_contents( get_theme_file_path( 'dist/blog-critical.min.css' ) );
        echo '<style>' . $critical_css . '</style>';
      } elseif ( $post_id == get_option( 'page_for_posts' ) ) {
        $critical_css = file_get_contents( get_theme_file_path( 'dist/blogs-critical.min.css' ) );
        echo '<style>.nemanja{color:blue}' . $critical_css . '</style>';
      }
    } else {
      $currentUrl = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? "https" : "http" )
                    . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
      
      $is_exact_blog = preg_match( '#^' . preg_quote( $blog_base_url, '#' ) . '/?$#', $current_url );
      
      if ( $is_exact_blog || $currentUrl === home_url( '/blog/' ) ) {
        $critical_css = file_get_contents( get_theme_file_path( 'dist/blogs-critical.min.css' ) );
        echo '<style>.nemanja{color:blue}' . $critical_css . '</style>';
      }
    }
    
    echo '<style>.zsiq-float { display: none !important; }</style>';
  } );
  
  /**
   * Enqueue scripts and styles for admin and frontend.
   *
   * @hook admin_enqueue_scripts | wp_enqueue_scripts
   */
  add_action(
    is_admin() ? 'admin_enqueue_scripts' : 'wp_enqueue_scripts',
    function () {
      $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
      if ( is_admin() && ! is_null( $screen ) && ! $screen->is_block_editor ) {
        return;
      }
      
      wp_register_style( 'constants', get_theme_file_uri( 'assets/css/_constants.scss' ), [], null );
      wp_register_script( 'custom-admin', get_theme_file_uri( 'assets/js/custom-admin.js' ), [], null, true );
      if ( is_admin() ) {
        wp_register_style( 'constants-editor', get_theme_file_uri( 'assets/css/_constants_editor.scss' ), [], null );
        wp_register_style( 'main', get_theme_file_uri( 'assets/css/style-editor.scss' ), [], null );
        wp_enqueue_style( 'constants-editor' );
        wp_enqueue_script( 'custom-admin' );
      } else {
        wp_register_style( 'main', get_theme_file_uri( 'assets/css/style.scss' ), [], null );
        wp_register_style( 'single', get_theme_file_uri( 'assets/css/single.scss' ), [], null );
        wp_register_style( '404', get_theme_file_uri( 'assets/css/404.scss' ), [], null );
        wp_register_style( 'search-form', get_theme_file_uri( 'assets/css/search-form.scss' ), [], null );
        wp_register_style( 'blog', get_theme_file_uri( 'assets/css/blog.scss' ), [], null );
        wp_register_style( 'print', get_theme_file_uri( 'assets/css/print.scss' ), [], null );
        wp_register_style( 'sidebar-template', get_theme_file_uri( 'assets/css/template-with-sidebar.scss' ), [], null );
        wp_register_style( 'single-custom-post-type', get_theme_file_uri( 'assets/css/single-custom-post-style.scss' ), [], null );
        // wp_register_script( 'partytown', get_theme_file_uri( '~partytown/partytown.js' ), [], null, true );
        wp_register_script( 'a11y', get_theme_file_uri( 'assets/js/accessibility.js' ), [], null, true );
      }
      wp_register_script( 'main', get_theme_file_uri( 'assets/js/scripts.js' ), [], null, true );
      wp_register_script( 'single', get_theme_file_uri( 'assets/js/single.js' ), [], null, true );
      wp_register_script( 'search-form', get_theme_file_uri( 'assets/js/search-form.js' ), [], null, true );
      wp_register_script( 'print', get_theme_file_uri( 'assets/js/print.js' ), [], null, true );
      wp_register_script( 'blog', get_theme_file_uri( 'assets/js/blog.js' ), [], null, true );
      wp_register_script( 'sidebar-template', get_theme_file_uri( 'assets/js/template-with-sidebar.js' ), [], null, true );
      wp_register_script( 'single-custom-post-type', get_theme_file_uri( 'assets/js/single-custom-post-script.js' ), [], null, true );
      wp_enqueue_style( 'constants' );
      wp_enqueue_style( 'main' );
      wp_enqueue_script( 'a11y' );
      wp_enqueue_script( 'partytown-config' );
      //      wp_localize_script( 'partytown-config', 'partytown_object', [
      //        'lib'     => get_theme_file_uri( '~partytown/' ),
      //        'forward' => [ '' ]
      //      ] );
      //      if ( ! is_admin() ) {
      //        wp_enqueue_script( 'partytown' );
      //      }
      wp_enqueue_script( 'main' );
      $localize_array = [
        'ajaxURL'            => admin_url( 'admin-ajax.php' ),
        'nonce'              => wp_create_nonce( 'session_nonce' ),
        'google_map_api_key' => get_field( 'google_map_api_key', 'option' )
      ];
      if ( get_option( 'wpcf7' ) && ! empty ( get_option( 'wpcf7' )['recaptcha'] ) ) {
        $localize_array['wpcf7_sitekey'] = array_key_first( get_option( 'wpcf7' )['recaptcha'] );
      }
      if ( is_user_logged_in() ) {
        $localize_array['user_phone'] = get_user_meta( get_current_user_id(), 'phone_number', true );
      }
      if ( ! empty ( get_field( 'cf7_error_messages', 'option' ) ) ) {
        $localize_array['error_messages'] = get_field( 'cf7_error_messages', 'option' );
      }
      wp_localize_script( 'main', 'mm_ajax_object', $localize_array );
      
      if ( is_single() ) {
        wp_enqueue_style( 'single' );
        wp_enqueue_script( 'single' );
      }
      
      if ( is_home() || is_archive() ) {
        wp_enqueue_style( 'blog' );
        wp_enqueue_script( 'blog' );
      }
      
      if ( is_page_template( 'template-with-sidebar.php' ) ) {
        wp_enqueue_style( 'sidebar-template' );
        wp_enqueue_script( 'sidebar-template' );
      }
      
      if ( ( is_singular( 'listings' ) || is_singular( 'buildings' ) ) && ! array_key_exists( 'print', $_GET ) ) {
        wp_enqueue_style( 'single-custom-post-type' );
        wp_enqueue_script( 'single-custom-post-type' );
      }
      
      if ( ! is_front_page() || ( is_singular( 'listings' ) && ! array_key_exists( 'print', $_GET ) ) ) {
        wp_enqueue_style( 'search-form' );
        wp_enqueue_script( 'search-form' );
      }
      
      if ( is_singular( 'listings' ) && array_key_exists( 'print', $_GET ) ) {
        wp_enqueue_style( 'print' );
        wp_enqueue_script( 'print' );
      }
      
      if ( is_404() ) {
        wp_enqueue_style( '404' );
      }
    }
  );
  
  function profidev_env( $var, $default ) {
    return "production";
  }
  
  add_filter( 'acf/fields/google_map/api', function ( $api ) {
    $api['key'] = get_field( 'google_map_api_key', 'option' );
    
    return $api;
  } );
  
  add_action( 'after_setup_theme', function () {
    register_nav_menu( 'header', 'Header Menu' );
    register_nav_menu( 'header_authorized', 'Menu For Authorized Users' );
    register_nav_menu( 'footer_neighborhoods', 'Footer Neighborhoods Menu' );
    register_nav_menu( 'footer_types', 'Footer Types Menu' );
    register_nav_menu( 'footer_menu', 'Footer Menu' );
    register_nav_menu( 'footer_small_menu', 'Footer Small Menu' );
  } );
  
  /**
   * Register custom post types: Listings and Buildings.
   *
   * @hook init
   */
  add_action( 'init', function () {
    register_post_type( 'listings', [
      'label'        => null,
      'labels'       => [
        'name'               => 'Listings',
        'singular_name'      => 'Listing',
        'add_new'            => 'Add Listing',
        'add_new_item'       => 'Adding Listing',
        'edit_item'          => 'Editing Listing',
        'new_item'           => 'New Listing',
        'view_item'          => 'View Listing',
        'search_items'       => 'Search Listing',
        'not_found'          => 'Not Found',
        'not_found_in_trash' => 'Not Found in trash',
        'parent_item_colon'  => '',
        'menu_name'          => 'Listings',
      ],
      'description'  => '',
      'public'       => true,
      'show_in_rest' => true,
      'hierarchical' => false,
      'supports'     => [ 'title', 'editor', 'thumbnail' ],
      // 'title','editor','author','thumbnail','excerpt','trackbacks','custom-fields','comments','revisions','page-attributes','post-formats'
      'taxonomies'   => [],
      'has_archive'  => false,
      'rewrite'      => [ 'slug' => 'listing', 'with_front' => false ],
      'query_var'    => true,
    ] );
    
    register_post_type( 'buildings', [
      'label'        => null,
      'labels'       => [
        'name'               => 'Buildings',
        'singular_name'      => 'Building',
        'add_new'            => 'Add Building',
        'add_new_item'       => 'Adding Building',
        'edit_item'          => 'Editing Building',
        'new_item'           => 'New Building',
        'view_item'          => 'View Building',
        'search_items'       => 'Search Building',
        'not_found'          => 'Not Found',
        'not_found_in_trash' => 'Not Found in trash',
        'parent_item_colon'  => '',
        'menu_name'          => 'Buildings',
      ],
      'description'  => '',
      'public'       => true,
      'show_in_rest' => true,
      'hierarchical' => false,
      'supports'     => [ 'title', 'editor', 'excerpt', 'thumbnail' ],
      // 'title','editor','author','thumbnail','excerpt','trackbacks','custom-fields','comments','revisions','page-attributes','post-formats'
      'taxonomies'   => [],
      'has_archive'  => false,
      'rewrite'      => [ 'slug' => 'buildings', 'with_front' => false ],
      'query_var'    => true,
    ] );
  } );
  
  add_action( 'init', function () {
    $location_labels = array(
      'name'              => 'Location',
      'singular_name'     => 'Location',
      'search_items'      => 'Search Location',
      'all_items'         => 'All Locations',
      'view_item'         => 'View Location',
      'parent_item'       => 'Parent Location',
      'parent_item_colon' => 'Parent Location:',
      'edit_item'         => 'Edit Location',
      'update_item'       => 'Update Location',
      'add_new_item'      => 'Add new Location',
      'new_item_name'     => 'New Location name',
      'not_found'         => 'No Location found',
      'back_to_items'     => 'Back to Locations',
      'menu_name'         => 'Location',
    );
    
    $location_args = array(
      'labels'            => $location_labels,
      'hierarchical'      => true,
      'public'            => false,
      'show_ui'           => true,
      'show_admin_column' => true,
      'query_var'         => true,
      'rewrite'           => array(),
      'show_in_rest'      => true,
    );
    
    
    register_taxonomy( 'location', [ 'listings', 'buildings' ], $location_args );
    
    
    $type_labels = array(
      'name'              => 'Listing types',
      'singular_name'     => 'Listing type',
      'search_items'      => 'Search listing type',
      'all_items'         => 'All Listing Type',
      'view_item'         => 'View Listing Type',
      'parent_item'       => 'Parent Listing Type',
      'parent_item_colon' => 'Parent Listing Type:',
      'edit_item'         => 'Edit Listing Type',
      'update_item'       => 'Update Listing Type',
      'add_new_item'      => 'Add New Listing Type',
      'new_item_name'     => 'New Listing Type name',
      'not_found'         => 'No Listing Type found',
      'back_to_items'     => 'Back to Listing Types',
      'menu_name'         => 'Listing types',
    );
    
    $type_args = array(
      'labels'            => $type_labels,
      'hierarchical'      => true,
      'public'            => false,
      'show_ui'           => true,
      'show_admin_column' => true,
      'query_var'         => true,
      'rewrite'           => array( 'slug' => 'uses', 'front' => false ),
      'show_in_rest'      => true,
    );
    
    
    register_taxonomy( 'listing-type', 'listings', $type_args );
    
    $categories_labels = array(
      'name'              => 'Categories',
      'singular_name'     => 'Category',
      'search_items'      => 'Search Category',
      'all_items'         => 'All Categories',
      'view_item'         => 'View Category',
      'parent_item'       => 'Parent Category',
      'parent_item_colon' => 'Parent Category:',
      'edit_item'         => 'Edit Category',
      'update_item'       => 'Update Category',
      'add_new_item'      => 'Add New Category',
      'new_item_name'     => 'New Category name',
      'not_found'         => 'No Category found',
      'back_to_items'     => 'Back to Category',
      'menu_name'         => 'Categories',
    );
    
    $categories_args = array(
      'labels'            => $categories_labels,
      'hierarchical'      => true,
      'public'            => false,
      'show_ui'           => true,
      'show_admin_column' => true,
      'query_var'         => true,
      'rewrite'           => array(),
      'show_in_rest'      => true,
    );
    
    
    register_taxonomy( 'listing-category', 'listings', $categories_args );
    
    
    $features_labels = array(
      'name'              => 'Features',
      'singular_name'     => 'Feature',
      'search_items'      => 'Search Feature',
      'all_items'         => 'All Features',
      'view_item'         => 'View Feature',
      'parent_item'       => 'Parent Feature',
      'parent_item_colon' => 'Parent Feature:',
      'edit_item'         => 'Edit Feature',
      'update_item'       => 'Update Feature',
      'add_new_item'      => 'Add New Feature',
      'new_item_name'     => 'New Feature name',
      'not_found'         => 'No Feature found',
      'back_to_items'     => 'Back to Feature',
      'menu_name'         => 'Features',
    );
    
    $features_args = array(
      'labels'            => $features_labels,
      'hierarchical'      => true,
      'public'            => false,
      'show_ui'           => true,
      'show_admin_column' => true,
      'query_var'         => true,
      'rewrite'           => array(),
      'show_in_rest'      => true,
    );
    
    
    register_taxonomy( 'feature', 'listings', $features_args );
  } );
  
  /**
   * Register theme options using ACF.
   *
   * @hook after_setup_theme
   */
  if ( function_exists( 'acf_add_options_page' ) ) {
    acf_add_options_page(
      array(
        'page_title' => 'Theme General Settings',
        'menu_title' => 'Theme Settings',
        'menu_slug'  => 'theme-general-settings',
        'capability' => 'edit_posts',
        'redirect'   => false
      )
    );
    acf_add_options_sub_page(
      array(
        'page_title'  => 'Theme Header Settings',
        'menu_title'  => 'Theme Header Settings',
        'parent_slug' => 'theme-general-settings',
      )
    );
    acf_add_options_sub_page(
      array(
        'page_title'  => 'Theme Footer Settings',
        'menu_title'  => 'Theme Footer Settings',
        'parent_slug' => 'theme-general-settings',
      )
    );
    acf_add_options_sub_page(
      array(
        'page_title'  => 'Theme Listing / Building Settings',
        'menu_title'  => 'Theme Listing / Building Settings',
        'parent_slug' => 'theme-general-settings',
      )
    );
    acf_add_options_sub_page(
      array(
        'page_title'  => 'Theme Print Settings',
        'menu_title'  => 'Theme Print Settings',
        'parent_slug' => 'theme-general-settings',
      )
    );
    acf_add_options_sub_page(
      array(
        'page_title'  => 'Theme Post Settings',
        'menu_title'  => 'Theme Post Settings',
        'parent_slug' => 'theme-general-settings',
      )
    );
    acf_add_options_sub_page(
      array(
        'page_title'  => 'Spam Filtering Settings',
        'menu_title'  => 'Spam Filtering',
        'parent_slug' => 'theme-general-settings',
      )
    );
  }
  
  add_action( 'after_setup_theme', function () {
    if ( ! current_user_can( 'administrator' ) && ! is_admin() ) {
      show_admin_bar( false );
    }
  } );
  
  /**
   * Add a meta box for Listing Types in Listings post type.
   *
   * @hook add_meta_boxes
   */
  add_action( 'add_meta_boxes', function () {
    add_meta_box(
      'listing-typediv',
      __( 'Display Listing Label On Detail Page' ),
      'listing_type_meta_box_callback',
      'listings',
      'normal',
      'high',
      array( 'taxonomy' => 'listing-type' )
    );
  } );
  
  add_action('wp_ajax_set_primary_listing_type', function () {
    check_ajax_referer('set-primary-listing-type', '_ajax_nonce');
    
    $post_id = intval($_POST['post_id']);
    $term_id = intval($_POST['term_id']);
    
    if (!current_user_can('edit_post', $post_id)) {
      wp_send_json_error('Unauthorized.');
    }
    
    if (get_post_type($post_id) !== 'listings') {
      wp_send_json_error('Invalid post type.');
    }
    
    // Update the primary listing type meta field
    update_post_meta($post_id, 'primary_listing_type', $term_id);
    
    // Retrieve the secondary term to maintain it
    $secondary_term = get_post_meta($post_id, 'secondary_listing_type', true);
    
    // Ensure `listing_type_shown_on_post` contains only primary and secondary terms, sorted
    $updated_terms = [$term_id]; // Start with the primary term
    if ($secondary_term && $secondary_term !== $term_id) {
      $updated_terms[] = $secondary_term; // Add secondary term if it exists and is not the same as the primary
    }
    
    // Update the meta field with the sorted values
    if ( $updated_terms[0] == 0 ) {
      delete_post_meta($post_id, 'listing_type_shown_on_post');
    } else {
      update_post_meta( $post_id, 'listing_type_shown_on_post', serialize( $updated_terms ) );
    }
    
    wp_send_json_success('Primary listing type updated.');
  });
  
  add_action('wp_ajax_set_secondary_listing_type', function () {
    check_ajax_referer('set-secondary-listing-type', '_ajax_nonce');
    
    $post_id = intval($_POST['post_id']);
    $term_id = intval($_POST['term_id']);
    
    if (!current_user_can('edit_post', $post_id)) {
      wp_send_json_error('Unauthorized.');
    }
    
    if (get_post_type($post_id) !== 'listings') {
      wp_send_json_error('Invalid post type.');
    }
    
    // Update the secondary listing type meta field
    update_post_meta($post_id, 'secondary_listing_type', $term_id);
    
    // Retrieve the primary term to maintain it
    $primary_term = get_post_meta($post_id, 'primary_listing_type', true);
    
    // Ensure `listing_type_shown_on_post` contains only primary and secondary terms, sorted
    $updated_terms = [];
    if ($primary_term) {
      $updated_terms[] = $primary_term; // Add primary term if it exists
    }
    $updated_terms[] = $term_id; // Add the secondary term
    
    // Update the meta field with the sorted values
    if ( $updated_terms[0] == 0 ) {
      delete_post_meta($post_id, 'listing_type_shown_on_post');
    } else {
      update_post_meta( $post_id, 'listing_type_shown_on_post', serialize( $updated_terms ) );
    }
    
    wp_send_json_success('Secondary listing type updated.');
  });
  
  add_action( 'wp_ajax_add_listing_type', 'add_listing_type_callback' );
  function add_listing_type_callback() {
    check_ajax_referer( 'add-listing-type' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
      wp_send_json_error( 'You do not have permission to perform this action.' );
    }
    
    $taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_text_field( $_POST['taxonomy'] ) : '';
    $term     = isset( $_POST['term'] ) ? sanitize_text_field( $_POST['term'] ) : '';
    
    if ( empty( $taxonomy ) || empty( $term ) ) {
      wp_send_json_error( 'Invalid taxonomy or term.' );
    }
    
    $term_exists = term_exists( $term, $taxonomy );
    if ( $term_exists ) {
      wp_send_json_error( 'This term already exists.' );
    }
    
    $new_term = wp_insert_term( $term, $taxonomy );
    if ( is_wp_error( $new_term ) ) {
      wp_send_json_error( $new_term->get_error_message() );
    }
    
    wp_send_json_success( 'New listing type added successfully.' );
  }
  
  add_filter( 'wpcf7_autop_or_not', '__return_false' );
  
  add_filter( 'use_block_editor_for_post', function ( $can_edit, $post ) {
    $page_for_posts = get_option( 'page_for_posts' );
    if ( $post->post_type == 'page' && $post->ID != $page_for_posts ) {
      return true;
    }
    
    return false;
  }, 10, 2 );
  
  add_action( 'post_updated', function ( $post_id ) {
    $custom_field = get_field( 'map', $post_id );
    if ( isset ( $custom_field['lat'] ) && isset ( $custom_field['lng'] ) ) {
      $is_exist_meta_latitude  = get_post_meta( $post_id, 'post_latitude', true );
      $is_exist_meta_longitude = get_post_meta( $post_id, 'post_longitude', true );
      if ( ! empty ( $is_exist_meta_latitude ) ) {
        update_post_meta( $post_id, 'post_latitude', $custom_field['lat'] );
      } else {
        add_post_meta( $post_id, 'post_latitude', $custom_field['lat'] );
      }
      if ( ! empty ( $is_exist_meta_longitude ) ) {
        update_post_meta( $post_id, 'post_longitude', $custom_field['lng'] );
      } else {
        add_post_meta( $post_id, 'post_longitude', $custom_field['lng'] );
      }
    }
  } );
  
  add_action( 'get_header', function () {
    remove_action( 'wp_head', '_admin_bar_bump_cb' );
  } );
  
  add_filter( 'user_contactmethods', function ( $contact_methods ) {
    $contact_methods['phone_number'] = 'Phone Number';
    
    return $contact_methods;
  } );
  
  add_filter( 'wp_nav_menu_items', function ( $items, $args ) {
    if ( $args->theme_location == 'header_authorized' ) {
      if ( is_user_logged_in() ) {
        $items .= '<li class="right"><a href="' . wp_logout_url( get_home_url() ) . '">' . __( "Log Out" ) . '</a></li>';
      }
    }
    
    return $items;
  }, 10, 2 );
  
  add_action( 'template_redirect', function () {
    $pages = get_field( 'choose_pages_for_authorized_users', 'option' );
    if ( $pages ) {
      foreach ( $pages as $page ) {
        if ( ! is_user_logged_in() && is_page( $page ) ) {
          wp_redirect( '/' );
          exit;
        }
      }
    }
  } );
  
  add_filter( 'wp_terms_checklist_args', function ( $args, $post_id ) {
    return [ 'taxonomy' => $args['taxonomy'], 'checked_ontop' => false ];
  }, 10, 2 );
  
  add_action( 'template_include', function ( $template ) {
    if ( isset ( $_GET['print'] ) && is_single( $_GET['print'] ) ) {
      return get_template_directory() . '/print-template.php';
    }
    
    return $template;
  } );
  
  add_action( 'wp_enqueue_scripts', function () {
    wp_dequeue_script( 'wpcf7-recaptcha' );
  }, 99, 0 );
  
  add_action( 'get_footer', function () {
    wp_register_script( 'cf7recap', get_theme_file_uri( 'assets/js/recaptcha.js' ), [], null, true );
    wp_enqueue_script( 'cf7recap' );
    if ( get_option( 'wpcf7' ) && ! empty ( get_option( 'wpcf7' )['recaptcha'] ) ) {
      wp_localize_script(
        'cf7recap',
        'wpcf7_recaptcha',
        array(
          'sitekey' => array_keys( get_option( 'wpcf7' )['recaptcha'] )[0],
          'actions' => apply_filters(
            'wpcf7_recaptcha_actions',
            array(
              'homepage'    => 'homepage',
              'contactform' => 'contactform',
            )
          ),
        )
      );
    }
  } );
  
  add_filter( 'script_loader_tag', function ( $tag, $handle, $src ) {
    $defer_scripts = [
      'contact-form-7',
      'swv',
      'a11y',
      'main',
      'acf-home-hero-section-script',
      'acf-featured-spaces-section-script',
      'acf-browse-listings-section-script',
      'acf-clients-say-section-script',
      'acf-youtube-section-script',
      'acf-news-section-script',
      'acf-resources-section-script',
      'acf-our-clients-section-script',
      'cf7recap',
      'swiper-core-abd832c7',
      'helpers-0ed1997f',
      'script-fa748a13',
      'script-3f17f19d',
      'script-62a4a278',
      'script-509b62f7',
      'script-8035f573',
      'script-d3111460',
      'script-cb8d8c8c',
      'script-90c36c5e',
      'scripts-3af06bf6'
    ];
    
    if ( in_array( $handle, $defer_scripts ) ) {
      if ( is_front_page() || $handle === 'contact-form-7' ) {
        $tag = str_replace( 'async=""', '', $tag );
        $tag = str_replace( '<script', '<script defer', $tag );
      }
    }
    
    return $tag;
  }, 10, 3 );
  
  add_action( 'manage_listings_posts_columns', function ( $columns ) {
    $columns['coordinates'] = 'Coordinates';
    
    if ( isset( $columns['taxonomy-listing-type'] ) ) {
      $columns['taxonomy-listing-type'] = 'Landing Pages';
    }
    
    $new_columns = [];
    foreach ( $columns as $key => $value ) {
      if ( $key === 'taxonomy-listing-type' ) {
        $new_columns['listing-type'] = 'Listing Type';
      }
      $new_columns[ $key ] = $value;
    }
    
    return $new_columns;
  } );
  
  add_action( 'manage_posts_custom_column', 'action_custom_columns_content', 10, 2 );
  function action_custom_columns_content( $column_id, $post_id ) {
    switch ( $column_id ) {
      case 'coordinates':
        $post_latitude  = get_post_meta( $post_id, 'post_latitude', true );
        $post_longitude = get_post_meta( $post_id, 'post_longitude', true );
        echo ( $post_latitude && $post_longitude ) ? $post_latitude . ', ' . $post_longitude : 'No Coordinates';
        break;
      case 'listing-type':
        $primary_listing_type  = get_post_meta( $post_id, 'primary_listing_type', true );
        $secondary_listing_type = get_post_meta( $post_id, 'secondary_listing_type', true );
        if ( $primary_listing_type && $secondary_listing_type ) {
            $primary_term = get_term( $primary_listing_type, 'listing-type' );
            $secondary_term = get_term( $secondary_listing_type, 'listing-type' );
            echo $primary_term->name . ', ' . $secondary_term->name;
        } else if ( $primary_listing_type ) {
          $primary_term = get_term( $primary_listing_type, 'listing-type' );
          echo $primary_term->name;
        } else if ( $secondary_listing_type ) {
          $secondary_term = get_term( $secondary_listing_type, 'listing-type' );
          echo $secondary_term->name;
        }
        break;
    }
  }
  
  add_action( 'init', function () {
    add_rewrite_rule(
      '^([^/]+)/page/([0-9]+)/?$',
      'index.php?pagename=$matches[1]&paged=$matches[2]',
      'top'
    );
  } );
  
  add_filter( 'wpseo_pre_analysis_post_content', function ( $content, $post_id ) {
    if ( empty ( $content ) ) {
      return;
    }
    $blocks = parse_blocks( $content );
    $html   = '';
    foreach ( $blocks as $block ) {
      $html .= render_block( $block );
    }
    $content = $html;
    
    return $content;
  }, PHP_INT_MAX, 2 );
  
  
  add_filter( 'classic_editor_enabled', function ( $enabled, $post ) {
    if ( is_page( $post->ID ) ) {
      $enabled = false;
    }
    
    return $enabled;
  }, PHP_INT_MAX, 2 );
  
  add_filter( 'acf/load_field', function ( $field ) {
    global $post;
    if ( ! is_admin() || empty ( $post ) ) {
      return $field;
    }
    
    if ( $post->post_status == 'auto-draft' && $post->post_type == 'listings' && $field['name'] === 'listing_id' && is_null( $field['value'] ) ) {
      $field['value'] = $post->ID;
    }
    
    return $field;
  }, PHP_INT_MAX, 1 );
  
  add_filter( 'wpcf7_form_elements', function ( $content ) {
    if ( str_contains( $content, 'name="page-name"' ) ) {
      $post_title = get_the_title();
      $content    = str_replace( 'value="" type="hidden" name="page-name"', 'type="hidden" name="page-name" value="' . esc_attr( $post_title ) . '"', $content );
    }
    
    return $content;
  }, PHP_INT_MAX, 3 );
  
  add_action( 'wp', function () {
    $search_page       = get_field( 'choose_search_page', 'option' );
    $resend_email_page = get_field( 'choose_resend_email_page', 'option' );
    
    if ( ! is_page( $search_page ) && ! empty ( $_SESSION['filter'] ) && ! is_page( $resend_email_page ) ) {
      $_SESSION['filter'] = null;
    }
  } );
  
  add_filter( 'wpseo_robots', function ( $robotsstr ) {
    if ( is_tax() && is_paged() ) {
      return 'noindex, follow';
    }
    
    return $robotsstr;
  }, 1, PHP_INT_MAX );
  
  
  add_action( 'init', function () {
    global $vxcf_form, $wp_filter;
    if ( array_key_exists( 'wp_footer', $wp_filter ) && $wp_filter['wp_footer'] instanceof WP_Hook ) {
      $wp_filter['wp_footer']->remove_filter( 'wp_footer', array( $vxcf_form, 'footer_js' ), 33 );
    }
  } );
  
  add_action( 'after_setup_theme', function () {
    if ( ! current_user_can( 'administrator' ) && ! is_admin() ) {
      show_admin_bar( false );
    }
  } );
  
  add_action( 'user_new_form', function ( $user ) {
    ?>
      <input name="is_user_registered_from_admin" value="true" type="hidden">
    <?php
  }, PHP_INT_MAX, 1 );
  
  add_action( 'user_register', function ( $user_id ) {
    if ( array_key_exists( 'is_user_registered_from_admin', $_POST ) && $_POST['is_user_registered_from_admin'] == 'true' ) {
      update_user_meta( $user_id, 'has_to_be_activated', 'verified' );
    }
  }, PHP_INT_MAX, 1 );
  
  add_action( 'admin_init', function () {
    $current_user = wp_get_current_user();
    
    if ( count( $current_user->roles ) == 1 && $current_user->roles[0] == 'subscriber' ) {
      if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        return;
      }
      
      wp_redirect( site_url( '/' ) );
    }
  } );
  
  add_action( 'check_admin_referer', function ( $action, $result ) {
    if ( $action == "log-out" ) {
      wp_logout();
      $redirect_to = isset ( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '/';
      header( "Location: $redirect_to" );
      die;
    }
  }, PHP_INT_MAX, 2 );
  
  add_action( 'admin_menu', function () {
    remove_meta_box( 'listing-typediv', 'listings', 'side' );
  } );
  
  add_action( 'admin_enqueue_scripts', function () {
    wp_enqueue_script( 'listing-info-toggle', get_theme_file_uri( '/assets/js/listing-info-toggle.js' ), [], null, true );
    
    wp_localize_script( 'listing-info-toggle', 'listingInfoAjax', [
      'ajaxUrl'                      => admin_url( 'admin-ajax.php' ),
      'nonceAddListingType'          => wp_create_nonce( 'add-listing-type' ),
      'nonceSetPrimaryListingType'   => wp_create_nonce( 'set-primary-listing-type' ),
      'nonceSetSecondaryListingType' => wp_create_nonce( 'set-secondary-listing-type' ),
    ] );
  } );
  
//  add_action( 'init', function () {
//    $type_2_labels = array(
//      'name'              => 'Landing Pages',
//      'singular_name'     => 'Landing Page',
//      'search_items'      => 'Search Landing Page Types',
//      'all_items'         => 'All Landing Page Types',
//      'view_item'         => 'View Landing Page Type',
//      'parent_item'       => 'Parent Landing Page Type',
//      'parent_item_colon' => 'Parent Landing Page Type:',
//      'edit_item'         => 'Edit Landing Page Type',
//      'update_item'       => 'Update Landing Page Type',
//      'add_new_item'      => 'Add New Landing Page Type',
//      'new_item_name'     => 'New Landing Page Type Name',
//      'not_found'         => 'No Landing Page Type found',
//      'back_to_items'     => 'Back to Landing Page Types',
//      'menu_name'         => 'Landing Page Types',
//    );
//
//    $type_2_args = array(
//      'labels'            => $type_2_labels,
//      'hierarchical'      => true,
//      'public'            => false,
//      'show_ui'           => true,
//      'show_admin_column' => true,
//      'query_var'         => true,
//      'rewrite'           => array(),
//      'show_in_rest'      => true,
//      'meta_box_cb'       => false,
//    );
//
//    register_taxonomy( 'display-landing-pages', 'listings', $type_2_args );
//  } );
  
  add_action( 'add_meta_boxes', function () {
    add_meta_box(
      'display-landing-pages-div',
      __( 'Landing Pages' ),
      'display_landing_pages_meta_box_callback',
      'listings',
      'normal',
      'default',
      array( 'taxonomy' => 'listing-type' )
    );
  } );
  
  function custom_sort_terms($terms) {
    $desired_order = [
      'Office Space', 'Commercial Loft', 'Startup &amp; Tech Space', 'Medical Space',
      'Financial Services', 'Law Firm Offices', 'Retail/Stores', 'Sublet Space',
      'Accounting', 'Ad Agency', 'Public Relations', 'Gallery',
      'Lab Space', 'Showroom', 'Green'
    ];
    
    $exclude_terms = ['Creative', 'Non profit', 'Suite'];
    
    // Filter out excluded terms
    $filtered_terms = array_filter($terms, function($term) use ($exclude_terms) {
      return !in_array($term->name, $exclude_terms);
    });
    
    usort($filtered_terms, function($a, $b) use ($desired_order) {
      $pos_a = array_search($a->name, $desired_order);
      $pos_b = array_search($b->name, $desired_order);
      $pos_a = ($pos_a === false) ? PHP_INT_MAX : $pos_a;
      $pos_b = ($pos_b === false) ? PHP_INT_MAX : $pos_b;
      
      return $pos_a - $pos_b;
    });
    
    return $filtered_terms;
  }
  
  function listing_type_meta_box_callback( $post, $box ) {
    $taxonomy = $box['args']['taxonomy'];
    $terms    = get_terms( [
      'taxonomy'   => $taxonomy,
      'hide_empty' => false,
    ] );
    
    $terms = custom_sort_terms($terms);
    
    $primary_term    = get_post_meta( $post->ID, 'primary_listing_type', true );
    $secondary_term    = get_post_meta( $post->ID, 'secondary_listing_type', true );
    $displayed_terms_serialized = get_post_meta( $post->ID, 'listing_type_shown_on_post', true );
    
    // Unserialize the stored meta value to get an array
    $displayed_terms = $displayed_terms_serialized ? unserialize( $displayed_terms_serialized ) : [];
    
    
    wp_nonce_field( 'save_listing_type_meta', 'listing_type_meta_nonce' );
    
    ?>
      <style>
          #listing-typediv ul {
              list-style: none;
              padding: 0;
              margin: 0
          }

          #listing-typediv li {
              display: flex;
              flex-direction: column;
              align-items: flex-start;
              margin-bottom: 10px;
              padding: 8px;
              border: 1px solid #ddd;
              border-radius: 4px;
              background-color: #f9f9f9
          }

          #listing-typediv li label {
              display: inline-flex;
              align-items: center
          }

          #listing-typediv li label:nth-of-type(2) {
              display: block;
              margin-top: 8px
          }

          #listing-typediv li input[type="checkbox"] {
              margin-right: 5px
          }

          #listing-typediv li.primary-row {
              background-color: #d8f0d8
          }

          #listing-typediv li.secondary-row {
              background-color: aliceblue;
          }

          .mark-primary,
          .remove-primary,
          .mark-secondary,
          .remove-secondary,
          .horizontal-line {
              display: inline;
              background-color: #5783db;
              border: 1px solid #5783db;
              border-radius: 4px;
              color: white;
              padding: 5px;
              margin-left: 10px;
          }

          .mark-primary:hover,
          .remove-primary:hover,
          .mark-secondary:hover {
              background-color: #4681f4;
          }

          #listing-typediv li.primary-row {
              background-color: #d8f0d8;
          }

          #listing-typediv li label:nth-of-type(2) {
              display: block;
              margin-top: 8px
          }

          .description-text {
              background-color: #faffac;
              color: black;
              font-size: 16px;
              text-align: center;
              padding: 10px
          }

          .description-img {
              text-align: center;
          }
          
          .description-img img {
              width: 100%;
              max-width: 739px;
          }

          #listing-typediv li:hover .mark-primary,
          #listing-typediv li:hover .remove-primary,
          #listing-typediv li:hover .horizontal-line,
          #listing-typediv li:hover .remove-secondary,
          #listing-typediv li:hover .mark-secondary {
              display: inline;
              margin-left: 10px;
          }
          
          .primary-mark,
          .secondary-mark {
              display: inline;
              border: 5px solid lightyellow;
              border-radius: 10px;
              padding: 3px;
              background-color: lightyellow;
              color: #2d211c;
              font-weight: 500;
          }

          .secondary-mark {
              background-color: antiquewhite;
              border: 5px solid antiquewhite;
          }
          
          .description-video {
              text-align: center;
              background-color: bisque;
              color: black;
              font-size: 14px;
              padding: 10px;
              display: none;
          }
          
          #listing-type-list input[type='checkbox'] {
              position: absolute;
              width: 1px;
              height: 1px;
              margin: -1px;
              padding: 0;
              border: 0;
              clip: rect(0, 0, 0, 0);
              clip-path: inset(50%);
              overflow: hidden;
              white-space: nowrap;
          }
          
          .remove-primary,
          .remove-secondary {
              background-color: #ff000087;
              margin-right: 10px;
              border-color: #ff000087;
          }

          .remove-primary:hover,
          .remove-secondary:hover {
              background-color: #ff0000bf;
              border-color: #ff0000bf;
          }
      </style>
    <?php
    
    echo '<p class="description-text">' . __( 'Display as a label on actual listing, see example:' ) . '</p>';
    echo '<p class="description-img"><img src="https://www.metro-manhattan.com/wp-content/uploads/2024/12/listing-type-label-img-01.png"  alt="Listing type label"/></p>';
    echo '<p class="description-video">Still not sure what this option does? Check out <a href="#">this</a> video.</p>';
    echo '<ul id="listing-type-list">';
    if ( ! empty( $terms ) ) {
      foreach ( $terms as $term ) {
        if ( $term->name == 'Startup &amp; Tech Space' ) {
          continue;
        }
        if ( $term->name == 'Financial Services' ) {
          continue;
        }
        
        if ( $term->name == 'Law Firm Offices' ) {
          continue;
        }
        
        if ( $term->name == 'Accounting' ) {
          continue;
        }
        
        if ( $term->name == 'Ad Agency' ) {
          continue;
        }
        
        if ( $term->name == 'Public Relations' ) {
          continue;
        }
        
        if ( $term->name == 'Green' ) {
          continue;
        }
        $checked      = in_array( $term->term_id, $displayed_terms ) ? 'checked' : '';
        $is_primary   = ( $term->term_id == $primary_term ) ? 'primary-row' : '';
        $is_secondary = ( $term->term_id == $secondary_term ) ? 'secondary-row' : '';
        
        if ( $is_primary ) {
          echo '<li class="' . esc_attr( $is_primary ) . '" data-term-id="' . esc_attr( $term->term_id ) . '">';
        } else if ( $is_secondary ) {
          echo '<li class="' . esc_attr( $is_secondary ) . '" data-term-id="' . esc_attr( $term->term_id ) . '">';
        } else {
          echo '<li data-term-id="' . esc_attr( $term->term_id ) . '">';
        }
        
        echo '<div style="display: flex; justify-content: space-between; align-items: center; width:100%;">';
        echo '<label>';
        echo '<input type="checkbox" name="listing_type[]" value="' . esc_attr( $term->term_id ) . '" ' . esc_attr( $checked ) . '>';
        echo esc_html( $term->name );
        echo '</label>';
        
        if ( ! $is_primary && ! $is_secondary ) {
          echo '<div class="button-wrappers">';
          echo '<button class="mark-primary" id="mark-primary-' . esc_attr( $term->term_id ) . '" data-term-id="' . $term->term_id . '">' . __( 'Mark as Primary', 'textdomain' ) . '</button>';
          echo '<button class="mark-secondary" id="mark-secondary' . esc_attr( $term->term_id ) . '">' . __( 'Mark as Secondary', 'textdomain' ) . '</button>';
          echo '</div>';
        }
        
        if ( $is_primary ) {
          echo '<div>';
          echo '<button class="remove-primary" id="remove-primary-' . esc_attr( $term->term_id ) . '" data-term-id="' . $term->term_id . '">' . __( 'Remove Primary Label', 'textdomain' ) . '</button>';
          echo '<span class="primary-mark" id="primary-mark' . esc_attr( $term->term_id ) . '">' . __( 'This is PRIMARY label', 'textdomain' ) . '</span>';
          echo '</div>';
        } else if ( $is_secondary ) {
          echo '<div>';
          echo '<button class="remove-secondary" id="remove-secondary-' . esc_attr( $term->term_id ) . '" data-term-id="' . $term->term_id . '">' . __( 'Remove Secondary Label', 'textdomain' ) . '</button>';
          echo '&nbsp;&nbsp;<span class="secondary-mark" id="secondary-mark' . esc_attr( $term->term_id ) . '">' . __( 'This is SECONDARY label', 'textdomain' ) . '</span>';
          echo '</div>';
        }
        
        echo '</div>';
        echo '</li>';
      }
    } else {
      echo '<p>' . __( 'No terms available for this taxonomy.', 'textdomain' ) . '</p>';
    }
    echo '</ul>';
  }
  
  function display_landing_pages_meta_box_callback( $post, $box ) {
    $taxonomy = 'listing-type';
    $terms    = get_terms( [
      'taxonomy'   => $taxonomy,
      'hide_empty' => false,
    ] );
    
    $terms = custom_sort_terms($terms);
    
    $post_terms = wp_get_object_terms( $post->ID, $taxonomy, [ 'fields' => 'ids' ] );
    wp_nonce_field( 'save_display_landing_pages_meta', 'display_landing_pages_meta_nonce' );
    
    echo '<p style="background-color:#faffac; color: black; font-size:16px; text-align:center; padding:10px;">' . __( 'Select the landing pages where this listing should appear.' ) . '</p>';
    echo '<p class="description-img"><img src="https://www.metro-manhattan.com/wp-content/uploads/2024/12/accounting-example.png"  alt="Listing type on landing pages"/></p>';
    echo '<p class="description-video">Still not sure what this option does? Check out <a href="#">this</a> video.</p>';
    echo '<ul id="display-landing-pages-list">';
    if ( ! empty( $terms ) ) {
      foreach ( $terms as $term ) {
        $checked = in_array( $term->term_id, $post_terms ) ? 'checked' : '';
        echo '<li>';
        echo '<label>';
        echo '<input type="checkbox" name="display_landing_pages[]" value="' . esc_attr( $term->term_id ) . '" ' . esc_attr( $checked ) . '>';
        echo esc_html( $term->name );
        echo '</label>';
        echo '</li>';
      }
    } else {
      echo '<p>' . __( 'No terms available.' ) . '</p>';
    }
    echo '</ul>';
  }
  
  add_action( 'save_post', function ( $post_id ) {
    $listing_type_nonce_valid = isset( $_POST['listing_type_meta_nonce'] ) && wp_verify_nonce( $_POST['listing_type_meta_nonce'], 'save_listing_type_meta' );
    $display_landing_pages_nonce_valid = isset( $_POST['display_landing_pages_meta_nonce'] ) && wp_verify_nonce( $_POST['display_landing_pages_meta_nonce'], 'save_display_landing_pages_meta' );
    
    if ( ! $listing_type_nonce_valid && ! $display_landing_pages_nonce_valid ) {
      return;
    }
    
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
      return;
    }
    
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
      return;
    }
    
    if ( get_post_type( $post_id ) !== 'listings' ) {
      return;
    }
    
    $detail_page_terms = [];
    $landing_page_terms = [];
    if ($listing_type_nonce_valid) {
      if (isset($_POST['listing_type']) && is_array($_POST['listing_type'])) {
        $detail_page_terms = array_map('intval', $_POST['listing_type']);
//        update_post_meta($post_id, 'listing_type_shown_on_post', serialize($detail_page_terms));
      } else {
//        delete_post_meta($post_id, 'listing_type_shown_on_post');
      }
    } else {
      $stored_meta = get_post_meta($post_id, 'listing_type_shown_on_post', true);
      $detail_page_terms = $stored_meta ? unserialize($stored_meta) : [];
    }
    
    if ($display_landing_pages_nonce_valid) {
      if (isset($_POST['display_landing_pages']) && is_array($_POST['display_landing_pages'])) {
        $landing_page_terms = array_map('intval', $_POST['display_landing_pages']);
//        update_post_meta($post_id, 'listing_type_shown_on_landing_pages', serialize($landing_page_terms));
      } else {
//        delete_post_meta($post_id, 'listing_type_shown_on_landing_pages');
      }
    } else {
      $stored_meta = get_post_meta($post_id, 'listing_type_shown_on_landing_pages', true);
      $landing_page_terms = $stored_meta ? unserialize($stored_meta) : [];
    }
    $merged_terms = array_unique(array_merge($detail_page_terms, $landing_page_terms));
    wp_set_object_terms($post_id, $merged_terms, 'listing-type');
  });