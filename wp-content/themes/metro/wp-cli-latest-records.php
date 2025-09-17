<?php
  if ( defined( 'WP_CLI' ) && WP_CLI ) {
    class Fetch_Latest_Records_Command {
      public function __invoke() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'search_stats';
        
        $query = "
              SELECT
                  user_id,
                  state,
                  created_at,
                  zoho_processed
              FROM
                  $table_name
              WHERE
                  user_id > 0
              ORDER BY
                  user_id, created_at DESC;
          ";
        
        $results = $wpdb->get_results( $query, ARRAY_A );
        
        if ( empty( $results ) ) {
          WP_CLI::success( 'No records found.' );
          return;
        }
        
        $latest_records = [];
        foreach ( $results as $row ) {
          $user_id = $row['user_id'];
          if ( ! isset( $latest_records[ $user_id ] ) ) {
            $latest_records[ $user_id ] = $row;
          }
        }
        
        foreach ( $latest_records as $record ) {
          $log_entry = "User ID: {$record['user_id']}, State: {$record['state']}, Created At: {$record['created_at']}, Zoho Processed: {$record['zoho_processed']}\n";
          WP_CLI::line( $log_entry );
          
          // Reset 'is_latest' flag for all records of the user
          $wpdb->update(
            $table_name,
            [ 'is_latest' => false ],            // Values to set
            [ 'user_id' => $record['user_id'] ], // Where condition
            [ '%d' ],                            // Value types
            [ '%d' ]                             // Where condition types
          );
          
          $wpdb->update(
            $table_name,
            [
              'is_latest'      => true,
              'zoho_processed' => 0
            ],
            [
              'user_id'    => $record['user_id'],
              'created_at' => $record['created_at']
            ],
            [ '%d', '%d' ],
            [ '%d', '%s' ]
          );
        }
      }
    }
    
    WP_CLI::add_command( 'fetch-latest-records', 'Fetch_Latest_Records_Command' );
  }
