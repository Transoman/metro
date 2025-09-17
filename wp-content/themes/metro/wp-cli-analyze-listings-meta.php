<?php
  if ( defined( 'WP_CLI' ) && WP_CLI ) {
    class Analyze_Listings_Meta_Command {
      public function __invoke() {
        global $wpdb;
        
        // Log file path
        $log_file = WP_CONTENT_DIR . '/listings-meta-analysis-log.txt';
        
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
        
        $discrepancies = [];
        
        foreach ( $posts as $post ) {
          $post_id = $post['ID'];
          
          // Fetch postmeta data
          $primary_listing_type       = get_post_meta( $post_id, 'primary_listing_type', true );
          $secondary_listing_type     = get_post_meta( $post_id, 'secondary_listing_type', true );
          $listing_type_shown_on_post = get_post_meta( $post_id, 'listing_type_shown_on_post', true );
          
          // Decode listing_type_shown_on_post if serialized
          if ( is_serialized( $listing_type_shown_on_post ) ) {
            $listing_type_shown_on_post = unserialize( $listing_type_shown_on_post );
          }
          
          // Convert to array if necessary
          $listing_type_shown_on_post = (array) $listing_type_shown_on_post;
          
          $errors = [];
          
          // Validation logic
          if ( ! empty( $primary_listing_type ) ) {
            if ( ! in_array( $primary_listing_type, $listing_type_shown_on_post ) ) {
              $errors[] = "Primary listing type ($primary_listing_type) is missing in listing_type_shown_on_post.";
            }
          }
          
          if ( ! empty( $secondary_listing_type ) ) {
            if ( ! in_array( $secondary_listing_type, $listing_type_shown_on_post ) ) {
              $errors[] = "Secondary listing type ($secondary_listing_type) is missing in listing_type_shown_on_post.";
            }
          }
          
          if ( count( $listing_type_shown_on_post ) > 1 ) {
            if (
              ( ! empty( $primary_listing_type ) && count( $listing_type_shown_on_post ) !== 1 ) ||
              ( ! empty( $secondary_listing_type ) && count( $listing_type_shown_on_post ) !== 1 )
            ) {
              $errors[] = "Invalid extra listing types in listing_type_shown_on_post.";
            }
          }
          
          if ( ! empty( $errors ) ) {
            $discrepancies[] = [
              'post_id' => $post_id,
              'errors'  => $errors,
            ];
          }
        }
        
        // Log results
        $current_time = ( new DateTime() )->format( 'Y-m-d H:i:s' );
        file_put_contents( $log_file, "[$current_time] Analysis Results:\n", FILE_APPEND );
        
        if ( empty( $discrepancies ) ) {
          file_put_contents( $log_file, "No discrepancies found.\n", FILE_APPEND );
          WP_CLI::success( "No discrepancies found. Log file: $log_file" );
        } else {
          foreach ( $discrepancies as $discrepancy ) {
            $log_entry = "Post ID: {$discrepancy['post_id']}\n";
            $log_entry .= implode( "\n", $discrepancy['errors'] ) . "\n\n";
            file_put_contents( $log_file, $log_entry, FILE_APPEND );
          }
          
          WP_CLI::success( "Discrepancies logged. Log file: $log_file" );
        }
      }
    }
    
    WP_CLI::add_command( 'analyze-listings-meta', 'Analyze_Listings_Meta_Command' );
  }
