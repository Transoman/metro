<?php
  // includes/functions.php
  
  /**
   * Miscellaneous functions, e.g., hooking into form submission to record search stats.
   */
  
  if ( ! defined( 'ABSPATH' ) ) {
    exit;
  }
  
  // Example of hooking into form submission to record search stats
  add_action( 'get_listings_search_result', 'cia_capture_search_stats' );
  function cia_capture_search_stats( $filters ) {
    if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
      return;
    }
    
    global $wpdb;
    
    $user_selected_locations = isset( $filters['locations'] ) ? array_map( 'htmlentities', $filters['locations'] ) : [];
    $location_groups         = [
      'Downtown Manhattan' => [
        'Chinatown',
        'City Hall/Insurance',
        'Civic Center',
        'Financial District',
        'WTC/World Financial'
      ],
      'Midtown Manhattan'  => [
        '5th Avenue/Madison Avenue',
        '6th Avenue/Rockefeller Center',
        'Bryant Park',
        'Columbus Circle',
        'East Side',
        'Garment District',
        'Grand Central',
        'Hudson Yards',
        'Midtown East',
        'Murray Hill',
        'Park Avenue',
        'Penn Station',
        'Plaza District',
        'Times Square',
        'United Nations',
        'West Side',
      ],
      'Midtown South'      => [
        'Chelsea',
        'Flatiron',
        'Gramercy Park',
        'Greenwich Village',
        'Herald Square',
        'Hudson Square/Tribeca',
        'Meatpacking District',
        'Noho/Soho',
        'Park Avenue/Madison Square',
        'Union Square',
      ],
      'Uptown Manhattan'   => [
        'Harlem',
        'Upper East Side',
        'Upper West Side',
      ],
    ];
    
    $selected_locations = [];
    
    if ( count( $user_selected_locations ) >= 38 || count( $user_selected_locations ) == 0 ) {
      $selected_locations = 'All';
    } else {
      foreach ( $location_groups as $main_location => $sub_locations ) {
        $all_selected = ! array_diff( $sub_locations, $user_selected_locations );
        
        if ( $all_selected ) {
          $selected_locations[]    = $main_location . ' - All';
          $user_selected_locations = array_diff( $user_selected_locations, $sub_locations );
          if ( in_array( $main_location, $user_selected_locations ) ) {
            $user_selected_locations = array_diff( $user_selected_locations, [ $main_location ] );
          }
        }
      }
      
      $selected_locations = array_merge( $selected_locations, $user_selected_locations );
      $selected_locations = join( '; ', $selected_locations );
    }
    
    $user_selected_uses = isset( $filters['uses'] ) ? array_map( 'htmlentities', $filters['uses'] ) : [];
    $selected_uses      = ( count( $user_selected_uses ) >= 8 || count( $user_selected_uses ) == 0 ) ? 'All' : join( '; ', $user_selected_uses );
    
    $user_selected_sizes = isset( $filters['sizes'] ) ? array_map( 'htmlentities', $filters['sizes'] ) : [];
    
    if ( count( $user_selected_sizes ) >= 5 || count( $user_selected_sizes ) == 0 ) {
      $selected_sizes = 'All';
    } else {
      $user_selected_sizes = array_map( function ( $item ) {
        $is_between = ( strpos( $item, '[between]' ) !== false );
        $is_min     = ( strpos( $item, '[min]' ) !== false );
        $is_max     = ( strpos( $item, '[max]' ) !== false );
        
        $value = preg_replace( '/\[(.+?)\]/', '', $item );
        $value = trim( $value );
        
        if ( $is_between && strpos( $value, '-' ) !== false ) {
          $parts = explode( '-', $value );
          $parts = array_map( function ( $part ) {
            $part = trim( $part );
            
            return is_numeric( $part ) ? number_format( $part ) : $part;
          }, $parts );
          $value = implode( '-', $parts ) . ' SF';
          
        } elseif ( $is_min ) {
          if ( is_numeric( $value ) ) {
            $value = number_format( $value );
          }
          $value = '> ' . $value . ' SF';
          
        } elseif ( $is_max ) {
          if ( is_numeric( $value ) ) {
            $value = number_format( $value );
          }
          $value = '< ' . $value . ' SF';
          
        } else {
          if ( strpos( $value, '-' ) !== false ) {
            $parts = explode( '-', $value );
            $parts = array_map( function ( $part ) {
              return is_numeric( $part ) ? number_format( $part ) : $part;
            }, $parts );
            $value = implode( '-', $parts ) . ' SF';
          } else {
            if ( is_numeric( $value ) ) {
              $value = number_format( $value ) . ' SF';
            }
          }
        }
        
        return $value;
      }, $user_selected_sizes );
      
      $selected_sizes = implode( '; ', $user_selected_sizes );
    }
    
    $user_selected_prices = isset( $filters['prices'] ) ? array_map( 'htmlentities', $filters['prices'] ) : [];
    if ( count( $user_selected_prices ) >= 6 || count( $user_selected_prices ) == 0 ) {
      $selected_prices = 'All';
    } else {
      $user_selected_prices = array_map( function ( $item ) {
        $is_min = ( strpos( $item, '[min]' ) !== false );
        $is_max = ( strpos( $item, '[max]' ) !== false );
        
        $value = preg_replace( '/\[(.+?)\]/', '', $item );
        
        if ( is_numeric( $value ) ) {
          $value = number_format( $value );
        }
        
        $value = '$' . $value;
        
        if ( $is_min ) {
          $value = '> ' . $value;
        } elseif ( $is_max ) {
          $value = '< ' . $value;
        }
        
        return $value;
      }, $user_selected_prices );
      
      $selected_prices = implode( '; ', $user_selected_prices );
    }
    
    $state['prices'] = $selected_prices;
    $geo             = [];
    if ( array_key_exists( 'HTTP_CF_IPCOUNTRY', $_SERVER ) ) {
      $geo[] = sanitize_text_field( $_SERVER['HTTP_CF_IPCOUNTRY'] );
    }
    if ( array_key_exists( 'HTTP_CF_REGION', $_SERVER ) ) {
      $geo[] = sanitize_text_field( $_SERVER['HTTP_CF_REGION'] );
    }
    if ( array_key_exists( 'HTTP_CF_IPCITY', $_SERVER ) ) {
      $geo[] = sanitize_text_field( $_SERVER['HTTP_CF_IPCITY'] );
    }
    $geo = join( '; ', $geo );
    
    $user = wp_get_current_user();
    if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    $ip = sanitize_text_field( $ip );
    
    $session_id = isset( $_COOKIE['cia_session_id'] ) ? sanitize_text_field( $_COOKIE['cia_session_id'] ) : null;
    
    $raw_cookie_email = isset( $_COOKIE['cf7UserEmail'] ) ? $_COOKIE['cf7UserEmail'] : null;
    $sanitized_email  = $raw_cookie_email ? sanitize_email( $raw_cookie_email ) : null;
    if ( $user && $user->user_email ) {
      $email = $user->user_email;
    } elseif ( $sanitized_email ) {
      $email = $sanitized_email;
    } else {
      $email = null;
    }
    
    $state = [
      'uses'      => $selected_uses,
      'locations' => $selected_locations,
      'sizes'     => $selected_sizes,
      'prices'    => $selected_prices,
      'ip'        => $ip,
      'geo'       => empty( $geo ) ? 'Unknown' : $geo,
    ];
    
    $wpdb->insert(
      $wpdb->prefix . 'search_stats',
      [
        'user_id'    => $user ? $user->ID : null,
        'session_id' => $session_id,
        'email'      => $email,
        'state'      => wp_json_encode( $state ),
      ],
      [ '%d', '%s', '%s', '%s' ]
    );
  }
