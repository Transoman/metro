<?php
  if ( defined( 'WP_CLI' ) && WP_CLI ) {
    class Update_Listings_Meta_Command {
      public function __invoke() {
        global $wpdb;
        
        // Log file path
        $log_file = WP_CONTENT_DIR . '/listings-meta-update-log.txt';
        
        // Get all posts with post_type 'listings'
        $posts = $wpdb->get_results(
          "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'listings'",
          ARRAY_A
        );
        
        if ( empty( $posts ) ) {
          WP_CLI::success( 'No listings found.' );
          file_put_contents( $log_file, "No listings found.\n", FILE_APPEND );
          
          return;
        }
        
        $updates = [];
        
        foreach ( $posts as $post ) {
          $post_id = $post['ID'];
          
          // Fetch postmeta data
          $primary_listing_type   = get_post_meta( $post_id, 'primary_listing_type', true );
          $secondary_listing_type = get_post_meta( $post_id, 'secondary_listing_type', true );
          
          // Skip if both primary and secondary are empty or zero
          if ( empty( $primary_listing_type ) && empty( $secondary_listing_type ) ) {
            continue;
          }
          
          $new_listing_types = [];
          
          // Add primary and secondary listing types if they exist
          if ( ! empty( $primary_listing_type ) ) {
            $new_listing_types[] = $primary_listing_type;
          }
          
          if ( ! empty( $secondary_listing_type ) ) {
            $new_listing_types[] = $secondary_listing_type;
          }
          
          // Sort and unique the new listing types
          $new_listing_types = array_unique( $new_listing_types );
          
          // Update the meta field
          update_post_meta( $post_id, 'listing_type_shown_on_post', serialize( $new_listing_types ) );
          $updates[] = "Updated post ID: $post_id with listing types: " . implode( ', ', $new_listing_types );
        }
        
        // Log results
        $current_time = ( new DateTime() )->format( 'Y-m-d H:i:s' );
        file_put_contents( $log_file, "[$current_time] Update Results:\n", FILE_APPEND );
        
        if ( empty( $updates ) ) {
          file_put_contents( $log_file, "No updates necessary.\n", FILE_APPEND );
          WP_CLI::success( "No updates were necessary. Log file: $log_file" );
        } else {
          file_put_contents( $log_file, implode( "\n", $updates ) . "\n", FILE_APPEND );
          WP_CLI::success( "Updates completed for all applicable records. Log file: $log_file" );
        }
      }
    }
    
    WP_CLI::add_command( 'update-listings-meta', 'Update_Listings_Meta_Command' );
  }
