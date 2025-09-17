<?php
  // File: wp-cli-remove-stats-duplicate.php
  if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
  }
  
  class RemoveStatsDuplicatesCommand {
    /**
     * Removes duplicate entries from the `search_stats` table.
     *
     * ## EXAMPLES
     *
     *     wp remove-stats-duplicates
     *
     * @when after_wp_load
     */
    public function __invoke() {
      global $wpdb;
      
      $table_name = $wpdb->prefix . 'search_stats';
      $query      = "
            SELECT user_id, state, MAX(created_at) AS latest_created_at, COUNT(*) AS cnt
            FROM $table_name
            WHERE user_id > 0
            GROUP BY user_id, state
            HAVING cnt > 1
        ";
      $duplicates = $wpdb->get_results( $query );
      
      if ( empty( $duplicates ) ) {
        WP_CLI::success( "[" . date('Y-m-d H:i:s') . "] No duplicates found." );
        
        return;
      }
      
      WP_CLI::log( "[" . date('Y-m-d H:i:s') . "] Found " . count( $duplicates ) . " duplicate sets. Processing..." );
      
      foreach ( $duplicates as $duplicate ) {
        $results = $wpdb->get_results( $wpdb->prepare(
          "SELECT id FROM $table_name WHERE user_id = %d AND state = %s ORDER BY created_at DESC",
          $duplicate->user_id,
          $duplicate->state
        ) );
        
        if ( ! $results ) {
          continue;
        }
        
        $latest_id     = $results[0]->id;
        $ids_to_delete = array_map( function ( $row ) {
          return $row->id;
        }, array_slice( $results, 1 ) );
        
        if ( ! empty( $ids_to_delete ) ) {
          $ids_to_delete_placeholders = implode( ',', array_fill( 0, count( $ids_to_delete ), '%d' ) );
          $wpdb->query( $wpdb->prepare(
            "DELETE FROM $table_name WHERE id IN ($ids_to_delete_placeholders)",
            $ids_to_delete
          ) );
          
          // Log deleted IDs
          WP_CLI::log( "[" . date('Y-m-d H:i:s') . "] Deleted duplicate record IDs: " . implode( ', ', $ids_to_delete ) );
        }
        
        $wpdb->update(
          $table_name,
          [ 'is_latest' => 1 ],
          [ 'id' => $latest_id ],
          [ '%d' ],
          [ '%d' ]
        );
        
        WP_CLI::log( "[" . date('Y-m-d H:i:s') . "] Marked record ID $latest_id as the latest." );
        unset( $results );
      }
      
      gc_collect_cycles();
      WP_CLI::success( "[" . date('Y-m-d H:i:s') . "] Duplicates removed successfully." );
    }
  }
  
  WP_CLI::add_command( 'remove-stats-duplicates', 'RemoveStatsDuplicatesCommand' );