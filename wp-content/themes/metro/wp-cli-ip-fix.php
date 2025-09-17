<?php
  if ( defined( 'WP_CLI' ) && WP_CLI ) {
    class Update_User_ID_By_IP {
      public function __invoke() {
        global $wpdb;
        
        $search_stats_table = $wpdb->prefix . 'search_stats';
        $users_table = $wpdb->prefix . 'users';
        
        // Fetch all records where user_id = 0
        $records_to_update = $wpdb->get_results(
          "SELECT id, JSON_UNQUOTE(JSON_EXTRACT(state, '$.ip')) as ip
                FROM {$search_stats_table}
                WHERE user_id = 0"
        );
        
        if ( empty( $records_to_update ) ) {
          WP_CLI::success( 'No records found with user_id = 0.' );
          return;
        }
        
        WP_CLI::success( 'Processing records with user_id = 0...' );
        foreach ( $records_to_update as $record ) {
          $record_id = $record->id;
          $ip_address = $record->ip;
          
          if ( empty( $ip_address ) ) {
            WP_CLI::warning( "No IP address found for record ID {$record_id}." );
            continue;
          }
          
          // Find a matching user with the same IP address and user_id > 0
          $matching_user = $wpdb->get_row(
            $wpdb->prepare(
              "SELECT s1.user_id, u.user_email
                        FROM {$search_stats_table} s1
                        INNER JOIN {$users_table} u ON u.ID = s1.user_id
                        WHERE s1.user_id > 0
                        AND JSON_UNQUOTE(JSON_EXTRACT(s1.state, '$.ip')) = %s
                        LIMIT 1",
              $ip_address
            )
          );
          
          if ( $matching_user ) {
            // Update the record with user_id = 0 to use the matching user's user_id and email
            $result = $wpdb->query(
              $wpdb->prepare(
                "UPDATE {$search_stats_table}
                            SET user_id = %d, email = %s
                            WHERE id = %d",
                $matching_user->user_id,
                $matching_user->user_email,
                $record_id
              )
            );
            
            if ( false === $result ) {
              WP_CLI::error( "Failed to update record ID {$record_id} with user_id {$matching_user->user_id}." );
            } else {
              WP_CLI::success( "Successfully updated record ID {$record_id} with user_id {$matching_user->user_id} and email {$matching_user->user_email}." );
            }
          } else {
            WP_CLI::warning( "No matching user found for record ID {$record_id} with IP {$ip_address}." );
          }
        }
        
        WP_CLI::success( 'All updates processed successfully.' );
      }
    }
    
    WP_CLI::add_command( 'update_user_id_by_ip', 'Update_User_ID_By_IP' );
  }
