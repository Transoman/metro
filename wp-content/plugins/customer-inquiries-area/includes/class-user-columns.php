<?php
  // includes/class-user-columns.php
  
  /**
   * Custom columns on the Users list page.
   */
  
  if ( ! defined( 'ABSPATH' ) ) {
    exit;
  }
  
  // Add custom columns for users
  add_filter( 'manage_users_columns', 'cia_manage_users_columns', PHP_INT_MAX );
  function cia_manage_users_columns( $columns ) {
    // Remove unwanted columns if they exist
    if ( isset( $columns['posts'] ) ) {
      unset( $columns['posts'] );
    }
    if ( isset( $columns['wfls_2fa_status'] ) ) {
      unset( $columns['wfls_2fa_status'] );
    }
    if ( isset( $columns['wfls_last_login'] ) ) {
      unset( $columns['wfls_last_login'] );
    }
    
    $columns['email_verify']       = __( 'Verify Email' );
    $columns['user_geo_data']      = __( 'Geo info' );
    $columns['timestamp_register'] = __( 'New York Time' );
    $columns['wfls_last_login']    = __( 'Last Login' );
    
    return $columns;
  }
  
  // Populate custom column data
  add_action( 'manage_users_custom_column', 'cia_manage_users_custom_column', 10, 3 );
  function cia_manage_users_custom_column( $output, $column_name, $user_id ) {
    global $wpdb;
    
    switch ( $column_name ) {
      case 'wfls_last_login':
        $last_login = get_user_meta( $user_id, 'wfls_last_login', true );
        if ( $last_login ) {
            $datetime = new DateTime( "@$last_login" ); // Create from Unix timestamp
            $datetime->setTimezone( new DateTimeZone( 'America/New_York' ) ); // Convert to NY time
            $output = $datetime->format( 'd-m-Y H:i:s' );
        } else {
            $output = '<span class="na" style="color:grey;"><em>No login recorded</em></span>';
        }
        break;
      
      case 'email_verify':
        $type_register_providers = $wpdb->get_var(
          $wpdb->prepare(
            'SELECT type FROM `' . $wpdb->prefix . 'social_users` WHERE ID = %d',
            $user_id
          )
        );
        if ( $type_register_providers ) {
          $output = '<span class="na" style="color:grey;"><em>Registered via ' . esc_html( $type_register_providers ) . '</em></span>';
        } else {
          $activate = get_user_meta( $user_id, 'has_to_be_activated', true );
          if ( $activate ) {
            if ( 'verified' === $activate ) {
              $output = '<span class="na" style="color:green;font-weight: bold;"><em>Verified</em></span>';
            } else {
              $output = '<span class="na" style="color:grey;"><em>Not Verified</em></span>';
            }
          } else {
            $output = '<span class="na" style="color:grey;"><em>No Information</em></span>';
          }
        }
        break;
      
      case 'user_geo_data':
        $geo = get_user_meta( $user_id, 'user_geo_data', true );
        if ( $geo ) {
          $output = sprintf( '<span>%s</span>', esc_html( $geo ) );
        } else {
          $output = '<span class="na" style="color:grey;"><em>No information</em></span>';
        }
        break;
      
      case 'timestamp_register':
        $unix_timestamp = get_user_meta( $user_id, 'timestamp_register', true );
        if ( $unix_timestamp ) {
          $datetime       = new DateTime( "@$unix_timestamp" );
          $time_zone_from = "UTC";
          $time_zone_to   = 'America/New_York';
          
          // Convert to DateTimeZone
          $datetime->setTimezone( new DateTimeZone( $time_zone_from ) );
          $datetime->setTimezone( new DateTimeZone( $time_zone_to ) );
          
          $output = '<span>' . $datetime->format( 'd-m-Y H:i:s' ) . '</span>';
        } else {
          $output = '<span class="na" style="color:grey;"><em>No information</em></span>';
        }
        break;
    }
    
    return $output;
  }
  
  // Make columns sortable
  add_filter( 'manage_users_sortable_columns', 'cia_users_sortable_columns' );
  function cia_users_sortable_columns( $sortable_columns ) {
    $sortable_columns['timestamp_register'] = 'timestamp_register';
    $sortable_columns['wfls_last_login']    = 'wfls_last_login';
    
    return $sortable_columns;
  }
  
  // Handle sorting
  add_action( 'pre_get_users', 'cia_pre_get_users' );
  function cia_pre_get_users( $query ) {
    if ( ! is_admin() ) {
      return;
    }
    
    $screen = get_current_screen();
    if ( 'users' !== $screen->id ) {
      return;
    }
    
    if ( isset( $_GET['orderby'] ) && $_GET['orderby'] === 'wfls_last_login' ) {
      $query->set( 'meta_key', 'wfls_last_login' );
      $query->set( 'orderby', 'meta_value_num' );
    }
    
    if ( isset( $_GET['orderby'] ) && $_GET['orderby'] === 'timestamp_register' ) {
      $query->set( 'meta_key', 'timestamp_register' );
      $query->set( 'orderby', 'meta_value_num' );
    }
    
    if ( isset( $_GET['order'] ) && strtolower( $_GET['order'] ) === 'desc' ) {
      $query->set( 'order', 'DESC' );
    } else {
      $query->set( 'order', 'ASC' );
    }
  }
