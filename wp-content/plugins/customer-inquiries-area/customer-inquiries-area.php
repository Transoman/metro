<?php
  /**
   * Plugin Name:       Customer Inquiries Area
   * Plugin URI:        https://www.metro-manhattan.com/
   * Description:       Customer Inquiries Area View
   * Version:           1.1.6
   * Author:            Metro
   * Author URI:        Metro
   * License:           GPL-2.0+
   * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
   */
  
  // customer-inquiries-area.php
  
  if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
  }
  
  // Define plugin constants (paths, version, etc. if needed).
  define( 'CIA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
  define( 'CIA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
  define( 'CIA_PLUGIN_VERSION', '0.0.2' );
  
  // Include necessary files.
  require_once CIA_PLUGIN_DIR . 'includes/class-search-list-table.php';
  require_once CIA_PLUGIN_DIR . 'includes/class-admin-menu.php';
  require_once CIA_PLUGIN_DIR . 'includes/class-admin-bar.php';
  require_once CIA_PLUGIN_DIR . 'includes/class-user-columns.php';
  require_once CIA_PLUGIN_DIR . 'includes/class-csv-export.php';
  require_once CIA_PLUGIN_DIR . 'includes/functions.php'; // for the POST form insertion logic, etc.
  
  // Activation hook.
  register_activation_hook( __FILE__, 'cia_plugin_activate' );
  function cia_plugin_activate() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    $table_name      = $wpdb->prefix . 'search_stats';
    
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      dbDelta( "CREATE TABLE `$table_name` (
            `id` BIGINT NOT NULL AUTO_INCREMENT,
            `user_id` INT NULL DEFAULT NULL,
            `state` TEXT NOT NULL DEFAULT '{}',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) $charset_collate;" );
    }
  }
  
  // No direct output in the main plugin file – everything is loaded through hooks/actions.
