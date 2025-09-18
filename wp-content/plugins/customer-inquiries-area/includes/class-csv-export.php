<?php
  // includes/class-csv-export.php
  
  /**
   * AJAX handler for generating the Search Stats CSV.
   */
  
  if ( ! defined( 'ABSPATH' ) ) {
    exit;
  }
  
  add_action( 'wp_ajax_generate-search-stats-csv', 'cia_generate_search_stats_csv' );
  function cia_generate_search_stats_csv() {
    header( 'Content-Type: text/csv; charset=UTF-8' );
    header( 'Content-Disposition: attachment; filename="export.csv";' );
    
    global $wpdb;
    
    $table = $wpdb->prefix . 'search_stats';
    $field = 'created_at';
    
    $orderByOptions = [ 'uses', 'locations', 'sizes', 'prices', 'ip', 'geo' ];
    if ( isset( $_REQUEST['filter']['orderby'] ) && in_array( $_REQUEST['filter']['orderby'], $orderByOptions, true ) ) {
      $field = "JSON_EXTRACT(state, '$." . sanitize_key( $_REQUEST['filter']['orderby'] ) . "')";
    }
    
    $order = 'ASC';
    if ( isset( $_REQUEST['filter']['order'] ) && mb_strtolower( $_REQUEST['filter']['order'] ) === 'desc' ) {
      $order = 'DESC';
    }
    
    $where = [];
    if ( isset( $_REQUEST['filter']['s'] ) && ! empty( $_REQUEST['filter']['s'] ) ) {
      $searchTerm = sanitize_text_field( $_REQUEST['filter']['s'] );
      foreach ( $orderByOptions as $option ) {
        $where[] = $wpdb->prepare( "JSON_EXTRACT(state, '$.$option') LIKE '%%%s%%'", $searchTerm );
      }
    }
    $prepareWhere = ! empty( $where ) ? 'WHERE ' . implode( ' OR ', $where ) : '';
    
    $sql   = "SELECT * FROM $table $prepareWhere ORDER BY $field $order";
    $items = $wpdb->get_results( $sql, ARRAY_A );
    
    // Hydrate items with user info
    $items = array_map( function ( $item ) {
      $item['state']        = json_decode( $item['state'], true );
      $item['display_name'] = 'Guest';
      $item['user_email']   = '';
      
      if ( ! empty( $item['user_id'] ) ) {
        $user = get_user_by( 'ID', $item['user_id'] );
        if ( $user ) {
          $item['display_name'] = $user->display_name;
          $item['user_email']   = $user->user_email;
        } else {
          $item['display_name'] = 'Unknown';
        }
      }
      
      return $item;
    }, is_array( $items ) ? $items : [] );
    
    ob_start();
    
    $csvHeader = [
      'created_at'   => 'Date',
      'display_name' => 'User Name',
      'user_email'   => 'User Email',
      'uses'         => 'Use',
      'locations'    => 'Locations',
      'sizes'        => 'Size',
      'prices'       => 'Rent',
      'geo'          => 'GEO',
      'ip'           => 'IP',
      'user_id'      => 'User ID',
    ];
    
    $csvResource = fopen( 'php://output', 'w' );
    fputcsv( $csvResource, $csvHeader );
    
    foreach ( $items as $item ) {
      fputcsv( $csvResource, [
        $item['created_at'],
        $item['display_name'],
        $item['user_email'],
        ! empty( $item['state']['uses'] ) ? $item['state']['uses'] : '',
        ! empty( $item['state']['locations'] ) ? $item['state']['locations'] : '',
        ! empty( $item['state']['sizes'] ) ? $item['state']['sizes'] : '',
        ! empty( $item['state']['prices'] ) ? $item['state']['prices'] : '',
        ! empty( $item['state']['geo'] ) ? $item['state']['geo'] : '',
        ! empty( $item['state']['ip'] ) ? $item['state']['ip'] : '',
        $item['user_id'],
      ] );
    }
    
    fclose( $csvResource );
    echo ob_get_clean();
    
    wp_die();
  }
