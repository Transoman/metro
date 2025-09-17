<?php
  // File: includes/user-sessions.php
  
  if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
  }
  
  // Check if the environment is production.
  if ( ! defined( 'WP_ENVIRONMENT_TYPE' ) || WP_ENVIRONMENT_TYPE !== 'production' ) {
    return;
  }
  
  /**
   * Initialize Redis connection.
   *
   * @return Redis
   */
  function get_redis_connection() {
    static $redis = null;
    if ( $redis === null ) {
      $redis = new Redis();
      $redis->connect( '127.0.0.1', 6379 );
    }
    
    return $redis;
  }
  
  /**
   * Converts a given UTC time to NYC timezone.
   *
   * @param string $time Time in UTC format.
   *
   * @return string Time in NYC timezone.
   */
  function get_nyc_time( $time ) {
    $datetime = new DateTime( $time, new DateTimeZone( 'UTC' ) );
    $datetime->setTimezone( new DateTimeZone( 'America/New_York' ) );
    
    return $datetime->format( 'Y-m-d H:i:s' );
  }
  
  /**
   * Logs messages to a custom log file for debugging purposes.
   *
   * @param string $message The message to log.
   */
  function log_to_file( $message ) {
    $log_file  = WP_CONTENT_DIR . '/user_session_log.txt';
    $log_entry = '[' . date( 'Y-m-d H:i:s' ) . '] ' . $message;
    file_put_contents( $log_file, $log_entry . PHP_EOL, FILE_APPEND );
  }
  
  /**
   * Logs the start of a user session using Redis.
   *
   * @param int $user_id The user ID.
   */
  function log_session_start( $user_id ) {
    $redis        = get_redis_connection();
    $current_time = get_nyc_time( current_time( 'mysql', true ) );
    
    // Retrieve user information.
    $user_info  = get_userdata( $user_id );
    $user_email = isset( $user_info->user_email ) ? $user_info->user_email : '';
    $first_name = get_user_meta( $user_id, 'first_name', true );
    $last_name  = get_user_meta( $user_id, 'last_name', true );
    
    // Compose full name.
    $full_name = trim( ( $first_name ?: '' ) . ' ' . ( $last_name ?: '' ) );
    if ( empty( $full_name ) ) {
      $full_name = ''; // Fallback if no name is provided.
    }
    
    // Check if an active session already exists.
    $session_key  = "user_session:$user_id";
    $session_data = $redis->get( $session_key );
    
    if ( $session_data ) {
      // Update last_ping for the active session.
      $session_data              = json_decode( $session_data, true );
      $session_data['last_ping'] = $current_time;
    } else {
      // Start a new session.
      $session_data = [
        'user_id'         => $user_id,
        'user_email'      => $user_email,
        'full_name'       => $full_name,
        'session_started' => $current_time,
        'last_ping'       => $current_time,
      ];
    }
    
    // Save session data in Redis.
    $redis->set( $session_key, json_encode( $session_data ) );
    $redis->expire( $session_key, 3600 ); // Set TTL (e.g., 1 hour).
    
    log_to_file( "Session started/updated for user ID: $user_id ($full_name) at $current_time" );
  }
  
  /**
   * Tracks user activity using heartbeat and updates session data in Redis.
   */
  function track_user_activity() {
    if ( is_user_logged_in() ) {
      $user_id = get_current_user_id();
      log_session_start( $user_id );
    }
  }
  
  add_action( 'wp', 'track_user_activity' );
  
  /**
   * Handles heartbeat requests to keep the session alive.
   */
  function handle_heartbeat_ping() {
    if ( is_user_logged_in() ) {
      $user_id = get_current_user_id();
      log_session_start( $user_id );
    }
    wp_send_json_success( 'Session updated' );
  }
  
  add_action( 'wp_ajax_nopriv_session_heartbeat', 'handle_heartbeat_ping' );
  add_action( 'wp_ajax_session_heartbeat', 'handle_heartbeat_ping' );
  
  /**
   * Extracts post ID and type from a given URL based on specific path prefixes.
   *
   * @param string $url The URL to analyze.
   *
   * @return array Contains 'post_id' and 'post_type' if found.
   */
  function get_post_id_and_type_from_url( $url ) {
    $post_id   = null;
    $post_type = null;
    
    $parsed_url = parse_url( $url );
    $path       = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '';
    
    // Check for listings
    if ( preg_match( '#/listing/([^/]+)#', $path, $matches ) ) {
      $slug = $matches[1];
      $post = get_page_by_path( $slug, OBJECT, 'listings' );
      if ( $post ) {
        $post_id   = $post->ID;
        $post_type = 'listings';
      }
    } // Check for buildings
    elseif ( preg_match( '#/buildings/([^/]+)#', $path, $matches ) ) {
      $slug = $matches[1];
      $post = get_page_by_path( $slug, OBJECT, 'buildings' );
      if ( $post ) {
        $post_id   = $post->ID;
        $post_type = 'buildings';
      }
    } // Check for other post types
    else {
      $post_id = url_to_postid( $url );
      if ( $post_id ) {
        $post_type = get_post_type( $post_id );
        // Handle revisions
        if ( $post_type === 'revision' ) {
          $post_id   = wp_get_post_parent_id( $post_id );
          $post_type = get_post_type( $post_id );
        }
      } else {
        $post_id   = null;
        $post_type = null;
      }
    }
    
    return array(
      'post_id'   => $post_id,
      'post_type' => $post_type,
    );
  }
  
  /**
   * Handles the logging of page visits (URL, time spent, post_id, post_type) via AJAX.
   */
  function handle_page_unload() {
    if ( ! is_user_logged_in() ) {
      wp_send_json_error( 'User not logged in' );
    }
    
    // Get and sanitize the data from the AJAX request.
    $page_url   = isset( $_POST['page_url'] ) ? esc_url_raw( $_POST['page_url'] ) : '';
    $start_time = isset( $_POST['start_time'] ) ? sanitize_text_field( $_POST['start_time'] ) : '';
    $end_time   = isset( $_POST['end_time'] ) ? sanitize_text_field( $_POST['end_time'] ) : '';
    $duration   = isset( $_POST['duration'] ) ? floatval( $_POST['duration'] ) : 0;
    
    if ( empty( $page_url ) || empty( $start_time ) || empty( $end_time ) || $duration <= 0 ) {
      wp_send_json_error( 'Invalid data provided.' );
    }
    
    // Extract post ID and type from URL
    $post_info = get_post_id_and_type_from_url( $page_url );
    $post_id   = $post_info['post_id'];
    $post_type = $post_info['post_type'];
    
    // Convert times to NYC timezone
    $start_time_nyc = get_nyc_time( $start_time );
    $end_time_nyc   = get_nyc_time( $end_time );
    
    // Retrieve or initialize session data
    $user_id          = get_current_user_id();
    $redis            = get_redis_connection();
    $session_key      = "user_session:$user_id";
    $session_data_raw = $redis->get( $session_key );
    
    if ( $session_data_raw ) {
      $session_data = json_decode( $session_data_raw, true );
    } else {
      $current_time = get_nyc_time( current_time( 'mysql', true ) );
      $user_info    = get_userdata( $user_id );
      $session_data = [
        'user_id'         => $user_id,
        'user_email'      => isset( $user_info->user_email ) ? $user_info->user_email : '',
        'full_name'       => trim( ( get_user_meta( $user_id, 'first_name', true ) ?: '' ) . ' ' . ( get_user_meta( $user_id, 'last_name', true ) ?: '' ) ),
        'session_started' => $current_time,
        'last_ping'       => $current_time,
        'page_visits'     => []
      ];
    }
    
    // Append new page visit data with post_id and post_type
    $session_data['page_visits'][] = [
      'page_url'   => $page_url,
      'start_time' => $start_time_nyc,
      'end_time'   => $end_time_nyc,
      'duration'   => round( $duration, 2 ),
      'post_id'    => $post_id,
      'post_type'  => $post_type,
    ];
    
    // Save to Redis
    $redis->set( $session_key, json_encode( $session_data ) );
    $redis->expire( $session_key, 3600 );
    
    // Update log message
    log_to_file( "Logged page visit for user ID: $user_id - URL: $page_url, Duration: " . round( $duration, 2 ) . "s, Post ID: " . ( $post_id ?? 'N/A' ) . ", Post Type: " . ( $post_type ?? 'N/A' ) );
    
    wp_send_json_success( 'Page visit logged' );
  }
  
  add_action( 'wp_ajax_nopriv_page_unload', 'handle_page_unload' );
  add_action( 'wp_ajax_page_unload', 'handle_page_unload' );
  
  /**
   * Outputs custom inline JavaScript for page visit tracking and other functionalities.
   * This code is output in the footer.
   */
  function add_custom_listing_js() {
    ?>
      <script type="text/javascript">
        // Define ajaxurl for the front end.
        var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
        (function () {
          // Record the time when the page loads.
          var pageStartTime = Date.now();
          
          // Function to send page visit data when the page is unloaded.
          function sendPageVisitData() {
            var pageEndTime = Date.now();
            var duration = (pageEndTime - pageStartTime) / 1000; // Duration in seconds
            
            // Prepare the parameters.
            var params = new URLSearchParams({
              action: 'page_unload',
              page_url: window.location.href,
              start_time: new Date(pageStartTime).toISOString(),
              end_time: new Date(pageEndTime).toISOString(),
              duration: duration.toString()
            });
            
            // Use sendBeacon API to reliably send data on unload.
            var blob = new Blob([params.toString()], {type: 'application/x-www-form-urlencoded'});
            navigator.sendBeacon(ajaxurl, blob);
          }
          
          // Use `pagehide` instead of `unload` to avoid deprecated API warning.
          window.addEventListener('pagehide', sendPageVisitData);
        })();
      </script>
    <?php
  }
  
  add_action( 'wp_footer', 'add_custom_listing_js' );
