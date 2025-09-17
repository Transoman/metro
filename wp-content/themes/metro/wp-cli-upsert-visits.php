<?php
  // File: wp-cli-upsert-visits.php
  if ( defined( 'WP_CLI' ) && WP_CLI ) {
    class Upsert_User_Sessions_Command {
      public static function get_access_token( $force_refresh = false ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'zoho_api_keys';
        
        if ( ! $force_refresh ) {
          $row = $wpdb->get_row( "SELECT * FROM {$table_name} WHERE expires_at > NOW() ORDER BY id DESC LIMIT 1" );
          if ( $row ) {
            return $row->access_token;
          }
        }
        
        // Refresh token
        $post_data = [
          'refresh_token' => ZOHO_REFRESH_TOKEN,
          'client_id'     => ZOHO_CLIENT_ID,
          'client_secret' => ZOHO_CLIENT_SECRET,
          'grant_type'    => 'refresh_token',
        ];
        
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, ZOHO_TOKEN_URL );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $post_data ) );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, [ 'Content-Type: application/x-www-form-urlencoded' ] );
        
        $server_output = curl_exec( $ch );
        $response      = json_decode( $server_output );
        curl_close( $ch );
        
        if ( isset( $response->access_token ) ) {
          $expires_at = date( 'Y-m-d H:i:s', time() + (int) $response->expires_in );
          $wpdb->query( "TRUNCATE TABLE {$table_name}" );
          $wpdb->insert(
            $table_name,
            [ 'access_token' => $response->access_token, 'expires_at' => $expires_at ],
            [ '%s', '%s' ]
          );
          
          return $response->access_token;
        }
        
        return null;
      }
      
      public function __invoke() {
        global $wpdb;
        try {
          $sessions_table = $wpdb->prefix . 'user_sessions';
          $visits_table   = $wpdb->prefix . 'user_session_page_visits';
          
          // Use JSON_ARRAYAGG() to properly format page_visits, and limit records to avoid high memory usage
          $records = $wpdb->get_results( "
                    SELECT s.*,
                           COALESCE(
                               JSON_ARRAYAGG(
                                   JSON_OBJECT(
                                       'page_url', v.page_url,
                                       'start_time', v.start_time,
                                       'end_time', v.end_time,
                                       'duration', v.duration
                                   )
                               ), '[]'
                           ) AS page_visits
                    FROM $sessions_table s
                    LEFT JOIN $visits_table v ON s.id = v.session_id
                    WHERE s.zoho_processed = 0
                    GROUP BY s.id
                    LIMIT 1000
                " );
          
          if ( empty( $records ) ) {
            WP_CLI::log( '[' . date('Y-m-d H:i:s') . '] No new sessions to process.' );
            
            return;
          }
          
          $batches      = array_chunk( $records, 50 );
          $access_token = self::get_access_token();
          if ( ! $access_token ) {
            WP_CLI::error( '[' . date('Y-m-d H:i:s') . '] Failed to retrieve Zoho API access token.' );
            
            return;
          }
          
          $api_url = "https://www.zohoapis.com/crm/v3/API_Requests";
          
          foreach ( $batches as $batch ) {
            $payload = [ 'data' => [], 'trigger' => [ 'workflow' ] ];
            
            foreach ( $batch as $record ) {
              list( $first_name, $last_name ) = array_pad( explode( ' ', $record->name, 2 ), 2, null );
              
              $page_visits = json_decode( $record->page_visits, true );
              if ( ! is_array( $page_visits ) ) {
                $page_visits = [];
              }
              
              foreach ( $page_visits as &$visit ) {
                $visit['duration'] = round( $visit['duration'] );
                $visit['page_url'] = stripslashes( $visit['page_url'] );
              }
              
              $request_body = [
                'id'              => $record->id,
                'user_id'         => $record->user_id,
                'email'           => $record->email,
                'first_name'      => $first_name,
                'last_name'       => $last_name,
                'session_started' => $record->session_started,
                'session_ended'   => $record->session_ended,
                'time_spent'      => $record->time_spent . ' seconds',
                'created_at'      => $record->created_at,
                'page_visits'     => $page_visits
              ];
              
              $payload['data'][] = [
                'Request_Body' => json_encode( $request_body, JSON_UNESCAPED_SLASHES ),
                'Request_Type' => 'Visits',
                'Email'        => $record->email,
              ];
            }
            
            $ch = curl_init();
            curl_setopt( $ch, CURLOPT_URL, $api_url );
            curl_setopt( $ch, CURLOPT_POST, true );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $payload ) );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, [
              "Authorization: Zoho-oauthtoken $access_token",
              'Content-Type: application/json',
            ] );
            
            $attempts = 0;
            $max_attempts = 2;
            $response_data = null;
            
            while ($attempts < $max_attempts) {
              $response = curl_exec( $ch );
              $error = curl_error( $ch );
              
              if ($error) {
                break;
              }
              
              $response_data = json_decode( $response, true );
              if ( isset( $response_data['code'] ) && $response_data['code'] === 'INVALID_TOKEN' ) {
                $access_token = self::get_access_token( true );
                if ( ! $access_token ) {
                  break;
                }
                $attempts++;
              } else {
                break;
              }
            }
            
            curl_close( $ch );
            
            if ( ! $response_data || ( isset( $response_data['code'] ) && $response_data['code'] === 'INVALID_TOKEN' ) ) {
              WP_CLI::error( '[' . date('Y-m-d H:i:s') . '] Zoho API request failed due to authentication issues.' );
              continue;
            }
            
            if ( isset( $response_data['data'] ) ) {
              $processed_ids   = array_map( fn( $record ) => $record->id, $batch );
              $ids_placeholder = implode( ',', array_fill( 0, count( $processed_ids ), '%d' ) );
              
              $wpdb->query( $wpdb->prepare(
                "UPDATE $sessions_table SET zoho_processed = 1 WHERE id IN ($ids_placeholder)",
                $processed_ids
              ) );
            }
          }
        } catch ( Exception $e ) {
          WP_CLI::error( "[" . date('Y-m-d H:i:s') . "] An error occurred: " . $e->getMessage() );
        }
      }
    }
    
    WP_CLI::add_command( 'upsert-user-sessions', 'Upsert_User_Sessions_Command' );
  }
