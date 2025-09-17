<?php
  if ( ! defined( 'ABSPATH' ) ) {
    exit;
  }
  
  function update_search_stats() {
    global $wpdb;
    $table_name  = $wpdb->prefix . 'search_stats';
    $rows        = $wpdb->get_results( "SELECT id, state FROM {$table_name}" );
    $updated_ids = [];
    
    // Define location groups
    $location_groups = [
      'Downtown Manhattan' => [
        'Chinatown',
        'City Hall/Insurance',
        'Civic Center',
        'Financial District',
        'WTC/World Financial',
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
    
    foreach ( $rows as $row ) {
      $id            = $row->id;
      $state         = json_decode( $row->state, true );
      $should_update = false;
      
      if ( ! is_array( $state ) ) {
        continue;
      }
      
      // Process uses
      if ( ! empty( $state['uses'] ) ) {
        $uses_array = array_map( 'trim', explode( ';', $state['uses'] ) );
        if ( count( $uses_array ) >= 8 ) {
          $state['uses'] = 'All';
          $should_update = true;
        }
      } else if ( empty( $state['uses'] ) ) {
        $state['uses'] = 'All';
        $should_update = true;
      }
      
      // Process locations
      if ( ! empty( $state['locations'] ) ) {
        $user_selected_locations = array_map( 'trim', explode( ';', $state['locations'] ) );
        
        if ( count( $user_selected_locations ) >= 38 || count( $user_selected_locations ) === 0 ) {
          $state['locations'] = 'All';
          $should_update      = true;
        } else {
          $selected_locations = [];
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
          $state['locations'] = join( '; ', $selected_locations );
          $should_update      = true;
        }
      } else {
        $state['locations'] = 'All';
        $should_update      = true;
      }
      
      // Process sizes
      if ( ! empty( $state['sizes'] ) ) {
        $sizes_array = array_map( 'trim', explode( ';', $state['sizes'] ) );
        if ( count( $sizes_array ) >= 5 ) {
          $state['sizes'] = 'All';
          $should_update  = true;
        }
      } else if ( empty( $state['sizes'] ) ) {
        $state['sizes'] = 'All';
        $should_update  = true;
      }
      
      // Process prices
      if ( ! empty( $state['prices'] ) ) {
        $prices_array = array_map( 'trim', explode( ';', $state['prices'] ) );
        if ( count( $prices_array ) >= 6 ) {
          $state['prices'] = 'All';
          $should_update   = true;
        }
      } else if ( empty( $state['prices'] ) ) {
        $state['prices'] = 'All';
        $should_update   = true;
      }
      
      // Update the state in the database if needed
      $new_state = wp_json_encode( $state );
      
      if ( $should_update ) {
        $wpdb->update(
          $table_name,
          [ 'state' => $new_state ],
          [ 'id' => $id ],
          [ '%s' ],
          [ '%d' ]
        );
        $updated_ids[] = $id;
      }
    }
    
    // Output results for WP-CLI
    if ( defined( 'WP_CLI' ) && WP_CLI ) {
      WP_CLI::success( 'Completed...' );
      if ( ! empty( $updated_ids ) ) {
        WP_CLI::log( 'Updated IDs: ' . implode( ', ', $updated_ids ) );
      } else {
        WP_CLI::log( 'No records were updated.' );
      }
    }
  }
  
  if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'update_search_stats', 'update_search_stats' );
  }
