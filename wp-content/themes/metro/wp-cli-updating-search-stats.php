<?php
  if ( defined( 'WP_CLI' ) && WP_CLI ) {
    class Update_Email_In_Search_Stats {
      public function __invoke() {
        global $wpdb;
        
        $prefix_users_table = $wpdb->prefix . 'users';
        $search_stats_table = $wpdb->prefix . 'search_stats';
        
        // Fetch all distinct user_ids where user_id > 0
        $user_ids = $wpdb->get_col(
          "SELECT DISTINCT user_id FROM {$search_stats_table} WHERE user_id > 0"
        );
        
        if ( empty( $user_ids ) ) {
          WP_CLI::error( 'No user_id > 0 records found in the search_stats table.' );
          
          return;
        }
        
        WP_CLI::success( 'Found user_ids to process:' );
        foreach ( $user_ids as $user_id ) {
          WP_CLI::log( "Processing user_id: $user_id" );
          
          // Fetch user_email for the given user_id
          $user_email = $wpdb->get_var(
            $wpdb->prepare(
              "SELECT user_email FROM {$prefix_users_table} WHERE ID = %d",
              $user_id
            )
          );
          
          if ( ! $user_email ) {
            WP_CLI::warning( "No email found for user_id $user_id." );
            continue;
          }
          
          // Update search_stats table with the email for the given user_id
          $result = $wpdb->query(
            $wpdb->prepare(
              "UPDATE {$search_stats_table} SET email = %s WHERE user_id = %d",
              $user_email,
              $user_id
            )
          );
          
          if ( false === $result ) {
            WP_CLI::error( "Failed to update email for user_id $user_id." );
          } else {
            WP_CLI::success( "Successfully updated email for user_id $user_id with email $user_email." );
          }
        }
        
        WP_CLI::success( 'All updates processed successfully.' );
      }
    }
    
    WP_CLI::add_command( 'update_emails_in_search_stats', 'Update_Email_In_Search_Stats' );
  }
