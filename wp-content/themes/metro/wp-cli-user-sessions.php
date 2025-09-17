<?php
  // File: wp-cli-user-sessions.php
  if ( defined( 'WP_CLI' ) && WP_CLI ) {
    class Sync_Redis_Sessions_Command {
      public function __invoke() {
        global $wpdb;
        
        $redis = new Redis();
        $redis->connect( '127.0.0.1', 6379 );
        
        $sessions_table    = $wpdb->prefix . 'user_sessions';
        $page_visits_table = $wpdb->prefix . 'user_session_page_visits';
        
        // Replace KEYS with SCAN to reduce memory usage
        $session_keys = [];
        $iterator     = null;
        while ( $keys = $redis->scan( $iterator, 'user_session:*', 100 ) ) {
          if ( $keys !== false ) {
            $session_keys = array_merge( $session_keys, $keys );
          }
        }
        
        $current_time     = time();
        $current_time_nyc = ( new DateTime( 'now', new DateTimeZone( 'America/New_York' ) ) )->format( 'Y-m-d H:i:s' );
        
        if ( empty( $session_keys ) ) {
          WP_CLI::log( '[' . date('Y-m-d H:i:s') . '] No sessions found in Redis.' );
          
          return;
        }
        
        $expired_sessions = [];
        $active_sessions  = [];
        $errors           = [];
        
        foreach ( $session_keys as $key ) {
          $session_data = json_decode( $redis->get( $key ), true );
          if ( ! $session_data ) {
            $errors[] = "Invalid session data for key: $key";
            continue;
          }
          
          $user_id             = $session_data['user_id'];
          $user_email          = $session_data['user_email'] ?? '';
          $full_name           = $session_data['full_name'] ?? '';
          $session_started_nyc = $session_data['session_started'];
          $last_ping_nyc       = $session_data['last_ping'];
          $page_visits         = $session_data['page_visits'] ?? [];
          
          $session_started_epoch = strtotime( $session_started_nyc . ' America/New_York' );
          $last_ping_epoch       = strtotime( $last_ping_nyc . ' America/New_York' );
          
          if ( ! $session_started_epoch || ! $last_ping_epoch ) {
            $errors[] = "Invalid timestamps for user ID: $user_id";
            continue;
          }
          
          // Check if session is expired (last ping > 120 seconds ago)
          if ( ( $current_time - $last_ping_epoch ) > 120 ) {
            $time_spent = $last_ping_epoch - $session_started_epoch;
            if ( $time_spent <= 0 ) {
              continue;
            }
            
            // Insert session into user_sessions table
            $insert_result = $wpdb->insert(
              $sessions_table,
              [
                'user_id'         => $user_id,
                'email'           => $user_email,
                'name'            => $full_name,
                'session_started' => $session_started_nyc,
                'session_ended'   => $last_ping_nyc,
                'time_spent'      => $time_spent,
                'zoho_processed'  => 0,
                'created_at'      => $current_time_nyc
              ],
              [
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%d',
                '%d',
                '%s'
              ]
            );
            
            if ( $insert_result === false ) {
              $errors[] = "Failed to insert session for user ID: $user_id";
              continue;
            }
            
            $session_id = $wpdb->insert_id;
            if ( ! $session_id ) {
              $errors[] = "Failed to retrieve session ID for user ID: $user_id";
              continue;
            }
            
            // Insert page visits if they exist
            if ( ! empty( $page_visits ) ) {
              WP_CLI::log( "[" . date('Y-m-d H:i:s') . "] Processing page visits for User ID: $user_id, Session ID: $session_id" );
              
              foreach ( $page_visits as $visit ) {
                $post_id = $visit['post_id'] ?? 0;
                
                $insert_visit_result = $wpdb->insert(
                  $page_visits_table,
                  [
                    'session_id' => $session_id,
                    'post_id'    => $post_id,
                    'page_url'   => $visit['page_url'],
                    'start_time' => $visit['start_time'],
                    'end_time'   => $visit['end_time'],
                    'duration'   => round( $visit['duration'], 2 )
                  ],
                  [
                    '%d',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%f'
                  ]
                );
                
                if ( $insert_visit_result === false ) {
                  $errors[] = "Failed to insert page visit for session ID: $session_id, URL: {$visit['page_url']}";
                }
              }
            }
            
            // Delete Redis session
            if ( $redis->del( $key ) ) {
              WP_CLI::log( "[" . date('Y-m-d H:i:s') . "] Deleted session key: $key" );
            } else {
              $errors[] = "Failed to delete session key: $key";
            }
            
            $expired_sessions[] = "Session ID: $session_id (User ID: $user_id)";
          } else {
            $active_sessions[] = "User ID: $user_id, Last ping: $last_ping_nyc";
          }
        }
        
        // Output results
        WP_CLI::log( PHP_EOL . "=== Sync Results ===" );
        WP_CLI::log( "[" . date('Y-m-d H:i:s') . "] Expired sessions migrated: " . count( $expired_sessions ) );
        WP_CLI::log( "[" . date('Y-m-d H:i:s') . "] Active sessions remaining: " . count( $active_sessions ) );
        WP_CLI::log( "[" . date('Y-m-d H:i:s') . "] Errors encountered: " . count( $errors ) );
        
        if ( ! empty( $errors ) ) {
          WP_CLI::log( PHP_EOL . "Error details:" );
          foreach ( $errors as $error ) {
            WP_CLI::warning( "[" . date('Y-m-d H:i:s') . "] " . $error );
          }
        }
      }
    }
    
    WP_CLI::add_command( 'sync-redis-sessions', 'Sync_Redis_Sessions_Command' );
  }
