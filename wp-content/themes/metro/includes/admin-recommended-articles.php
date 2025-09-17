<?php
  /**
   * Admin Page for Generating & Viewing Recommended Articles, High Quality Articles, and Post Categories
   *
   * Creates an admin page under Tools with buttons to generate:
   * - Recommended Articles,
   * - High Quality Articles, and
   * - A listing of each post's category slugs.
   *
   * Place this file in your theme's "includes" folder and include it in your theme's functions.php.
   */
  
  if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
  }
  
  /**
   * Process the generation of recommended articles.
   */
  function rra_generate_recommended_articles() {
    if ( ! current_user_can( 'manage_options' ) ) {
      return;
    }
    if ( isset( $_POST['rra_generate'] ) && check_admin_referer( 'rra_generate_nonce' ) ) {
      $args          = array(
        'post_type'      => 'post',
        'posts_per_page' => - 1,
        'post_status'    => 'publish'
      );
      $query         = new WP_Query( $args );
      $updated_count = 0;
      if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
          $query->the_post();
          $post_id = get_the_ID();
          $post    = get_post();
          compute_and_store_openai_embedding( $post_id, $post, false );
          $recommended_articles = get_related_posts( $post_id, 3 );
          update_post_meta( $post_id, '_generated_recommended_articles', $recommended_articles );
          $updated_count ++;
        }
        wp_reset_postdata();
      }
      echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $updated_count ) . ' post(s) updated with recommended articles.</p></div>';
    }
  }
  
  /**
   * Process the generation of high quality recommended articles.
   */
  function rra_generate_high_quality_articles() {
    if ( ! current_user_can( 'manage_options' ) ) {
      return;
    }
    if ( isset( $_POST['rra_generate_hq'] ) && check_admin_referer( 'rra_generate_hq_nonce' ) ) {
      $args          = array(
        'post_type'      => 'post',
        'posts_per_page' => - 1,
        'post_status'    => 'publish'
      );
      $query         = new WP_Query( $args );
      $updated_count = 0;
      if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
          $query->the_post();
          $post_id = get_the_ID();
          $post    = get_post();
          compute_and_store_openai_embedding( $post_id, $post, false );
          $hq_articles = get_semantically_similar_high_quality_posts( $post_id, 3 );
          update_post_meta( $post_id, '_generated_high_quality_articles', $hq_articles );
          $updated_count ++;
        }
        wp_reset_postdata();
      }
      echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $updated_count ) . ' post(s) updated with high quality recommended articles.</p></div>';
    }
  }
  
  /**
   * Process the listing of categories for each published post.
   */
  function rra_list_post_categories() {
    if ( ! current_user_can( 'manage_options' ) ) {
      return;
    }
    if ( isset( $_POST['rra_list_categories'] ) && check_admin_referer( 'rra_list_categories_nonce' ) ) {
      $args  = array(
        'post_type'      => 'post',
        'posts_per_page' => - 1,
        'post_status'    => 'publish'
      );
      $query = new WP_Query( $args );
      if ( $query->have_posts() ) {
        echo '<h2>Posts and Their Category Slugs</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Post Title</th><th>Category Slugs</th></tr></thead>';
        echo '<tbody>';
        while ( $query->have_posts() ) {
          $query->the_post();
          $post_id       = get_the_ID();
          $title         = get_the_title();
          $cat_slugs     = wp_get_post_terms( $post_id, 'category', array( 'fields' => 'slugs' ) );
          $cat_slugs_str = ( is_array( $cat_slugs ) && ! empty( $cat_slugs ) ) ? implode( ', ', $cat_slugs ) : 'No Categories';
          echo '<tr>';
          echo '<td><a href="' . esc_url( get_permalink( $post_id ) ) . '" target="_blank">' . esc_html( $title ) . '</a></td>';
          echo '<td>' . esc_html( $cat_slugs_str ) . '</td>';
          echo '</tr>';
        }
        echo '</tbody></table>';
        wp_reset_postdata();
      } else {
        echo '<p>No posts found.</p>';
      }
    }
  }
  
  /**
   * Render the admin page for Recommended Articles, High Quality Articles, and Categories.
   */
  function render_recommended_articles_admin_page() {
    ?>
      <div class="wrap">
          <h1>Metro-Manhattan AI Tool</h1>
          <div class="nav-tab-wrapper">
              <a href="#recommended" class="nav-tab nav-tab-active">Recommended Articles</a>
              <a href="#high_quality" class="nav-tab">High Quality Articles</a>
              <!--<a href="#categories" class="nav-tab">Categories</a>-->
          </div>

          <div id="recommended" style="display:block;">
              <form method="post" action="">
                <?php wp_nonce_field( 'rra_generate_nonce' ); ?>
                  <p>
                      <input type="submit" name="rra_generate" class="button button-primary"
                             value="Generate Recommended Articles">
                  </p>
              </form>
            <?php
              rra_generate_recommended_articles();
              $args  = array(
                'post_type'      => 'post',
                'posts_per_page' => - 1,
                'post_status'    => 'publish'
              );
              $query = new WP_Query( $args );
              if ( $query->have_posts() ) {
                echo '<h2>Posts with Recommended Articles</h2>';
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr><th>Post Title</th><th>Recommended Articles</th></tr></thead>';
                echo '<tbody>';
                while ( $query->have_posts() ) {
                  $query->the_post();
                  $post_id     = get_the_ID();
                  $title       = get_the_title();
                  $recommended = get_post_meta( $post_id, '_generated_recommended_articles', true );
                  if ( is_array( $recommended ) && ! empty( $recommended ) ) {
                    $recommended_str = '<ul style="margin:0; padding-left:20px;">';
                    $counter         = 1;
                    foreach ( $recommended as $rec_id ) {
                      $rec_title = get_the_title( $rec_id );
                      $rec_link  = get_permalink( $rec_id );
                      if ( $rec_title && $rec_link ) {
                        $recommended_str .= '<li>' . $counter . '. <a href="' . esc_url( $rec_link ) . '" target="_blank">' . esc_html( $rec_title ) . '</a></li>';
                        $counter ++;
                      }
                    }
                    $recommended_str .= '</ul>';
                  } else {
                    $recommended_str = 'Not Generated';
                  }
                  echo '<tr>';
                  echo '<td><a href="' . esc_url( get_permalink( $post_id ) ) . '" target="_blank">' . esc_html( $title ) . '</a></td>';
                  echo '<td>' . $recommended_str . '</td>';
                  echo '</tr>';
                }
                echo '</tbody></table>';
                wp_reset_postdata();
              } else {
                echo '<p>No posts found.</p>';
              }
            ?>
          </div>

          <div id="high_quality" style="display:none;">
              <form method="post" action="">
                <?php wp_nonce_field( 'rra_generate_hq_nonce' ); ?>
                  <p>
                      <input type="submit" name="rra_generate_hq" class="button button-primary"
                             value="Generate High Quality Articles">
                  </p>
              </form>
            <?php
              rra_generate_high_quality_articles();
              $args  = array(
                'post_type'      => 'post',
                'posts_per_page' => - 1,
                'post_status'    => 'publish'
              );
              $query = new WP_Query( $args );
              if ( $query->have_posts() ) {
                echo '<h2>Posts with High Quality Articles</h2>';
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr><th>Post Title</th><th>High Quality Articles</th></tr></thead>';
                echo '<tbody>';
                while ( $query->have_posts() ) {
                  $query->the_post();
                  $post_id     = get_the_ID();
                  $title       = get_the_title();
                  $hq_articles = get_post_meta( $post_id, '_generated_high_quality_articles', true );
                  if ( is_array( $hq_articles ) && ! empty( $hq_articles ) ) {
                    $hq_str  = '<ul style="margin:0; padding-left:20px;">';
                    $counter = 1;
                    foreach ( $hq_articles as $hq_id ) {
                      $hq_title = get_the_title( $hq_id );
                      $hq_link  = get_permalink( $hq_id );
                      if ( $hq_title && $hq_link ) {
                        $hq_str .= '<li>' . $counter . '. <a href="' . esc_url( $hq_link ) . '" target="_blank">' . esc_html( $hq_title ) . '</a></li>';
                        $counter ++;
                      }
                    }
                    $hq_str .= '</ul>';
                  } else {
                    $hq_str = 'Not Generated';
                  }
                  echo '<tr>';
                  echo '<td><a href="' . esc_url( get_permalink( $post_id ) ) . '" target="_blank">' . esc_html( $title ) . '</a></td>';
                  echo '<td>' . $hq_str . '</td>';
                  echo '</tr>';
                }
                echo '</tbody></table>';
                wp_reset_postdata();
              } else {
                echo '<p>No posts found.</p>';
              }
            ?>
          </div>

          <div id="categories" style="display:none;">
              <form method="post" action="">
                <?php wp_nonce_field( 'rra_list_categories_nonce' ); ?>
                  <p>
                      <input type="submit" name="rra_list_categories" class="button button-primary"
                             value="List Post Categories">
                  </p>
              </form>
            <?php rra_list_post_categories(); ?>
          </div>

          <script>
            (function () {
              const tabs = document.querySelectorAll('.nav-tab');
              const sections = {
                recommended: document.getElementById('recommended'),
                high_quality: document.getElementById('high_quality'),
                categories: document.getElementById('categories')
              };
              tabs.forEach(tab => {
                tab.addEventListener('click', function (e) {
                  e.preventDefault();
                  tabs.forEach(t => t.classList.remove('nav-tab-active'));
                  this.classList.add('nav-tab-active');
                  const target = this.getAttribute('href').substring(1);
                  for (const key in sections) {
                    sections[key].style.display = (key === target) ? 'block' : 'none';
                  }
                });
              });
            })();
          </script>
      </div>
    <?php
  }
  
  /**
   * Register the admin menu item.
   */
  function rra_register_admin_menu() {
    add_submenu_page(
      'tools.php',
      'Metro-Manhattan AI Tool',
      'Metro-Manhattan AI Tool',
      'manage_options',
      'recommended-articles',
      'render_recommended_articles_admin_page'
    );
  }
  
  add_action( 'admin_menu', 'rra_register_admin_menu' );
