<?php
  // unistall.php
  
  /**
   * On plugin uninstall, remove database tables or plugin options if needed.
   */
  
  if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
  }
  
  //  global $wpdb;
  //  $table_name = $wpdb->prefix . 'search_stats';
  //  $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
